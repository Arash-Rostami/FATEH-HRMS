<?php

namespace App\Traits;

use Livewire\Attributes\Url;

trait FocusOnRecord
{
    #[Url]
    public ?int $open = null;

    /** Livewire lifecycle hook — auto-invoked during mount(); no wiring needed. */
    public function mountWithRecordFocus(): void
    {
        if (! $this->open) {
            return;
        }

        if (method_exists($this, 'focusRecord')) {
            $this->focusRecord((int) $this->open);
        }

        $this->dispatch('record-focus', type: $this->recordFocusType(), id: (int) $this->open);
    }

    /** Anchor namespace. Override in modules whose class basename is ambiguous. */
    protected function recordFocusType(): string
    {
        return strtolower(class_basename(static::class));
    }
}
