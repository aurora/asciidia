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
 * Plugin base class. Implements drawing primitives and provides plugins with 
 * all other needed methods.
 *
 * @octdoc      c:libs/plugin
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
abstract class plugin
/**/
{
    /**
     * Abstract method(s) to be implemented by plugins.
     *
     * @octdoc  m:plugin/parse
     */
    abstract public function parse($content);
    /**/
    
    /**
     * Constructor.
     *
     * @octdoc  m:plugin/__construct
     */
    public function __construct()
    /**/
    {
    }

    /**
     * Return size of canvas.
     *
     * @octdoc  m:plugin/getSize
     * @param   array                       w,h size of canvas.
     */
    public function getSize()
    /**/
    {
        return array(
            ($this->w + 1) * $this->xs - 1, 
            ($this->h + 1) * $this->ys - 1
        );
    }

    /**
     * Add MVG command to command-list.
     *
     * @octdoc  m:plugin/addCommand
     * @param   string      $command        Command to at.
     */
    public function addCommand($command)
    /**/
    {
        $this->mvg[] = $command;
    }

    /**
     * Return all commands needed for drawing diagram.
     *
     * @octdoc  m:plugin/getCommands
     * @return  array                       Imagemagick MVG commands.
     */
    public function getCommands()
    /**/
    {
        $debug = array();
        
        if ($this->grid) {
            // draw debugging grid
            for ($x = 0; $x <= $this->w; ++$x) {
                $debug[] = sprintf(
                    'line %d,0 %d,%d',
                    $x * $this->xs,
                    $x * $this->xs,
                    $this->h * $this->ys + $this->ys
                );
            }
            for ($y = 0; $y <= $this->h; ++$y) {
                $debug[] = sprintf(
                    'line 0,%d %d,%d',
                    $y * $this->ys,
                    $this->w * $this->xs + $this->xs,
                    $y * $this->ys
                );
            }
        }
        
        return array_merge(
            array(
                'push graphic-context',
                'font Courier',
                sprintf('font-size %f', $this->ys)
            ), 
            $this->mvg,
            $debug,
            array(
                'pop graphic-context'
            )
        );
    }

    /**
     * Clear MVG command stack.
     *
     * @octdoc  m:plugin/clearCommands
     */
    public function clearCommands()
    /**/
    {
        $this->mvg = array();
    }

    /**
     * Enable a grid overlay useful for debugging asciidia.
     *
     * @octdoc  m:plugin/enableGrid
     * @param   bool        $enable             Whether to enable / disable grid.
     */
    public function enableGrid($enable)
    /**/
    {
        $this->grid = $enable;
    }

    /**
     * Execute plugin.
     *
     * @octdoc  m:plugin/run
     * @param   string      $inp                Name of input.
     * @param   string      $out                Name of output.
     * @param   string      $fmt                Output format.
     * @return  array                           Status information.
     */
    public function run($inp, $out, $fmt = 'png')
    /**/
    {
        list($status, $msg, $content) = $this->loadFile($inp);
        
        if ($status) {
            list($status, $msg) = $this->testFile($out);
            
            if ($status) {
                $mvg = $this->parse($content);
                
                $this->saveFile($out, $fmt, $mvg);
            }
        }

        return array($status, $msg);
    }

    /**
     * Check command-line arguments.
     *
     * @octdoc  m:plugin/checkArgs
     * @return  array                           Status information.
     */
    public function checkArgs()
    /**/
    {
        return array(true, '');
    }

    /**
     * Load a file.
     *
     * @octdoc  m:plugin/loadFile
     * @param   string      $name               Name of file to load.
     * @return  array                           Status information.
     */
    public function loadFile($name)
    /**/
    {
        $return = array(true, '', '');
        
        if ($name != '-' && !is_readable($name)) {
            $return = array(false, 'input is not readable', '');
        } else {
            $return[2] = file_get_contents(
                ($name == '-' ? 'php://stdin' : $name)
            );
        }
        
        return $return;
    }

    /**
     * Test if a file is valid for writing.
     *
     * @octdoc  m:plugin/testFile
     * @param   string      $name               Name of file to save.
     * @return  array                           Status information.
     */
    public function testFile($name)
    /**/
    {
        $return = array(true, '');
        
        if (is_dir($name)) {
            $return = array(false, 'only a filename is allowed as output');
        } elseif ($name != '-') {
            if (file_exists($name)) {
                $return = array(false, 'output already exists');
            } elseif (!touch($name) || !is_writable($name)) {
                $return = array(false, 'output is not writable');
            }
        }
        
        return $return;
    }

    /**
     * Save a file or test if file can be saved to.
     *
     * @octdoc  m:plugin/saveFile
     * @param   string      $name               Name of file to save.
     * @param   string      $fmt                Fileformat to save file as.
     * @param   array       $commands           Imagemagick commands to save.
     */
    public function saveFile($name, $fmt, array $commands)
    /**/
    {
        if ($fmt == 'mvg') {
            // imagemagick mvg commands
            file_put_contents(
                ($name == '-' ? 'php://stdout' : $name),
                implode("\n", $commands)
            );
        } else {
            list($w, $h) = $this->getSize();

            $cmd = sprintf(
                'convert -size %dx%d xc:white -stroke black -fill none -draw %s %s png:%s',
                $w, $h,
                escapeshellarg(implode(' ', $commands)),
                '', // TODO: ($scale ? '-scale ' . $scale : ''),
                $name
            );

            passthru($cmd);
        }
    }
}
