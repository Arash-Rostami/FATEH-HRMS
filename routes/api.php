<?php

use Illuminate\Support\Facades\Route;

Route::get('/module-features', fn () => response()->file(config_path('module-features.json'), [
    'Content-Type' => 'application/json',
    'Cache-Control' => 'public, max-age=300',
]))->name('module-features');