<?php

/*
 * This file is part of asciidia
 * Copyright (c) by Harald Lapp <harald@octris.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * This script can be found at:
 * https://github.com/aurora/asciidia
 */

namespace asciidia {
    /**
     * Provides primitives for drawing charts.
     *
     * @octdoc      c:libs/chart
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class chart
    /**/
    {
        /**
         * Scaling types.
         *
         * @octdoc  d:chart/T_SCALE_LIN, T_SCALE_LOG
         */
        const T_SCALE_LIN = 1;
        const T_SCALE_LOG = 2;
        /**/

        /**
         * Drawing context.
         *
         * @octdoc  p:chart/$context
         * @type    context
         */
        protected $context;
        /**/

        /**
         * Width of chart to create.
         *
         * @octdoc  p:chart/$width
         * @type    int
         */
        protected $width;
        /**/
    
        /**
         * Height of chart to create.
         *
         * @octdoc  p:chart/$height
         * @type    int
         */
        protected $height;
        /**/

        /**
         * Graphs assigned to chart.
         *
         * @octdoc  p:chart/$graphs
         * @type    array
         */
        protected $graphs = array();
        /**/

        /**
         * Options.
         *
         * @octdoc  p:chart/$options
         * @type    array
         */
        protected $options = array(
            'ticks'             => 10,

            'labels'            => true,

            'scale_type'        => self::T_SCALE_LIN,
            'scale_base'        => 10,

            'grid'              => true,
            'border'            => true,

            'grid_color'        => array(128, 128, 128),
            'border_color'      => array(  0,   0,   0),
            'background_color'  => array(255, 255, 255)
        );
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:chart/__construct
         * @param   context             $context            Drawing context.
         * @param   int                 $width              Width of chart to create.
         * @param   int                 $height             Height of chart to create.
         * @param   array               $options            Optional options.
         */
        public function __construct(context $context, $width, $height, array $options = array())
        /**/
        {
            $this->context = $context->addContext();
            $this->context->xs = 1;
            $this->context->ys = 1;
            $this->context->setSize($width, $height);

            $this->width  = $width;
            $this->height = $height;

            if (count($tmp = array_diff_key($options, $this->options)) > 0) {
                throw new \Exception('invalid option name(s) "' . implode('", "', array_keys($tmp)) . '"');
            } else {
                $this->options = array_merge($this->options, $options);
            }
        }

        /**
         * Add a graph to the chart.
         *
         * @octdoc  m:chart/addDataset
         * @param   graph               $graph              Graph to add.
         */
        public function addGraph(\chart\graph $graph)
        /**/
        {
            $this->graphs[] = $graph;
        }

        /**
         * Helper methods to create nice values for a diagram, as found in the
         * book "Graphics Gems".
         *
         * @octdoc  m:chart/looseLabels
         * @param   float           $min                Minimum value in the chart.
         * @param   float           $max                Maximum value in the chart.
         */
        public function looseLabels($min, $max)
        /**/
        {
            $nice_number = function($num, $round) {
                $e = floor(log10($num));
                $f = $num / pow(10, $e);
            
                if ($round) {
                    if ($f < 1.5) {
                        $nf = 1;
                    } elseif ($f < 3) {
                        $nf = 2;
                    } elseif ($f < 7) {
                        $nf = 5;
                    } else {
                        $nf = 10;
                    }
                } else {
                    if ($f <= 1) {
                        $nf = 1;
                    } elseif ($f <= 2) {
                        $nf = 2;
                    } elseif ($f <= 5) {
                        $nf = 5;
                    } else {
                        $nf = 10;
                    }
                }

                return $nf * pow(10, $e);
            };

            $range = $nice_number($max - $min, false);
            $d     = $nice_number($range / ($this->options['ticks'] - 1), true);
        
            $g_min = floor($min / $d) * $d;
            $g_max = ceil($max / $d) * $d;
        
            $nfrac = max(-floor(log10($d)), 0);

            $labels = array();
        
            for ($i = $g_min, $to = $g_max + 0.5 * $d; $i <= $to; $i += $d) {
                $labels[] = $i;
            }

            return $labels;
        }

        /**
         * Get drawing context.
         *
         * @octdoc  m:chart/getContext
         * @return  context                                 Drawing context.
         */
        public function getContext()
        /**/
        {
            return $this->context;
        }

        /**
         * Create drawing commands for chart.
         *
         * @octdoc  m:chart/create
         */
        public function create()
        /**/
        {
            $min   = min(0, $this->getMin());
            $max   = $this->getMax();
            $x_cnt = $this->getCount();

            if ($this->options['border']) {
                $height = $this->height - 2;
                $width  = $this->width - 2;
            } else {
                $height = $this->height;
                $width  = $this->width;
            }

            $y_mul = $height / ($h = ($max - $min));
            $x_mul = $width / $x_cnt;

            $labels = $this->looseLabels($min, $max);
            $y_cnt  = count($labels);
            $l_mul  = $height / $y_cnt;

            // draw border, background, grid
            $this->context->setStroke(array('width' => 1, 'color' => $this->options['grid_color']));
            $this->context->setFill(array('color' => $this->options['background_color']));

            if ($this->options['border']) {
                $this->context->drawRectangle(0, 0, $this->width - 1, $this->height - 1);
            }

            if ($this->options['grid']) {
                for ($i = 0; $i < $x_cnt; ++$i) {
                    $this->context->drawLine($i * $x_mul, 0, $i * $x_mul, $this->height);
                }
                for ($i = 0; $i < $y_cnt; ++$i) {
                    $this->context->drawLine(0, $i * $l_mul, $this->width, $i * $l_mul);
                }
            }

            $this->context->setStroke(array('width' => 1, 'color' => $this->options['border_color']));
            $this->context->setFill(array('color' => 'transparent'));

            if ($this->options['border']) {
                $this->context->drawRectangle(0, 0, $this->width - 1, $this->height - 1);
            }

            // draw graphs
            if ($this->options['grid']) {
                $this->context->translate(1, 1);
            }

            foreach ($this->graphs as $graph) {
                $graph->create($this->context, $width, $height, $height - $min * $y_mul, $x_mul, $y_mul);
            }
        }

        /** proxy for dataset **/

        /**
         * Get max value.
         *
         * @octdoc  m:chart/getMax
         * @return  float                                       Max value.
         */
        public function getMax()
        /**/
        {
            $tmp = array();
            foreach ($this->graphs as $graph) {
                $tmp[] = $graph->getMax();
            }

            return max($tmp);
        }

        /**
         * Get maximum logarithmus value.
         *
         * @octdoc  m:chart/getMaxLog
         * @param   int                                         Optional base.
         * @return  float                                       Max. logarithm value.
         */
        public function getMaxLog($base = 10)
        /**/
        {
            $tmp = array();
            foreach ($this->graphs as $graph) {
                $tmp[] = $graph->getMaxLog();
            }

            return max($tmp);
        }

        /**
         * Get min value.
         *
         * @octdoc  m:chart/getMin
         * @return  float                                       Min value.
         */
        public function getMin()
        /**/
        {
            $tmp = array();
            foreach ($this->graphs as $graph) {
                $tmp[] = $graph->getMin();
            }

            return min($tmp);
        }

        /**
         * Get number of values.
         *
         * @octdoc  m:chart/getCount
         * @return  int                                         Number of values.
         */
        public function getCount()
        /**/
        {
            $tmp = array();
            foreach ($this->graphs as $graph) {
                $tmp[] = $graph->getCount();
            }

            return max($tmp);
        }
    }
}
