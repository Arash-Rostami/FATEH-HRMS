<?php

use App\Livewire\Dashboard\Launchpad\Actions\RefreshLaunchpadAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune')
    ->daily()
    ->at('01:10')
    ->withoutOverlapping()
    ->onFailure(fn () => Log::error('schedule failed: model:prune'));

Schedule::command('queue:prune-failed --hours=720')
    ->daily()
    ->at('01:40')
    ->withoutOverlapping()
    ->onFailure(fn () => Log::error('schedule failed: queue:prune-failed'));

Schedule::command('suggestions:auto-resolve-stale')
    ->hourlyAt(45)
    ->withoutOverlapping();

Schedule::command('notifications:prune-stale')
    ->daily()
    ->at('02:05')
    ->withoutOverlapping()
    ->onFailure(fn () => Log::error('schedule failed: notifications:prune-stale'));

Schedule::command('tasks:sweep-overdue')
    ->when(fn () => !tenantOff('taskboard'))
    ->hourlyAt(15)
    ->between('06:00', '22:16')
    ->withoutOverlapping();

Schedule::command('reminders:sweep-due')
    ->when(fn () => !tenantOff('reminder'))
    ->hourly()
    ->withoutOverlapping();

Schedule::command('tasks:archive-stale-done')
    ->daily()
    ->at('02:35')
    ->withoutOverlapping();

Schedule::command('edges:prune-stale')
    ->daily()
    ->at('03:05')
    ->withoutOverlapping()
    ->onFailure(fn () => Log::error('schedule failed: edges:prune-stale'));

Schedule::command('reminders:prune-completed')
    ->daily()
    ->at('03:15')
    ->withoutOverlapping()
    ->onFailure(fn () => Log::error('schedule failed: reminders:prune-completed'));

Schedule::command('exports:prune-old')
    ->daily()
    ->at('03:35')
    ->withoutOverlapping();

Schedule::command('presence:reset-stale')
    ->daily()
    ->at('04:05')
    ->withoutOverlapping();

Schedule::command('storage:prune-tmp')
    ->daily()
    ->at('04:35')
    ->withoutOverlapping()
    ->onFailure(fn () => Log::error('schedule failed: storage:prune-tmp'));

Schedule::call(fn () => app(RefreshLaunchpadAction::class)->plan())
    ->name('launchpad:refresh-plan')
    ->when(fn () => !tenantOff('launchpad'))
    ->daily()
    ->at((string) config('launchpad.refresh.at'))
    ->withoutOverlapping()
    ->onFailure(fn () => Log::error('schedule failed: launchpad:refresh-plan'));
