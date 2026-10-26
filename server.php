<?php

$publicPath = __DIR__ . '/public';

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/'
);

/*
 * Permite que PHP entregue directamente los archivos
 * CSS, JavaScript, imágenes y demás archivos públicos.
 */
if (
    $uri !== '/' &&
    file_exists($publicPath . $uri) &&
    !is_dir($publicPath . $uri)
) {
    return false;
}

/*
 * Todas las demás solicitudes pasan por Laravel.
 */
require_once $publicPath . '/index.php';
