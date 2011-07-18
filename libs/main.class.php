<?php

/*
 * asciidia
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
 * Main application class.
 *
 * @octdoc      c:libs/main
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class main 
/**/
{
    /**
     * Instance of loaded plugin.
     *
     * @octdoc  v:main/$plugin
     * @var     asciidia
     */
    protected $plugin = null;
    /**/
    
    /**
     * Available plugins.
     *
     * @octdoc  v:main/$plugins
     * @var     array
     */
    protected $plugins = array();
    /**/

    /**
     * Constructor.
     *
     * @octdoc  m:main/__construct
     * @param   
     */
    public function __construct()
    /**/
    {
    }
    
    /**
     * Run main application.
     *
     * @octdoc  m:main/run
     */
    public function run()
    /**/
    {
        $this->getPlugins();

        // process standard command-line parameters
        $scale = '';
        $cell  = '';
        
        $opt = getopt('t:i:o:s:c:dh');

        if (array_key_exists('t', $opt)) {
            $plugin = $this->loadPlugin($opt['t']);
        }
        if (array_key_exists('h', $opt)) {
            $this->usage('', true);
        }
        if (!array_key_exists('t', $opt) || !array_key_exists('i', $opt) || !array_key_exists('o', $opt)) {
            $this->usage();
        }
        if (array_key_exists('s', $opt)) {
            if (!preg_match('/^(\d*x\d+|\d+x\d*)$/', $opt['s'])) {
                $this->usage('wrong scaling parameter');
            } else {
                $scale = $opt['s'];
            }
        }
        if (array_key_exists('c', $opt)) {
            if (!preg_match('/^\d+(x\d+|)$/', $opt['c'])) {
                $this->usage('wrong cell-size parameter');
            } elseif (strpos($opt['c'], 'x') !== false) {
                $cell = explode('x', $opt['c']);
            } else {
                $cell = array($opt['c'], $opt['c']);
            }
        }
        $debug = array_key_exists('d', $opt);

        if (!is_null($this->plugin)) {
            // test environment
            list($status, $msg) = $this->plugin->testEnv();
            
            if (!$status) {
                $this->usage($msg);
                return;
            }
            
            // process command line args of a loaded plugin
            list($status, $msg) = $this->plugin->checkArgs();
            
            if (!$status) {
                $this->usage($msg);
                return;
            }
            
            // plugin setup
            $this->plugin->enableDebug($debug);
            
            if ($cell) {
                $this->plugin->setCellSize($cell[0], $cell[1]);
            }
            if ($scale) {
                $this->plugin->setScaleTo($scale);
            }
            
            // execute plugin
            list($status, $msg) = $this->plugin->run($opt['i'], $opt['o']);
            
            if (!$status) $this->usage($msg);
        }
    }
    
    /**
     * Determine available "plugins".
     *
     * @octdoc  m:main/getPlugins
     * @return  array                       Array of plugin names.
     */
    protected function getPlugins()
    /**/
    {
        return ($this->plugins = array_map(function($name) {
            return basename($name, '.class.php');
        }, glob(__DIR__ . '/plugins/*.php')));
    }
    
    /**
     * Load a plugin to use.
     *
     * @octdoc  m:main/loadPlugin
     * @param   string          $plugin         Name of plugin to load.
     * @return  asciidia                        Instance of loaded plugin.
     */
    protected function loadPlugin($plugin)
    /**/
    {
        if (!in_array($plugin, $this->plugins)) {
            $this->usage(sprintf('unknown plugin type "%s"', $plugin));
        }
        
        require_once(__DIR__ . '/plugins/' . $plugin . '.class.php');
        
        $this->plugin = new $plugin();
        
        return $this->plugin;
    }
    
    /**
     * Show usage information.
     *
     * @octdoc  m:main/usage
     * @param   string      $msg            Optional error message to print.
     * @param   bool        $help           Optional flag to show complete help.
     */
    protected function usage($msg = '', $help = false)
    /**/
    {
        global $argv;
        
        if ($msg) {
            printf("error: %s\n", $msg);
            exit(1);
        }

        printf("usage: %s -h\n", $argv[0]);
        printf("usage: %s -t ... -h\n", $argv[0]);
        printf("usage: %s -t ... -i ... -o ... [-c ...] [-s ...]\n", $argv[0]);
        
        if ($help) {
            printf("default options:
    -h  show information about command-line arguments. provide a diagram type
        with '-t' to show help about the plugin

    -t  plugin type to load. available plugins are:
    
        %s

    -i  input filename or '-' for STDIN

    -o  output filename or '-' for STDOUT

    -c  defines the widht/height of each cell / character on the canvas in 
        pixel. Notation is ...x... (width x height) or ... (width x width).

    -s  scales image. notation is ...x... (width x height) whereas ... is a 
        number to scale to. if width or height are ommited, image will be 
        scaled by keeping aspect ratio.\n", 
                implode("\n        ", $this->plugins)
            );
        
            if (!is_null($this->plugin)) {
                printf("\n%s plugin:\n\n", get_class($this->plugin));

                $this->plugin->usage();
            }
        }
        
        exit(0);
    }
}
