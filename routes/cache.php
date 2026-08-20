<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {

    Route::get('/clear', function () {
        abort_unless(auth()->user()->isDeveloper(), 403);

        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('optimize:clear');

        return response()->json([
            'message' => 'Caches cleared successfully!', 'timestamp' => now()->toDateTimeString(),
        ]);
    });

    Route::get('/cache', function () {
        abort_unless(auth()->user()->isDeveloper(), 403);

        Artisan::call('route:cache');
        Artisan::call('view:cache');
        Artisan::call('filament:cache-components');

        return response()->json([
            'message' => 'Caches set successfully!', 'timestamp' => now()->toDateTimeString()
        ]);
    });

    Route::get('/reset', function () {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isDeveloper(), 403);

        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('optimize:clear');
        sleep(1);
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        Artisan::call('filament:cache-components');

        if (function_exists('opcache_reset')) opcache_reset();

        return response()->json([
            'message' => 'DB memory refreshed!', 'timestamp' => now()->toDateTimeString(),
        ]);
    });
});
