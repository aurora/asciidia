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
     * Instance of main graphic context.
     *
     * @octdoc  v:plugin/$context
     * @var     context
     */
    private $context = null;
    /**/
    
    /**
     * Size of the final bitmap.
     *
     * @octdoc  v:plugin/$scale_to
     * @var     string
     */
    private $scale_to = null;
    /**/
    
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
     * Return instance of main context.
     *
     * @octdoc  m:plugin/getContext
     * @return  context                     Instance of main context.
     */
    protected function getContext()
    /**/
    {
        if (is_null($this->context)) {
            require_once(__DIR__ . '/context.class.php');
            
            $this->context = new context();
        }
        
        return $this->context;
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
        return $this->getContext()->getCommands();
    }

    /**
     * Get size of bitmap to render.
     *
     * @octdoc  m:plugin/getSize
     * @return  array                       w, h of bitmap to be rendered.   
     */
    public function getSize()
    /**/
    {
        return $this->getContext()->getSize();
    }

    /**
     * Set the size of the cells.
     *
     * @octdoc  m:plugin/setCellSize
     * @param   float       $w                  Width of a cell.
     * @param   float       $h                  Height of a cell.
     */
    final public function setCellSize($w, $h)
    /**/
    {
        $context = $this->getContext();
        $context->xs = $w;
        $context->ys = $h;
    }

    /**
     * Set the size of the bitmap the result should be scaled to.
     *
     * @octdoc  m:plugin/setScaleTo
     * @param   float       $scale              Imagemagick scaling size.
     */
    final public function setScaleTo($scale)
    /**/
    {
        $this->scale_to = $scale;
    }

    /**
     * Enable a debugging mode for context.
     *
     * @octdoc  m:plugin/enableDebug
     * @param   bool        $enable             Whether to enable / disable debugging.
     */
    public function enableDebug($enable)
    /**/
    {
        $this->getContext()->enableDebug($enable);
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
     * Method to perform environment checks for example, if imagemagick 
     * is found through the path.
     *
     * @octdoc  m:plugin/testEnv
     * @return  array                           Status information.
     */
    public function testEnv()
    /**/
    {
        $status = true;
        $msg    = '';
        $out    = array();
        $err    = 0;
        
        // test if imagemagick is available
        exec('which convert', $out, $err);
        $mh = getenv('MAGICK_HOME');
        
        if ($err != 0) {
            $status = false;
            $msg    = 'imagemagick "convert" is not found in path';
        } elseif ($mh == '') {
            $status = false;
            $msg    = '"MAGICK_HOME" environment variable is not set';
        }
        
        return array($status, $msg);
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
                'convert -size %dx%d xc:white -stroke black -fill none -draw %s %s %s:%s',
                $w, $h,
                escapeshellarg(implode(' ', $commands)),
                ($this->scale_to ? '-scale ' . $this->scale_to : ''),
                $fmt,
                $name
            );

            passthru($cmd);
        }
    }
}
