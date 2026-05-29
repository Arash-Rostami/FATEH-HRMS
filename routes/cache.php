<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {

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
        Artisan::call('filament:cache-components');

        return response()->json([
            'message' => 'Caches set successfully!',
            'timestamp' => now()->toDateTimeString()
        ]);
    });
});
