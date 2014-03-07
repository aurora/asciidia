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

namespace asciidia {
    /**
     * Abstract backend base class.
     *
     * @octdoc      c:libs/backend
     * @copyright   copyright (c) 2014 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    abstract class backend
    /**/
    {
        /**
         * Instance of main graphic context.
         *
         * @octdoc  p:backend/$context
         * @type    context
         */
        protected $context = null;
        /**/
    
        /**
         * Size of the final bitmap.
         *
         * @octdoc  p:backend/$scale_to
         * @type    string
         */
        protected $scale_to = null;
        /**/
    
        /**
         * Return instance of main context.
         *
         * @octdoc  m:backend/getContext
         * @return  \asciidia\context                       Instance of main context.
         */
        abstract public function getContext();
        /**/
        
        /**
         * Return all commands needed for drawing diagram.
         *
         * @octdoc  m:backend/getCommands
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
         * @octdoc  m:backend/getSize
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
         * @octdoc  m:backend/setCellSize
         * @param   float       $w                  Width of a cell.
         * @param   float       $h                  Height of a cell.
         */
        public function setCellSize($w, $h)
        /**/
        {
            $context = $this->getContext();
            $context->xs = $w;
            $context->ys = $h;
        }
        
        /**
         * Set the size of the bitmap the result should be scaled to.
         *
         * @octdoc  m:backend/setScaleTo
         * @param   float       $scale              Imagemagick scaling size.
         */
        public function setScaleTo($scale)
        /**/
        {
            $this->scale_to = $scale;
        }

        /**
         * Get scaling value.
         *
         * @octdoc  m:backend/getScaleTo
         * @return  string|null                     Configured scaling parameter.
         */
        public function getScaleTo()
        /**/
        {
            return $this->scale_to;
        }

        /**
         * Enable a debugging mode for context.
         *
         * @octdoc  m:backend/enableDebug
         * @param   bool        $enable             Whether to enable / disable debugging.
         */
        public function enableDebug($enable)
        /**/
        {
            $this->getContext()->enableDebug($enable);
        }
        
        /**
         * Method to perform environment checks for availability of tools the backend may
         * require.
         *
         * @octdoc  m:backend/testEnv
         * @return  array                           Status information.
         */
        abstract public function testEnv();
        /**/

        /**
         * Save a file.
         *
         * @octdoc  m:backend/saveFile
         * @param   string      $name               Name of file to save.
         * @param   array       $commands           Imagemagick commands to save.
         * @param   string      $fmt                Output file format.
         */
        abstract public function saveFile($name, array $commands, $fmt);
        /**/
    }
}
