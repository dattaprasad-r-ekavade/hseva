<?php
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$root = __DIR__;
$path = $root . str_replace('/', DIRECTORY_SEPARATOR, $uri);

if ($uri !== '/' && is_file($path)) {
    return false;
}

$trimmed = rtrim($uri, '/');
if ($trimmed !== '') {
    $dirIndex = $root . str_replace('/', DIRECTORY_SEPARATOR, $trimmed) . DIRECTORY_SEPARATOR . 'index.html';
    if (is_file($dirIndex)) {
        readfile($dirIndex);
        return true;
    }
}

if (str_starts_with($uri, '/api/')) {
    $_GET['path'] = $uri;
    require $root . '/backend/api.php';
    return true;
}

$fallback = $root . '/index.html';
if (is_file($fallback)) {
    readfile($fallback);
    return true;
}

http_response_code(404);
echo 'Not Found';
