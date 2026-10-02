<?php

namespace App\Traits;

use Livewire\Attributes\Url;

trait FocusOnRecord
{
    #[Url]
    public ?int $open = null;

    public function mountFocusOnRecord(): void
    {
        if (! $this->open) {
            return;
        }

        $handled = false;
        if (method_exists($this, 'focusRecord')) {
            $handled = (bool) $this->focusRecord((int) $this->open);
        }

        if (! $handled) {
            $this->dispatch('record-focus', type: $this->recordFocusType(), id: (int) $this->open);
        }
    }

    public function isFocusing(): bool
    {
        return $this->open !== null;
    }

    public function clearFocus(): void
    {
        $this->open = null;

        // Paginated modules (WithPagination) jump back to page 1.
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }

        // ID-array / custom modules rebuild their full list (see restoreAfterFocus()).
        if (method_exists($this, 'restoreAfterFocus')) {
            $this->restoreAfterFocus();
        }
    }

    protected function recordFocusType(): string
    {
        return strtolower(class_basename(static::class));
    }

    protected function focusQueryParam(string $key): ?string
    {
        $value = request()->query($key);
        if ($value !== null) {
            return is_scalar($value) ? (string) $value : null;
        }

        parse_str((string) (parse_url((string) request()->header('Referer', ''), PHP_URL_QUERY) ?: ''), $query);

        return isset($query[$key]) && is_scalar($query[$key]) ? (string) $query[$key] : null;
    }
}
