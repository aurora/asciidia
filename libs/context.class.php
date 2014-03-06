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
    abstract public function drawPath(array $points, $arrow = false, $round = false);
    /**/

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
