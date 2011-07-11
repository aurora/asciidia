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

# syntax (not all implemented, yet)
#
# +   - punkt / kreuzung / ecke
# |   - vertikale linie
# -   - horizontale linie
# x   - kreuz-markierung
# o   - kreis-markierung
# <   - pfeil links
# >   - pfeil rechts
# ^   - pfeil oben
# V   - pfeil unten
# /   - round top left corner / round bottom right corner
# \   - round top right / round bottom left corner

/**
 * Class for converting simple ASCII diagrams.
 *
 * @octdoc      c:libs/diagram
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class diagram extends asciidia
/**/
{
    /**
     * Draw a diagram.
     *
     * @octdoc  m:diagram/draw
     * @param   int         $x                  X-position of cell to draw.
     * @param   int         $y                  Y-position of cell to draw.
     * @param   string      $ch                 Character to draw.
     * @param   callback    $get_surrounding    Callback to determine surrounding characters.
     * @return  string                          Imagemagick draw commands.
     */
    public function draw($x, $y, $ch, $get_surrounding)
    /**/
    {
        list($a, $r, $b, $l) = $get_surrounding();
        
        $output = array();
        
        if ($ch == '+' || (($ch == 'x' || $ch == 'o') && ($a == '|' || $r == '-' || $b == '|' || $l == '-'))) {
            if ($l == '-' || $r == '-') {
                $output[] = sprintf(
                    "line   %d,%d %d,%d",
                    ($l == '-' ? $x * $this->xs : $x * $this->xs + $this->xf),
                    $y * $this->ys + $this->yf,
                    ($r == '-' ? ($x + 1) * $this->xs - 1 : $x * $this->xs + $this->xf), 
                    $y * $this->ys + $this->yf
                );
            }
            if ($a == '|' || $b == '|') {
                $output[] = sprintf(
                    "line   %d,%d %d,%d",
                    $x * $this->xs + $this->xf,
                    ($a == '|' ? $y * $this->ys : $y * $this->ys + $this->yf),
                    $x * $this->xs + $this->xf,
                    ($b == '|' ? ($y + 1) * $this->ys - 1 : $y * $this->ys + $this->yf)
                );
            }
            
            if ($ch == 'x') {
                $output[] = sprintf(
                    "line   %d,%d %d,%d",
                    $x * $this->xs,
                    $y * $this->ys,
                    ($x + 1) * $this->xs - 1,
                    ($y + 1) * $this->ys - 1
                );
                $output[] = sprintf(
                    "line   %d,%d %d,%d",
                    ($x + 1) * $this->xs - 1,
                    $y * $this->ys,
                    $x * $this->xs,
                    ($y + 1) * $this->ys - 1
                );
            } elseif ($ch == 'o') {
                
            }
        } elseif ($ch == '-') {
            $output[] = sprintf(
                "line   %d,%d %d,%d",
                $x * $this->xs, $y * $this->ys + $this->yf,
                ($x + 1) * $this->xs - 1, $y * $this->ys + $this->yf
            );
        } elseif ($ch == '|') {
            $output[] = sprintf(
                "line   %d,%d %d,%d",
                $x * $this->xs + $this->xf, $y * $this->ys,
                $x * $this->xs + $this->xf, ($y + 1) * $this->ys - 1
            );
        } elseif ($ch == '>' && $l == '-') {
            $output[] = sprintf(
                "fill black path 'M %d,%d %d,%d %d,%d Z'",
                $x * $this->xs + $this->xf, $y * $this->ys,
                ($x + 1) * $this->xs - 1, $y * $this->ys + $this->yf,
                $x * $this->xs + $this->xf, ($y + 1) * $this->ys - 1
            );
            $output[] = sprintf(
                "line   %d,%d %d,%d",
                $x * $this->xs, $y * $this->ys + $this->yf,
                ($x + 1) * $this->xs - 1, $y * $this->ys + $this->yf
            );
        } elseif ($ch == '<' && $r == '-') {
            $output[] = sprintf(
                "fill black path 'M %d,%d %d,%d %d,%d Z'",
                $x * $this->xs + $this->xf, $y * $this->ys,
                $x * $this->xs, $y * $this->ys + $this->yf,
                $x * $this->xs + $this->xf, ($y + 1) * $this->ys - 1
            );
            $output[] = sprintf(
                "line   %d,%d %d,%d",
                $x * $this->xs, $y * $this->ys + $this->yf,
                ($x + 1) * $this->xs - 1, $y * $this->ys + $this->yf
            );
        } elseif ($ch == 'V' && $a == '|') {
            $output[] = sprintf(
                "fill black path 'M %d,%d %d,%d %d,%d Z'",
                $x * $this->xs, $y * $this->ys + $this->yf,
                $x * $this->xs + $this->xf, ($y + 1) * $this->ys - 1,
                ($x + 1) * $this->xs - 1, $y * $this->ys + $this->yf 
            );
            $output[] = sprintf(
                "line   %d,%d %d,%d",
                $x * $this->xs + $this->xf, $y * $this->ys,
                $x * $this->xs + $this->xf, ($y + 1) * $this->ys - 1
            );
        } elseif ($ch == '^' && $b == '|') {
            $output[] = sprintf(
                "fill black path 'M %d,%d %d,%d %d,%d Z'",
                $x * $this->xs, $y * $this->ys + $this->yf,
                $x * $this->xs + $this->xf, $y * $this->ys,
                ($x + 1) * $this->xs - 1, $y * $this->ys + $this->yf 
            );
            $output[] = sprintf(
                "line   %d,%d %d,%d",
                $x * $this->xs + $this->xf, $y * $this->ys,
                $x * $this->xs + $this->xf, ($y + 1) * $this->ys - 1
            );
        }
        
        return $output;
    }
}
