<?php
$requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = explode('/', $requestUri);

// Skip requests to favicon, robots, etc.
if ($parts[0] === 'favicon.ico') {
    http_response_code(404);
    exit;
}

if (count($parts) === 1 && $parts[0] !== '') {
    $_GET['user'] = $parts[0];
    include 'schedule.php';
    exit;
}

if (count($parts) === 2) {
    $_GET['user'] = $parts[0];
    $_GET['type'] = $parts[1];
    include 'schedule.php';
    exit;
}

// Optional: fallback route or 404
http_response_code(404);
echo "Page not found.";
