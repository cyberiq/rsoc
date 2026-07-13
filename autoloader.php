<?php
require_once __DIR__ . '/vendor/autoload.php';
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require $file;
        return true;
    }
    return false;
});
