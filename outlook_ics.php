<?php
require 'vendor/autoload.php';

use Sabre\VObject;

function getBusyTimesFromICS($icsFilePath) {
    $busyTimes = [];

    if (!file_exists($icsFilePath)) {
        return $busyTimes;
    }

    $data = file_get_contents($icsFilePath);
    $vcalendar = VObject\Reader::read($data);

    $startRange = new DateTime('now', new DateTimeZone('America/Chicago'));
    $endRange = (clone $startRange)->modify('+30 days');

    foreach ($vcalendar->getComponents() as $component) {
        if ($component->name !== 'VEVENT') continue;

        try {
            $it = new VObject\Recur\EventIterator($vcalendar, $component->UID);
            $it->fastForward($startRange);

            while ($it->valid() && $it->getDTStart() < $endRange) {
                $start = $it->getDTStart();
                $end = $it->getDTEnd();
                $busyTimes[] = [
                    'start' => $start,
                    'end' => $end
                ];
                $it->next();
            }
        } catch (Exception $e) {
            // If not a recurring event, fallback to normal DTSTART/DTEND
            $start = $component->DTSTART->getDateTime();
            $end = $component->DTEND->getDateTime();
            if ($start < $endRange && $end > $startRange) {
                $busyTimes[] = [
                    'start' => $start,
                    'end' => $end
                ];
            }
        }
    }

    return $busyTimes;
}
