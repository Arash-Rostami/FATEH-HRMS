<?php

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\LogoutButton;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\VerifyNotice;
use App\Livewire\Dashboard\Dms\Main as Dms;
use App\Livewire\Dashboard\Profile\Main as Profile;
use App\Livewire\Dashboard\Tab\Main;
use App\Livewire\Dashboard\Taskboard\Main as TaskBoard;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect()->route('dashboard');
});


Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/logout', LogoutButton::class)->name('logout');
    Route::get('/register', Register::class)->name('register');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/email/verify', VerifyNotice::class)->name('verification.notice');
    Route::get('/dashboard', Main::class)->name('dashboard');
    Route::get('/profile', Profile::class)->name('profile');
    Route::get('/tasks', TaskBoard::class)->name('tasks');
    Route::get('/dms', Dms::class)->name('dms');
    Route::get('/authorized/{filename}', function ($filename) { return response()->file(storage_path("app/private/dms/" . $filename)); })->where('filename', '.*')->name('secure-file');
    Route::view('/coming', 'layouts.toCome')->name('coming');


});


