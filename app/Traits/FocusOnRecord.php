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
}
