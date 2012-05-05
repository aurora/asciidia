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

require_once(__DIR__ . '/util/spline.class.php');

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
     * Whether debugging is enabled. 1: enabled, 2: propagate to sub-contexts.
     *
     * @octdoc  v:context/$debug
     * @var     int|bool
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
        $this->mvg[] = array(
            'tx'        => $this->tx,
            'ty'        => $this->ty,
            'context'   => ($context = new static())
        );
        
        $context->__set('xs', $this->xs);
        $context->__set('ys', $this->ys);

        $debug = ((int)$this->debug == 2);
        $context->enableDebug($debug, $debug);
        
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
            if (is_array($cmd) && isset($cmd['context'])) {
                $cb($cmd['context'], $cmd['tx'], $cmd['ty']);
            }
        }
    }
    
    /**
     * Set new size of canvas. Note, that a canvas cannot be smaller, than the
     * current content. Therefore the specified values are dismissed, if they 
     * are smaller, than the current width/height of the canvas.
     *
     * @octdoc  m:context/setSize
     * @param   int     $x                  X-value to use for calculation.
     * @param   int     $y                  Y-value to use for calculation.
     */
    public function setSize($x, $y)
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
        $xs = $this->xs;
        $ys = $this->ys;
        
        $w = ($cells ? $this->w : $this->w * $this->xs - 1);
        $h = ($cells ? $this->h : $this->h * $this->ys - 1);
        
        $this->applyCallback(function($context, $tx, $ty) use (&$w, &$h, $xs, $ys, $cells) {
            list($cw, $ch) = $context->getSize($cells);
            
            $w = max($w, ($cells ? $tx + $cw : $tx * $xs + $cw));
            $h = max($h, ($cells ? $ty + $ch : $ty * $ys + $ch));
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
            if (is_array($cmd) && isset($cmd['context'])) {
                $mvg = array_merge($mvg, $cmd['context']->getCommands());
            } else {
                $mvg[] = $cmd;
            }
        }
        
        // draw debugging grid and context boundaries
        if ($this->debug) {
            $mvg[] = sprintf('translate %f,%f', -$this->tx * $this->xs, -$this->ty * $this->ys);
            $mvg[] = 'push graphic-context';
            $mvg[] = 'fill transparent';
            
            list($cw, $ch) = $this->getSize(true);
            
            for ($x = 0; $x < $cw; ++$x) {
                $mvg[] = sprintf(
                    'line %f,0 %f,%f',
                    $x * $this->xs,
                    $x * $this->xs,
                    $ch * $this->ys
                );
            }
            for ($y = 0; $y < $ch; ++$y) {
                $mvg[] = sprintf(
                    'line 0,%f %f,%f',
                    $y * $this->ys,
                    $cw * $this->xs,
                    $y * $this->ys
                );
            }
            $mvg[] = sprintf(
                'rectangle 0,0, %f,%f', 
                $cw * $this->xs, 
                $ch * $this->ys
            );
            
            $this->mvg[] = sprintf(
                "text 0,%f '%d,%d'", $this->ys - ($this->yf / 2), $cw, $ch
            );
            
            $mvg[] = 'pop graphic-context';
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
     * @param   bool        $propagate          Whether debug setting should be propagated to sub-contexts.
     */
    public function enableDebug($enable, $propagate = false)
    /**/
    {
        if ($propagate) {
            $this->applyCallback(function($context) use ($enable, $propagate) {
                $context->enableDebug($enable, $propagate);
            });
        }
        
        $this->debug = ($enable ? (int)$enable + (int)$propagate : false);
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
    
        $this->setSize(1, 1);
        
        $this->mvg[] = sprintf(
            'translate %f,%f', 
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
        $this->setSize(max($x1, $x2), max($y1, $y2));
        
        if ($round) {
            $this->mvg[] = sprintf(
                'rectangle  %f,%f %f,%f',
                $x1 * $this->xs + $this->xf,
                $y1 * $this->ys + $this->yf,
                $x2 * $this->xs + $this->xf,
                $y2 * $this->ys + $this->yf
            );
        } else {
            $this->mvg[] = sprintf(
                'roundrectangle  %f,%f %f,%f %f,%f',
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
     * Draw a spline using cubic bezier primitives.
     *
     * @octdoc  m:context/drawSpline
     * @param   array       $points             Points to draw spline in.
     */
    public function drawSpline(array $points)
    /**/
    {
        if (($cnt = count($points)) < 2) return;

        if ($cnt == 2) {
            // 2 points is just a straight line
            list($x1, $y1) = $points[0];
            list($x2, $y2) = $points[1];

            $this->drawLine($x1, $y1, $x2, $y2);

            return;
        }

        list($cp1, $cp2) = spline::getControlPoints($points);

        for ($i = 0, $cnt = $cnt - 1; $i < $cnt; ++$i) {
            list($x1, $y1) = $points[$i];
            list($x2, $y2) = $points[$i + 1];

            list($cx1, $cy1) = $cp1[$i];
            list($cx2, $cy2) = $cp2[$i];

            $this->mvg[] = sprintf(
                "path 'M %f,%f C %f,%f %f,%f %f,%f'",
                $x1 * $this->xs + $this->xf,  $y1 * $this->ys + $this->yf,
                $cx1 * $this->xs + $this->xf, $cy1 * $this->ys + $this->yf,
                $cx2 * $this->xs + $this->xf, $cy2 * $this->ys + $this->yf,
                $x2 * $this->xs + $this->xf,  $y2 * $this->ys + $this->yf
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
     * Before drawing a path, point normalization is performed: invalid points 
     * (points with missing x or y value) will be removed and required additional
     * points are inserted (path drawing is only allowed for horizontals and 
     * verticals)
     *
     * @octdoc  m:context/drawPath
     * @param   array       $points             Points of path.
     * @param   int|bool    $arrow              Optional arrow head.
     * @param   bool        $round              Whether corners should be round.
     */
    public function drawPath(array $points, $arrow = false, $round = false)
    /**/
    {
        // normalize points
        if (count($points) == 0) return;
        
        $_tmp = array();
        $x = $y = null;
        
        foreach ($points as $p) {
            $ox = $x; $oy = $y;
            
            if (!is_array($p) || count($p) != 2 || !is_int($p[0]) || !is_int($p[1])) continue;      // remove invalid point
            
            list($x, $y) = $p;

            if ($x == $ox && $y == $oy) continue;       // remove point, if it's same position as previous point
            
            if (!is_null($ox) && !is_null($oy)) {
                if ($ox != $x && $oy != $y) {
                    // insert additional point if both x and y are different
                    $_tmp[] = array($ox, $y);
                }
            }

            $_tmp[] = array($x, $y);
        }

        $points = $_tmp;

        if (count($points) == 0) return;

        // misc initialization
        $order = '--';

        // helper functions
        $get_point = function($p1, $p2, $middle = true) use ($round) {
            return ($round && $p1 != $p2 && $middle
                    ? ($p1 > $p2 ? $p2 + 0.5 : $p2 - 0.5)
                    : $p2);
        };
        $get_corner = function($x_dir, $y_dir) use (&$order) {
            if ($x_dir < 0 && $y_dir < 0) {
                $c = ($order == 'xy' ? 'bl' : 'tr');
            } elseif ($x_dir < 0 && $y_dir > 0) { 
                $c = ($order == 'xy' ? 'tl' : 'br'); 
            } elseif ($x_dir > 0 && $y_dir > 0) {
                $c = ($order == 'xy' ? 'tr' : 'bl');
            } elseif ($x_dir > 0 && $y_dir < 0) {
                $c = ($order == 'xy' ? 'br' : 'tl');
            } else return '';
            
            return $c;
        };
        $get_angle = function($x_dir, $y_dir) {
            return ($x_dir != 0
                    ? ($x_dir < 0 ? -90 : 90)
                    : ($y_dir < 0 ? 0 : 180));
        };
        $set_order = function($type) use (&$order) {
            $order = substr($order . $type, 1, 2);
        };

        // create MVG path command
        list($x1, $y1) = $points[0];

        $x_max = $x1; 
        $y_max = $y1;
        $x_dir = 0;
        $y_dir = 0;
        
        for ($i = 1, $cnt = count($points); $i < $cnt; ++$i) {
            list($x2, $y2) = $points[$i];
            
            if ($x2 != $x1) { $set_order('x'); $x_dir = ($x2 - $x1); }
            if ($y2 != $y1) { $set_order('y'); $y_dir = ($y2 - $y1); }
            
            if ($i == 1 && $arrow !== false && $arrow <= 0) $this->_drawArrowHead($x1, $y1, $get_angle($x_dir, $y_dir));

            if ($x_dir != 0 && $y_dir != 0) {
                // corner detected
                $this->drawCorner(
                    $x1, $y1,
                    $get_corner($x_dir, $y_dir),
                    true
                );
            }

            $this->drawLine(
                $get_point($x2, $x1, ($i > 1)),
                $get_point($y2, $y1, ($i > 1)),
                $get_point($x1, $x2, ($i + 1 < $cnt)),
                $get_point($y1, $y2, ($i + 1 < $cnt))
            );
            
            $x_max = max($x_max, $x2);
            $y_max = max($y_max, $y2);
            
            $x1 = $x2; $y1 = $y2;
        }

        if ($arrow !== false && $arrow >= 0) $this->_drawArrowHead($x2, $y2, $get_angle($x_dir, $y_dir));

        $this->setSize($x_max, $y_max);
    }

    /**
     * Draw a line between two points. An optional arrow head may be specified:
     *
     * *    false -- no arrow heads
     * *    0 -- draw arrow heads on both sides (x1/y1 <-> x2/y2)
     * *    1 -- draw arrow x1/y1 -> x2/y2
     * *    -1 -- draw arrow x1/y1 <- x2/y2
     *
     * @todo    arrow head needs to be implemented.
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
            'line   %f,%f %f,%f',
            $x1 * $this->xs + $this->xf,
            $y1 * $this->ys + $this->yf,
            $x2 * $this->xs + $this->xf,
            $y2 * $this->ys + $this->yf
        );
        
        if ($arrow !== false) {
            if ($x1 > $x2) $x1 ^= $x2 ^= $x1 ^= $x2;

            $angle = rad2deg(atan2(($y2 - $y1), ($x2 - $x1)));
            
            if ($arrow <= 0) $this->_drawArrowHead($x1, $y1, $angle - 90);
            if ($arrow >= 0) $this->_drawArrowHead($x2, $y2, $angle + 90);
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
            'line   %f,%f %f,%f',
            $x1 * $this->xs,
            $y * $this->ys + $this->yf,
            $x2 * $this->xs + $this->xs,
            $y * $this->ys + $this->yf
        );
        
        if ($arrow !== false) {
            if ($x1 > $x2) $x1 ^= $x2 ^= $x1 ^= $x2;
            
            if ($arrow <= 0) $this->_drawArrowHead($x1 + 0.5, $y, -90);
            if ($arrow >= 0) $this->_drawArrowHead($x2 - 0.5, $y,  90);
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
            'line   %f,%f %f,%f',
            $x * $this->xs + $this->xf,
            $y1 * $this->ys,
            $x * $this->xs + $this->xf,
            $y2 * $this->ys + $this->ys
        );
        
        if ($arrow !== false) {
            if ($y1 > $y2) $y1 ^= $y2 ^= $y1 ^= $y2;
            
            if ($arrow <= 0) $this->_drawArrowHead($x, $y1,   0);
            if ($arrow >= 0) $this->_drawArrowHead($x, $y2, 180);
        }
    }

    /**
     * Draw line crossing symbol.
     *
     * @octdoc  m:context/drawLineCrossing
     * @param   int         $x                  x point.
     * @param   int         $y                  y point.
     */
    public function drawLineCrossing($x, $y)
    /**/
    {
        $this->setSize($x, $y);

        $yf = $this->yf / 2;
        $xf = $this->xf / 2;

        // draw lines
        $this->mvg[] = sprintf(
            'line   %f,%f %f,%f',
            $x * $this->xs,
            $y * $this->ys + $this->yf,
            $x * $this->xs + $this->xf + $this->xf,
            $y * $this->ys + $this->yf
        );

        // draw crossing
        $this->mvg[] = sprintf(
            'fill transparent ellipse %f,%f %f,%f %f,%f',
            $x * $this->xs + $this->xf, 
            $y * $this->ys + $this->yf,
            $this->xf,
            $this->yf,
            -90,
            -270
        );
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
            'line   %f,%f %f,%f',
            $x * $this->xs + ((1 - (int)$cl) * $this->xf),
            $y * $this->ys + $this->yf,
            $x * $this->xs + $this->xf + ((int)$cr * $this->xf),
            $y * $this->ys + $this->yf
        );
        $this->mvg[] = sprintf(
            'line   %f,%f %f,%f',
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
                'line   %f,%f %f,%f',
                $x * $this->xs + $hxf,
                $y * $this->ys + $hyf,
                $x * $this->xs + $this->xs - $hxf,
                $y * $this->ys + $this->ys - $hyf
            );
            $this->mvg[] = sprintf(
                'line   %f,%f %f,%f',
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
                'fill %s ellipse %f,%f %f,%f 0,360',
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
            "text %f,%f '%s'",
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
                'fill transparent ellipse %f,%f %f,%f %f,%f',
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
    
    /**
     * Draw the head of an arrow.
     *
     * @octdoc  m:context/drawArrowHead
     * @param   int         $x              X-position of the arrow-head
     * @param   int         $y              Y-position of the arrow-head
     * @param   float       $angle          Rotation angle.
     */
    public function drawArrowHead($x, $y, $angle)
    /**/
    {
        $this->setSize($x, $y);
        
        $this->_drawArrowHead($x, $y, $angle);
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
    
    /*
     * misc helper functions
     */
     
    /**
     * This is a helper function to draw an arrow head for example for a line
     * or a path. This method is protected to prevent a direct call. Use one of
     * the line- or path-drawing functions to draw an arrow.
     *
     * @octdoc  m:context/_drawArrowHead
     * @param   int         $x              X-position of the arrow-head
     * @param   int         $y              Y-position of the arrow-head
     * @param   float       $angle          Rotation angle.
     */
    protected function _drawArrowHead($x, $y, $angle)
    /**/
    {
        $xs = $this->xs;
        $ys = $this->ys;
        $xf = $this->xf;
        $yf = $this->yf;
        
        $get_xy = function($x1, $y1) use ($angle, $x, $y, $xs, $ys, $xf, $yf) {
            $x2 = ($x1 * cos(deg2rad($angle)) - $y1 * sin(deg2rad($angle)));
            $y2 = ($y1 * cos(deg2rad($angle)) + $x1 * sin(deg2rad($angle)));
            
            return sprintf(
                '%f,%f',
                ($x * $xs + $xf) + $x2,
                ($y * $ys + $yf) + $y2
            );
        };

        $this->mvg[] = 'push graphic-context';
        $this->mvg[] = sprintf(
            "fill %s path 'M %s L %s %s Z'",
            $this->stroke,
            $get_xy(-$this->xf, 0),
            $get_xy(0, -$this->yf),
            $get_xy($this->xf, 0)
        );
        $this->mvg[] = 'pop graphic-context';
    }
}
