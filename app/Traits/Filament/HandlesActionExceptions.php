<?php

namespace App\Traits\Filament;

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
        } catch (Halt|Cancel $e) {
            throw $e;
        } catch (ValidationException $e) {
            $inline = fn(string $key) => str_starts_with($key, 'mountedActions')
                || (method_exists($this, 'save') && str_starts_with($key, 'data.'));

            if (collect($e->errors())->keys()->contains($inline)) {
                throw $e;
            }

            $this->reportActionException($e);

            if ($this->getMountedAction()?->getName() === 'delete') {
                $this->unmountAction();
            }

            return null;
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