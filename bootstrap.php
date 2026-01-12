<?php
$config = require __DIR__ . '/config.php';
$GLOBALS['config'] = $config;

define('IVES_TIMEZONE', $config['timezone'] ?? 'America/Chicago');
date_default_timezone_set(IVES_TIMEZONE);

if (($config['env'] ?? 'prod') === 'dev') {
  error_reporting(E_ALL);
  ini_set('display_errors', '1');
} else {
  ini_set('display_errors', '0');
}

function ives_session_start(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }
}
