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

require_once(__DIR__ . '/../plugin.class.php');

/**
 * Class for creating "nice"-looking directory-trees from ASCII representation.
 *
 * @octdoc      c:plugin/test
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class test extends plugin
/**/
{
    /**
     * Overwrite loadFile of plugin class -- to load nothing.
     *
     * @octdoc  m:tree/loadFile
     * @param   string      $name               Name of file to do nothing with.
     * @return  array                           Status information.
     */
    public function loadFile($name)
    /**/
    {
        return array(true, '', '');
    }
    
    /**
     * Display usage information.
     *
     * @octdoc  m:test/usage
     */
    public function usage()
    /**/
    {
        print "description:
    this is a dummy plugin -- it let's the developer try and test (new) 
    drawing functionality.
    
    -i  this parameter does not have any effect for this plugin, but as it 
        is a required parameter and to keep command-line short, just set 
        it to '-'\n";
    }
    
    /**
     * Sanbox for testing.
     *
     * @octdoc  m:test/parse
     * @param   string      $diagram        throw-away parameter.
     * @return  string                      Imagemagick commands to draw diagram.
     */
    public function parse($diagram)
    /**/
    {
        $context = $this->getContext();
        
        /* insert your commands here */


        /*---------------------------*/
        
        return $this->getCommands();
    }
}
