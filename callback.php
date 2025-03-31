<?php
require_once 'vendor/autoload.php';

$config = require 'config.php';

if (!isset($_GET['code'])) {
    echo "Authorization code not found.";
    exit;
}

// Exchange authorization code for tokens
$client = new \GuzzleHttp\Client();

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

// Get the authenticated user's email from Graph
$userResponse = $client->get('https://graph.microsoft.com/v1.0/me', [
    'headers' => [
        'Authorization' => 'Bearer ' . $data['access_token']
    ]
]);

$userInfo = json_decode($userResponse->getBody(), true);
$userEmail = strtolower($userInfo['mail'] ?? $userInfo['userPrincipalName'] ?? '');

// Allow only specific accounts to authorize
$authorizedEmails = ['kyle.vanderburg@ndsu.edu', 'kyle@noteforge.com']; // <- your email(s) here

if (!in_array($userEmail, $authorizedEmails)) {
    echo "<h2>Access Denied</h2>";
    echo "<p>This calendar system is restricted. The email you logged in with (<strong>$userEmail</strong>) is not authorized to configure this account.</p>";
    exit;
}

// Save token with expiration time
$data['expires_at'] = time() + $data['expires_in'];
file_put_contents('token.json', json_encode($data));

echo "<h2>Authorization successful</h2>";
echo "<p>The token has been saved and you're ready to accept bookings.</p>";
