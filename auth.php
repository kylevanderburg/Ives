<?php
$config = require 'config.php';

$params = [
    'client_id' => $config['client_id'],
    'response_type' => 'code',
    'redirect_uri' => $config['redirect_uri'],
    'response_mode' => 'query',
    'scope' => $config['scopes'],
    //'prompt' => 'consent'
];

$tenantId = $config['tenant_id'] ?? 'common';
$authUrl = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/authorize?' . http_build_query($params);
?>

<?php include 'header.php'; ?>
    <div class="container text-center mt-5">
        <h1 class="mb-4">Authorize This Booking System</h1>
        <p class="lead">Click below to log in with your Microsoft account and connect your calendar.</p>
        <a href="<?= htmlspecialchars($authUrl) ?>" class="btn btn-primary btn-lg mt-3">Log In with Microsoft</a>
    </div>
<?php include 'footer.php'; ?>
