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

namespace asciidia\backend\_default {
    /**
     * Provides graphics context and drawing primitives.
     *
     * @octdoc      c:_default/context
     * @copyright   copyright (c) 2011-2014 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class context extends \asciidia\context
    /**/
    {
        /**
         * Imagemagick MVG commands.
         *
         * @octdoc  p:context/$mvg
         * @type    array
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
            parent::__construct();
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
         * Add child context to current one. The new context will inherit the
         * cell scaling setting of current context.
         *
         * @octdoc  m:context/addContext
         * @return  \asciidia\context                     New graphic context.
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
            parent::translate($tx, $ty);
        
            $this->mvg[] = sprintf(
                'translate %f,%f', 
                $tx * $this->xs,
                $ty * $this->ys
            );
        }

        /**
         * Rotate cursor.
         *
         * @octdoc  m:context/rotate
         * @param   float       $angle          Angle to rotate by.
         */
        public function rotate($angle)
        /**/
        {
            $this->mvg[] = sprintf('rotate %f', $angle);
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
            $set = array();

            foreach ($settings as $name => $value) {
                switch ($name) {
                case 'font':
                    $set[] = 'font ' . $value;
                    break;
                case 'size':
                    $set[] = sprintf('font-size %f', $value);
                    break;
                }
            }

            if (count($set) > 0) {
                $this->mvg[] = implode(' ', $set);
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
            $set = array();

            foreach ($settings as $name => $value) {
                switch ($name) {
                case 'color':
                    if (is_string($value)) {
                        $set[] = 'fill ' . $value;
                    } elseif (is_array($value) && count($value) == 3) {
                        $set[] = vsprintf('fill rgb(%d,%d,%d)', $value);
                    }
                    break;
                case 'opacity':
                    $set[] = sprintf('fill-opacity %f', $value);
                    break;
                }
            }

            if (count($set) > 0) {
                $this->mvg[] = implode(' ', $set);
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
            $set = array();

            foreach ($settings as $name => $value) {
                switch ($name) {
                case 'antialias':
                    $set[] = sprintf('stroke-antialias %d', (int)$value);
                    break;
                case 'width':
                    $set[] = sprintf('stroke-width %f', $value);
                    break;
                case 'color':
                    if (is_string($value)) {
                        $set[] = 'stroke ' . $value;
                    } elseif (is_array($value) && count($value) == 3) {
                        $set[] = vsprintf('stroke rgb(%d,%d,%d)', $value);
                    }
                    break;
                case 'opacity':
                    $set[] = sprintf('stroke-opacity %f', $value);
                    break;
                }
            }

            if (count($set) > 0) {
                $this->mvg[] = implode(' ', $set);
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
        
            if (!$round) {
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
         * Draw a path.
         *
         * @octdoc  m:context/drawPath
         * @param   string      $d                  Path definition.
         */
        public function drawPath($d)
        /**/
        {
            $this->mvg[] = "path '" . $d . "'";
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
}
