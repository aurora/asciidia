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
    protected $xs = 9.0;
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
     * X-translation of origin.
     *
     * @octdoc  v:context/$tx
     * @var     int
     */
    protected $tx = 0;
    /**/
    
    /**
     * Y-translation of origin.
     *
     * @octdoc  v:context/$ty
     * @var     int
     */
    protected $ty = 0;
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
     * Whether debugging is enabled
     *
     * @octdoc  v:context/$debug
     * @var     bool
     */
    protected $debug = false;
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
     * Add child context to current one. The new context will inherit the
     * cell scaling setting of current context.
     *
     * @octdoc  m:context/addContext
     * @return  context                     New graphic context.
     */
    public function addContext()
    /**/
    {
        $this->mvg[] = $context = new context();
        $context->xs = $this->xs;
        $context->ys = $this->ys;
        
        $context->enableDebug($this->debug);
        
        return $context;
    }
    
    /**
     * Apply a callback method to the instances of sub-contexts.
     *
     * @octdoc  m:context/applyCallback
     * @param   callback        $cb         Callback to apply to sub-context.   
     */
    protected function applyCallback($cb)
    /**/
    {
        foreach ($this->mvg as $cmd) {
            if (is_object($cmd) && $cmd instanceof context) {
                $cb($cmd);
            }
        }
    }
    
    /**
     * Calculate new size of context.
     *
     * @octdoc  m:context/setSize
     * @param   int     $x                  X-value to use for calculation.
     * @param   int     $y                  Y-value to use for calculation.
     */
    protected function setSize($x, $y)
    /**/
    {
        $this->w = max($this->w, $this->tx + $x + 1);
        $this->h = max($this->h, $this->ty + $y + 1);
    }
    
    /**
     * Return size of canvas.
     *
     * @octdoc  m:context/getSize
     * @param   bool        $cells          Whether to return size in cells instead of pixels.
     * @return  array                       w,h size of canvas.
     */
    public function getSize($cells = false)
    /**/
    {
        $w = ($cells ? $this->w : $this->w * $this->xs - 1);
        $h = ($cells ? $this->h : $this->h * $this->ys - 1);
        
        $this->applyCallback(function($context) use (&$w, &$h, $cells) {
            list($cw, $ch) = $context->getSize($cells);
            
            $w = max($w, $cw);
            $h = max($h, $ch);
        });
        
        return array($w, $h);
    }

    /**
     * Add MVG command to command-list.
     *
     * @octdoc  m:context/addCommand
     * @param   string      $command        Command to at.
     */
    public function addCommand($command)
    /**/
    {
        $this->mvg[] = $command;
    }

    /**
     * Return MVG commands.
     *
     * @octdoc  m:context/getCommands
     * @return  array                           Imagemagick MVG commands.
     */
    public function getCommands()
    /**/
    {
        // ---
        $mvg = array(
            'push graphic-context',
            'font Courier',
            sprintf('font-size %f', $this->ys)
        );
        
        // resolve child contexts
        foreach ($this->mvg as $cmd) {
            if (is_object($cmd) && $cmd instanceof context) {
                $mvg = array_merge($mvg, $cmd->getCommands());
            } else {
                $mvg[] = $cmd;
            }
        }
        
        // draw debugging grid and context boundaries
        if ($this->debug) {
            for ($x = 0; $x < $this->w; ++$x) {
                $mvg[] = sprintf(
                    'line %d,0 %d,%d',
                    $x * $this->xs,
                    $x * $this->xs,
                    $this->h * $this->ys
                );
            }
            for ($y = 0; $y < $this->h; ++$y) {
                $mvg[] = sprintf(
                    'line 0,%d %d,%d',
                    $y * $this->ys,
                    $this->w * $this->xs,
                    $y * $this->ys
                );
            }
            $mvg[] = sprintf(
                'rectangle 0,0, %d,%d', 
                $this->w * $this->xs, 
                $this->h * $this->ys
            );
        }
    
        // ---
        $mvg[] = 'pop graphic-context';

        return $mvg;
    }

    /**
     * Clear MVG command stack.
     *
     * @octdoc  m:context/clearCommands
     */
    public function clearCommands()
    /**/
    {
        $this->applyCallback(function($context) {
            $context->clearCommands();
            
            unset($context);
        });
        
        $this->mvg = array();
    }

    /**
    * Enable a debugging mode for context.
    *
     * @octdoc  m:context/enableDebug
     * @param   bool        $enable             Whether to enable / disable debugging.
     */
    public function enableDebug($enable)
    /**/
    {
        $this->applyCallback(function($context) use ($enable) {
            $context->enableDebug($enable);
        });
        
        $this->debug = $enable;
    }

    /*
     * misc MVG commands
     */
    
    /**
     * Translate origin. Note that translation is always relative to the 
     * current position of origin. While negative translation is allowed,
     * it's not allowed to move the origin to a negative position.
     *
     * @octdoc  m:context/translate
     * @param   int         $tx             x-value for translating origin.
     * @param   int         $ty             y-value for translating origin.
     */
    public function translate($tx, $ty)
    /**/
    {
        $this->tx = max(0, $this->tx + $tx);
        $this->ty = max(0, $this->ty + $ty);
    
        $this->setSize($this->tx, $this->ty);
        
        $this->mvg[] = sprintf(
            'translate %d,%d', 
            $tx * $this->xs,
            $ty * $this->ys
        );
    }

    /*
     * drawing primitives
     */

    /**
     * Draw a rectangle.
     *
     * @octdoc  m:context/drawRectangle
     * @param   int         $x1                 x start-point.
     * @param   int         $y1                 y start-point.
     * @param   int         $x2                 x end-point.
     * @param   int         $y2                 y end-point.
     * @param   bool        $round              Whether corners should be round.
     */
    public function drawRectangle($x1, $y1, $x2, $y2, $round = false)
    /**/
    {
        $this->setSize(max($x1, $x2), max($x1, $y2));
        
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
     * Draw a path between specified points. An optional arrow head may be
     * specified.
     *
     * *    false -- no arrow heads
     * *    0 -- draw arrow heads on both sides (x1/y1 <-> x2/y2)
     * *    1 -- draw arrow x1/y1 -> x2/y2
     * *    -1 -- draw arrow x1/y1 <- x2/y2
     *
     * @octdoc  m:context/drawPath
     * @param   array       $points             Points of path.
     * @param   int|bool    $arrow              Optional arrow head.
     * @param   bool        $round              Whether corners should be round.
     */
    public function drawPath(array $points, $arrow = false, $round = false)
    /**/
    {
        // callback for fetching next point from list
        $get_next = function() use (&$points) {
            return (($return = (list(, $p) = each($points) && 
                        count($p) == 2 && 
                        list($x, $y) = $p &&
                        is_int($x) &&
                        is_int($y)))
                    ? array()
                    : false);
        };

        // corner definitions
        $corners = array(
            'bl' => sprintf('A %d,%d 90 0,0 %%d,%%d', $this->xs, $this->ys),    // bottom left: A 15,15 90 0,0 15,15
            'br' => sprintf('A %d,%d 90 0,0 %%d,%%d', $this->xs, $this->ys),    // bottom right: A 15,15 90 0,0 15,0
            'tr' => sprintf('A %d,%d 90 0,0 %%d,%%d', $this->xs, $this->ys),    // top right: A 15,15 90 0,0 0,0
            'tl' => sprintf('A %d,%d 90 0,1 %%d,%%d', $this->xs, $this->ys)     // top left: A 15,15 90 0,1 15,0
        );

        // create MVG path command
        if (!(list($x1, $y1) = $get_next())) return;

        $x_max = $x1; 
        $y_max = $y1;
        
        $path = array(sprintf(
            'M %d,%d', 
            $x1 * $this->xs + $this->xf, 
            $y1 * $this->ys + $this->yf
        ));
        
        while (list($x2, $y2) = $get_next()) {
            $x_max = max($x_max, $x2);
            $y_max = max($y_max, $y2);
        
            if ($x2 != $x1 && $y2 != $y1) {
                // insert a point, because one of the points must always be equal
                $path[] = sprintf(
                    'L %d,%d', 
                    $x1 * $this->xs + $this->xf,
                    $y2 * $this->ys + $this->yf
                );
            }
            
            $path[] = sprintf(
                'L %d,%d',
                $x2 * $this->xs + $this->xf,
                $y2 * $this->ys + $this->yf
            );
        }

        $this->setSize($x_max, $y_max);
        
        $this->mvg[] = sprintf("path '%s'", implode(' ', $path));
    }

    /**
     * Draw a line between two points. An optional arrow head may be specified:
     *
     * *    false -- no arrow heads
     * *    0 -- draw arrow heads on both sides (x1/y1 <-> x2/y2)
     * *    1 -- draw arrow x1/y1 -> x2/y2
     * *    -1 -- draw arrow x1/y1 <- x2/y2
     *
     * @octdoc  m:context/drawLine
     * @param   int         $x1                 x start-point.
     * @param   int         $y1                 y start-point.
     * @param   int         $x2                 x end-point.
     * @param   int         $y2                 y end-point.
     * @param   int|bool    $arrow              Optional arrow head.
     */
    public function drawLine($x1, $y1, $x2, $y2, $arrow = false)
    /**/
    {
        $this->setSize(max($x1, $x2), max($y1, $y2));

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
     * @octdoc  m:context/drawLine
     * @param   int         $x1                 x start-point.
     * @param   int         $y                  y start-point.
     * @param   int         $x2                 x end-point.
     * @param   int|bool    $arrow              Optional arrow head.
     */
    public function drawHLine($x1, $y, $x2, $arrow = false)
    /**/
    {
        $this->setSize(max($x1, $x2), $y);

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
     * @octdoc  m:context/drawLine
     * @param   int         $x                  x start-point.
     * @param   int         $y1                 y start-point.
     * @param   int         $y2                 y end-point.
     * @param   int|bool    $arrow              Optional arrow head.
     */
    public function drawVLine($x, $y1, $y2, $arrow = false)
    /**/
    {
        $this->setSize($x, max($y1, $y2));

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
     * @octdoc  m:context/drawMarker
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
        $this->setSize($x, $y);

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
     * @octdoc  m:context/drawText
     * @param   int         $x                  x point.
     * @param   int         $y                  y point.
     * @param   string      $text               Text to draw.
     */
    public function drawText($x, $y, $text)
    /**/
    {
        $this->setSize($x + strlen($text), $y);
        
        $this->mvg[] = sprintf(
            "text %d,%d '%s'",
            $x * $this->xs,
            ($y + 1) * $this->ys - ($this->yf / 2),
            addcslashes($text, "'")
        );
    }

    /**
     * Draw a text-label. A text-label is a string centered inside a rectangle
     * with optional round corners.
     *
     * @todo    how do we get this centered, if font-metrics do not fit grid-
     *          metrics?
     *
     * @octdoc  m:context/drawLabel
     * @param   int         $x1                 x point.
     * @param   int         $y1                 y point.
     * @param   string      $text               Text to draw.
     * @param   bool        $round              Whether corners should be round.
     */
    public function drawLabel($x1, $y1, $text, $round = false)
    /**/
    {
        $x2 = $x1 + ($w = strlen($text) + 1);
        $y2 = $y1 + ($h = 2);
        
        $this->drawRectangle($x1, $y1, $x2, $y2, $round);
        $this->drawText($x1 + 1, $y1 + 1, $text);
    }

    /**
     * Draw a (rounded) corner. The type of the corner is defined as follows:
     *
     * *    tl -- Top-Left
     * *    tr -- Top-Right
     * *    br -- Bottom-Right
     * *    bl -- Bottom-Left
     *
     * @octdoc  m:context/drawCorner
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
        
        $this->setSize($x, $y);

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
    
    /*
     * misc tools
     */
     
    /**
     * Return metrics for font.
     *
     * @octdoc  m:context/getFontMetrics
     * @param   string      $font           Font to return metrics for.
     * @param   string      $text           Text to return metrics for.
     * @param   float       $pointsize      Pointsize to return metrics for.
     * @return  array                       Font metrics.
     */
    public static function getFontMetrics($font, $text, $pointsize)
    /**/
    {
        $return = false;
        
        $cmd = sprintf(
            "convert -size 100x150 xc:lightblue -font %s \
                     -pointsize %f -fill none -undercolor white \
                     -annotate +20+100 %s -trim info:",
            escapeshellarg($font),
            escapeshellarg($pointsize),
            escapeshellarg($text)
        );
        
        if (preg_match('/XC (\d+)x(\d/+)/', `$cmd`, $m)) {
           $return = array('width' => $m[1], 'height' => $m[2]);
        }
        
        return $return;
    }
}
