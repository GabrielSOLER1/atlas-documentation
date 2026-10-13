<?php

// Serve only files inside the public directory.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath(__DIR__.'/public'.rawurldecode($path));
if ($file && str_starts_with($file, __DIR__.'/public/') && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false;
}
require __DIR__.'/public/index.php';
