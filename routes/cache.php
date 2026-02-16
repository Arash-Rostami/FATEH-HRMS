<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');

    return response()->json([
        'message' => 'Caches cleared successfully!',
        'timestamp' => now()->toDateTimeString(),
    ]);
});


Route::get('/cache', function () {
    Artisan::call('route:cache');
    Artisan::call('view:cache');

    return response()->json([
        'message' => 'Caches set successfully!',
        'timestamp' => now()->toDateTimeString()
    ]);
});

