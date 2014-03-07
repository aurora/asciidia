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

namespace asciidia\backend {
    /**
     * Default backend class using imagemagick.
     *
     * @octdoc      c:backend/_default
     * @copyright   copyright (c) 2014 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class _default extends \asciidia\backend
    /**/
    {
        /**
         * Return instance of main context.
         *
         * @octdoc  m:_default/getContext
         * @return  context                     Instance of main context.
         */
        public function getContext()
        /**/
        {
            if (is_null($this->context)) {
                $this->context = new \asciidia\backend\_default\context();
            }
        
            return $this->context;
        }
        
        /**
         * Checks if imagemagick 'convert' is available in search PATH and if 
         * MAGICK_HOME variable is set.
         *
         * @octdoc  m:_default/testEnv
         * @return  array                           Status information.
         */
        public function testEnv()
        /**/
        {
            $status = true;
            $msg    = '';
            $out    = array();
            $err    = 0;
        
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
         * Save a file.
         *
         * @octdoc  m:_default/saveFile
         * @param   string      $name               Name of file to save.
         * @param   array       $commands           Imagemagick commands to save.
         * @param   string      $fmt                Output file format.
         */
        public function saveFile($name, array $commands, $fmt)
        /**/
        {
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
