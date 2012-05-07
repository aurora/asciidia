<?php

/*
 * This file is part of asciidia
 * Copyright (C) 2012 by Harald Lapp <harald@octris.org>
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

namespace chart {
    /**
     * Graph base class.
     *
     * @octdoc      c:chart/graph
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    abstract class graph
    /**/
    {
        /**
         * Dataset for graph.
         *
         * @octdoc  p:graph/$dataset
         * @var     dataset
         */
        protected $dataset;
        /**/

        /**
         * Context to assign to graph.
         *
         * @octdoc  p:graph/$context
         * @var     context|null
         */
        protected $context = null;
        /**/

        /**
         * Options.
         *
         * @octdoc  p:graph/$options
         * @var     array
         */
        protected $options = array();
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:graph/__construct
         * @param   dataset             $dataset                Dataset to use for graph.
         * @param   array               $options                Optional options.
         */
        public function __construct(\chart\dataset $dataset, array $options = array())
        /**/
        {
            $this->dataset = $dataset;

            if (count($tmp = array_diff_key($options, $this->options)) > 0) {
                throw new \Exception('invalid option name(s) "' . implode('", "', array_keys($tmp)) . '"');
            } else {
                $this->options = array_merge($this->options, $options);
            }
        }

        /**
         * Create graph.
         *
         * @octdoc  m:graph/create
         * @param   context             $context                Drawing context.
         * @param   int                 $width                  Width of graph.
         * @param   int                 $height                 Height of graph.
         * @param   float               $zero                   Zero line (X-Axis).
         * @param   float               $x_mul                  Point multiplicator for X-Axis.
         * @param   float               $y_mul                  Point multiplicator for Y-Axis.
         */
        abstract public function create(\context $context, $width, $height, $zero, $x_mul, $y_mul);
        /**/

        /** proxy for dataset **/

        /**
         * Get max value.
         *
         * @octdoc  m:graph/getMax
         * @return  float                                       Max value.
         */
        public function getMax()
        /**/
        {
            return $this->dataset->getMax();
        }

        /**
         * Get maximum logarithmus value.
         *
         * @octdoc  m:graph/getMaxLog
         * @param   int                                         Optional base.
         * @return  float                                       Max. logarithm value.
         */
        public function getMaxLog($base = 10)
        /**/
        {
            return $this->dataset->getMaxLog();
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
            return $this->dataset->getMin();
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
            return $this->dataset->getCount();
        }
    }
}
