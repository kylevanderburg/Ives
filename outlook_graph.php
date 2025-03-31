<?php
$config = require __DIR__ . '/config.php';
require 'vendor/autoload.php';
use GuzzleHttp\Client;

function getAccessToken() {
    $config = require 'config.php';
    $tokenData = json_decode(file_get_contents('token/token.json'), true);

    $accessToken = $tokenData['access_token'];
    $expiresAt = isset($tokenData['expires_at']) ? $tokenData['expires_at'] : 0;

    // Check if token is expired (or about to)
    if (time() >= $expiresAt - 60) {
        // Refresh it
        $client = new \GuzzleHttp\Client();
        $response = $client->post("https://login.microsoftonline.com/{$config['tenant_id']}/oauth2/v2.0/token", [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $tokenData['refresh_token'],
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'scope' => $config['scopes']
            ]
        ]);

        $newToken = json_decode($response->getBody(), true);

        // Compute new expiration time
        $newToken['expires_at'] = time() + $newToken['expires_in'];
        file_put_contents('token/token.json', json_encode($newToken));

        $accessToken = $newToken['access_token'];
    }

    return $accessToken;
}

function getBusyTimesFromGraph($start, $end) {
    $token = getAccessToken();
    $client = new \GuzzleHttp\Client();

    $response = $client->get('https://graph.microsoft.com/v1.0/me/calendarView', [
        'headers' => ['Authorization' => "Bearer $token"],
        'query' => [
            'startDateTime' => $start,
            'endDateTime' => $end,
            '$select' => 'start,end'
        ]
    ]);

    $data = json_decode($response->getBody(), true);
    $busy = [];

    foreach ($data['value'] as $event) {
        $startUtc = new DateTime($event['start']['dateTime'], new DateTimeZone($event['start']['timeZone'] ?? 'UTC'));
        $endUtc = new DateTime($event['end']['dateTime'], new DateTimeZone($event['end']['timeZone'] ?? 'UTC'));

        // Convert to Central Time
        $start = $startUtc->setTimezone(new DateTimeZone('America/Chicago'));
        $end = $endUtc->setTimezone(new DateTimeZone('America/Chicago'));

        $busy[] = [
            'start' => $start,
            'end' => $end
        ];
    }

    return $busy;
}

function createGraphEvent($subject, $start, $end, $attendeeEmail, $attendeeName, $platform = 'zoom') {
    $token = getAccessToken();
    $client = new \GuzzleHttp\Client();
    $config = require __DIR__ . '/config.php';

    $json = [
        'subject' => $subject,
        'start' => [
            'dateTime' => $start,
            'timeZone' => 'America/Chicago'
        ],
        'end' => [
            'dateTime' => $end,
            'timeZone' => 'America/Chicago'
        ],
        'attendees' => [[
            'emailAddress' => [
                'address' => $attendeeEmail,
                'name' => $attendeeName
            ],
            'type' => 'required'
        ]]
    ];

    if ($platform === 'zoom') {
        $zoomLink = $config['zoom_link'];
        $json['location'] = ['displayName' => 'Zoom Meeting'];
        $json['body'] = [
            'contentType' => 'HTML',
            'content' => "<p>Join via Zoom: <a href=\"$zoomLink\">$zoomLink</a></p>"
        ];
    } elseif ($platform === 'teams') {
        $json['isOnlineMeeting'] = true;
        $json['onlineMeetingProvider'] = 'teamsForBusiness';
    } elseif ($platform === 'in_person') {
        $json['location'] = ['displayName' => $config['in_person_location']];
        $json['body'] = [
            'contentType' => 'HTML',
            'content' => "<p>This is an in-person meeting. Location: {$config['in_person_location']}.</p>"
        ];
    }
    

    $client->post('https://graph.microsoft.com/v1.0/me/events', [
        'headers' => [
            'Authorization' => "Bearer $token",
            'Content-Type' => 'application/json'
        ],
        'json' => $json
    ]);
}

function sendGraphEmail($toEmail, $subject, $bodyText) {
    $token = getAccessToken();
    $client = new \GuzzleHttp\Client();

    $emailData = [
        'message' => [
            'subject' => $subject,
            'body' => [
                'contentType' => 'Text',
                'content' => $bodyText
            ],
            'toRecipients' => [[
                'emailAddress' => [
                    'address' => $toEmail
                ]
            ]]
        ],
        'saveToSentItems' => true
    ];

    $client->post('https://graph.microsoft.com/v1.0/me/sendMail', [
        'headers' => [
            'Authorization' => "Bearer $token",
            'Content-Type' => 'application/json'
        ],
        'json' => $emailData
    ]);
}
