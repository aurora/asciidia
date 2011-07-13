<?php

/**
 * Standard lib provides misc methods for aciidia cli app.
 *
 * @octdoc      c:libs/stdlib
 * @copyright   copyright (c) 2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class stdlib
/**/
{
    /**
     * Determine available "plugins".
     *
     * @octdoc  m:stdlib/getPlugins
     * @return  array                       Array of plugin names.
     */
    public static function getPlugins()
    /**/
    {
        return array_map(function($name) {
            return basename($name, '.class.php');
        }, glob(__DIR__ . '/libs/plugins/*.php'));
    }
}
