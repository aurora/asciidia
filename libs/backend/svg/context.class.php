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

namespace asciidia\backend\svg {
    /**
     * Provides graphics context and drawing primitives.
     *
     * @octdoc      c:context/context
     * @copyright   copyright (c) 2014 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class context extends \asciidia\context
    /**/
    {
        /**
         * SVG Document.
         *
         * @octdoc  p:context/$doc
         * @type    \DOMDocument
         */
        protected $doc;
        /**/
    
        /**
         * SVG root node.
         *
         * @octdoc  p:context/$svg
         * @type    \DOMNode
         */
        protected $svg;
        /**/
    
        /**
         * Child context class instances.
         *
         * @octdoc  p:context/$children
         * @type    array
         */
        protected $children = array();
        /**/
    
        /**
         * Font settings.
         *
         * @octdoc  p:context/$font
         * @type    array
         */
        protected $font = array(
            'family' => 'Courier',
            'size'   => null
        );
        /**/
    
        /**
         * Fill settings.
         *
         * @octdoc  p:context/$fill
         * @type    array
         */
        protected $fill = array(
            'color'   => 'transparent',
            'opacity' => 1
        );
        /**/
    
        /**
         * Stroke settings.
         *
         * @octdoc  p:context/$stroke
         * @type    array
         */
        protected $stroke = array(
            'width'   => 1,
            'color'   => 'black',
            'opacity' => 1
        );
        /**/    

        /**
         * Constructor.
         *
         * @octdoc  m:context/__construct
         * @param   \DOMNode            $parent             Parent node.
         */
        public function __construct(\DOMNode $parent)
        /**/
        {
            parent::__construct();
        
            $this->doc = $parent->ownerDocument;
            $this->svg = $parent->appendChild($this->doc->createElement('g'));
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
            foreach ($this->children as $child) {
                $cb($child['context'], $child['tx'], $child['ty']);
            }
        }
    
        /**
         * Add child context to current one. The new context will inherit the
         * cell scaling setting of current context.
         *
         * @octdoc  m:context/addContext
         * @return  \asciidia\context                     New graphic context.
         */
        public function addContext()
        /**/
        {
            $this->children[] = array(
                'tx'        => $this->tx,
                'ty'        => $this->ty,
                'context'   => ($context = new static($this->svg))
            );
        
            $context->__set('xs', $this->xs);
            $context->__set('ys', $this->ys);

            $debug = ((int)$this->debug == 2);
            $context->enableDebug($debug, $debug);
        
            return $context;
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
            trigger_error("'addCommand' is not available for 'svg' backend\n");
        }

        /**
         * Clear MVG command stack.
         *
         * @octdoc  m:context/clearCommands
         */
        public function clearCommands()
        /**/
        {
            trigger_error("'clearCommand' is not available for 'svg' backend\n");
        }

        /*
         * misc commands
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
            parent::translate($tx, $ty);

            if ($tx != 0 || $ty != 0) {
                $g = $this->svg->appendChild($this->doc->createElement('g'));
                $g->setAttribute('transform', sprintf(
                    'translate(%d %d)', 
                    $tx * $this->xs, 
                    $ty * $this->ys
                ));
            
                $this->svg = $g;
            }
        }

        /**
         * Change font settings.
         *
         * @octdoc  m:context/setFont
         * @param   array       $settings           Font settings.
         */
        public function setFont(array $settings)
        /**/
        {
            foreach ($settings as $name => $value) {
                switch ($name) {
                    case 'font':
                        $this->font['family'] = $value;
                        break;
                    case 'size':
                        $this->font['size'] = $value;
                        break;
                }
            }
        }

        /**
         * Change fill settings.
         *
         * @octdoc  m:context/setFill
         * @param   array       $settings           Fill settings.
         */
        public function setFill(array $settings)
        /**/
        {
            foreach ($settings as $name => $value) {
                switch ($name) {
                    case 'color':
                        if (is_string($value)) {
                            $this->fill['color'] = $value;
                        } elseif (is_array($value) && count($value) == 3) {
                            $this->fill['color'] = vsprintf('rgb(%d,%d,%d)', $value);
                        }
                        break;
                    case 'opacity':
                        $this->fill['opacity'] = $value;
                        break;
                }
            }
        }

        /**
         * Change stroke settings.
         *
         * @octdoc  m:context/setStroke
         * @param   array       $settings           Stroke settings.
         */
        public function setStroke(array $settings)
        /**/
        {
            foreach ($settings as $name => $value) {
                switch ($name) {
                    case 'width':
                        $this->stroke['width'] = $value;
                        break;
                    case 'color':
                        if (is_string($value)) {
                            $this->stroke['color'] = $value;
                        } elseif (is_array($value) && count($value) == 3) {
                            $this->stroke['color'] = vsprintf('rgb(%d,%d,%d)', $value);
                        }
                        break;
                    case 'opacity':
                        $this->stroke['opacity'] = $value;
                        break;
                }
            }
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
     
            $rect = $this->doc->createElement('rect');
            $rect->setAttribute('x', min($x1, $x2) * $this->xs + $this->xf);
            $rect->setAttribute('y', min($y1, $y2) * $this->ys + $this->yf);
            $rect->setAttribute('width',  abs($x2 - $x1) * $this->xs);
            $rect->setAttribute('height', abs($y2 - $y1) * $this->ys);
            
            $this->setStyles($rect);
        
            if ($round) {
                $rect->setAttribute('rx', $this->xf);
                $rect->setAttribute('ry', $this->yf);
            }
        
            $this->svg->appendChild($rect);
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

            $tmp_x = array();
            $tmp_y = array();
            foreach ($points as $point) {
                $tmp_x[] = $point[0]; $tmp_y[] = $point[1];
            }
            $this->setSize(max($tmp_x), max($tmp_y));

            list($cp1, $cp2) = spline::getControlPoints($points);

            for ($i = 0, $cnt = $cnt - 1; $i < $cnt; ++$i) {
                list($x1, $y1) = $points[$i];
                list($x2, $y2) = $points[$i + 1];

                list($cx1, $cy1) = $cp1[$i];
                list($cx2, $cy2) = $cp2[$i];

                $path = $this->doc->createElement('path');
                $path->setAttribute('d', sprintf(
                    "M %f,%f C %f,%f %f,%f %f,%f",
                    $x1 * $this->xs + $this->xf,  $y1 * $this->ys + $this->yf,
                    $cx1 * $this->xs + $this->xf, $cy1 * $this->ys + $this->yf,
                    $cx2 * $this->xs + $this->xf, $cy2 * $this->ys + $this->yf,
                    $x2 * $this->xs + $this->xf,  $y2 * $this->ys + $this->yf
                ));
                
                $this->setStyles($path);
        
                $this->svg->appendChild($path);
            }
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

            $line = $this->doc->createElement('line');

            $line->setAttribute('x1', $x1 * $this->xs + $this->xf);
            $line->setAttribute('y1', $y1 * $this->ys + $this->yf);
            $line->setAttribute('x2', $x2 * $this->xs + $this->xf);
            $line->setAttribute('y2', $y2 * $this->ys + $this->yf);
        
            $this->setStyles($line);
        
            $this->svg->appendChild($line);
        
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

            $line = $this->doc->createElement('line');

            $line->setAttribute('x1', $x1 * $this->xs);
            $line->setAttribute('y1', $y * $this->ys + $this->yf);
            $line->setAttribute('x2', $x2 * $this->xs + $this->xs);
            $line->setAttribute('y2', $y * $this->ys + $this->yf);
        
            $this->setStyles($line);
        
            $this->svg->appendChild($line);
        
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

            $line = $this->doc->createElement('line');

            $line->setAttribute('x1', $x * $this->xs + $this->xf);
            $line->setAttribute('y1', $y1 * $this->ys);
            $line->setAttribute('x2', $x * $this->xs + $this->xf);
            $line->setAttribute('y2', $y2 * $this->ys + $this->ys);
        
            $this->setStyles($line);
        
            $this->svg->appendChild($line);
        
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
            $line = $this->doc->createElement('line');

            $line->setAttribute('x1', $x * $this->xs);
            $line->setAttribute('y1', $y * $this->ys + $this->yf);
            $line->setAttribute('x2', $x * $this->xs + $this->xf + $this->xf);
            $line->setAttribute('y2', $y * $this->ys + $this->yf);
        
            $this->setStyles($line);
        
            $this->svg->appendChild($line);

            // draw crossing
            trigger_error("'drawLineCrossing' not yet fully implemented for 'svg' backend\n");
        
            // $this->mvg[] = sprintf(
            //     'fill transparent ellipse %f,%f %f,%f %f,%f',
            //     $x * $this->xs + $this->xf, 
            //     $y * $this->ys + $this->yf,
            //     $this->xf,
            //     $this->yf,
            //     -90,
            //     -270
            // );
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
            $line = $this->doc->createElement('line');

            $line->setAttribute('x1', $x * $this->xs + ((1 - (int)$cl) * $this->xf));
            $line->setAttribute('y1', $y * $this->ys + $this->yf);
            $line->setAttribute('x2', $x * $this->xs + $this->xf + ((int)$cr * $this->xf));
            $line->setAttribute('y2', $y * $this->ys + $this->yf);
        
            $this->setStyles($line);
        
            $this->svg->appendChild($line);

            $line = $this->doc->createElement('line');

            $line->setAttribute('x1', $x * $this->xs + $this->xf);
            $line->setAttribute('y1', $y * $this->ys + ((1 - (int)$ca) * $this->yf));
            $line->setAttribute('x2', $x * $this->xs + $this->xf);
            $line->setAttribute('y2', $y * $this->ys + $this->yf + ((int)$cb * $this->yf));
        
            $this->setStyles($line);
        
            $this->svg->appendChild($line);
        
            // draw marker
            $hxf = $this->xf / 2;
            $hyf = $this->yf / 2;
        
            switch ($type) {
            case 'x':
                $line = $this->doc->createElement('line');

                $line->setAttribute('x1', $x * $this->xs + $hxf);
                $line->setAttribute('y1', $y * $this->ys + $hyf);
                $line->setAttribute('x2', $x * $this->xs + $this->xs - $hxf);
                $line->setAttribute('y2', $y * $this->ys + $this->ys - $hyf);
        
                $this->setStyles($line);
        
                $this->svg->appendChild($line);

                $line = $this->doc->createElement('line');

                $line->setAttribute('x1', $x * $this->xs + $this->xs - $hxf);
                $line->setAttribute('y1', $y * $this->ys + $hyf);
                $line->setAttribute('x2', $x * $this->xs + $hxf);
                $line->setAttribute('y2', $y * $this->ys + $this->ys - $hyf);
        
                $this->setStyles($line);
        
                $this->svg->appendChild($line);
                break;
            case 'o':
                $x = $x * $this->xs + $this->xf;
                $y = $y * $this->ys + $this->yf;

                $ellipse = $this->doc->createElement('ellipse');
            
                $ellipse->setAttribute('cx', $x);
                $ellipse->setAttribute('cy', $y);
                $ellipse->setAttribute('rx', $hxf);
                $ellipse->setAttribute('ry', $hyf);
            
                $this->setStyles($ellipse, array('fill' => $this->bg));
        
                $this->svg->appendChild($ellipse);
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
     
            $txt = $this->doc->createElement('text');
        
            $txt->setAttribute('x', $x * $this->xs);
            $txt->setAttribute('y', ($y + 1) * $this->ys - ($this->yf / 2));
            $txt->setAttribute('font-size', (is_null($this->font['size']) ? $this->ys : $this->font['size']));
            $txt->setAttribute('font-family', $this->font['family']);
            $txt->appendChild($this->doc->createTextNode($text));
        
            $this->svg->appendChild($txt);
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
         * misc helper functions
         */
     
        /**
         * Set styles.
         *
         * @octdoc  m:context/setStyles
         * @param   \DOMNode    $node           Node to set styles for.
         * @param   array       $values         Optional values to overwrite defaults with.
         */
        public function setStyles(\DOMNode $node, array $values = array())
        /**/
        {
            $values = array_merge(array(
                'fill'           => $this->fill['color'],
                'fill-opacity'   => $this->fill['opacity'],
                'stroke'         => $this->stroke['color'],
                'stroke-opacity' => $this->stroke['opacity'],
                'stroke-width'   => $this->stroke['width']
            ), $values);
            
            $node->setAttribute('style', sprintf(
                'fill: %s; fill-opacity: %f; ' .
                'stroke: %s; stroke-opacity: %f; stroke-width: %d;',
                $values['fill'],
                $values['fill-opacity'],
                $values['stroke'],
                $values['stroke-opacity'],
                $values['stroke-width']
            ));
        }
     
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

            $group = $this->doc->createElement('g');
            $this->svg->appendChild($group);

            $path = $this->doc->createElement('path');
            $path->setAttribute('d', sprintf(
                "M %s L %s %s Z",
                $get_xy(-$this->xf, 0),
                $get_xy(0, -$this->yf),
                $get_xy($this->xf, 0)
            ));
        
            $this->setStyles($path, array('fill' => $this->stroke['color']));
        
            $group->appendChild($path);
        }
    }
}
