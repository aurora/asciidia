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
     * Options.
     *
     * @octdoc  p:chart/$options
     * @var     array
     */
    protected $options = array(
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

        $y_mul = $this->height / ($h = ($max - $min));
        $x_mul = $this->width / $cnt;

        // draw border, background, grid
//        $this->context->setFill(array('color' => $this->options['background_color']));
        $this->context->setStroke(array('width' => 1, 'color' => $this->options['grid_color']));

        for ($i = 0; $i < $cnt; ++$i) {
            $this->context->drawLine($i * $x_mul, 0, $i * $x_mul, $this->height);
        }
        for ($i = 0; $i < $h; ++$i) {
            $this->context->drawLine(0, $i * $y_mul, $this->width, $i * $y_mul);
        }

        $this->context->drawRectangle(0, 0, $this->width, $this->height);

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
