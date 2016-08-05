<?php

if (function_exists('__autoload')) {
    spl_autoload_register('__autoload');
}

spl_autoload_register(function ($sClassName){

    if (!preg_match("/^ApnsPHP/", $sClassName)) {
        return;
    }

    if (empty($sClassName)) {
        throw new Exception('Class name is empty');
    }

    $sPath = dirname(dirname(__FILE__) . "/../third_party/ApnsPHP");
    if (empty($sPath)) {
        throw new Exception('Current path is empty');
    }

    $sFile = sprintf('%s%s%s.php',
        $sPath, DIRECTORY_SEPARATOR,
        str_replace('_', DIRECTORY_SEPARATOR, $sClassName)
    );
    if (!is_file($sFile) || !is_readable($sFile)) {
        throw new Exception("Class file '{$sFile}' does not exists");
    }

    require_once $sFile;

    if (!class_exists($sClassName, false) && !interface_exists($sClassName, false)) {
        throw new Exception("File '{$sFile}' was loaded but class '{$sClassName}' was not found in file");
    }
});

