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
 * ASCII Diagram core class. Implements text parser and core functionality of
 * text-diagram to imagemagick-draw converter.
 *
 * @octdoc      c:libs/asciidia
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
abstract class asciidia
/**/
{
    /**
     * X-multiplicator -- the width of a cell on the canvas.
     *
     * @octdoc  v:asciidia/$xs
     * @var     float
     */
    protected $xs = 10.0;
    /**/
    
    /**
     * Y-multiplicator -- the height of a cell on the canvas.
     *
     * @octdoc  v:asciidia/$ys
     * @var     float
     */
    protected $ys = 15.0;
    /**/
    
    /**
     * X-fract -- half of a cells width.
     *
     * @octdoc  v:asciidia/$xf
     * @var     float
     */
    protected $xf = 0;
    /**/
    
    /**
     * Y-fract -- half of a cells height.
     *
     * @octdoc  v:asciidia/yf
     * @var     float
     */
    protected $yf = 0;
    /**/
    
    /**
     * Width of diagram canvas.
     *
     * @octdoc  v:asciidia/$w
     * @var     int
     */
    protected $w = 0;
    /**/
    
    /**
     * Height of diagram canvas.
     *
     * @octdoc  v:asciidia/$h
     * @var     int
     */
    protected $h = 0;
    /**/
    
    /**
     * Constructor.
     *
     * @octdoc  m:asciidia/__construct
     */
    public function __construct()
    /**/
    {
        $this->xf = $this->xs / 2;
        $this->yf = $this->ys / 2;
    }
    
    /**
     * Magic setter.
     *
     * @octdoc  m:asciidia/__set
     * @param   string      $name               Name of property to set.
     * @param   mixed       $value              Value to set for property.
     */
    public function __set($name, $value)
    /**/
    {
        switch ($name) {
        case 'xs':
            $this->xs = $value;
            $this->xf = $value / 2;
            break;
        case 'ys':
            $this->ys = $value;
            $this->yf = $value / 2;
            break;
        }
    }
    
    /**
     * Abstract method that needs to be implemented in each subclass.
     *
     * @octdoc  m:asciidia/draw
     * @param   int         $x                  X-position of cell to draw.
     * @param   int         $y                  Y-position of cell to draw.
     * @param   string      $ch                 Character to draw.
     * @param   callback    $get_surrounding    Callback to determine surrounding characters.
     * @return  string                          Imagemagick draw commands.
     */
    abstract public function draw($x, $y, $ch, $get_surrounding);
    /**/
    
    /**
     * Parse an ASCII diagram an convert it to imagemagick commants.
     *
     * @octdoc  m:asciidia/parse
     * @param   string      $diagram        ASCII Diagram to parse.
     * @return  string                      Imagemagick commands to draw diagram.
     */
    public function parse($diagram)
    /**/
    {
        $rows = explode("\n", $diagram);
        $h    = count($rows);
        $w    = 0;
    
        $get_surrounding = function($x, $y) use ($rows) {
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
        };
    
        $output = array();
        $output[] = "push graphic-context";
        $output[] = "font Courier";
        $output[] = sprintf("font-size %f", $this->ys);
    
        for ($y = 0; $y < $h; ++$y) {
            for ($x = 0, $len = strlen($rows[$y]); $x < $len; ++$x) {
                $w = max($w, $len);
            
                $output = array_merge(
                    $output,
                    $this->draw($x, $y, $rows[$y][$x], function() use ($get_surrounding, $x, $y) {
                        return $get_surrounding($x, $y);
                    })
                );
            }
        }
        
        $output[] = "pop graphic-context";

        $this->w = $w; $this->h = $h;
        
        return $output;
    }
    
    /**
     * Return size of canvas.
     *
     * @octdoc  m:asciidia/getSize
     * @param   array                       w,h size of canvas.
     */
    public function getSize()
    /**/
    {
        return array($this->w * $this->xs, $this->h * $this->ys);
    }
}
