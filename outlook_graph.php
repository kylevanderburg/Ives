<?php
$config = require __DIR__ . '/config.php';
require 'vendor/autoload.php';
use GuzzleHttp\Client;

// 🧠 Utility: Safe token filename based on email
function getTokenPath($userEmail) {
    return 'token/' . preg_replace('/[^a-z0-9_\-\.]/i', '_', strtolower($userEmail)) . '.json';
}

// ✅ Get or refresh access token for a given user
function getAccessToken($userEmail) {
    $tokenFile = getTokenPath($userEmail);
    if (!file_exists($tokenFile)) {
        throw new Exception("No token found for user: $userEmail");
    }

    $tokenData = json_decode(file_get_contents($tokenFile), true);
    $accessToken = $tokenData['access_token'];
    $expiresAt = $tokenData['expires_at'] ?? 0;

    if (time() >= $expiresAt - 60) {
        // Refresh token
        $client = new Client();
        $response = $client->post("https://login.microsoftonline.com/{$GLOBALS['config']['tenant_id']}/oauth2/v2.0/token", [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $tokenData['refresh_token'],
                'client_id' => $GLOBALS['config']['client_id'],
                'client_secret' => $GLOBALS['config']['client_secret'],
                'scope' => $GLOBALS['config']['scopes']
            ]
        ]);

        $newToken = json_decode($response->getBody(), true);
        $newToken['expires_at'] = time() + $newToken['expires_in'];

        file_put_contents($tokenFile, json_encode($newToken));
        $accessToken = $newToken['access_token'];
    }

    return $accessToken;
}

// ✅ Fetch busy time ranges for a user
function getBusyTimesFromGraph($start, $end, $userEmail) {
    $token = getAccessToken($userEmail);
    $client = new Client();

    $response = $client->get("https://graph.microsoft.com/v1.0/users/{$userEmail}/calendarView", [
        'headers' => ['Authorization' => "Bearer $token"],
        'query' => [
            'startDateTime' => $start,
            'endDateTime' => $end,
            '$select' => 'start,end,subject',
            '$orderby' => 'start/dateTime',
            '$top' => 1000
        ]
    ]);

    $data = json_decode($response->getBody(), true);
    $busy = [];

    foreach ($data['value'] as $event) {
        $startUtc = new DateTime($event['start']['dateTime'], new DateTimeZone($event['start']['timeZone'] ?? 'UTC'));
        $endUtc = new DateTime($event['end']['dateTime'], new DateTimeZone($event['end']['timeZone'] ?? 'UTC'));

        $start = $startUtc->setTimezone(new DateTimeZone('America/Chicago'));
        $end = $endUtc->setTimezone(new DateTimeZone('America/Chicago'));

        $busy[] = ['start' => $start, 'end' => $end, 'subject' => $event['subject'] ?? '(No Title)'];
    }

    return $busy;
}

// ✅ Create a calendar event for a user
function createGraphEvent($subject, $start, $end, $attendeeEmail, $attendeeName, $platform, $userEmail) {
    $token = getAccessToken($userEmail);
    $client = new Client();
    $config = require __DIR__ . '/config.php';

    $eventData = [
        'subject' => $subject,
        'start' => ['dateTime' => $start, 'timeZone' => 'America/Chicago'],
        'end' => ['dateTime' => $end, 'timeZone' => 'America/Chicago'],
        'attendees' => [[
            'emailAddress' => ['address' => $attendeeEmail, 'name' => $attendeeName],
            'type' => 'required'
        ]]
    ];

    if ($platform === 'zoom') {
        $eventData['location'] = ['displayName' => 'Zoom Meeting'];
        $eventData['body'] = [
            'contentType' => 'HTML',
            'content' => "<p>Join via Zoom: <a href=\"{$config['zoom_link']}\">{$config['zoom_link']}</a></p>"
        ];
    } elseif ($platform === 'teams') {
        $eventData['isOnlineMeeting'] = true;
        $eventData['onlineMeetingProvider'] = 'teamsForBusiness';
    } elseif ($platform === 'in_person') {
        $eventData['location'] = ['displayName' => $config['in_person_location']];
        $eventData['body'] = [
            'contentType' => 'HTML',
            'content' => "<p>This is an in-person meeting. Location: {$config['in_person_location']}.</p>"
        ];
    }

    $client->post("https://graph.microsoft.com/v1.0/users/{$userEmail}/events", [
        'headers' => [
            'Authorization' => "Bearer $token",
            'Content-Type' => 'application/json'
        ],
        'json' => $eventData
    ]);
}

// ✅ Send an email using the user's mailbox
function sendGraphEmail($fromEmail, $toEmail, $subject, $bodyText) {
    $token = getAccessToken($fromEmail);
    $client = new Client();

    $emailData = [
        'message' => [
            'subject' => $subject,
            'body' => [
                'contentType' => 'Text',
                'content' => $bodyText
            ],
            'toRecipients' => [[
                'emailAddress' => ['address' => $toEmail]
            ]]
        ],
        'saveToSentItems' => true
    ];

    $client->post("https://graph.microsoft.com/v1.0/users/{$fromEmail}/sendMail", [
        'headers' => [
            'Authorization' => "Bearer $token",
            'Content-Type' => 'application/json'
        ],
        'json' => $emailData
    ]);
}
