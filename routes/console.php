<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command('model:prune')
    ->daily()
    ->withoutOverlapping();

Schedule::command('suggestions:auto-resolve-stale')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('notifications:prune-stale')
    ->daily()
    ->withoutOverlapping();

Schedule::command('tasks:nudge-overdue')
    ->hourly()
    ->between('06:00', '22:00')
    ->withoutOverlapping();

Schedule::command('tasks:archive-stale-done')
    ->daily()
    ->at('02:30')
    ->withoutOverlapping();

Schedule::command('edges:prune-stale')
    ->daily()
    ->withoutOverlapping();
