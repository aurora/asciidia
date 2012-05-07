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
     * Drawing context.
     *
     * @octdoc  p:chart/$context
     * @var     context
     */
    protected $context;
    /**/

    /**
     * Width of chart to create.
     *
     * @octdoc  p:chart/$width
     * @var     int
     */
    protected $width;
    /**/
    
    /**
     * Height of chart to create.
     *
     * @octdoc  p:chart/$height
     * @var     int
     */
    protected $height;
    /**/

    /**
     * Graphs assigned to chart.
     *
     * @octdoc  p:chart/$graphs
     * @var     array
     */
    protected $graphs = array();
    /**/

    /**
     * Constructor.
     *
     * @octdoc  m:chart/__construct
     * @param   context             $context            Drawing context.
     * @param   int                 $width              Width of chart to create.
     * @param   int                 $height             Height of chart to create.
     */
    public function __construct(context $context, $width, $height)
    /**/
    {
        $this->context = $context->addContext();
        $this->context->xs = 1;
        $this->context->ys = 1;
        $this->context->setSize($width, $height);

        $this->width  = $width;
        $this->height = $height;
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
     * X-Axis configuration.
     *
     * @octdoc  m:chart/setXAxis
     * @param   string              $label              Label to set.
     */
    public function setXAxis($label)
    /**/
    {
        
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
        $min = min(0, $this->getMin());
        $max = $this->getMax();
        $cnt = $this->getCount();

        $y_mul = $this->height / ($max - $min);
        $x_mul = $this->width / $cnt;

        // draw graphs
        foreach ($this->graphs as $graph) {
            $graph->create($this->context, $this->width, $this->height, $this->height - $min * $y_mul, $x_mul, $y_mul);
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
