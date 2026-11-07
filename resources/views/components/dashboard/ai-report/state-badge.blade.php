@props(['state' => 'saved'])

@php
    [$icon, $label, $tone] = match ($state) {
        'inactive' => ['visibility_off', 'غیرفعال', 'text-[var(--md-sys-color-on-surface-variant)]'],
        'draft' => ['edit_note', 'پیش‌نویس', 'text-[var(--tool-gold-color)]'],
        default => ['task_alt', 'ذخیره‌شده', 'text-[var(--md-sys-color-success)]'],
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-0.5 rounded px-1 py-px text-[10px] font-medium leading-4 whitespace-nowrap bg-[color-mix(in_srgb,currentColor_9%,transparent)] {$tone}"]) }}>
    <span class="material-symbols-rounded text-[11px] opacity-80" aria-hidden="true">{{ $icon }}</span>
    {{ $label }}
</span>
