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

require_once(__DIR__ . '/../context.class.php');
require_once(__DIR__ . '/../util/spline.class.php');

/**
 * Provides graphics context and drawing primitives.
 *
 * @octdoc      c:backend/svg
 * @copyright   copyright (c) 2014 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class svg extends context
/**/
{
    /**
     * SVG Document.
     *
     * @octdoc  p:svg/$doc
     * @type    \DOMDocument
     */
    protected $doc;
    /**/
    
    
    /**
     * SVG root node.
     *
     * @octdoc  p:svg/$svg
     * @type    \DOMNode
     */
    protected $svg;
    /**/
    
    /**
     * Constructor.
     *
     * @octdoc  m:svg/__construct
     */
    public function __construct()
    /**/
    {
        parent::__construct();
        
        $this->doc = new DOMDocument();
        $this->svg = $this->doc->appendChild($this->doc->createElement('svg'));
        $this->svg->setAttibute('xmlns', 'http://www.w3.org/2000/svg');
        $this->svg->setAttribute('version', '1.1');
    }

    /**
     * Add MVG command to command-list.
     *
     * @octdoc  m:svg/addCommand
     * @param   string      $command        Command to at.
     */
    public function addCommand($command)
    /**/
    {
        trigger_error("'addCommand' is not available for 'svg' backend\n");
    }

    /**
     * Return MVG commands.
     *
     * @octdoc  m:svg/getCommands
     * @return  array                           Imagemagick MVG commands.
     */
    public function getCommands()
    /**/
    {
        // 
        // // resolve child contexts
        // foreach ($this->mvg as $cmd) {
        //     if (is_array($cmd) && isset($cmd['context'])) {
        //         $mvg = array_merge($mvg, $cmd['context']->getCommands());
        //     } else {
        //         $mvg[] = $cmd;
        //     }
        // }
        
        // draw debugging grid and context boundaries
        // if ($this->debug) {
        //     $mvg[] = sprintf('translate %f,%f', -$this->tx * $this->xs, -$this->ty * $this->ys);
        //     $mvg[] = 'push graphic-context';
        //     $mvg[] = 'fill transparent';
        //     
        //     list($cw, $ch) = $this->getSize(true);
        //     
        //     for ($x = 0; $x < $cw; ++$x) {
        //         $mvg[] = sprintf(
        //             'line %f,0 %f,%f',
        //             $x * $this->xs,
        //             $x * $this->xs,
        //             $ch * $this->ys
        //         );
        //     }
        //     for ($y = 0; $y < $ch; ++$y) {
        //         $mvg[] = sprintf(
        //             'line 0,%f %f,%f',
        //             $y * $this->ys,
        //             $cw * $this->xs,
        //             $y * $this->ys
        //         );
        //     }
        //     $mvg[] = sprintf(
        //         'rectangle 0,0, %f,%f', 
        //         $cw * $this->xs, 
        //         $ch * $this->ys
        //     );
        //     
        //     $this->mvg[] = sprintf(
        //         "text 0,%f '%d,%d'", $this->ys - ($this->yf / 2), $cw, $ch
        //     );
        //     
        //     $mvg[] = 'pop graphic-context';
        // }

        return $this->doc->saveXML();
    }

    /**
     * Clear MVG command stack.
     *
     * @octdoc  m:svg/clearCommands
     */
    public function clearCommands()
    /**/
    {
        trigger_error("'clearCommand' is not available for 'svg' backend\n");
    }

    /*
     * misc MVG commands
     */
    
    /**
     * Translate origin. Note that translation is always relative to the 
     * current position of origin. While negative translation is allowed,
     * it's not allowed to move the origin to a negative position.
     *
     * @octdoc  m:svg/translate
     * @param   int         $tx             x-value for translating origin.
     * @param   int         $ty             y-value for translating origin.
     */
    public function translate($tx, $ty)
    /**/
    {
        parent::translate($tx, $ty);

        trigger_error("'translate' is not yet implemented for 'svg' backend\n");
    }

    /**
     * Change font settings.
     *
     * @octdoc  m:svg/setFont
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
     * @octdoc  m:svg/setFill
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
     * @octdoc  m:svg/setStroke
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
     * @octdoc  m:svg/drawRectangle
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
        $rect->setAttribute('x', min($x1, $x2) + $this->xf);
        $rect->setAttribute('y', min($y1, $y2) + $this->yf);
        $rect->setAttribute('width',  abs($x2 - $x1) + (2 * $this->xf));
        $rect->setAttribute('height', abs($y2 - $y1) + (2 * $this->yf));
        
        if ($round) {
            $rect->setAttribute('rx', $this->xf);
            $rect->setAttribute('ry', $this->yf);
        }
        
        $this->svg->appendChild($rect);
    }

    /**
     * Draw a spline using cubic bezier primitives.
     *
     * @octdoc  m:svg/drawSpline
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
                "path 'M %f,%f C %f,%f %f,%f %f,%f'",
                $x1 * $this->xs + $this->xf,  $y1 * $this->ys + $this->yf,
                $cx1 * $this->xs + $this->xf, $cy1 * $this->ys + $this->yf,
                $cx2 * $this->xs + $this->xf, $cy2 * $this->ys + $this->yf,
                $x2 * $this->xs + $this->xf,  $y2 * $this->ys + $this->yf
            ));
            
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
     * @octdoc  m:svg/drawLine
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
     * @octdoc  m:svg/drawLine
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
     * @octdoc  m:svg/drawLine
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
     * @octdoc  m:svg/drawLineCrossing
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
     * @octdoc  m:svg/drawMarker
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
        
        $this->svg->appendChild($line);

        $line = $this->doc->createElement('line');

        $line->setAttribute('x1', $x * $this->xs + $this->xf);
        $line->setAttribute('y1', $y * $this->ys + ((1 - (int)$ca) * $this->yf));
        $line->setAttribute('x2', $x * $this->xs + $this->xf);
        $line->setAttribute('y2', $y * $this->ys + $this->yf + ((int)$cb * $this->yf));
        
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
        
            $this->svg->appendChild($line);

            $line = $this->doc->createElement('line');

            $line->setAttribute('x1', $x * $this->xs + $this->xs - $hxf);
            $line->setAttribute('y1', $y * $this->ys + $hyf);
            $line->setAttribute('x2', $x * $this->xs + $hxf);
            $line->setAttribute('y2', $y * $this->ys + $this->ys - $hyf);
        
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
            $ellipse->setAttribute('style', 'fill:' . $this->bg);
            
            $this->svg->appendChild($ellipse);
            break;
        }
    }

    /**
     * Draw a text string.
     *
     * @octdoc  m:svg/drawText
     * @param   int         $x                  x point.
     * @param   int         $y                  y point.
     * @param   string      $text               Text to draw.
     */
    public function drawText($x, $y, $text)
    /**/
    {
        $this->setSize($x + strlen($text), $y);
     
        $text = $this->doc->createElement('text');
        
        $text->setAttribute('x', $x * $this->xs);
        $text->setAttribute('y', ($y + 1) * $this->ys - ($this->yf / 2));
        $text->appendChild($this->doc->createTextNode($text));
        
        $this->svg->appendChild($text);
    }

    /**
     * Draw a text-label. A text-label is a string centered inside a rectangle
     * with optional round corners.
     *
     * @todo    how do we get this centered, if font-metrics do not fit grid-
     *          metrics?
     *
     * @octdoc  m:svg/drawLabel
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
     * @octdoc  m:svg/drawCorner
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
     * @octdoc  m:svg/drawArrowHead
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
     * This is a helper function to draw an arrow head for example for a line
     * or a path. This method is protected to prevent a direct call. Use one of
     * the line- or path-drawing functions to draw an arrow.
     *
     * @octdoc  m:svg/_drawArrowHead
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

        trigger_error("'drawArrowHead' todo for 'svg' backend: 'fill'");

        $group = $this->doc->createElement('g');
        $this->svg->appendChild($group);

        $path = $this->doc->createElement('path');
        $path->setAttribute('d', sprintf(
            "M %s L %s %s Z",
            $this->stroke,
            $get_xy(-$this->xf, 0),
            $get_xy(0, -$this->yf),
            $get_xy($this->xf, 0)
        ));
        
        $group->appendChild($path);
    }
}
