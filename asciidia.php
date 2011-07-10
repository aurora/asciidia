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

// simple class autoloader
function __autoload($classname) {
    require_once(__DIR__ . '/libs/' . $classname . '.class.php');
}

// testing
$dia = new diagram();
$out = $dia->parse('
o----->
|     |
x-----+
 
    +--------+
    | Test   |
    +--------+

    
        +->-+
        |   |
        ^   V
        |   |
        +-<-+
        

'
);

$dia = new tree();
$out = $dia->parse('
 /
 +-home
 | +-harald
 | +-shared
');

list($w, $h) = $dia->getSize();

$cmd = sprintf(
    'convert -size %dx%d xc:white -stroke black -fill none -draw "%s" png:/tmp/test.png',
    $w, $h,
    implode(' ', $out)
);
        
print "$cmd\n";

`$cmd`;
