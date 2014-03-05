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
     * Bar graph.
     *
     * @octdoc      c:graph/bar
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class bar extends \chart\graph
    /**/
    {
        /**
         * Options.
         *
         * @octdoc  p:graph/$options
         * @type    array
         */
        protected $options = array(
            'border_color'      => array(128, 128, 128),
            'background_color'  => array(128, 128, 128)
        );
        /**/

        /**
         * Create bar graph.
         *
         * @octdoc  m:bar/create
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
            $values = $this->dataset->getValues();

            $ctx = $context->addContext();
            $ctx->setFill(array('color' => $this->options['background_color']));
            $ctx->setStroke(array('color' => $this->options['border_color']));

            $b_w = $x_mul * 0.6;
            $b_o = ($x_mul - $b_w) / 2;

            for ($i = 0, $cnt = count($values); $i < $cnt; ++$i) {
                if ($values[$i] > 0) {
                    $ctx->addCommand(sprintf(
                        'rectangle %f,%f %f,%f', 
                        $i * $x_mul + $b_o, $zero, $i * $x_mul + $b_o + $b_w, $zero - $values[$i] * $y_mul
                    ));
                }
            }
        }
    }
}
