<?php
$config = require 'config.php';

$params = [
    'client_id' => $config['client_id'],
    'response_type' => 'code',
    'redirect_uri' => $config['redirect_uri'],
    'response_mode' => 'query',
    'scope' => $config['scopes'],
    'prompt' => 'consent'
];

$tenantId = $config['tenant_id'] ?? 'common';
$authUrl = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/authorize?' . http_build_query($params);
?>

<?php include 'header.php'; ?>
    <div class="container text-center mt-5">
        <h1 class="mb-4">Authorize This Booking System</h1>
        <p class="lead">Click below to log in with your Microsoft account and connect your calendar.</p>
        <p class="mb-0">
            <a href="terms.php" class="d-block d-sm-inline">Terms of Service</a>
            <span class="d-none d-sm-inline"> | </span>
            <a href="//github.com/kylevanderburg/Ives" class="d-block d-sm-inline">Source</a>
        </p>
    </div>
<?php include 'footer.php'; ?>
