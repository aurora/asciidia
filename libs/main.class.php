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

namespace asciidia {
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
         * @type    asciidia
         */
        protected $plugin = null;
        /**/
    
        /**
         * Available plugins.
         *
         * @octdoc  v:main/$plugins
         * @type    array
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
         * Parse command line options and return Array of them. The parameters are required to have
         * the following format:
         *
         * - short options: -l -a -b
         * - short options combined: -lab
         * - short options with value: -l val -a val -b "with whitespace"
         * - long options: --option1 --option2
         * - long options with value: --option=value --option value --option "with whitespace"
         *
         * @octdoc  m:main/getOptions
         * @return  array                               Parsed command line parameters.
         */
        protected function getOptions()
        /**/
        {
            global $argv;
            static $opts = null;
        
            if (is_array($opts)) {
                // already parsed
                return $opts;
            }

            $args = $argv;
            $opts = array();
            $key  = '';
            $idx  = 1;

            array_shift($args);

            foreach ($args as $arg) {
                if (preg_match('/^-([a-zA-Z]+)$/', $arg, $match)) {
                    // short option, combined short options
                    $tmp  = str_split($match[1], 1);
                    $opts = array_merge(array_combine($tmp, array_fill(0, count($tmp), true)), $opts);
                    $key  = array_pop($tmp);
                
                    continue;
                } elseif (preg_match('/^--([a-zA-Z][a-zA-Z0-9]+)(=.*|)$/', $arg, $match)) {
                    // long option
                    $key  = $match[1];
                    $opts = array_merge(array($key => true), $opts);

                    if (strlen($match[2]) == 0) {
                        continue;
                    }

                    $arg = substr($match[2], 1);
                } elseif (strlen($arg) > 1 && substr($arg, 0, 1) == '-') {
                    // invalid option format
                    throw new \Exception('invalid option format "' . $arg . '"');
                }

                if ($key == '') {
                    // no option name, add as numeric option
                    $opts[$idx++] = $arg;
                } else {
                    if (!is_bool($opts[$key])) {
                        // multiple values for this option
                        if (!is_array($opts[$key])) {
                            $opts[$key] = array($opts[$key]);
                        }
                    
                        $opts[$key][] = $arg;
                    } else {
                        $opts[$key] = $arg;
                    }
                }
            }

            return $opts;
        }

        /**
         * Run main application.
         *
         * @octdoc  m:main/run
         */
        public function run()
        /**/
        {
            global $argv;

            $this->getPlugins();

            // process standard command-line parameters
            $scale   = '';
            $cell    = '';
            $out_fmt = 'png';
        
            $opt = $this->getOptions();

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
            if (preg_match('/^([a-z][a-z0-9]*):(.+)$/', $opt['o'], $match)) {
                $out_fmt  = $match[1];
                $opt['o'] = $match[2];
            }
            $debug = array_key_exists('d', $opt);

            if (!is_null($this->plugin)) {
                $backend = '\\asciidia\\backend\\' . $out_fmt . '\\backend';
            
                if (class_exists($backend)) {
                    $this->plugin->setBackend(new $backend());
                } else {
                    $this->plugin->setBackend(new \asciidia\backend\_default\backend());
                }
            
                // test environment
                list($status, $msg) = $this->plugin->testEnv();
            
                if (!$status) {
                    $this->usage($msg);
                    return;
                }
            
                // process command line args of a loaded plugin
                list($status, $msg, $usage) = $this->plugin->checkArgs($argv[0], $opt);
            
                if (!$status) {
                    $this->usage($msg, false, $usage);
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
         */
        protected function getPlugins()
        /**/
        {
            $this->plugins = array();
            
            foreach (new \DirectoryIterator(__DIR__ . '/plugins/') as $file) {
                if (!$file->isDot() && substr(($name = $file->getFilename()), -4) == '.php') {
                    $this->plugins[] = basename($name, '.class.php');
                }
            }
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
        
            $class = '\\asciidia\\plugins\\' . $plugin;
        
            $this->plugin = new $class();
        
            return $this->plugin;
        }
    
        /**
         * Show usage information.
         *
         * @octdoc  m:main/usage
         * @param   string      $msg            Optional error message to print.
         * @param   bool        $help           Optional flag to show complete help.
         * @param   string      $usage          Optional additional usage examples.
         */
        protected function usage($msg = '', $help = false, $usage = '')
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

            if ($usage != '') {
                print $usage;
            }
        
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

                    $this->plugin->usage($argv[0]);
                }
            }
        
            exit(0);
        }

        /**
         * Class Autoloader.
         *
         * @octdoc  m:main/autoload
         * @param   string          $classpath              Path of class to load.
         */
        public static function autoload($classpath)
        /**/
        {
            if (substr($classpath, 0, 8) == 'asciidia') {
                $file = __DIR__ . '/' . preg_replace('|\\\\|', '/', substr($classpath, 8)) . '.class.php';

                if (file_exists($file)) {
                    require_once($file);
                }
            }        
        }
    }

    spl_autoload_register(array('\asciidia\main', 'autoload'));
}
