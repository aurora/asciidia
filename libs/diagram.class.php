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
# x   - kreuz-markierung
# o   - kreis-markierung
# |   - vertical line
# -   - horizontal line
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
     * Determined text-strings of diagram.
     *
     * @octdoc  v:diagram/$strings
     * @var     array
     */
    protected $strings = array();
    /**/

    /**
     * Determined string positions and mapping to stored strings.
     *
     * @octdoc  v:diagram/$strings_pos
     * @var     array
     */
    protected $strings_pos = array();
    /**/
    
    /**
     * Horizontal line parser.
     *
     * @octdoc  m:diagram/parseHLine
     * @param   int         $x1             X-position the line starts at.
     * @param   int         $y              Y-position the line starts at.
     * @param   array       $rows           Diagram to parse.
     * @return  bool                        Returns always false to let main parser continue.
     */
    protected function parseHLine($x1, $y, array &$rows)
    /**/
    {
        list($a, $r, $b, $l) = $this->getSurrounding($x1, $y, $rows);

        $arrow = ($rows[$y][$x1] == '<' ? -1 : false);
        $x2    = $x1;

        if ($arrow !== false && $r != '-') return false;
        
        do {
            $rows[$y][$x2++] = '';
        } while (isset($rows[$y][$x2]) && $rows[$y][$x2] == '-');
        
        if ($rows[$y][$x2] == '>') {
            $arrow = (int)$arrow + 1;
            $rows[$y][$x2] = '';
        }
        
        $this->drawHLine($x1, $y, $x2, $arrow);

        return false;
    }
    
    /**
     * Vertical line parser.
     *
     * @octdoc  m:diagram/parseVLine
     * @param   int         $x              X-position the line starts at.
     * @param   int         $y1             Y-position the line starts at.
     * @param   array       $rows           Diagram to parse.
     * @return  bool                        Returns always false to let main parser continue.
     */
    protected function parseVLine($x, $y1, array &$rows)
    /**/
    {
        list($a, $r, $b, $l) = $this->getSurrounding($x, $y1, $rows);

        $arrow = ($rows[$y1][$x] == '^' ? -1 : false);
        $y2    = $y1;

        if ($arrow !== false && $b != '|') return false;
        
        do {
            $rows[$y2++][$x] = '';
        } while (isset($rows[$y2]) && isset($rows[$y2][$x]) && $rows[$y2][$x] == '|');
        
        if ($rows[$y2][$x] == 'V') {
            $arrow = (int)$arrow + 1;
            $rows[$y2][$x] = '';
        }
        
        $this->drawVLine($x, $y1, $y2, $arrow);

        return false;
    }

    /**
     * Add character to list of characters to determine text-strings to render.
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
            $this->strings_pos[$y][$x] = $idx;
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
     * @octdoc  m:asciidia/parse
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

                if ($c == '/' && (($a == '|' && $l == '-') || ($r == '-' && $b == '|'))) {
                    // round corner (top-left or bottom-right)
                    $this->drawCorner($x, $y, ($a == '|' && $l == '-' ? 'br' : 'tl'));
                } elseif ($c == '\\' && (($a == '|' && $r == '-') || ($b == '|' && $l == '-'))) {
                    // round corner (top-right or bottom-left)
                    $this->drawCorner($x, $y, ($a == '|' && $r == '-' ? 'bl' : 'tr'));
                } elseif (($c == 'x' || $c == 'o' || $c == '+') && ($a == '|' || $r == '-' || $b == '|' || $l == '-')) {
                    // marker
                    $this->drawMarker($x, $y, $c, ($a == '|'), ($r == '-'), ($b == '|'), ($l == '-'));
                } elseif (($c == '-' || $c == '<') && $this->parseHLine($x, $y, $rows)) {
                    // could be a horizontal line
                    /* implemented in parseLine */
                } elseif (($c == '|' || $c == '^') && $this->parseVLine($x, $y, $rows)) {
                    // could be a vertical line
                    /* implemented in parseLine */
                // } elseif ($c == '+') && $this->parseLine($x, $y, $rows)) {
                //     // could be a starting point of a line of unknown direction
                //     /* implemented in parseLine */
                } elseif ($c != ' ') {
                    // a text string
                    $this->addChar($x, $y, $c);
                }
            }
        }
        
        foreach ($this->strings as $string) {
            $this->drawText($string['x'], $string['y'], $string['text']);
        }
        
        return $this->getCommands();
    }
}
