<?php

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\VerifyNotice;
use App\Livewire\Dashboard\Ads\Main as Ads;
use App\Livewire\Dashboard\Analytics\Main as Analytics;
use App\Livewire\Dashboard\Authority\Main as Authority;
use App\Livewire\Dashboard\Contact\Main as Contact;
use App\Livewire\Dashboard\Channel\Main as Channel;
use App\Livewire\Dashboard\Correspondence\Main as Correspondence;
use App\Livewire\Dashboard\Dms\Main as Dms;
use App\Livewire\Dashboard\Energy\Main as Energy;
use App\Livewire\Dashboard\Launchpad\Main as Launchpad;
use App\Livewire\Dashboard\Knowledge\Main as Knowledge;
use App\Livewire\Dashboard\Profile\Main as Profile;
use App\Livewire\Dashboard\AiReport\Main as AiReport;
use App\Livewire\Dashboard\Project\Main as Project;
use App\Livewire\Dashboard\Reservation\Main as Reservation;
use App\Livewire\Dashboard\Suggestion\Main as Suggestion;
use App\Livewire\Dashboard\Tabs;
use App\Livewire\Dashboard\TaskBoard\Main as TaskBoard;
use App\Livewire\Dashboard\Tasksheet\Main as Tasksheet;
use App\Livewire\Dashboard\Ths\Main as Ths;
use App\Http\Controllers\CorrespondenceLinkController;
use App\Http\Controllers\CorrespondenceAttachmentController;
use App\Http\Controllers\CorrespondencePdfController;
use App\Http\Controllers\IconMissController;
use App\Http\Controllers\LaunchpadFileController;
use App\Http\Controllers\LaunchpadGoController;
use App\Http\Controllers\MediaNoteController;
use App\Http\Controllers\MediaProgressController;
use App\Http\Middleware\UpdateLastSeen;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Controllers\ProjectPulseController;
use App\Http\Controllers\ToggleCalendarController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect()->route('dashboard');
});


Route::get('/site.webmanifest', fn () => response()
    ->view('components.manifest')
    ->header('Content-Type', 'application/manifest+json; charset=utf-8')
    ->header('Cache-Control', 'no-store'))
    ->name('manifest');


Route::post('/icon-miss', IconMissController::class)->middleware('throttle:icon-miss')->name('icon.miss');

Route::middleware(['letter.link', 'throttle:letter-link'])
    ->withoutMiddleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        ValidateCsrfToken::class,
        UpdateLastSeen::class,
    ])
    ->controller(CorrespondenceLinkController::class)
    ->where(['id' => '[1-9][0-9]{0,9}', 'token' => '[A-Za-z0-9_-]{43}'])
    ->group(function () {
        Route::get('/c/{id}-{token}', 'show')->name('correspondence.link');
        Route::get('/c/{id}-{token}/pdf', 'pdf')->name('correspondence.link.pdf');
    });

Route::middleware('throttle:launchpad-file')
    ->withoutMiddleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        ValidateCsrfToken::class,
        UpdateLastSeen::class,
    ])
    ->where(['launchpad' => '[1-9][0-9]{0,9}', 'token' => '[A-Za-z0-9_-]{20,80}', 'file' => '[^/]+'])
    ->get('/launchpad/{launchpad}/{token}/{file}', LaunchpadFileController::class)
    ->name('launchpad.file');

Route::post('/media/progress', MediaProgressController::class)->middleware(['throttle:media-progress', 'tenant.on:knowledge:media'])->name('media.progress');
Route::post('/media/notes', MediaNoteController::class)->middleware(['throttle:media-notes', 'tenant.on:knowledge:media'])->name('media.notes');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::middleware(['guest', 'tenant.on:password-reset'])->group(function () {
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/email/verify', VerifyNotice::class)->name('verification.notice');
    Route::get('/toggle-calendar', ToggleCalendarController::class);
    Route::get('/dashboard', Tabs::class)->name('dashboard');
    Route::get('/profile', Profile::class)->name('profile');
    Route::get('/tasks', TaskBoard::class)->name('tasks');
    Route::get('/projects', Project::class)->name('projects');
    Route::get('/tasksheet', Tasksheet::class)->name('tasksheet');
    Route::get('/tasksheet/shared/{subject}', Tasksheet::class)->middleware('signed')->name('tasksheet.shared');
    Route::get('/launchpad', Launchpad::class)->name('launchpad');
    Route::get('/knowledge', Knowledge::class)->name('knowledge');
    Route::get('/media', fn (Request $request) => redirect()->route('knowledge', ['tab' => 'media'] + $request->only('open', 't'), 302));
    Route::get('/launchpad/{launchpad}/go', LaunchpadGoController::class)->middleware('throttle:30,1')->where('launchpad', '[1-9][0-9]{0,9}')->name('launchpad.go');
    Route::get('/ai-report', AiReport::class)->name('ai-report');
    Route::get('/ai-report/shared/{report}', AiReport::class)->middleware('signed')->name('ai-report.shared');
    Route::get('/correspondence', Correspondence::class)->name('correspondence');
    Route::get('/dms', Dms::class)->name('dms');
    Route::get('/ths', Ths::class)->name('ths');
    Route::get('/ads', Ads::class)->name('ads');
    Route::get('/suggestion', Suggestion::class)->name('suggestion');
    Route::get('/authority', Authority::class)->name('authority');
    Route::get('/contacts', Contact::class)->name('contact');
    Route::get('/channels', Channel::class)->name('channels');
    Route::get('/energy', Energy::class)->name('energy');
    Route::get('/analytics', Analytics::class)->name('analytics');
    Route::get('/reservation', Reservation::class)->name('reservation');
    Route::get('/correspondence/{id}/pdf', CorrespondencePdfController::class)->whereNumber('id')->name('correspondence.pdf');
    Route::get('/correspondence/{id}/attachment/{index}', CorrespondenceAttachmentController::class)->whereNumber(['id', 'index'])->name('correspondence.attachment');

    Route::controller(Dms::class)->group(function () {
        Route::get('/authorized/{filename}', 'getAuthorizedFile')
            ->where('filename', '.*')
            ->name('dms.file');

        Route::get('/authorized-extra/{filename}', 'getAuthorizedExtraFile')
            ->where('filename', '.*')
            ->name('dms.extra-file');
    });

    Route::view('/coming', 'layouts.toCome')->name('coming');
    Route::get('/ping', fn() => response('', 204)->header('Cache-Control', 'no-store'));

    Route::get('/projects/{id}/pulse', ProjectPulseController::class)->whereNumber('id')->name('projects.pulse');
});


