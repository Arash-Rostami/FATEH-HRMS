<?php

namespace App\Filament\Traits;

use App\Services\ExceptionPresenter;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Throwable;

trait HandlesSaveExceptions
{
    protected function reportSaveException(Throwable $e): never
    {
        $presented = ExceptionPresenter::present($e);

        Notification::make()
            ->danger()
            ->title($presented['title'])
            ->body($presented['body'])
            ->persistent()
            ->send();

        throw (new Halt)->rollBackDatabaseTransaction();
    }
}