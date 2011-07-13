<?php

/*
 * This file is part of asciidia
 * Copyright (C) 2011 by Harald Lapp <harald@octris.org>
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
 * ASCII Diagram core class. Implements text parser and core functionality of
 * text-diagram to imagemagick-draw converter.
 *
 * @octdoc      c:libs/asciidia
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
abstract class asciidia
/**/
{
    /**
     * X-multiplicator -- the width of a cell on the canvas.
     *
     * @octdoc  v:asciidia/$xs
     * @var     float
     */
    protected $xs = 10.0;
    /**/
    
    /**
     * Y-multiplicator -- the height of a cell on the canvas.
     *
     * @octdoc  v:asciidia/$ys
     * @var     float
     */
    protected $ys = 15.0;
    /**/
    
    /**
     * X-fract -- half of a cells width.
     *
     * @octdoc  v:asciidia/$xf
     * @var     float
     */
    protected $xf = 0;
    /**/
    
    /**
     * Y-fract -- half of a cells height.
     *
     * @octdoc  v:asciidia/yf
     * @var     float
     */
    protected $yf = 0;
    /**/
    
    /**
     * Width of diagram canvas.
     *
     * @octdoc  v:asciidia/$w
     * @var     int
     */
    protected $w = 0;
    /**/
    
    /**
     * Height of diagram canvas.
     *
     * @octdoc  v:asciidia/$h
     * @var     int
     */
    protected $h = 0;
    /**/
    
    /**
     * Whether grid overlay is enabled
     *
     * @octdoc  v:asciidia/$grid
     * @var     bool
     */
    protected $grid = false;
    /**/
    
    /**
     * Background color.
     *
     * @octdoc  v:asciidia/$bg
     * @var     string
     */
    protected $bg = 'white';
    /**/
    
    /**
     * Stroke color
     *
     * @octdoc  v:asciidia/$stroke
     * @var     string
     */
    protected $stroke = 'black';
    /**/
    
    /**
     * Imagemagick MVG commands.
     *
     * @octdoc  v:asciidia/$mvg
     * @var     array
     */
    protected $mvg = array();
    /**/
    
    /**
     * Constructor.
     *
     * @octdoc  m:asciidia/__construct
     */
    public function __construct()
    /**/
    {
        $this->xf = $this->xs / 2;
        $this->yf = $this->ys / 2;
    }
    
    /**
     * Magic setter.
     *
     * @octdoc  m:asciidia/__set
     * @param   string      $name               Name of property to set.
     * @param   mixed       $value              Value to set for property.
     */
    public function __set($name, $value)
    /**/
    {
        switch ($name) {
        case 'xs':
            $this->xs = $value;
            $this->xf = $value / 2;
            break;
        case 'ys':
            $this->ys = $value;
            $this->yf = $value / 2;
            break;
        }
    }
    
    /**
     * Return size of canvas.
     *
     * @octdoc  m:asciidia/getSize
     * @param   array                       w,h size of canvas.
     */
    public function getSize()
    /**/
    {
        return array(
            ($this->w + 1) * $this->xs - 1, 
            ($this->h + 1) * $this->ys - 1
        );
    }

    /**
     * Add MVG command to command-list.
     *
     * @octdoc  m:asciidia/addCommand
     * @param   string      $command        Command to at.
     */
    public function addCommand($command)
    /**/
    {
        $this->mvg[] = $command;
    }

    /**
     * Return all commands needed for drawing diagram.
     *
     * @octdoc  m:asciidia/getCommands
     * @return  array                       Imagemagick MVG commands.
     */
    public function getCommands()
    /**/
    {
        $debug = array();
        
        if ($this->grid) {
            // draw debugging grid
            for ($x = 0; $x <= $this->w; ++$x) {
                $debug[] = sprintf(
                    'line %d,0 %d,%d',
                    $x * $this->xs,
                    $x * $this->xs,
                    $this->h * $this->ys + $this->ys
                );
            }
            for ($y = 0; $y <= $this->h; ++$y) {
                $debug[] = sprintf(
                    'line 0,%d %d,%d',
                    $y * $this->ys,
                    $this->w * $this->xs + $this->xs,
                    $y * $this->ys
                );
            }
        }
        
        return array_merge(
            array(
                'push graphic-context',
                'font Courier',
                sprintf('font-size %f', $this->ys)
            ), 
            $this->mvg,
            $debug,
            array(
                'pop graphic-context'
            )
        );
    }

    /**
     * Clear MVG command stack.
     *
     * @octdoc  m:asciidia/clearCommands
     */
    public function clearCommands()
    /**/
    {
        $this->mvg = array();
    }

    /*
     * drawing primitives
     */

    /**
     * Draw a line between two points. An optional arrow head may be specified:
     *
     * @octdoc  m:asciidia/drawLine
     * @param   int         $x1                 x start-point.
     * @param   int         $y1                 y start-point.
     * @param   int         $x2                 x end-point.
     * @param   int         $y2                 y end-point.
     * @param   int|bool    $arrow              Optional arrow head.
     */
    protected function drawLine($x1, $y1, $x2, $y2, $arrow = false)
    /**/
    {
        $this->w = max($this->w, $x1, $x2);
        $this->h = max($this->h, $y1, $y2);

        $this->mvg[] = sprintf(
            'line   %d,%d %d,%d',
            $x1 * $this->xs + $this->xf,
            $y1 * $this->ys + $this->yf,
            $x2 * $this->xs + $this->xf,
            $y2 * $this->ys + $this->yf
        );
        
        if ($arrow !== false) {
            $angle = rad2deg(atan2(($y2 - $y1), ($x2 - $x1)));
            
            // TODO
        }
    }
    
    /**
     * Draw a horizontal line. An optional arrow head may be specified:
     *
     * *    false -- no arrow heads
     * *    0 -- draw arrow heads on both sides (x1/y1 <-> x2/y2)
     * *    1 -- draw arrow x1/y1 -> x2/y2
     * *    -1 -- draw arrow x1/y1 <- x2/y2
     *
     * @octdoc  m:asciidia/drawLine
     * @param   int         $x1                 x start-point.
     * @param   int         $y                  y start-point.
     * @param   int         $x2                 x end-point.
     * @param   int|bool    $arrow              Optional arrow head.
     */
    protected function drawHLine($x1, $y, $x2, $arrow = false)
    /**/
    {
        $this->w = max($this->w, $x1, $x2);
        $this->h = max($this->h, $y);

        $this->mvg[] = sprintf(
            'line   %d,%d %d,%d',
            $x1 * $this->xs,
            $y * $this->ys + $this->yf,
            $x2 * $this->xs,
            $y * $this->ys + $this->yf
        );
        
        if ($arrow !== false) {
        }
    }
    
    /**
     * Draw a vertical line. An optional arrow head may be specified:
     *
     * *    false -- no arrow heads
     * *    0 -- draw arrow heads on both sides (x1/y1 <-> x2/y2)
     * *    1 -- draw arrow x1/y1 -> x2/y2
     * *    -1 -- draw arrow x1/y1 <- x2/y2
     *
     * @octdoc  m:asciidia/drawLine
     * @param   int         $x                  x start-point.
     * @param   int         $y1                 y start-point.
     * @param   int         $y2                 y end-point.
     * @param   int|bool    $arrow              Optional arrow head.
     */
    protected function drawVLine($x, $y1, $y2, $arrow = false)
    /**/
    {
        $this->w = max($this->w, $x);
        $this->h = max($this->h, $y1, $y2);

        $this->mvg[] = sprintf(
            'line   %d,%d %d,%d',
            $x * $this->xs + $this->xf,
            $y1 * $this->ys,
            $x * $this->xs + $this->xf,
            $y2 * $this->ys
        );
        
        if ($arrow !== false) {
        }
    }

    /**
     * Draw a marker.
     *
     * @octdoc  m:asciidia/drawMarker
     * @param   int         $x                  x point.
     * @param   int         $y                  y point.
     * @param   string      $type               Type of marker (x, o).
     * @param   bool        $ca                 Draw top connector.
     * @param   bool        $cr                 Draw right connector.
     * @param   bool        $cb                 Draw bottom connector.
     * @param   bool        $cl                 Draw left connector.
     */
    protected function drawMarker($x, $y, $type, $ca, $cr, $cb, $cl)
    /**/
    {
        $this->w = max($this->w, $x);
        $this->h = max($this->h, $y);

        printf("%s: %d,%d,%d,%d\n", $type, $ca, $cr, $cb, $cl);

        // draw connectors
        $this->mvg[] = sprintf(
            'line   %d,%d %d,%d',
            $x * $this->xs + ((1 - (int)$cl) * $this->xf),
            $y * $this->ys + $this->yf,
            $x * $this->xs + $this->xf + ((int)$cr * $this->xf),
            $y * $this->ys + $this->yf
        );
        $this->mvg[] = sprintf(
            'line   %d,%d %d,%d',
            $x * $this->xs + $this->xf,
            $y * $this->ys + ((1 - (int)$ca) * $this->yf),
            $x * $this->xs + $this->xf,
            $y * $this->ys + $this->yf + ((int)$cb * $this->yf)
        );
        
        // draw marker
        switch ($type) {
        case 'x':
            $this->mvg[] = sprintf(
                'line   %d,%d %d,%d',
                $x * $this->xs,
                $y * $this->ys,
                ($x + 1) * $this->xs - 1,
                ($y + 1) * $this->ys - 1
            );
            $this->mvg[] = sprintf(
                'line   %d,%d %d,%d',
                ($x + 1) * $this->xs - 1,
                $y * $this->ys,
                $x * $this->xs,
                ($y + 1) * $this->ys - 1
            );
            break;
        case 'o':
            $x = $x * $this->xs + $this->xf;
            $y = $y * $this->ys + $this->yf;

            $this->mvg[] = sprintf(
                'fill %s circle %d,%d %d,%d',
                $this->bg, $x, $y, $x + $this->xf, $y
            );
            break;
        }
    }

    /**
     * Draw a text string.
     *
     * @octdoc  m:asciidia/drawText
     * @param   int         $x                  x point.
     * @param   int         $y                  y point.
     * @param   string      $text               Text to draw.
     */
    protected function drawText($x, $y, $text)
    /**/
    {
        $this->w = max($this->w, $x + strlen($text));
        $this->h = max($this->h, $y);
        
        $this->mvg[] = sprintf(
            "text %d,%d '%s'",
            $x * $this->xs,
            ($y + 1) * $this->ys - ($this->yf / 2),
            addcslashes($text, "'")
        );
    }
    
    /**
     * Draw a rounded corner. The type of the corner is defined as follows:
     *
     * *    tl -- Top-Left
     * *    tr -- Top-Right
     * *    br -- Bottom-Right
     * *    bl -- Bottom-Left
     *
     * @octdoc  m:asciidia/drawCorner
     * @param   int         $x                  x point.
     * @param   int         $y                  y point.
     * @param   string      $type               Type of corner.
     */
    protected function drawCorner($x, $y, $type)
    /**/
    {   
        $rotation = array('tl' => 2, 'tr' => 3, 'br' => 0, 'bl' => 1);
        
        $this->w = max($this->w, $x);
        $this->h = max($this->h, $y);

        $this->mvg[] = sprintf(
            'arc %d,%d %d,%d %d,%d', 
            $x * $this->xs,
            $y * $this->ys,
            $x * $this->xs + $this->xf,
            $y * $this->ys + $this->yf,
            $rotation[$type] * 90,
            ($rotation[$type] + 1) * 90
        );
    }
}
