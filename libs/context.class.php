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
 * Provides graphics context and drawing primitives.
 *
 * @octdoc      c:libs/context
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class context
/**/
{
    /**
     * X-multiplicator -- the width of a cell on the canvas.
     *
     * @octdoc  v:context/$xs
     * @var     float
     */
    protected $xs = 15.0;
    /**/
    
    /**
     * Y-multiplicator -- the height of a cell on the canvas.
     *
     * @octdoc  v:context/$ys
     * @var     float
     */
    protected $ys = 15.0;
    /**/
    
    /**
     * X-fract -- half of a cells width.
     *
     * @octdoc  v:context/$xf
     * @var     float
     */
    protected $xf = 0;
    /**/
    
    /**
     * Y-fract -- half of a cells height.
     *
     * @octdoc  v:context/yf
     * @var     float
     */
    protected $yf = 0;
    /**/
    
    /**
     * Width of diagram canvas.
     *
     * @octdoc  v:context/$w
     * @var     int
     */
    protected $w = 0;
    /**/
    
    /**
     * Height of diagram canvas.
     *
     * @octdoc  v:context/$h
     * @var     int
     */
    protected $h = 0;
    /**/
    
    /**
     * Whether grid overlay is enabled
     *
     * @octdoc  v:context/$grid
     * @var     bool
     */
    protected $grid = false;
    /**/
    
    /**
     * Background color.
     *
     * @octdoc  v:context/$bg
     * @var     string
     */
    protected $bg = 'white';
    /**/
    
    /**
     * Stroke color
     *
     * @octdoc  v:context/$stroke
     * @var     string
     */
    protected $stroke = 'black';
    /**/
    
    /**
     * Imagemagick MVG commands.
     *
     * @octdoc  v:context/$mvg
     * @var     array
     */
    protected $mvg = array();
    /**/
    
    /**
     * Constructor.
     *
     * @octdoc  m:context/__construct
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
     * @octdoc  m:context/__set
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
     * Return MVG commands.
     *
     * @octdoc  m:context/getCommands
     */
    public function getCommands()
    /**/
    {
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

    /*
     * drawing primitives
     */

    /**
     * Draw a rectangle.
     *
     * @octdoc  m:plugin/drawRectangle
     * @param   int         $x1                 x start-point.
     * @param   int         $y1                 y start-point.
     * @param   int         $x2                 x end-point.
     * @param   int         $y2                 y end-point.
     * @param   bool        $round              Whether corners should be round.
     */
    public function drawRectangle($x1, $y1, $x2, $y2, $round = false)
    /**/
    {
        $this->w = max($this->w, $x1, $x2);
        $this->h = max($this->h, $y1, $y2);
        
        if ($round) {
            $this->mvg[] = sprintf(
                'rectangle  %d,%d %d,%d',
                $x1 * $this->xs + $this->xf,
                $y1 * $this->ys + $this->yf,
                $x2 * $this->xs + $this->xf,
                $y2 * $this->ys + $this->yf
            );
        } else {
            $this->mvg[] = sprintf(
                'roundrectangle  %d,%d %d,%d %d,%d',
                $x1 * $this->xs + $this->xf,
                $y1 * $this->ys + $this->yf,
                $x2 * $this->xs + $this->xf,
                $y2 * $this->ys + $this->yf,
                $this->xf,
                $this->yf
            );
        }
    }

    /**
     * Draw a line between two points. An optional arrow head may be specified:
     *
     * @octdoc  m:plugin/drawLine
     * @param   int         $x1                 x start-point.
     * @param   int         $y1                 y start-point.
     * @param   int         $x2                 x end-point.
     * @param   int         $y2                 y end-point.
     * @param   int|bool    $arrow              Optional arrow head.
     */
    public function drawLine($x1, $y1, $x2, $y2, $arrow = false)
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
     * @octdoc  m:plugin/drawLine
     * @param   int         $x1                 x start-point.
     * @param   int         $y                  y start-point.
     * @param   int         $x2                 x end-point.
     * @param   int|bool    $arrow              Optional arrow head.
     */
    public function drawHLine($x1, $y, $x2, $arrow = false)
    /**/
    {
        $this->w = max($this->w, $x1, $x2);
        $this->h = max($this->h, $y);

        $this->mvg[] = sprintf(
            'line   %d,%d %d,%d',
            $x1 * $this->xs,
            $y * $this->ys + $this->yf,
            $x2 * $this->xs + $this->xs,
            $y * $this->ys + $this->yf
        );
        
        if ($arrow !== false) {
            if ($x1 > $x2) $x1 ^= $x2 ^= $x1 ^= $x2;
            $f = $this->yf / 2;
            
            if ($arrow <= 0) {
                $this->mvg[] = sprintf(
                    "fill %s path 'M %d,%d %d,%d %d,%d Z'",
                    $this->stroke,
                    $x1 * $this->xs, $y * $this->ys + $this->yf,
                    $x1 * $this->xs + $this->xf, $y * $this->ys + $f,
                    $x1 * $this->xs + $this->xf, $y * $this->ys + $this->yf + $f
                );
            }
            if ($arrow >= 0) {
                $this->mvg[] = sprintf(
                    "fill %s path 'M %d,%d %d,%d %d,%d Z'",
                    $this->stroke,
                    $x2 * $this->xs + $this->xs, $y * $this->ys + $this->yf,
                    $x2 * $this->xs + $this->xf, $y * $this->ys + $f,
                    $x2 * $this->xs + $this->xf, $y * $this->ys + $this->yf + $f
                );
            }
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
     * @octdoc  m:plugin/drawLine
     * @param   int         $x                  x start-point.
     * @param   int         $y1                 y start-point.
     * @param   int         $y2                 y end-point.
     * @param   int|bool    $arrow              Optional arrow head.
     */
    public function drawVLine($x, $y1, $y2, $arrow = false)
    /**/
    {
        $this->w = max($this->w, $x);
        $this->h = max($this->h, $y1, $y2);

        $this->mvg[] = sprintf(
            'line   %d,%d %d,%d',
            $x * $this->xs + $this->xf,
            $y1 * $this->ys,
            $x * $this->xs + $this->xf,
            $y2 * $this->ys + $this->ys
        );
        
        if ($arrow !== false) {
            if ($y1 > $y2) $y1 ^= $y2 ^= $y1 ^= $y2;
            $f = $this->xf / 2;
            
            if ($arrow <= 0) {
                $this->mvg[] = sprintf(
                    "fill %s path 'M %d,%d %d,%d %d,%d Z'",
                    $this->stroke,
                    $x * $this->xs + $this->xf, $y1 * $this->ys,
                    $x * $this->xs + $f, $y1 * $this->ys + $this->yf,
                    $x * $this->xs + $this->xf + $f, $y1 * $this->ys + $this->yf
                );
            }
            if ($arrow >= 0) {
                $this->mvg[] = sprintf(
                    "fill %s path 'M %d,%d %d,%d %d,%d Z'",
                    $this->stroke,
                    $x * $this->xs + $f, $y2 * $this->ys + $this->yf,
                    $x * $this->xs + $this->xf, $y2 * $this->ys + $this->ys,
                    $x * $this->xs + $this->xf + $f, $y2 * $this->ys + $this->yf
                );
            }
        }
    }

    /**
     * Draw a marker.
     *
     * @octdoc  m:plugin/drawMarker
     * @param   int         $x                  x point.
     * @param   int         $y                  y point.
     * @param   string      $type               Type of marker (x, o).
     * @param   bool        $ca                 Draw top connector.
     * @param   bool        $cr                 Draw right connector.
     * @param   bool        $cb                 Draw bottom connector.
     * @param   bool        $cl                 Draw left connector.
     */
    public function drawMarker($x, $y, $type, $ca, $cr, $cb, $cl)
    /**/
    {
        $this->w = max($this->w, $x);
        $this->h = max($this->h, $y);

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
        $hxf = $this->xf / 2;
        $hyf = $this->yf / 2;
        
        switch ($type) {
        case 'x':
            $this->mvg[] = sprintf(
                'line   %d,%d %d,%d',
                $x * $this->xs + $hxf,
                $y * $this->ys + $hyf,
                $x * $this->xs + $this->xs - $hxf,
                $y * $this->ys + $this->ys - $hyf
            );
            $this->mvg[] = sprintf(
                'line   %d,%d %d,%d',
                $x * $this->xs + $this->xs - $hxf,
                $y * $this->ys + $hyf,
                $x * $this->xs + $hxf,
                $y * $this->ys + $this->ys - $hyf
            );
            break;
        case 'o':
            $x = $x * $this->xs + $this->xf;
            $y = $y * $this->ys + $this->yf;

            $this->mvg[] = sprintf(
                'fill %s ellipse %d,%d %d,%d 0,360',
                $this->bg, $x, $y, $hxf, $hyf
            );
            break;
        }
    }

    /**
     * Draw a text string.
     *
     * @octdoc  m:plugin/drawText
     * @param   int         $x                  x point.
     * @param   int         $y                  y point.
     * @param   string      $text               Text to draw.
     */
    public function drawText($x, $y, $text)
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
     * Draw a (rounded) corner. The type of the corner is defined as follows:
     *
     * *    tl -- Top-Left
     * *    tr -- Top-Right
     * *    br -- Bottom-Right
     * *    bl -- Bottom-Left
     *
     * @octdoc  m:plugin/drawCorner
     * @param   int         $x                  x point.
     * @param   int         $y                  y point.
     * @param   string      $type               Type of corner.
     * @param   bool        $round              Whether corner should be round.
     */
    public function drawCorner($x, $y, $type, $round = false)
    /**/
    {   
        $rotation = array(
            'br' => array(0, 0, 0, 90),
            'bl' => array($this->xs, 0, 90, 180),
            'tl' => array($this->xs, $this->ys, 180, 270),
            'tr' => array(0, $this->ys, 270, 360)
        );
        
        if (!isset($rotation[$type])) {
            return;
        }
        
        list($xf, $yf, $rs, $re) = $rotation[$type];
        
        $this->w = max($this->w, $x);
        $this->h = max($this->h, $y);

        if ($round) {
            $this->mvg[] = sprintf(
                'fill transparent ellipse %d,%d %d,%d %d,%d',
                $x * $this->xs + $xf, 
                $y * $this->ys + $yf,
                $this->xf,
                $this->yf,
                $rs,
                $re
            );
        } else {
            $this->drawMarker(
                $x, $y, '+', 
                ($type == 'br' || $type == 'bl'),
                ($type == 'bl' || $type == 'tl'),
                ($type == 'tl' || $type == 'tr'),
                ($type == 'tr' || $type == 'br')
            );
        }
    }
}