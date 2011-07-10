#!/usr/bin/env php
<?php

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

/*
 * main
 */

// process command-line parameters
$opt   = getopt('t:ri:o:');

if (!array_key_exists('i', $opt) || !array_key_exists('o', $opt)) {
    usage();
}
if (array_key_exists('t', $opt)) {
     if (in_array($opt['t'], $types)) {
         $type = $opt['t'];
     } else {
         usage(sprintf("unknown type '%s'", $opt['t']));
     }
}
$raw = array_key_exists('r', $opt);

// process diagram
$content = file_get_contents(
    ($opt['i'] == '-' ? 'php://stdin' : $opt['i'])
);

$dia = new $type();
$out = $dia->parse($content);

if ($raw) {
    file_put_contents(
        ($opt['o'] == '-' ? 'php://stdout' : $opt['o']),
        implode("\n", $out)
    );
} else {
    list($w, $h) = $dia->getSize();

    $cmd = sprintf(
        'convert -size %dx%d xc:white -stroke black -fill none -draw "%s" png:%s',
        $w, $h,
        implode(' ', $out),
        $opt['o']
    );

    `$cmd`;
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
    
    printf("usage: %s [-t ...] [-r] -i ... -o ...\n", $argv[0]);
    printf("options:
    -t  type: %s. Default is: diagram
    -r  output as imagemagick draw commands instead of creating a bitmap
    -i  input filename. if '-', input is read from STDIN
    -o  output filename. if '-', output is written to STDOUT
", implode(', ', $types));
    exit(0);
}
