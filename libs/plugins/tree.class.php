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

require_once(__DIR__ . '/diagram.class.php');

/**
 * Class for creating "nice"-looking directory-trees from ASCII representation.
 *
 * @octdoc      c:plugin/tree
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class tree extends diagram
/**/
{
    /**
     * Overwrite loadFile of plugin class, because tree plugin may read
     * directories and use their content for rendering a diagram.
     *
     * @octdoc  m:tree/loadFile
     * @param   string      $name               Name of file to load.
     * @return  array                           Status information.
     */
    public function loadFile($name)
    /**/
    {
        $return = array(true, '', '');
        
        if (is_dir($name)) {
            $return[2] = $this->getTree($name);
        } else {
            $return = parent::loadFile($name);
        }
        
        return $return;
    }
    /**
     * Parse an ASCII tree diagram an convert it to imagemagick commands.
     *
     * @octdoc  m:tree/parse
     * @param   string      $diagram        ASCII Diagram to parse.
     * @return  string                      Imagemagick commands to draw diagram.
     */
    public function parse($diagram)
    /**/
    {
        $rows = explode("\n", $diagram);
    
        for ($y = 0, $h = count($rows); $y < $h; ++$y) {
            for ($x = 0, $w = strlen($rows[$y]); $x < $w; ++$x) {
                $c = $rows[$y][$x];
                list($a, $r, $b, $l) = $this->getSurrounding($x, $y, $rows);

                if ($c == '+' && $r == '-') {
                    $this->drawMarker($x, $y, $c, true, true, ($b == '+' || $b == '|'), false);
                } elseif ($c == '-' && ($l == '+' || $l == '-' || $r == '-' || $r == '>')) {
                    $this->addLine($x, $y, $rows);
                } elseif ($c == '>' && ($l == '-')) {
                    $this->addLine($x, $y, $rows);
                } elseif ($c == '|' && ($a == '+' || $a == '|' || $b == '+' || $b == '|')) {
                    $this->addLine($x, $y, $rows);
                } elseif ($c != ' ') {
                    $this->addChar($x, $y, $c);
                }
            }
        }
        
        foreach ($this->strings as $string) {
            $this->drawText($string['x'], $string['y'], $string['text']);
        }
        
        foreach ($this->lines as $line) {
            if ($line['type'] == 'H') {
                $this->drawHLine($line['x1'], $line['y1'], $line['x2'], $line['arrow']);
            } elseif ($line['type'] == 'V') {
                $this->drawVLine($line['x1'], $line['y1'], $line['y2'], $line['arrow']);
            }
        }
        
        return $this->getCommands();
    }

    /**
     * Return recursive directory ASCII tree for a specified path.
     *
     * @octdoc  m:tree/getTree
     * @return  string      $path           Path to return directory tree for.
     */
    protected function getTree($path)
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
