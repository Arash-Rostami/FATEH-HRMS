<?php

namespace App\Traits\Filament;

use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait FilamentModelRuleNotice
{
    public function callMountedAction(array $arguments = []): mixed
    {
        try {
            return parent::callMountedAction($arguments);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();

            if (Str::startsWith((string) array_key_first($errors), 'mountedActions')) {
                throw $exception;
            }

            Notification::make()->danger()->title((string) collect($errors)->flatten()->first())->send();

            if ($this->getMountedAction()?->getName() === 'delete') {
                $this->unmountAction();
            }

            return null;
        }
    }
}
