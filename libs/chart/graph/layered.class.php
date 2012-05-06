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

namespace chart\graph {
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
         * @var     array
         */
        protected $datasets;
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:graph/__construct
         * @param   array               $datasets               Datasets to use for graph
         */
        public function __construct(array $datasets)
        /**/
        {
            $this->datasets = $datasets;
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

            for ($i = 0, $cnt = count($values); $i < $cnt; ++$i) {
                $tmp = $values[$i];
                arsort($tmp);

                foreach ($tmp as $v) {
                    if ($v > 0) {
                        $ctx->addCommand(sprintf(
                            'rectangle %f,%f %f,%f', 
                            $i * $x_mul, $zero, $i * $x_mul, $zero - $v * $y_mul
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
