<?php
require_once __DIR__ . '/bootstrap.php';
require_once 'event_types.php';
require_once 'outlook_graph.php';

$users = require 'users.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  include 'header.php';
  echo "<div class='container mt-5'><div class='alert alert-warning'>This page is only used to submit bookings. Please return to the scheduler.</div></div>";
  include 'footer.php';
  exit;
}


$username = isset($_POST['user']) ? strtolower($_POST['user']) : null;
if (!$username || !isset($users[$username])) {
    http_response_code(404);
    echo "User not found.";
    exit;
}
$userData = $users[$username];
$userEmail = $userData['email'] ?? null;
if (!$userEmail) {
    http_response_code(500);
    echo "User misconfigured.";
    exit;
}

$userLabel = $userData['label'] ?? $username;
$userTypes = $userData['types'] ?? [];

$eventTypes = getEventTypes();

$errors = [];


// Sanitize input
$type = isset($_POST['type']) ? strtolower($_POST['type']) : null;
$slot = $_POST['slot'] ?? null;
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$platform = $_POST['platform'] ?? 'in_person';
$exp = isset($_POST['exp']) ? (int)$_POST['exp'] : 0;
$sig = $_POST['sig'] ?? '';

if (!$type || !isset($eventTypes[$type])) {
    $errors[] = "Invalid appointment type.";
}

if (!$type || !in_array($type, $userTypes,true)) {
    $errors[] = "Invalid appointment type for this user.";
}

$slotStart = null;
if (!$slot) {
    $errors[] = "Invalid time slot.";
} else {
    try {
        $slotStart = new DateTimeImmutable($slot); // expects ISO/RFC3339
    } catch (Exception $e) {
        $errors[] = "Invalid time slot.";
    }
}

if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please provide a valid name and email.";
}

if (!in_array($platform, ['zoom', 'teams', 'in_person'],true)) {
    $errors[] = "Invalid meeting platform.";
}

$signKey = $config['slot_signing_key'] ?? null;
if (!$signKey) {
  http_response_code(500);
  echo "Server misconfigured.";
  exit;
}

$now = time();
if (!$exp || $exp < $now - 30) {
  $errors[] = "Booking session expired. Please refresh and try again.";
}

if (!$sig || !preg_match('/^[a-f0-9]{64}$/', $sig)) {
  $errors[] = "Invalid booking signature.";
}

$payload = $username . '|' . $type . '|' . $slot . '|' . $exp;
$expected = hash_hmac('sha256', $payload, $signKey);

if (!hash_equals($expected, $sig)) {
  $errors[] = "Invalid booking signature.";
}

if ($errors) {
    include 'header.php';
    echo "<div class='container mt-5'>";
    foreach ($errors as $error) {
        echo "<div class='alert alert-danger' aria-live='assertive'>" . htmlspecialchars($error) . "</div>";
    }
    echo "<a href='/" . urlencode($username ?? '') . "' class='btn btn-outline-primary mt-3'>Back to booking</a>";
    echo "</div>";
    include 'footer.php';
    exit;
}

// Convert slot to DateTime objects
$userTzName = $userData['timezone'] ?? ($config['timezone'] ?? 'America/Chicago');
$tz = new DateTimeZone($userTzName);

$viewerTzName = $_POST['viewer_tz'] ?? null;
$displayTz = $tz;
if ($viewerTzName) { try { $displayTz = new DateTimeZone($viewerTzName); } catch (Exception $e) {} }

$slotDisplay = $slotStart->setTimezone($displayTz)->format('l, F j, g:i a T');

$event = $eventTypes[$type];
$slotEnd = $slotStart->modify("+{$event['duration']} minutes");

// Final check: make sure slot isn't already booked
$busy = getBusyTimesFromGraph($slotStart->format(DateTime::ATOM), $slotEnd->format(DateTime::ATOM), $userEmail);


foreach ($busy as $b) {
    $bs = $b['start'] instanceof DateTimeInterface ? $b['start'] : new DateTimeImmutable($b['start']);
    $be = $b['end']   instanceof DateTimeInterface ? $b['end']   : new DateTimeImmutable($b['end']);

    if ($slotStart < $be && $slotEnd > $bs) {
        echo "<div class='container mt-5'>";
        echo "<div class='alert alert-danger' aria-live='assertive'>That time is no longer available. Please choose a different slot.</div>";
        echo "<a href='/$username/$type' class='btn btn-primary mt-2'>Return to booking</a>";
        echo "</div>";
        exit;
    }
}

$guestNameSafe = preg_replace('/[\r\n\t]+/', ' ', $name);
$guestNameSafe = trim($guestNameSafe);
$subject = sprintf('%s — %s with %s', $event['label'], $guestNameSafe, $userLabel);
// Build event times in the HOST timezone (wall clock)
$slotStartHost = $slotStart->setTimezone($tz);
$slotEndHost   = $slotEnd->setTimezone($tz);

// Graph wants a "local" datetime string + a Windows timezone ID
$startLocal = $slotStartHost->format('Y-m-d\TH:i:s');
$endLocal   = $slotEndHost->format('Y-m-d\TH:i:s');

createGraphEvent(
    $subject,
    $startLocal,
    $endLocal,
    $email,
    $name,
    $platform,
    $userEmail,
    $userTzName   // pass host IANA tz so we can map to Windows inside the function
);

// Send confirmation email to you
$adminEmail = $userData['email'] ?? $userEmail;
if ($adminEmail) {
    $platformLabels = [
        'zoom' => 'Zoom Meeting',
        'teams' => 'Microsoft Teams',
        'in_person' => $config['in_person_location'] ?? 'In Person'
    ];
    $platformDisplay = $platformLabels[$platform] ?? ucfirst($platform);

    $bodyText = <<<EOD
New appointment booked:

Type: {$event['label']}
Time: {$slotDisplay}
Attendee: {$name} <{$email}>
Platform: {$platformDisplay}
EOD;

    //sendGraphEmail($adminEmail, "New {$event['label']} Booked", $bodyText);
    sendGraphEmail(
        $userEmail,                       // from this user's mailbox
        $adminEmail,                     // send to you (the host)
        "New {$event['label']} Booked",  // subject
        $bodyText                        // body
    );
}

// Confirmation screen
$appName = $config['app_name'] ?? 'Ives';
$appLogo = $config['app_logo'] ?? null;
$platformLabels = [
    'zoom' => 'Zoom Meeting',
    'teams' => 'Microsoft Teams',
    'in_person' => $config['in_person_location'] ?? 'In Person'
];
$platformDisplay = $platformLabels[$platform] ?? ucfirst($platform);

include 'header.php'; ?>
    <div class="container mt-5 text-center">
       <div class="alert alert-success shadow-sm" aria-live="polite">
            <h2 class="mb-3">You're booked!</h2>
            <p class="lead">
                You have scheduled a <strong><?= htmlspecialchars($event['label']) ?></strong><br>
                on <strong><?= htmlspecialchars($slotDisplay) ?></strong><br>
                via <strong><?= htmlspecialchars($platformDisplay) ?></strong>.
            </p>
            <p>You’ll receive an email invitation shortly.</p>
        </div>

        <a href="/<?= urlencode($username) ?>/<?= urlencode($type) ?>" class="btn btn-outline-primary mt-3">Book Another Appointment</a>
        <br>
        <a href="/<?= urlencode($username) ?>" class="text-muted d-block mt-2">Return to Home</a>
    </div>
    <?php include 'footer.php'; ?>
