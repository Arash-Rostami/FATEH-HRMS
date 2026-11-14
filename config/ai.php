<?php

return [
    'gateway' => [
        'url' => env('AI_REPORT_API_URL'),
        'key' => env('AI_REPORT_API_KEY'),
        'model' => env('AI_REPORT_MODEL', 'Gemini-3-Flash-Preview'),
        'timeout' => 60,
        'connect_timeout' => 10,
        'max_output' => 16384,
    ],

];
