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
        $this->notifySaveException($e);

        throw (new Halt)->rollBackDatabaseTransaction();
    }

    protected function notifySaveException(Throwable $e): void
    {
        $presented = ExceptionPresenter::present($e);

        Notification::make()
            ->danger()
            ->title($presented['title'])
            ->body($presented['body'])
            ->persistent()
            ->send();
    }
}