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

namespace asciidia\chart\graph {
    /**
     * layered bar graph.
     *
     * @octdoc      c:graph/layered
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class layered extends \chart\graph
    /**/
    {
        /**
         * Datasets.
         *
         * @octdoc  p:graph/$datasets
         * @type    array
         */
        protected $datasets;
        /**/

        /**
         * Color range for calculating default colors for bars.
         *
         * @octdoc  p:graph/$color_range
         * @type    array
         */
        protected $color_range = array(64, 164);
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:graph/__construct
         * @param   array               $datasets               Datasets to use for graph
         * @param   array               $options                Optional options.
         */
        public function __construct(array $datasets, array $options = array())
        /**/
        {
            $this->datasets = $datasets;

            $cnt   = count($datasets);
            $c_dec = ($this->color_range[1] - $this->color_range[0]) / $cnt;

            $tmp = array();
            $col = $this->color_range[1];
            for ($i = 0; $i < $cnt; ++$i) {
                $tmp[] = array(
                    'background_color' => array($col, $col, $col),
                    'border_color'     => array($col, $col, $col)
                );
                $col  -= $c_dec;
            }

            for ($i = 0; $i < $cnt; ++$i) {
                if (count($keys = array_diff_key($options[$i], array('background_color' => 0, 'border_color' => 1))) > 0) {
                    throw new \Exception('invalid option name(s) "' . implode('", "', array_keys($keys)) . '"');
                } else {
                    $this->options[] = array_merge($tmp[$i], $options[$i]);
                }
            }

            $this->options = $this->options + $tmp;
        }

        /**
         * Create bar graph.
         *
         * @octdoc  m:layered/create
         * @param   context             $context                Drawing context.
         * @param   int                 $width                  Width of graph.
         * @param   int                 $height                 Height of graph.
         * @param   float               $zero                   Zero line (X-Axis).
         * @param   float               $x_mul                  Point multiplicator for X-Axis.
         * @param   float               $y_mul                  Point multiplicator for Y-Axis.
         */
        public function create(\context $context, $width, $height, $zero, $x_mul, $y_mul)
        /**/
        {
            $values = array();

            foreach ($this->datasets as $dataset) {
                $tmp = $dataset->getValues();

                for ($i = 0, $cnt = count($tmp); $i < $cnt; ++$i) {
                    if (!isset($values[$i])) {
                        $values[$i] = array();
                    }

                    $values[$i][] = $tmp[$i];
                }
            }

            $ctx = $context->addContext();

            $b_w = $x_mul * 0.6;
            $b_o = ($x_mul - $b_w) / 2;

            for ($i = 0, $cnt = count($values); $i < $cnt; ++$i) {
                $tmp = $values[$i];
                arsort($tmp);

                foreach ($tmp as $k => $v) {
                    if ($v > 0) {
                        $ctx->setFill(array('color' => $this->options[$k]['background_color']));
                        $ctx->setStroke(array('color' => $this->options[$k]['border_color']));

                        $ctx->addCommand(sprintf(
                            'rectangle %f,%f %f,%f', 
                            $i * $x_mul + $b_o, $zero, $i * $x_mul + $b_o + $b_w, $zero - $v * $y_mul
                        ));
                    }
                }
            }
        }

        /** proxy for datasets **/

        /**
         * Get max value.
         *
         * @octdoc  m:graph/getMax
         * @return  float                                       Max value.
         */
        public function getMax()
        /**/
        {
            $tmp = array();
            foreach ($this->datasets as $dataset) {
                $tmp[] = $dataset->getMax();
            }

            return max($tmp);
        }

        /**
         * Get min value.
         *
         * @octdoc  m:graph/getMin
         * @return  float                                       Min value.
         */
        public function getMin()
        /**/
        {
            $tmp = array();
            foreach ($this->datasets as $dataset) {
                $tmp[] = $dataset->getMin();
            }

            return min($tmp);
        }

        /**
         * Get number of values.
         *
         * @octdoc  m:graph/getCount
         * @return  int                                         Number of values.
         */
        public function getCount()
        /**/
        {
            $tmp = array();
            foreach ($this->datasets as $dataset) {
                $tmp[] = $dataset->getCount();
            }

            return max($tmp);
        }
    }
}
