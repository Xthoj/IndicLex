<?php
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];

    // Find the project root by locating /public/ in the requested URL.
    // Works on any host regardless of subdirectory depth.
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $pos        = strpos($scriptName, '/public/');
    $basePath   = ($pos !== false) ? substr($scriptName, 0, $pos) : '';

    define('BASE_URL', $protocol . '://' . $host . $basePath);
}
