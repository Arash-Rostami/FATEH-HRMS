<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command('model:prune')
    ->daily()
    ->withoutOverlapping();

Schedule::command('suggestions:auto-resolve-stale')
    ->hourly()
    ->withoutOverlapping();
