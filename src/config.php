<?php
    require 'lib/utils.php';
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $srcDirectory = dirname($scriptName);
    $baseUrl = preg_replace('/\/src$/', '', $srcDirectory);

    if ($baseUrl === '')
    {
        $baseUrl = '/';
    }

    define('BASE_URL', $baseUrl);
?>