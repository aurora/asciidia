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

        $context->xs = 10;
        $context->ys = 10;

        // $context->drawPath(
        //     array(
        //         array(0, 1), array(1, 1), array(1, 3), array(3, 3)
        //     ),
        //     false, true
        // );

        // left -> right
        $context->drawPath(
            array(array(0, 0), array(3, 0), array(3, 3), array(0,3), array(0,1), array(2,1)),
            false, true
        );

        // // right -> left
        // $context->drawPath(
        //     array(array(3, 3), array(3, 0), array(0, 0), array(0, 3), array(2,3), array(2,1)),
        //     false, true
        // );

        // $context->addCommand("path 'M 15,15 A 15,15 90 0,0 15,15'");
        // $context->addCommand("path 'M 15,15 A 15,15 90 0,0 15,0'");
        // $context->addCommand("path 'M 15,15 A 15,15 90 0,0 0,0'");
        // $context->addCommand("path 'M 15,15 A 15,15 90 0,1 15,0'");
        

        // $context->translate(20, 20);

        // $context->drawPath(
        //     array(array(5, 0), array(3, 0), array(3, 3)),
        //     false, false
        // );
        // 
        // $context->drawPath(
        //     array(array(5, 1), array(5, 4), array(3, 4)),
        //     false, false
        // );

        /*---------------------------*/
        
        return $this->getCommands();
    }
}
