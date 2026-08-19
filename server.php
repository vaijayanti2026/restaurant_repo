<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// If the URI starts with /public/, strip it so we can find the file in the public directory
$checkUri = $uri;
if (strpos($uri, '/public/') === 0) {
    $checkUri = substr($uri, 7); // remove '/public'
}

if ($checkUri !== '/' && file_exists(__DIR__.'/public'.$checkUri)) {
    if ($checkUri !== $uri) {
        // We stripped /public. We can't just return false because the built-in server 
        // will look for the original URI (/public/...) which doesn't exist.
        // Instead, we serve the file manually.
        $filePath = __DIR__.'/public'.$checkUri;
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css' => 'text/css',
            'js'  => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'ico' => 'image/x-icon',
        ];
        if (isset($mimeTypes[$extension])) {
            header('Content-Type: ' . $mimeTypes[$extension]);
        }
        readfile($filePath);
        exit;
    }
    return false;
}

// require_once __DIR__.'/public/index.php';
require_once __DIR__.'/index.php';
