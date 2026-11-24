<?php

return [
    'gateway' => [
        'url' => env('AI_REPORT_API_URL'),
        'key' => env('AI_REPORT_API_KEY'),
        'model' => env('AI_REPORT_MODEL', 'Gemini-3-Flash-Preview'),
        'daily_cap' => 5,
        'cooldown' => 3,
        'timeout' => 45,
        'connect_timeout' => 10,
        'max_output' => 16384,
        'down_for' => 30,
        'retry_within' => 10,
    ],

];
