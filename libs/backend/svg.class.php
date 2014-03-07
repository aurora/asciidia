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
     * Backend class for SVG output format.
     *
     * @octdoc      c:backend/svg
     * @copyright   copyright (c) 2014 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class svg extends \asciidia\backend
    /**/
    {
        /**
         * Return instance of main context.
         *
         * @octdoc  m:svg/getContext
         * @return  context                     Instance of main context.
         */
        protected function getContext()
        /**/
        {
            if (is_null($this->context)) {
                $this->context = new \asciidia\backend\svg\context();
            }
        
            return $this->context;
        }

        /**
         * Save a file or test if file can be saved to.
         *
         * @octdoc  m:svg/saveFile
         * @param   string      $name               Name of file to save.
         * @param   array       $commands           Imagemagick commands to save.
         * @param   string      $fmt                Output file format.
         */
        public function saveFile($name, array $commands, $fmt)
        /**/
        {
            file_put_contents(
                ($name == '-' ? 'php://stdout' : $name),
                $commands
            );
        }
    }
}
