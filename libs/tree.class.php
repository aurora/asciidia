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
 * Class for creating "nice"-looking directory-trees from ASCII representation.
 *
 * @octdoc      c:libs/tree
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class tree extends asciidia
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
        
        if ($ch == '+' && ($a == '|' || $r == '-' || $b == '|' || $l == '-' || $b == '+' || $a == '+')) {
            if ($l == '-' || $r == '-') {
                $output[] = sprintf(
                    "line   %d,%d %d,%d",
                    ($l == '-' ? $x * $this->xs : $x * $this->xs + $this->xf),
                    $y * $this->ys + $this->yf,
                    ($r == '-' ? ($x + 1) * $this->xs - 1 : $x * $this->xs + $this->xf), 
                    $y * $this->ys + $this->yf
                );
            }
            
            $output[] = sprintf(
                "line   %d,%d %d,%d",
                $x * $this->xs + $this->xf,
                $y * $this->ys,
                $x * $this->xs + $this->xf,
                (($b == '|' || $b == '+') ? ($y + 1) * $this->ys - 1 : $y * $this->ys + $this->yf)
            );
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
        } elseif ($ch != ' ') {
            // draw text
            $output[] = sprintf(
                "text %d,%d '%s'",
                $x * $this->xs,
                ($y + 1) * $this->ys - ($this->yf / 2),
                addcslashes($ch, "'")
            );
        }
        
        return $output;
    }
    
    /**
     * Return recursive directory ASCII tree for a specified path.
     *
     * @octdoc  m:tree/getTree
     * @return  string      $path           Path to return directory tree for.
     */
    public function getTree($path)
    /**/
    {
        $path = rtrim($path, '/');
        $out  = array(basename($path));
        
        $rglob = function($path, $struct = '') use (&$rglob, &$out) {
            $dirs = glob($path . '/*', GLOB_ONLYDIR);
            
            for ($i = 0, $cnt = count($dirs); $i < $cnt; ++$i) {
                $out[] = $struct . '+-' . basename($dirs[$i]);
                
                $rglob($dirs[$i], $struct . ($i == $cnt - 1 ? '  ' : '| '));
            }
        };
        
        $rglob($path);

        return implode("\n", $out);
    }
}
