<?php

function getEventTypes() {
    return [
        '30-lesson' => [
            'label' => 'Composition Lesson: 30 minute',
            'duration' => 30,
            'kind' => 'lesson',
            'platforms' => ['zoom','teams','in_person'],
            'buffer_before' => 0,
            'buffer_after' => 0,
            'min_notice_minutes' => 0,
        ],
        '60-lesson' => [
            'label' => 'Composition Lesson: 1 hour',
            'duration' => 60,
            'kind' => 'lesson',
            'platforms' => ['zoom','teams','in_person'],
            'buffer_before' => 0,
            'buffer_after' => 0,
            'min_notice_minutes' => 0,
        ],
        '15-meeting' => [
            'label' => 'Meeting: 15 minutes',
            'duration' => 15,
            'kind' => 'meeting',
            'platforms' => ['zoom','teams','in_person'],
            'buffer_before' => 0,
            'buffer_after' => 0,
            'min_notice_minutes' => 0,    
        ],
        '30-meeting' => [
            'label' => 'Meeting: 30 minutes',
            'duration' => 30,
            'kind' => 'meeting',
            'platforms' => ['zoom','teams','in_person'],
            'buffer_before' => 0,
            'buffer_after' => 0,
            'min_notice_minutes' => 0,
        ],
        '60-meeting' => [
            'label' => 'Meeting: 1 hour',
            'duration' => 60,
            'kind' => 'meeting',
            'platforms' => ['zoom','teams','in_person'],
            'buffer_before' => 0,
            'buffer_after' => 0,
            'min_notice_minutes' => 0,
        ]
    ];
} 
?>
