<?php
require_once __DIR__ . '/bootstrap.php';
require_once 'event_types.php';
require_once 'availability.php';

header('Cache-Control: no-store');

$users = require 'users.php';

$username = isset($_GET['user']) ? strtolower($_GET['user']) : null;
if (!$username || !isset($users[$username])) {
  http_response_code(404);
  echo "User not found.";
  exit;
}

$userData  = $users[$username];
$userEmail = $userData['email'] ?? null;
$userLabel = $userData['label'] ?? $username;
$userTypes = $userData['types'] ?? [];

if (!$userEmail) {
  http_response_code(500);
  echo "User misconfigured.";
  exit;
}

// ✅ NOW it's safe to use $userData
$userTzName = $userData['timezone'] ?? ($config['timezone'] ?? 'America/Chicago');
$tz  = new DateTimeZone($userTzName);
$utc = new DateTimeZone('UTC');

$viewerTzName = $_GET['tz'] ?? null;
$displayTz = $tz; // default host tz

if ($viewerTzName) {
try { $displayTz = new DateTimeZone($viewerTzName); } catch (Exception $e) { /* ignore */ }
}

$username = isset($_GET['user']) ? strtolower($_GET['user']) : null;
if (!$username || !isset($users[$username])) {
  http_response_code(404);
  echo "User not found.";
  exit;
}
$userData = $users[$username] ?? null;
$userEmail = $userData['email'] ?? null;
$userLabel = $userData['label'] ?? $username;
$userTypes = $userData['types'] ?? [];

if (!$userEmail) {
  http_response_code(500);
  echo "User misconfigured.";
  exit;
}

$allEventTypes = getEventTypes();

// Filter only allowed event types for this user
$eventTypes = array_filter($allEventTypes, fn($key) => in_array($key, $userTypes,true), ARRAY_FILTER_USE_KEY);

$selectedType = $_GET['type'] ?? null;
if (!$selectedType || !isset($eventTypes[$selectedType])) {
    $selectedType = null;
}


include 'header.php'; ?>
<?php if ($selectedType): ?>
<script>
(() => {
  const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
  const url = new URL(window.location.href);

  // If tz already present and same, do nothing
  if (url.searchParams.get('tz') === tz) return;

  // Set tz param and reload once
  url.searchParams.set('tz', tz);
  window.location.replace(url.toString());
})();
</script>
<?php endif; ?>
    <div class="container text-center mt-4 mb-4">
        <p class="text-muted small">Schedule your meeting below</p>
    </div>
    <div class="container py-2">
        <div class="row justify-content-center">
            <div class="col-lg-6">

                <?php if (!$selectedType) :
                    echo "<div class='text-center mb-4'>";
                    echo "<h1 class='display-4'>" . htmlspecialchars($userLabel) . "</h1>";
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
                        $slots = getAvailableSlotsForEventType($selectedType, $userEmail, $userTzName);

                        $slotsByViewerDay = [];

                        foreach ($slots as $date => $times) {
                            foreach ($times as $time) {
                                // $time already includes +00:00, so it's authoritative UTC
                                $dtUtc  = new DateTimeImmutable($time);

                                // Group by viewer day
                                $dayKey = $dtUtc->setTimezone($displayTz)->format('Y-m-d');

                                // Store canonical UTC ISO (for POST + signing)
                                $slotsByViewerDay[$dayKey][] = $dtUtc->format(DateTime::ATOM);
                            }
                        }

                        ksort($slotsByViewerDay);
                        foreach ($slotsByViewerDay as &$times) {
                            sort($times); // ISO UTC strings sort correctly
                        }
                        unset($times);

                        $signKey = $config['slot_signing_key'] ?? null;
                        if (!$signKey) {
                        throw new RuntimeException("Missing slot_signing_key in config");
                        }
                        $exp = time() + 20 * 60; // 20 minutes
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
                                    <p class="text-muted small mb-2">
                                        Times shown in <strong><?= htmlspecialchars($displayTz->getName()) ?></strong>
                                        (<?= htmlspecialchars((new DateTimeImmutable('now', $displayTz))->format('T')) ?>)
                                    </p>

                                    <div class="border rounded p-3 bg-white position-relative" style="max-height: 400px; overflow-y: auto;">
                                        <?php $slotId = 0; ?>
                                        <?php 
                                        foreach ($slotsByViewerDay as $date => $times): ?>
                                            <?php if (count($times) > 0): ?>
                                                <div class="mb-3">
                                                <strong><?= (new DateTimeImmutable($date, $displayTz))->format('l, F j') ?></strong><br>
                                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                                        <?php foreach ($times as $time): ?>
                                                        <?php
                                                            $dt = new DateTimeImmutable($time); // UTC already
                                                            $display = $dt->setTimezone($displayTz)->format('g:i a');
                                                            $value = $dt->format(DateTime::ATOM); // canonical UTC for POST/signing

                                                            $id = 'slot' . $slotId++;
                                                            //To sign the slot
                                                            $payload = $username . '|' . $selectedType . '|' . $value . '|' . $exp;
                                                            $sig = hash_hmac('sha256', $payload, $signKey);
                                                        ?>
                                                        <input type="radio" class="btn-check slot-radio" name="slot"
                                                                id="<?= htmlspecialchars($id) ?>"
                                                                value="<?= htmlspecialchars($value) ?>"
                                                                data-sig="<?= htmlspecialchars($sig) ?>" required>
                                                        <label class="btn btn-outline-primary btn-sm" for="<?= htmlspecialchars($id) ?>">
                                                            <?= htmlspecialchars($display) ?>
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
                                <input type="hidden" name="exp" value="<?= htmlspecialchars((string)$exp) ?>">
                                <input type="hidden" name="sig" id="sig" value="">
                                <input type="hidden" name="viewer_tz" id="viewer_tz" value="">
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
    <script>
        document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('slot-radio')) {
            document.getElementById('sig').value = e.target.dataset.sig || '';
        }
        });
        document.addEventListener('DOMContentLoaded', () => {
        const checked = document.querySelector('input.slot-radio[name="slot"]:checked');
        if (checked) document.getElementById('sig').value = checked.dataset.sig || '';
        });
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('viewer_tz').value = Intl.DateTimeFormat().resolvedOptions().timeZone;
        });

    </script>
<?php include 'footer.php'; ?>