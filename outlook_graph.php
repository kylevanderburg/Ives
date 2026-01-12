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
        $newToken['expires_at'] = time() + ($newToken['expires_in'] ?? 0);

        // Preserve refresh_token if not returned
        if (empty($newToken['refresh_token']) && !empty($tokenData['refresh_token'])) {
            $newToken['refresh_token'] = $tokenData['refresh_token'];
        }

        // Preserve scope if not returned (optional but tidy)
        if (empty($newToken['scope']) && !empty($tokenData['scope'])) {
            $newToken['scope'] = $tokenData['scope'];
        }

        file_put_contents($tokenFile, json_encode($newToken));
        $accessToken = $newToken['access_token'];
        $tokenData = $newToken; // keep in-memory consistent

    }

    $tokenData['access_token'] = $accessToken;
    $tokenData['scopes'] = explode(' ', $tokenData['scope'] ?? '');
    return $tokenData;
}

// ✅ Fetch busy time ranges for a user
function getBusyTimesFromGraph($start, $end, $userEmail) {
    $hostTz = new DateTimeZone($config['timezone'] ?? 'America/Chicago');

    $tokenData = getAccessToken($userEmail);
    $accessToken = $tokenData['access_token'];
    $client = new Client();

    $response = $client->get("https://graph.microsoft.com/v1.0/users/{$userEmail}/calendarView", [
        'headers' => ['Authorization' => "Bearer $accessToken"],
        'query' => [
            'startDateTime' => $start,
            'endDateTime' => $end,
            '$select' => 'start,end,subject,showAs',
            '$orderby' => 'start/dateTime',
            '$top' => 1000
        ]
    ]);

    $data = json_decode($response->getBody(), true);
    $busy = [];

    $parseGraphDateTime = function (string $dt, ?string $tzName): DateTimeImmutable {
        $tzName = $tzName ?: 'UTC';

        // If dt already has timezone info, parse as-is
        if (preg_match('/(Z|[+\-]\d{2}:\d{2})$/', $dt)) {
            return new DateTimeImmutable($dt);
        }

        // Otherwise interpret dt in the supplied timezone
        return new DateTimeImmutable($dt, new DateTimeZone($tzName));
    };


    foreach (($data['value'] ?? []) as $event) {
        // Skip events marked as "free"
        if (isset($event['showAs']) && strtolower($event['showAs']) === 'free') {
            continue;
        }
    
        $startDT = $parseGraphDateTime($event['start']['dateTime'], $event['start']['timeZone'] ?? 'UTC');
        $endDT   = $parseGraphDateTime($event['end']['dateTime'],   $event['end']['timeZone'] ?? 'UTC');

        $start = $startDT->setTimezone($hostTz);
        $end   = $endDT->setTimezone($hostTz);
    
        $busy[] = [
            'start' => $start,
            'end' => $end,
            'subject' => $event['subject'] ?? '(No Title)',
            'showAs' => $event['showAs'] ?? 'unknown'
        ];
    }

    return $busy;
}

function ianaToWindowsTz(string $iana): string {
    static $map = [
        'America/Chicago'    => 'Central Standard Time',
        'America/New_York'   => 'Eastern Standard Time',
        'America/Denver'     => 'Mountain Standard Time',
        'America/Los_Angeles'=> 'Pacific Standard Time',
        'America/Phoenix'    => 'US Mountain Standard Time',
        'America/Anchorage'  => 'Alaskan Standard Time',
        'Pacific/Honolulu'   => 'Hawaiian Standard Time',
        'UTC'                => 'UTC',
        'Etc/UTC'            => 'UTC',
    ];

    return $map[$iana] ?? 'UTC'; // safe fallback
}


// ✅ Create a calendar event for a user
function createGraphEvent($subject, $start, $end, $attendeeEmail, $attendeeName, $platform, $userEmail, $ianaTz = 'America/Chicago') {
    $tokenData = getAccessToken($userEmail);
    $accessToken = $tokenData['access_token'];
    $client = new Client();
    global $config;

    // Map IANA -> Windows (Graph/Outlook uses Windows timezone IDs)
    $windowsTz = ianaToWindowsTz($ianaTz);

    $eventData = [
        'subject' => $subject,
        'start' => ['dateTime' => $start, 'timeZone' => $windowsTz],
        'end'   => ['dateTime' => $end,   'timeZone' => $windowsTz],
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
            'Authorization' => "Bearer $accessToken",
            'Content-Type' => 'application/json'
        ],
        'json' => $eventData
    ]);
}


// ✅ Send an email using the user's mailbox
function sendGraphEmail($fromEmail, $toEmail, $subject, $bodyText) {
    global $config;

    $client = new \GuzzleHttp\Client();
    $tokenData = getAccessToken($fromEmail);
    $accessToken = $tokenData['access_token'] ?? null;

    if (!$accessToken) {
        sendAdminEmail($toEmail, $subject, $bodyText);
        return;
    }

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

    try {
        // NOTE: double quotes so {$fromEmail} interpolates
        $client->post("https://graph.microsoft.com/v1.0/users/{$fromEmail}/sendMail", [
            'headers' => [
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json'
            ],
            'json' => $emailData
        ]);
        return;
    } catch (\Exception $e) {
        error_log("Graph email failed ({$fromEmail} -> {$toEmail}): " . $e->getMessage());
        sendAdminEmail($toEmail, $subject, $bodyText);
    }
}



function sendAdminEmail($toEmail, $subject, $bodyText) {
    global $config;
    $client = new \GuzzleHttp\Client();

    $postData = [
        'to'      => $toEmail,
        'from'    => $config['postal_from'],  // e.g. 'scheduling@example.com'
        'subject' => $subject,
        'plain_body' => $bodyText,
    ];

    try {
        $response = $client->post($config['postal_api_url'], [
            'headers' => [
                'X-Server-API-Key' => $config['postal_api_key'],
                'Accept' => 'application/json',
            ],
            'json' => $postData
        ]);
    } catch (Exception $e) {
        error_log("Postal send failed: " . $e->getMessage());
    }
}