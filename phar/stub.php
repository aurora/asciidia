#!/usr/bin/env php
<?php

/**
 * Asciidia PHAR stub.
 *
 * @octdoc      h:phar/stub
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
/**/

if (!class_exists('PHAR')) {
    print 'unable to execute -- wrong PHP version\n';
    exit(1);
}

Phar::mapPhar();
include 'phar://asciidia.phar/main.class.php';

$main = new main();
$main->run();
__HALT_COMPILER();