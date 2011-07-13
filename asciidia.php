#!/usr/bin/env php
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
 * Main application.
 *
 * @octdoc      h:asciidia/asciidia
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
/**/

/*
 * config
 */
$types = array('diagram', 'tree');
$type  = 'diagram';
$scale = '';
$cell  = '';

/*
 * main
 */

// process command-line parameters
$opt   = getopt('t:ri:o:s:c:');

if (array_key_exists('t', $opt)) {
     if (in_array($opt['t'], $types)) {
         $type = $opt['t'];
     } else {
         usage(sprintf('unknown type "%s"', $opt['t']));
     }
}
if (!array_key_exists('i', $opt) || !array_key_exists('o', $opt)) {
    usage();
} elseif (is_dir($opt['i']) && $opt['t'] != 'tree') {
    usage('a directory as input is only allowed for diagram type "tree"');
} elseif ($opt['i'] != '-' && !is_readable($opt['i'])) {
    usage('input is not readable');
} elseif (is_dir($opt['o'])) {
    usage('only a filename is allowed as output');
} elseif ($opt['o'] != '-') {
    if (file_exists($opt['o'])) {
        usage('output already exists');
    } elseif (!touch($opt['o']) || !is_writable($opt['o'])) {
        usage('output is not writable');
    }
}
if (array_key_exists('s', $opt)) {
    if (!preg_match('/^(\d*x\d+|\d+x\d*)$/', $opt['s'])) {
        usage('wrong scaling parameter');
    } else {
        $scale = $opt['s'];
    }
}
if (array_key_exists('c', $opt)) {
    if (!preg_match('/^\d+(x\d+|)$/', $opt['c'])) {
        usage('wrong cell-size parameter');
    } elseif (strpos($opt['c'], 'x') !== false) {
        $cell = explode('x', $opt['c']);
    } else {
        $cell = array($opt['c'], $opt['c']);
    }
}

$raw = array_key_exists('r', $opt);

// process diagram
$dia = new $type();

if ($type == 'tree' && $opt['i'] != '-' && is_dir($opt['i'])) {
    // directory as input
    $content = $dia->getTree($opt['i']);
} else {
    // file or STDIN as input
    $content = file_get_contents(
        ($opt['i'] == '-' ? 'php://stdin' : $opt['i'])
    );
}

if ($cell) {
    $dia->xs = $cell[0];
    $dia->ys = $cell[1];
}

$out = $dia->parse($content);

if ($raw) {
    file_put_contents(
        ($opt['o'] == '-' ? 'php://stdout' : $opt['o']),
        implode("\n", $out)
    );
} else {
    list($w, $h) = $dia->getSize();

    $cmd = sprintf(
        'convert -size %dx%d xc:white -stroke black -fill none -draw %s %s png:%s',
        $w, $h,
        escapeshellarg(implode(' ', $out)),
        ($scale ? '-scale ' . $scale : ''),
        $opt['o']
    );

    passthru($cmd);
}

/*
 * functions
 */
/**
 * Simple class autoloader.
 *
 * @octdoc  f:asciidia/__autoload
 * @param   string      $classname          Name of class to load.
 */
function __autoload($classname)
/**/
{
    require_once(__DIR__ . '/libs/' . $classname . '.class.php');
}

/**
 * Show simple usage information and exit application.
 *
 * @octdoc  f:asciidia/usage
 */
function usage($msg = '')
/**/
{
    global $argv, $types;
    
    if ($msg) {
        printf("error: %s\n", $msg);
    }
    
    printf("usage: %s [-t ...] [-r] [-c ...] [-s ...] -i ... -o ...\n", $argv[0]);
    printf("options:
    -t  type: %s. Default is: diagram
    -r  output as imagemagick draw commands instead of creating a bitmap
    -c  defines the widht/height of each cell / character on the canvas in 
        pixel. Notation is ...x... (width x height) or ... (width x width).
    -s  only if '-r' is not specified. scales image. notation is ...x...
        (width x height) whereas ... is a number to scale to. if width or
        height are ommited, image will be scale by keeping aspect ratio.
    -i  input filename. if '-', input is read from STDIN. If a directory is
        specified, the directory will be drawn as tree-diagram instead.
    -o  output filename. if '-', output is written to STDOUT
", implode(', ', $types));
    exit(0);
}
