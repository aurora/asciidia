<?php

/*
 * This file is part of phpreprocess
 * Copyright (C) 2011-2012 by Harald Lapp <harald@octris.org>
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
 * https://github.com/aurora/phpreprocess
 */

namespace util {
    /**
     * Execute a command using pipes.
     *
     * @octdoc      c:util/pipe
     * @copyright   copyright (c) 2011-2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class pipe
    /**/
    {
        /**
         * Opened pipes.
         *
         * @octdoc  v:pipe/$pipes
         * @type    array
         */
        protected $pipes = array();
        /**/

        /**
         * Process resource handler.
         *
         * @octdoc  v:pipe/$process
         * @type    resource
         */
        protected $process = null;
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:pipe/__construct
         * @param   string          $command                Command to execute.
         * @param   string|null     $cwd                    Optional current working directory.
         */
        public function __construct($command, $cwd = null)
        /**/
        {
            $descriptors = array(
               array('pipe', 'r'),                  // stdin
               array('pipe', 'w'),                  // stdout
               array('file', '/dev/null', 'a'),     // stderr
            );
        
            $this->process = proc_open($command, $descriptors, $this->pipes, $cwd);
        }

        /**
         * Destructor.
         *
         * @octdoc  m:pipe/__destruct
         */
        public function __destruct()
        /**/
        {
            $this->close();
        }

        /**
         * Write a row to STDIN of the executed pipe.
         *
         * @octdoc  m:pipe/write
         * @param   string      $row                Row to write.
         * @return  bool                            Returns false if writing failed.
         */
        public function write($row)
        /**/
        {

            if (($return = is_resource($this->process))) {
                fwrite($this->pipes[0], $row);
            }
        
            return $return;
        }

        /**
         * Read from STDOUT of executed pipe.
         *
         * @octdoc  m:pipe/read
         * @return  string                          Output of executed pipe.
         */
        public function read()
        /**/
        {
            if (!is_null($this->pipes[0])) {
                // close STDIN first
                fclose($this->pipes[0]);
            
                $this->pipes[0] = null;
            }
        
            $out = fgets($this->pipes[1]);
        
            return $out;
        }

        /**
         * Close resources.
         *
         * @octdoc  m:pipe/close
         */
        public function close()
        /**/
        {
            foreach ($this->pipes as $pipe) {
                if (!is_null($pipe)) fclose($pipe);
            }
        
            if (!is_null($this->process)) {
                proc_close($this->process);
            
                $this->pipes   = array();
                $this->process = null;
            }
        }
    }
}
