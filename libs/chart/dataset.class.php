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

namespace asciidia\chart {
    /**
     * Dataset for creating charts.
     *
     * @octdoc      c:chart/dataset
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class dataset
    /**/
    {
        /**
         * Name of dataset.
         *
         * @octdoc  p:dataset/$name
         * @type    string
         */
        protected $name = '';
        /**/

        /**
         * Dataset data.
         *
         * @octdoc  p:dataset/$data
         * @type    array
         */
        protected $data = array();
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:dataset/__construct
         * @param   string                  $name               Name of dataset.
         * @param   array                   $data               Optional data to set.
         */
        public function __construct($name, array $data = array())
        /**/
        {
            $this->name = $name;
            $this->data = $data;
        }

        /**
         * Add a value to dataset.
         *
         * @octdoc  m:dataset/addValue
         * @param   mixed                   $value              Value to add.
         */
        public function addValue($value)
        /**/
        {
            $this->data[] = $value;
        }

        /**
         * Get max value.
         *
         * @octdoc  m:dataset/getMax
         * @return  float                                       Max value.
         */
        public function getMax()
        /**/
        {
            return max($this->data);
        }

        /**
         * Get min value.
         *
         * @octdoc  m:dataset/getMin
         * @return  float                                       Min value.
         */
        public function getMin()
        /**/
        {
            return min($this->data);
        }

        /**
         * Get average value.
         *
         * @octdoc  m:dataset/getAverage
         * @return  float                                       Average value.
         */
        public function getAverage()
        /**/
        {
            return (array_sum($this->data) / count($this->data));
        }

        /**
         * Get sum of all values.
         *
         * @octdoc  m:dataset/getSum
         * @return  mixed                                       Sum of values.
         */
        public function getSum()
        /**/
        {
            return array_sum($this->data);
        }

        /**
         * Get maximum logarithmus value.
         *
         * @octdoc  m:dataset/getMaxLog
         * @param   int                                         Optional base.
         * @return  float                                       Max. logarithm value.
         */
        public function getMaxLog($base = 10)
        /**/
        {
            $max = array();
            foreach ($this->data as $v) {
                $max[] = log($v, $base);
            }

            return max($max);
        }

        /**
         * Get values of dataset.
         *
         * @octdoc  m:dataset/getValues
         * @return  array                                       Values.
         */
        public function getValues()
        /**/
        {
            return $this->data;
        }

        /**
         * Get a single value of dataset.
         *
         * @octdoc  m:dataset/getValue
         * @param   int                 $pos                    Position of value to return.
         * @return  mixed|null                                  Value, returns 'null' if position is invalid.
         */
        public function getValue($pos)
        /**/
        {
            return (array_key_exists($pos, $this->data) ? $this->data[$pos] : null);
        }

        /**
         * Get number of values.
         *
         * @octdoc  m:dataset/getCount
         * @return  int                                         Number of values.
         */
        public function getCount()
        /**/
        {
            return count($this->data);
        }

        /**
         * Get a new dataset with moving average values.
         *
         * @octdoc  m:dataset/getSimpleMovingAverage
         * @param   string                  $name               Name of new dataset.
         * @param   mixed                   $initial            Optional initial value.
         * @return  dataset                                     Dataset of running average.
         */
        public function getSimpleMovingAverage($name, $initial = 0)
        /**/
        {
            $avg = array();

            for ($i = 0, $cnt = count($this->data); $i < $cnt; ++$i) {
                $avg[] = ($i == 0 ? $initial : ($avg[$i - 1] + $this->data[$i]) / 2);
            }

            return new static($name, $avg);
        }
    }
}
