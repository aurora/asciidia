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

namespace asciidia {
    /**
     * Plugin base class. Implements drawing primitives and provides plugins with 
     * all other needed methods.
     *
     * @octdoc      c:libs/plugin
     * @copyright   copyright (c) 2011-2014 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    abstract class plugin
    /**/
    {
        /**
         * Output backend.
         *
         * @octdoc  p:plugin/$backend
         * @type    \asciidia\backend|null
         */
        private $backend = null;
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
         * Abstract method(s) to be implemented by plugins.
         *
         * @octdoc  m:plugin/parse
         */
        abstract public function parse($content);
        /**/
    
        /**
         * Display usage information.
         *
         * @octdoc  m:diagram/usage
         * @param   string          $script     Contains name of the script.
         */
        public function usage($script)
        /**/
        {
            print "no additional help available for plugin\n";
        }
    
        /**
         * Set output backend.
         *
         * @octdoc  m:plugin/setBackend
         * @param   \asciidia\backend   $backend                Output format.
         */
        public function setBackend(\asciidia\backend $backend)
        /**/
        {
            $this->backend = $backend;
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
            return $this->backend->getContext();
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
            return $this->backend->getCommands();
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
            return $this->backend->getSize();
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
            $this->backend->setCellSize($w, $h);
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
            $this->backend->setScaleTo($scale);
        }

        /**
         * Get scaling value.
         *
         * @octdoc  m:plugin/getScaleTo
         * @return  string|null                     Configured scaling parameter.
         */
        public function getScaleTo()
        /**/
        {
            return $this->backend->getScaleTo();
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
            $this->backend->enableDebug($enable);
        }

        /**
         * Execute plugin.
         *
         * @octdoc  m:plugin/run
         * @param   string      $inp                Name of input.
         * @param   string      $out                Name of output.
         * @param   string      $fmt                Output file format.
         * @return  array                           Status information.
         */
        public function run($inp, $out, $fmt)
        /**/
        {
            list($status, $msg, $content) = $this->loadFile($inp);
        
            if ($status) {
                list($status, $msg) = $this->testFile($out);
            
                if ($status) {
                    $mvg = $this->parse($content);
                
                    $this->backend->saveFile($out, $mvg, $fmt);
                }
            }

            return array($status, $msg);
        }

        /**
         * Check command-line arguments.
         *
         * @octdoc  m:plugin/checkArgs
         * @param   string          $script         Contains name of the script.
         * @param   string          $opt            Commandline options.
         * @return  array                           Status information.
         */
        public function checkArgs($script, array $opt)
        /**/
        {
            return array(true, '', '');
        }

        /**
         * Method to perform environment checks for availability of tools the backend may
         * require.
         *
         * @octdoc  m:plugin/testEnv
         * @return  array                           Status information.
         */
        public function testEnv()
        /**/
        {
            return $this->backend->testEnv();
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
    }
}
