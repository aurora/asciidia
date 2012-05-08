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

namespace chart\graph {
    /**
     * Spline graph.
     *
     * @octdoc      c:graph/spline
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class spline extends \chart\graph
    /**/
    {
        /**
         * Options.
         *
         * @octdoc  p:graph/$options
         * @var     array
         */
        protected $options = array(
            'stroke_width'  => 2,
            'color'         => array(  0,   0,   0),
        );
        /**/

        /**
         * Create bar graph.
         *
         * @octdoc  m:spline/create
         * @param   context             $context                Drawing context.
         * @param   int                 $width                  Width of graph.
         * @param   int                 $height                 Height of graph.
         * @param   float               $zero                   Zero line (X-Axis).
         * @param   float               $x_mul                  Point multiplicator for X-Axis.
         * @param   float               $y_mul                  Point multiplicator for Y-Axis.
         */
        public function create(\context $context, $width, $height, $zero, $x_mul, $y_mul)
        /**/
        {
            $points = array();
            $values = $this->dataset->getValues();

            $xoffs  = $x_mul / 2;

            for ($i = 0, $cnt = count($values); $i < $cnt; ++$i) {
                $points[] = array($i * $x_mul + $xoffs, $zero - $values[$i] * $y_mul);
            }
            
            $ctx = $context->addContext();
            $ctx->setStroke(array('color' => $this->options['color'], 'width' => $this->options['stroke_width']));
            $ctx->drawSpline($points);
        }
    }
}
