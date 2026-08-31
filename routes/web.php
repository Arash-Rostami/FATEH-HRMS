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
use App\Livewire\Dashboard\Dms\Main as Dms;
use App\Livewire\Dashboard\Energy\Main as Energy;
use App\Livewire\Dashboard\Profile\Main as Profile;
use App\Livewire\Dashboard\Project\Main as Project;
use App\Livewire\Dashboard\Reservation\Main as Reservation;
use App\Livewire\Dashboard\Suggestion\Main as Suggestion;
use App\Livewire\Dashboard\Tabs;
use App\Livewire\Dashboard\TaskBoard\Main as TaskBoard;
use App\Livewire\Dashboard\Tasksheet\Main as Tasksheet;
use App\Livewire\Dashboard\Ths\Main as Ths;
use App\Http\Controllers\ToggleCalendarController;
use App\Models\Project as ProjectModel;
use App\Services\ProjectTask\ChannelProvisioner;
use App\Services\ProjectTask\ProjectHeartbeat;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect()->route('dashboard');
});


Route::get('/site.webmanifest', fn () => response()
    ->view('components.manifest')
    ->header('Content-Type', 'application/manifest+json; charset=utf-8')
    ->header('Cache-Control', 'no-store'))
    ->name('manifest');


Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
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

    Route::controller(Dms::class)->group(function () {
        Route::get('/authorized/{filename}', 'getAuthorizedFile')
            ->where('filename', '.*')
            ->name('secure-file');

        Route::get('/authorized-extra/{filename}', 'getAuthorizedExtraFile')
            ->where('filename', '.*')
            ->name('secure-extra-file');
    });

    Route::view('/coming', 'layouts.toCome')->name('coming');
    Route::get('/ping', fn() => response('', 204)->header('Cache-Control', 'no-store'));

    Route::get('/projects/{id}/pulse', function (int $id) {
        $project = ProjectModel::visibleTo(auth()->user())->find($id);
        abort_unless($project, 404);

        if (request()->query('tab') === 'teamChat') {
            $channel = app(ChannelProvisioner::class)->resolve($project);
            if (!$channel->memberUsers()->where('users.id', auth()->id())->exists()) {
                return response()->json(['gone' => true])->header('Cache-Control', 'no-store');
            }
        }

        return response()->json(ProjectHeartbeat::versions($project->id))->header('Cache-Control', 'no-store');
    })->name('projects.pulse');
});


