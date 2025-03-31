<?php
require_once 'event_types.php';
require_once 'availability.php';

$eventTypes = getEventTypes();
$selectedType = $_GET['type'] ?? null;

// If the selected type is missing or invalid
if (!$selectedType || !array_key_exists($selectedType, $eventTypes)) {
    $selectedType = null;
}

include 'header.php'; ?>
    <?php $config = require 'config.php'; ?>

    <div class="container text-center mt-4 mb-4">
        <p class="text-muted small">Schedule your meeting below</p>
    </div>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">

                <?php if (!$selectedType): ?>
                    <div class="text-center mb-4">
                        <h1 class="display-5">Choose Appointment Type</h1>
                        <p class="text-muted">Select the kind of appointment you'd like to book.</p>
                    </div>

                    <div class="list-group">
                        <?php foreach ($eventTypes as $key => $event): ?>
                            <a href="?type=<?= htmlspecialchars($key) ?>" class="list-group-item list-group-item-action">
                                <?= htmlspecialchars($event['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                <?php else: ?>
                    <?php
                        $eventLabel = $eventTypes[$selectedType]['label'];
                        $slots = getAvailableSlotsForEventType($selectedType);
                    ?>

                    <div class="text-center mb-4">
                        <h1 class="display-5">Book a <?= htmlspecialchars($eventLabel) ?></h1>
                        <p class="text-muted">Select your time, enter your details, and choose your platform.</p>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form method="POST" action="submit.php">
                                <input type="hidden" name="type" value="<?= htmlspecialchars($selectedType) ?>">

                                <div class="mb-3">
                                    <label class="form-label">Available Times</label>
                                    <div class="border rounded p-3 bg-white" style="max-height: 400px; overflow-y: auto;">
                                        <?php $slotId = 0; ?>
                                        <?php foreach ($slots as $date => $times): ?>
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

                                <button type="submit" class="btn btn-primary w-100">Book Appointment</button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <h5 class="mb-3">Other Appointment Types</h5>
                        <div class="list-group">
                            <?php foreach ($eventTypes as $key => $event): ?>
                                <?php if ($key !== $selectedType): ?>
                                    <a href="?type=<?= htmlspecialchars($key) ?>" class="list-group-item list-group-item-action">
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