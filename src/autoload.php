<?php

if (defined('DEVELOPMENT_MODE')) {
    if (DEVELOPMENT_MODE === true) {
        ini_set('memory_limit', '-1');
        ini_set('opcache.enable', '0');
    }
}

if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    throw new Exception('The WOR Framework requires PHP version 5.4 or higher.');
}

spl_autoload_register(function ($class) {
    // project-specific namespace prefix
    $prefix = 'Mooda\\';

    // base directory for the namespace prefix
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR;

    // does the class use the namespace prefix?
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relativeClass = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('\\', '/', $relativeClass) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'Mooda.php');
