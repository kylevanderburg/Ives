<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'event_types.php';
require_once 'outlook_graph.php';

$config = require 'config.php';
$users = require 'users.php';

$username = $_POST['user'] ?? null;
$userData = $users[$username] ?? null;
$userEmail = $userData['email'] ?? null;
$userLabel = $userData['label'] ?? $username;
$userTypes = $userData['types'] ?? [];

$eventTypes = getEventTypes();

$errors = [];

// Sanitize input
$type = $_POST['type'] ?? null;
$slot = $_POST['slot'] ?? null;
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$platform = $_POST['platform'] ?? 'in_person';

if (!$type || !isset($eventTypes[$type])) {
    $errors[] = "Invalid appointment type.";
}

if (!$type || !in_array($type, $userTypes)) {
    $errors[] = "Invalid appointment type for this user.";
}

if (!$slot || !DateTime::createFromFormat('Y-m-d g:i a', $slot)) {
    $errors[] = "Invalid time slot.";
}

if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please provide a valid name and email.";
}

if (!in_array($platform, ['zoom', 'teams', 'in_person'])) {
    $errors[] = "Invalid meeting platform.";
}

if ($errors) {
    include 'header.php';
    echo "<div class='container mt-5'>";
    foreach ($errors as $error) {
        echo "<div class='alert alert-danger'aria-live='assertive'>$error</div>";
    }
    echo "<a href='schedule.php' class='btn btn-outline-primary mt-3'>Back to booking</a>";
    echo "</div>";
    include 'footer.php';
    exit;
}

// Convert slot to DateTime objects
$slotStart = DateTime::createFromFormat('Y-m-d g:i a', $slot, new DateTimeZone('America/Chicago'));
$event = $eventTypes[$type];
$slotEnd = (clone $slotStart)->modify("+{$event['duration']} minutes");

// Final check: make sure slot isn't already booked
$busy = getBusyTimesFromGraph($slotStart->format(DateTime::ATOM), $slotEnd->format(DateTime::ATOM), $userEmail);

foreach ($busy as $b) {
    if ($slotStart < $b['end'] && $slotEnd > $b['start']) {
        echo "<div class='container mt-5'>";
        echo "<div class='alert alert-danger' aria-live='assertive'>That time is no longer available. Please choose a different slot.</div>";
        echo "<a href='/$username/$type' class='btn btn-primary mt-2'>Return to booking</a>";
        echo "</div>";
        exit;
    }
}

// Book the appointment
createGraphEvent(
    $event['label'],
    $slotStart->format(DateTime::ATOM),
    $slotEnd->format(DateTime::ATOM),
    $email,
    $name,
    $platform,
    $userEmail
);


// Send confirmation email to you
// $adminEmail = $config['notification_email'] ?? null;
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
Time: {$slot}
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
                on <strong><?= htmlspecialchars($slot) ?></strong><br>
                via <strong><?= htmlspecialchars($platformDisplay) ?></strong>.
            </p>
            <p>You’ll receive an email invitation shortly.</p>
        </div>

        <a href="/<?= urlencode($username) ?>/<?= urlencode($type) ?>" class="btn btn-outline-primary mt-3">Book Another Appointment</a>
        <br>
        <a href="/<?= urlencode($username) ?>" class="text-muted d-block mt-2">Return to Home</a>
    </div>
    <?php include 'footer.php'; ?>
