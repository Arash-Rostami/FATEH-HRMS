<?php

return [
    'mimes' => [
        'audio' => ['mp3', 'm4a', 'wav', 'ogg'],
        'video' => ['mp4', 'webm'],
    ],

    'track_reports' => filter_var(env('MEDIA_TRACK_REPORTS', false), FILTER_VALIDATE_BOOLEAN),

    'completion_ratio' => 0.95,

    'position_tolerance' => 5,

    'notes_max' => 100,

    'new_days' => 30,

    'unknown_duration_advance' => 600,

    'sync' => [
        'local_seconds' => 5,
        'safety_seconds' => 90,
        'min_delta' => 10,
        'prebuffer_remaining_seconds' => 120,
        'resume_rewind' => 10,
    ],
];
