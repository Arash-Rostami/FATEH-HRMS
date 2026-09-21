<?php

namespace App\Filament\Traits;

use App\Services\ExceptionPresenter;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Cancel;
use Filament\Support\Exceptions\Halt;
use Illuminate\Validation\ValidationException;
use Throwable;

trait HandlesActionExceptions
{
    public function callMountedAction(array $arguments = []): mixed
    {
        try {
            return parent::callMountedAction($arguments);
        } catch (ValidationException|Halt|Cancel $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->reportActionException($e);

            return null;
        }
    }

    protected function reportActionException(Throwable $e): void
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