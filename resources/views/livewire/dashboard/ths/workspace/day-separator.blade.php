@php
    $label = match (true) {
        $date === null => '—',
        $date->isToday() => 'امروز',
        $date->isYesterday() => 'دیروز',
        default => toJalali($date, 'j F Y'),
    };
@endphp

<div wire:key="ths-day-{{ $dayKey ?? 'none' }}" data-day="{{ $dayKey ?? '' }}" class="flex items-center gap-3 py-2" role="heading" aria-level="3" aria-label="{{ $label }}">
    <div class="flex-1 h-px bg-[linear-gradient(to_left,transparent,color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent))]" aria-hidden="true"></div>
    <span class="text-[10px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-lg shadow-sm bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_85%,transparent)] text-[var(--md-sys-color-on-surface-variant)]">{{ $label }}</span>
    <div class="flex-1 h-px bg-[linear-gradient(to_right,transparent,color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent))]" aria-hidden="true"></div>
</div>
