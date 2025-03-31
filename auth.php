<?php
$config = require 'config.php';

$params = [
    'client_id' => $config['client_id'],
    'response_type' => 'code',
    'redirect_uri' => $config['redirect_uri'],
    'response_mode' => 'query',
    'scope' => $config['scopes'],
    'prompt' => 'consent'  // force consent screen in case scopes change
];

$authUrl = 'https://login.microsoftonline.com/' . $config['tenant_id'] . '/oauth2/v2.0/authorize?' . http_build_query($params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authorize Calendar Access</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container text-center mt-5">
        <h1 class="mb-4">Authorize This Booking System</h1>
        <p class="lead">Click below to log in with your Microsoft account and connect your calendar.</p>
        <a href="<?= htmlspecialchars($authUrl) ?>" class="btn btn-primary btn-lg mt-3">Log In with Microsoft</a>
    </div>
</body>
</html>
