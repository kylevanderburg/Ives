<?php
require_once 'event_types.php';
require_once 'availability.php';
error_reporting(E_ALL);
ini_set('display_errors', '1');
$users = require 'users.php';
$config = require 'config.php';

$username = $_GET['user'] ?? null;
$userData = $users[$username] ?? null;
$userEmail = $userData['email'] ?? null;
$userLabel = $userData['label'] ?? $username;
$userTypes = $userData['types'] ?? [];

$allEventTypes = getEventTypes();

// Filter only allowed event types for this user
$eventTypes = array_filter($allEventTypes, fn($key) => in_array($key, $userTypes), ARRAY_FILTER_USE_KEY);

$selectedType = $_GET['type'] ?? null;
if (!in_array($selectedType, array_keys($eventTypes))) {
    $selectedType = null;
}

include 'header.php'; ?>
    <?php $config = require 'config.php'; ?>

    <div class="container text-center mt-4 mb-4">
        <p class="text-muted small">Schedule your meeting below</p>
    </div>
    <div class="container py-2">
        <div class="row justify-content-center">
            <div class="col-lg-6">

                <?php if (!$selectedType) :
                    echo "<div class='text-center mb-4'>";
                    echo "<h1 class='display-4'>{$userLabel}</h1>";
                    echo "<h2 class='display-5'>Choose Appointment Type</h2>";
                    echo "<p class='text-muted'>Select the kind of appointment you'd like to book.</p>";
                    echo "</div>";

                    if (empty($eventTypes)) {
                        echo "<div class='alert alert-warning'>No appointment types available.</div>";
                    } else {
                        echo "<div class='list-group'>";
                        foreach ($eventTypes as $key => $event) {
                            echo "<a href='/" . urlencode($username) . "/" . urlencode($key) . "' class='list-group-item list-group-item-action'>";
                            echo htmlspecialchars($event['label']);
                            echo "</a>";
                        }
                        echo "</div>";
                    }

                    include 'footer.php';
                    exit;
                else: ?>
                    <?php
                        $eventLabel = $eventTypes[$selectedType]['label'];
                        $slots = getAvailableSlotsForEventType($selectedType, $userEmail);
                    ?>

                    <div class="text-center mb-4">
                        <h1 class="display-5">Book a <?= htmlspecialchars($eventLabel) ?> with <?= htmlspecialchars($userLabel) ?></h1>
                        <p class="text-muted">Select your time, enter your details, and choose your platform.</p>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form method="POST" action="/submit.php">
                                <input type="hidden" name="type" value="<?= htmlspecialchars($selectedType) ?>">

                                <div class="mb-3">
                                    <label class="form-label">Available Times</label>
                                    <div class="border rounded p-3 bg-white" style="max-height: 400px; overflow-y: auto;">
                                        <?php $slotId = 0; ?>
                                        <?php ksort($slots);
                                        foreach ($slots as $date => $times): ?>
                                            <?php if (count($times) > 0): ?>
                                                <div class="mb-3">
                                                <strong><?= (new DateTime($date))->format('l, F j') ?></strong><br>
                                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                                        <?php foreach ($times as $time): ?>
                                                            <?php
                                                                $id = 'slot' . $slotId++;
                                                                $display = (new DateTime($time))->format('g:i a');
                                                            ?>
                                                            <input type="radio" class="btn-check" name="slot" id="<?= $id ?>" value="<?= htmlspecialchars($time) ?>" required>
                                                            <label class="btn btn-outline-primary btn-sm" for="<?= $id ?>">
                                                                <?= $display ?>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>


                                <div class="mb-3">
                                    <label for="name" class="form-label">Your Name</label>
                                    <input type="text" name="name" id="name" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Your Email</label>
                                    <input type="email" name="email" id="email" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label for="platform" class="form-label">Meeting Platform</label>
                                    <select name="platform" id="platform" class="form-select" required>
                                        <option value="in_person">In Person</option>
                                        <option value="zoom">Zoom</option>
                                        <option value="teams">Microsoft Teams</option>
                                    </select>
                                </div>
                                <input type="hidden" name="user" value="<?= htmlspecialchars($username) ?>">
                                <button type="submit" class="btn btn-primary w-100">Book Appointment</button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <h5 class="mb-3">Other Appointment Types</h5>
                        <div class="list-group">
                            <?php foreach ($eventTypes as $key => $event): ?>
                                <?php if ($key !== $selectedType): ?>
                                    <a href="/<?= urlencode($username) ?>/<?= urlencode($key) ?>" class="list-group-item list-group-item-action">
                                        <?= htmlspecialchars($event['label']) ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>