<?php
require_once 'event_types.php';
require_once 'outlook_graph.php';

$config = require 'config.php';
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
    foreach ($errors as $error) {
        echo "<p style='color:red;'>$error</p>";
    }
    echo "<a href='index.php'>Back to booking</a>";
    exit;
}

// Convert slot to DateTime objects
$slotStart = DateTime::createFromFormat('Y-m-d g:i a', $slot, new DateTimeZone('America/Chicago'));
$event = $eventTypes[$type];
$slotEnd = (clone $slotStart)->modify("+{$event['duration']} minutes");

// Final check: make sure slot isn't already booked
$busy = getBusyTimesFromGraph($slotStart->format(DateTime::ATOM), $slotEnd->format(DateTime::ATOM));
foreach ($busy as $b) {
    if ($slotStart < $b['end'] && $slotEnd > $b['start']) {
        echo "<div class='container mt-5'>";
        echo "<div class='alert alert-danger'>That time is no longer available. Please choose a different slot.</div>";
        echo "<a href='index.php?type=$type' class='btn btn-primary mt-2'>Return to booking</a>";
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
    $platform
);

// Send confirmation email to you
$adminEmail = $config['notification_email'] ?? null;
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

    sendGraphEmail($adminEmail, "New {$event['label']} Booked", $bodyText);
}

// Confirmation screen
$appName = $config['app_name'] ?? 'MeetSheet';
$appLogo = $config['app_logo'] ?? null;
$platformLabels = [
    'zoom' => 'Zoom Meeting',
    'teams' => 'Microsoft Teams',
    'in_person' => $config['in_person_location'] ?? 'In Person'
];
$platformDisplay = $platformLabels[$platform] ?? ucfirst($platform);

include 'header.php'; ?>
    <div class="container mt-5 text-center">
        <?php if ($appLogo): ?>
            <img src="<?= htmlspecialchars($appLogo) ?>" alt="Logo" style="max-height: 80px;" class="mb-3">
        <?php endif; ?>
        <h1 class="mb-4"><?= htmlspecialchars($appName) ?></h1>

        <div class="alert alert-success shadow-sm">
            <h2 class="mb-3">You're booked!</h2>
            <p class="lead">
                You have scheduled a <strong><?= htmlspecialchars($event['label']) ?></strong><br>
                on <strong><?= htmlspecialchars($slot) ?></strong><br>
                via <strong><?= htmlspecialchars($platformDisplay) ?></strong>.
            </p>
            <p>You’ll receive an email invitation shortly.</p>
        </div>

        <a href="index.php?type=<?= urlencode($type) ?>" class="btn btn-outline-primary mt-3">Book Another Appointment</a>
        <br>
        <a href="index.php" class="text-muted d-block mt-2">Return to Home</a>
    </div>
    <?php include 'footer.php'; ?>
