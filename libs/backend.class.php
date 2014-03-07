<?php

/*
 * This file is part of asciidia
 * Copyright (C) 2014 by Harald Lapp <harald@octris.org>
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
         * Return instance of main context.
         *
         * @octdoc  m:backend/getContext
         * @return  context                     Instance of main context.
         */
        abstract protected function getContext()
        /**/
        {
            if (is_null($this->context)) {
                switch ($this->out_format) {
                    case 'svg':
                        require_once(__DIR__ . '/backend/svg.class.php');
                        $this->context = new svg();
                        break;
                    default:
                        require_once(__DIR__ . '/backend/imagemagick.class.php');
                        $this->context = new imagemagick();
                        break;
                }
            }
        
            return $this->context;
        }
    }
}
