<?php

namespace App\Livewire\Dashboard\Tab\Presentation;

use App\Models\FAQ;
use Illuminate\Support\Str;

class FaqPresenter
{
    public function badge(FAQ $faq): array
    {
        $isUpdated = $faq->updated_at?->gt($faq->created_at);

        return [
            'isUpdated' => $isUpdated,
            'icon' => $isUpdated ? 'edit_calendar' : 'calendar_today',
            'label' => $isUpdated ? 'به‌روزرسانی' : 'ثبت',
            'date' => toJalali($isUpdated ? $faq->updated_at : $faq->created_at, 'j F Y'),
            'colorClasses' => $isUpdated
                ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-[var(--md-sys-color-secondary)]/20 shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-secondary)_25%,transparent)]'
                : 'bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] dark:bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)]',
        ];
    }

    public function questionText(FAQ $faq): string
    {
        return superClean($faq->question ?: 'بدون عنوان', 300);
    }

    public function answerSnippet(FAQ $faq): string
    {
        $text = html_entity_decode(preg_replace('/<[^>]+>/', ' ', (string) $faq->answer), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::limit(trim(preg_replace('/\s+/u', ' ', $text)), 140);
    }
}