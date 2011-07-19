<?php

/**
 * Build PHAR package.
 *
 * @octdoc      h:install/build
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
/**/

define('APP_NAME', 'asciidia');

if ($argc <= 1) {
    printf("usage: %s installation-path\n", $argv[0]);
    exit(1);
}

if (!class_exists('PHAR') || !Phar::canWrite()) {
    printf("unable to create PHAR package\n");
    exit(1);
}

$dir  = rtrim($argv[1], '/');
$exec = $dir . '/' . APP_NAME;
$file = $exec . '.phar';

if (!is_writable($dir)) {
    printf("destination is not writable '%s'\n", $dir);
    exit(1);
}

if (file_exists($exec)) {
    unlink($exec);
}

$phar = new Phar(
    $file, 
    FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME, 
    basename($file)
);
$phar->buildFromDirectory(__DIR__ . '/../libs/', '/.php$/');
$phar->setStub(file_get_contents(__DIR__ . '/stub.php'));

rename($file, $exec);

chmod($exec, 0755);