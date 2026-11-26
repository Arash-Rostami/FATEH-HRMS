<?php

namespace App\Traits\Livewire;

use Closure;
use Illuminate\Validation\ValidationException;
use Throwable;

trait AnnouncesInvalidForm
{
    protected ?string $invokedMethod = null;

    protected function announceInvalidOn(): array
    {
        return [];
    }

    protected function announceInvalidWithToast(): bool
    {
        return true;
    }

    public function callAnnouncesInvalidForm(string $methodName): void
    {
        $this->invokedMethod = $methodName;
    }

    public function exceptionAnnouncesInvalidForm(Throwable $e, Closure $stopPropagation): void
    {
        if (! $e instanceof ValidationException || ! in_array($this->invokedMethod, $this->announceInvalidOn(), true)) {
            return;
        }

        $errors = $e->errors();
        $field = array_key_first($errors);

        $this->dispatch('form-invalid', field: $field);

        if (! $this->announceInvalidWithToast()) {
            return;
        }

        $count = count($errors);
        $message = $count === 1
            ? $errors[$field][0]
            : 'برای ادامه، موارد مشخص‌شده در فرم را بررسی کنید (' . convertToPersian((string) $count) . ' مورد).';

        $this->dispatch('toast', message: $message, type: 'error');
    }
}
