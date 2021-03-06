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

# +   - line-crossing / line-corner
# x   - cross marker
# o   - circle marker
# |   - vertical line
# -   - horizontal line
# <   - left arrow
# >   - right arrow
# ^   - up arrow
# V   - down arrow
# /   - round top left corner / round bottom right corner
# \   - round top right / round bottom left corner

namespace asciidia\plugins {
    /**
     * Class for converting simple ASCII diagrams.
     *
     * @octdoc      c:plugin/diagram
     * @copyright   copyright (c) 2011-2014 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class diagram extends \asciidia\plugin
    /**/
    {
        /**
         * Determined text-strings of diagram.
         *
         * @octdoc  p:diagram/$strings
         * @type    array
         */
        protected $strings = array();
        /**/

        /**
         * Determined string positions and mapping to stored strings.
         *
         * @octdoc  p:diagram/$strings_pos
         * @type    array
         */
        protected $strings_pos = array();
        /**/
    
        /**
         * Determined lines in diagram.
         *
         * @octdoc  p:diagram/$lines
         * @type    array
         */
        protected $lines = array();
        /**/
    
        /**
         * Determined line positions and mapping to stored lines.
         *
         * @octdoc  p:diagram/$lines_pos
         * @type    array
         */
        protected $lines_pos = array(array('H' => array(), 'V' => array()));
        /**/
    
        /**
         * Add character to list of strings to determine text-strings to render.
         *
         * @octdoc  m:diagram/addChar
         * @param   int         $x              X-position of character.
         * @param   int         $y              Y-position of character.
         * @param   string      $ch             Character to add.
         */
        protected function addChar($x, $y, $ch)
        /**/
        {
            if (isset($this->strings_pos[$y]) && isset($this->strings_pos[$y][$x - 1])) {
                // string found, concatenate character
                $this->strings_pos[$y][$x] = $this->strings_pos[$y][$x - 1];
                $this->strings[$this->strings_pos[$y][$x]]['text'] .= $ch;
            } else {
                // add new string
                $this->strings[$idx = count($this->strings)] = array(
                    'x'    => $x,
                    'y'    => $y,
                    'text' => $ch
                );
            
                if (!isset($this->strings_pos[$y])) $this->strings_pos[$y] = array();
            
                $this->strings_pos[$y][$x] = $idx;
            }
        }
    
        /**
         * Add line to list of lines.
         *
         * @octdoc  m:diagram/addLine
         * @param   int         $x              X-position of character.
         * @param   int         $y              Y-position of character.
         * @param   array       $rows           Diagram to parse.
         */
        public function addLine($x, $y, &$rows)
        /**/
        {
            $ch = $rows[$y][$x];
            list($a, $r, $b, $l) = $this->getSurrounding($x, $y, $rows);

            if ($ch == '|' || $ch == '^' || $ch == 'V') {
                // vertical line
                if (!isset($this->lines_pos['V'][$y])) $this->lines_pos['V'][$y] = array();
            
                if ($ch != '^' && $a != 'V' && isset($this->lines_pos['V'][$y - 1]) && isset($this->lines_pos['V'][$y - 1][$x])) {
                    // line found
                    $idx = $this->lines_pos['V'][$y - 1][$x];
                
                    $this->lines_pos['V'][$y][$x] = $idx;
                    ++$this->lines[$idx]['y2'];
                
                    if ($ch == 'V') $this->lines[$idx]['arrow'] = (int)$this->lines[$idx]['arrow'] + 1;
                } else {
                    $this->lines[$idx = count($this->lines)] = array(
                        'type'  => 'V',
                        'x1'    => $x,
                        'y1'    => $y,
                        'y2'    => $y,
                        'arrow' => ($ch == '^' ? -1 : false)
                    );

                    $this->lines_pos['V'][$y][$x] = $idx;
                }
            } elseif ($ch == '-' || $ch == '<' || $ch == '>') {
                // horizontal line
                if (!isset($this->lines_pos['H'][$y])) $this->lines_pos['H'][$y] = array();
            
                if ($ch != '<' && $l != '>' && isset($this->lines_pos['H'][$y]) && isset($this->lines_pos['H'][$y][$x - 1])) {
                    // line found
                    $idx = $this->lines_pos['H'][$y][$x - 1];
                
                    $this->lines_pos['H'][$y][$x] = $idx;
                    ++$this->lines[$idx]['x2'];
                
                    if ($ch == '>') $this->lines[$idx]['arrow'] = (int)$this->lines[$idx]['arrow'] + 1;
                } else {
                    $this->lines[$idx = count($this->lines)] = array(
                        'type'  => 'H',
                        'x1'    => $x,
                        'x2'    => $x,
                        'y1'    => $y,
                        'arrow' => ($ch == '<' ? -1 : false)
                    );

                    $this->lines_pos['H'][$y][$x] = $idx;
                }
            }
        }
    
        /**
         * Get character surrounding current x/y position.
         *
         * @octdoc  m:diagram/getSurrounding
         * @param   int         $x              Current x-position.
         * @param   int         $y              Current y-position.
         * @param   array       $rows           Diagram to parse.
         * @return  array                       List of surrounding characters.
         */
        protected function getSurrounding($x, $y, array &$rows)
        /**/
        {
            return array(
                // above
                (isset($rows[$y - 1]) && isset($rows[$y - 1][$x]) 
                    ? $rows[$y - 1][$x]
                    : ''),
        
                // right
                (isset($rows[$y]) && isset($rows[$y][$x + 1]) 
                    ? $rows[$y][$x + 1]
                    : ''),
        
                // below
                (isset($rows[$y + 1]) && isset($rows[$y + 1][$x]) 
                    ? $rows[$y + 1][$x]
                    : ''),
            
                // left
                (isset($rows[$y]) && isset($rows[$y][$x - 1]) 
                    ? $rows[$y][$x - 1]
                    : '')
            );
        }
    
        /**
         * Parse an ASCII diagram an convert it to imagemagick commands.
         *
         * @octdoc  m:diagram/parse
         * @param   string      $diagram        ASCII Diagram to parse.
         * @return  string                      Imagemagick commands to draw diagram.
         */
        public function parse($diagram)
        /**/
        {
            $ctx  = $this->getContext();
            $rows = explode("\n", $diagram);
    
            for ($y = 0, $h = count($rows); $y < $h; ++$y) {
                for ($x = 0, $w = strlen($rows[$y]); $x < $w; ++$x) {
                    $c = $rows[$y][$x];
                    list($a, $r, $b, $l) = $this->getSurrounding($x, $y, $rows);

                    if ($c == '/' && (($a == '|' && $l == '-') || ($r == '-' && $b == '|'))) {
                        // round corner (top-left or bottom-right)
                        if ($a == '|' && $l == '-') $ctx->drawCorner($x, $y, 'br', true);
                        if ($r == '-' && $b == '|') $ctx->drawCorner($x, $y, 'tl', true);
                    } elseif ($c == '\\' && (($a == '|' && $r == '-') || ($b == '|' && $l == '-'))) {
                        // round corner (top-right or bottom-left)
                        if ($a == '|' && $r == '-') $ctx->drawCorner($x, $y, 'bl', true);
                        if ($b == '|' && $l == '-') $ctx->drawCorner($x, $y, 'tr', true);
                    } elseif (($c == 'x' || $c == 'o' || $c == '+') && ($a == '|' || $r == '-' || $b == '|' || $l == '-')) {
                        // marker
                        $ctx->drawMarker($x, $y, $c, ($a == '|'), ($r == '-'), ($b == '|'), ($l == '-'));
                    } elseif ($c == 'C' && ($a == '|' && $r == '-' && $b == '|' && $l == '-')) {
                        // line crossing
                        $ctx->drawLineCrossing($x, $y);
                    } elseif ($c == '-' || ($c == '<' && $r == '-') || ($c == '>' && $l == '-')) {
                        // horizontal line
                        $this->addLine($x, $y, $rows);
                    } elseif ($c == '|' || ($c == '^' && $b == '|') || ($c == 'V' && $a == '|')) {
                        // Vertical line
                        $this->addLine($x, $y, $rows);
                    } elseif ($c != ' ') {
                        // a text string
                        $this->addChar($x, $y, $c);
                    }
                }
            }
        
            foreach ($this->strings as $string) {
                $ctx->drawText($string['x'], $string['y'], $string['text']);
            }
        
            foreach ($this->lines as $line) {
                if ($line['type'] == 'H') {
                    $ctx->drawHLine($line['x1'], $line['y1'], $line['x2'], $line['arrow']);
                } elseif ($line['type'] == 'V') {
                    $ctx->drawVLine($line['x1'], $line['y1'], $line['y2'], $line['arrow']);
                }
            }
        
            return $this->getDocument();
        }
    }
}
