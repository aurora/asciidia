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

namespace asciidia {
    /**
     * Provides graphics context and drawing primitives.
     *
     * @octdoc      c:libs/context
     * @copyright   copyright (c) 2011-2014 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    abstract class context
    /**/
    {
        /**
         * X-multiplicator -- the width of a cell on the canvas.
         *
         * @octdoc  p:context/$xs
         * @type    float
         */
        protected $xs = 9.0;
        /**/
    
        /**
         * Y-multiplicator -- the height of a cell on the canvas.
         *
         * @octdoc  p:context/$ys
         * @type    float
         */
        protected $ys = 15.0;
        /**/
    
        /**
         * X-fract -- half of a cells width.
         *
         * @octdoc  p:context/$xf
         * @type    float
         */
        protected $xf = 0;
        /**/
    
        /**
         * Y-fract -- half of a cells height.
         *
         * @octdoc  p:context/yf
         * @type    float
         */
        protected $yf = 0;
        /**/
    
        /**
         * X-translation of origin.
         *
         * @octdoc  p:context/$tx
         * @type    int
         */
        protected $tx = 0;
        /**/
    
        /**
         * Y-translation of origin.
         *
         * @octdoc  p:context/$ty
         * @type    int
         */
        protected $ty = 0;
        /**/
    
        /**
         * Width of diagram canvas.
         *
         * @octdoc  p:context/$w
         * @type    int
         */
        protected $w = 0;
        /**/
    
        /**
         * Height of diagram canvas.
         *
         * @octdoc  p:context/$h
         * @type    int
         */
        protected $h = 0;
        /**/
    
        /**
         * Whether debugging is enabled. 1: enabled, 2: propagate to sub-contexts.
         *
         * @octdoc  p:context/$debug
         * @type    int|bool
         */
        protected $debug = false;
        /**/
    
        /**
         * Background color.
         *
         * @octdoc  p:context/$bg
         * @type    string
         */
        protected $bg = 'white';
        /**/
    
        /**
         * Stroke color
         *
         * @octdoc  p:context/$stroke
         * @type    string
         */
        protected $stroke = 'black';
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
                // $this->xf = floor($value / 2);
                break;
            case 'ys':
                $this->ys = $value;
                $this->yf = $value / 2;
                // $this->yf = floor($value / 2);
                break;
            }
        }
    
        /**
         * Add child context to current one. The new context will inherit the
         * cell scaling setting of current context.
         *
         * @octdoc  m:context/addContext
         * @return  \asciidia\context                     New graphic context.
         */
        abstract public function addContext();
        /**/
    
        /**
         * Apply a callback method to the instances of sub-contexts.
         *
         * @octdoc  m:context/applyCallback
         * @param   callback        $cb         Callback to apply to sub-context.   
         */
        abstract protected function applyCallback($cb);
        /**/
    
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
         * Return size of a cell.
         *
         * @octdoc  m:context/getCellSize
         * @return  array                       w,h size of a cell.
         */
        public function getCellSize()
        /**/
        {
            return array($this->xs, $this->ys);
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
         * misc drawing commands
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
        }

        /**
         * Rotate cursor.
         *
         * @octdoc  m:context/rotate
         * @param   float       $angle          Angle to rotate by.
         */
        abstract public function rotate($angle);
        /**/

        /**
         * Change font settings.
         *
         * @octdoc  m:context/setFont
         * @param   array       $settings           Font settings.
         */
        abstract public function setFont(array $settings);
        /**/

        /**
         * Change fill settings.
         *
         * @octdoc  m:context/setFill
         * @param   array       $settings           Fill settings.
         */
        abstract public function setFill(array $settings);
        /**/

        /**
         * Change stroke settings.
         *
         * @octdoc  m:context/setStroke
         * @param   array       $settings           Stroke settings.
         */
        abstract public function setStroke(array $settings);
        /**/

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
        abstract public function drawRectangle($x1, $y1, $x2, $y2, $round = false);
        /**/

        /**
         * Draw a spline using cubic bezier primitives.
         *
         * @octdoc  m:context/drawSpline
         * @param   array       $points             Points to draw spline in.
         */
        abstract public function drawSpline(array $points);
        /**/

        /**
         * Draw a path.
         *
         * @octdoc  m:context/drawPath
         * @param   string      $d                  Path definition.
         */
        abstract public function drawPath($d);
        /**/

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
         * @octdoc  m:context/drawConnection
         * @param   array       $points             Points of path.
         * @param   int|bool    $arrow              Optional arrow head.
         * @param   bool        $round              Whether corners should be round.
         */
        public function drawConnection(array $points, $arrow = false, $round = false)
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

            // create path
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
        abstract public function drawLine($x1, $y1, $x2, $y2, $arrow = false);
        /**/
    
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
        abstract public function drawHLine($x1, $y, $x2, $arrow = false);
        /**/
    
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
        abstract public function drawVLine($x, $y1, $y2, $arrow = false);
        /**/

        /**
         * Draw line crossing symbol.
         *
         * @octdoc  m:context/drawLineCrossing
         * @param   int         $x                  x point.
         * @param   int         $y                  y point.
         */
        abstract public function drawLineCrossing($x, $y);
        /**/

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
        abstract public function drawMarker($x, $y, $type, $ca, $cr, $cb, $cl);
        /**/

        /**
         * Draw a text string.
         *
         * @octdoc  m:context/drawText
         * @param   int         $x                  x point.
         * @param   int         $y                  y point.
         * @param   string      $text               Text to draw.
         */
        abstract public function drawText($x, $y, $text);
        /**/

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
        abstract function drawLabel($x1, $y1, $text, $round = false);
        /**/

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
        abstract public function drawCorner($x, $y, $type, $round = false);
        /**/
    
        /**
         * Draw the head of an arrow.
         *
         * @octdoc  m:context/drawArrowHead
         * @param   int         $x              X-position of the arrow-head
         * @param   int         $y              Y-position of the arrow-head
         * @param   float       $angle          Rotation angle.
         */
        abstract public function drawArrowHead($x, $y, $angle);
        /**/
    }
}
