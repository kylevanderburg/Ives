<?php
require_once 'vendor/autoload.php';

$config = require 'config.php';

if (!isset($_GET['code'])) {
    echo "Authorization code not found.";
    exit;
}

$client = new \GuzzleHttp\Client();

// Get token
$response = $client->post("https://login.microsoftonline.com/{$config['tenant_id']}/oauth2/v2.0/token", [
    'form_params' => [
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => $config['redirect_uri'],
        'code' => $_GET['code'],
        'grant_type' => 'authorization_code',
        'scope' => $config['scopes']
    ]
]);

$data = json_decode($response->getBody(), true);

// Get user info
$userResponse = $client->get('https://graph.microsoft.com/v1.0/me', [
    'headers' => ['Authorization' => 'Bearer ' . $data['access_token']]
]);

$userInfo = json_decode($userResponse->getBody(), true);
$userEmail = strtolower($userInfo['mail'] ?? $userInfo['userPrincipalName'] ?? '');

include 'header.php';

// Save token per user
$data['expires_at'] = time() + $data['expires_in'];
$tokenFile = 'token/' . preg_replace('/[^a-z0-9_\-\.]/i', '_', $userEmail) . '.json';

if (!is_dir('token')) {
    mkdir('token', 0755, true);
}

if (!file_put_contents($tokenFile, json_encode($data))) {
    echo "<div class='alert alert-danger'>Failed to save token for $userEmail. Check permissions.</div>";
    include 'footer.php';
    exit;
}

echo "<div class='container mt-5 text-center'>";
echo "<h2 class='mb-3'>Authorization Successful</h2>";
echo "<p class='lead'>You're now ready to accept bookings as <strong>$userEmail</strong>.</p>";
echo "<a href='/' class='btn btn-primary mt-3'>Return to Home</a>";
echo "</div>";

include 'footer.php';
