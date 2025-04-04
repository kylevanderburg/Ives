<?php
require_once 'event_types.php';
require_once 'outlook_graph.php';
$config = require __DIR__ . '/config.php';

date_default_timezone_set('America/Chicago');
define('IVES_TIMEZONE', 'America/Chicago');

function getAvailableSlotsForEventType($eventTypeKey, $userEmail) {
    $eventTypes = getEventTypes();
    $duration = $eventTypes[$eventTypeKey]['duration'] ?? 30;

    $startDate = new DateTime('now', new DateTimeZone(IVES_TIMEZONE));
    $endDate = (clone $startDate)->modify('+30 days');

    // Step 1: Generate static availability
    $slots = generateTimeSlots($startDate, $endDate, $duration);

    // Step 2: Get busy times from Outlook (Microsoft Graph)
    $busyTimes = getBusyTimesFromGraph(
        $startDate->format(DateTime::ATOM),
        $endDate->format(DateTime::ATOM),
        $userEmail
    );

    // Step 3: Filter out overlapping slots
    $filtered = filterSlotsAgainstBusyTimes($slots, $busyTimes, $duration);

    return $filtered;
}

// Generate time slots for weekdays only
function generateTimeSlots(DateTime $start, DateTime $end, int $duration) {
    global $config;
    $slots = [];
    $now = new DateTime('now', new DateTimeZone(IVES_TIMEZONE));

    for ($day = clone $start; $day <= $end; $day->modify('+1 day')) {
        // Skip weekends
        if (in_array($day->format('N'), [6, 7])) {
            continue;
        }

        $date = $day->format('Y-m-d');
        $dailySlots = [];

        for ($hour = $config['workday_start_hour']; $hour < $config['workday_end_hour']; $hour++) {
            for ($minute = 0; $minute < 60; $minute += $duration) {
                $slot = DateTime::createFromFormat(
                    'Y-m-d H:i',
                    "$date " . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minute, 2, '0', STR_PAD_LEFT),
                    new DateTimeZone(IVES_TIMEZONE)
                );

                // Skip past times today
                if ($date === $now->format('Y-m-d') && $slot < $now) {
                    continue;
                }

                if ($slot !== false) {
                    $dailySlots[] = $slot->format('Y-m-d g:i a');
                }
            }
        }

        $slots[$date] = $dailySlots;
    }

    return $slots;
}

// Remove any slots that overlap with existing Outlook events
function filterSlotsAgainstBusyTimes($slots, $busyTimes, $duration) {
    foreach ($slots as $date => &$dailySlots) {
        $dailySlots = array_filter($dailySlots, function ($slot) use ($busyTimes, $duration) {
            $slotStart = DateTime::createFromFormat('Y-m-d g:i a', $slot, new DateTimeZone(IVES_TIMEZONE));
            $slotEnd = (clone $slotStart)->modify("+$duration minutes");

            foreach ($busyTimes as $busy) {
                if ($slotStart < $busy['end'] && $slotEnd > $busy['start']) {
                    return false; // Overlaps
                }
            }

            return true;
        });
    }

    return $slots;
}