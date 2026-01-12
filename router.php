<?php
require_once __DIR__ . '/bootstrap.php';
$requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');

if ($requestUri === '') {
    include __DIR__ . '/index.php';
    exit;
}

$parts = explode('/', $requestUri);

// Skip common well-known requests
$skip = ['favicon.ico', 'robots.txt', 'sitemap.xml', 'apple-touch-icon.png'];
if (in_array($parts[0], $skip, true)) {
    http_response_code(404);
    exit;
}

function clean_slug(string $s): ?string {
    $s = strtolower($s);
    return preg_match('/^[a-z0-9_-]{1,50}$/', $s) ? $s : null;
}

if (count($parts) === 1 && $parts[0] !== '') {
    $user = clean_slug($parts[0]);
    if (!$user) { http_response_code(404); exit; }

    $_GET['user'] = $user;
    include __DIR__ . '/schedule.php';
    exit;
}

if (count($parts) === 2) {
    $user = clean_slug($parts[0]);
    $type = clean_slug($parts[1]); // or a different regex if you want
    if (!$user || !$type) { http_response_code(404); exit; }

    $_GET['user'] = $user;
    $_GET['type'] = $type;
    include __DIR__ . '/schedule.php';
    exit;
}

http_response_code(404);
echo "Page not found.";
