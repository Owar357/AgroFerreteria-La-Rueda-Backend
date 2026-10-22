<?php

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE, PATCH');
    header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With, Application');
    exit(0);
}

if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}


require_once __DIR__.'/public/index.php';
