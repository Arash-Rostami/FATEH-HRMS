@props([
    'state' => [],
    'title' => null,
    'color' => null,
])

@php
    $title = $title ?? ($state['title'] ?? '');
    $color = $color ?: ($state['color'] ?? 'primary');

    $container = match ($color) {
        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] ring-[var(--md-sys-color-primary)]/25',
        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] ring-[var(--md-sys-color-secondary)]/25',
        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] ring-[var(--md-sys-color-tertiary)]/25',
        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] ring-[var(--md-sys-color-error)]/25',
        default => 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] ring-[var(--md-sys-color-outline)]/25',
    };

    $dot = match ($color) {
        'primary' => 'bg-[var(--md-sys-color-primary)]',
        'secondary' => 'bg-[var(--md-sys-color-secondary)]',
        'tertiary' => 'bg-[var(--md-sys-color-tertiary)]',
        'error' => 'bg-[var(--md-sys-color-error)]',
        default => 'bg-[var(--md-sys-color-outline)]',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-lg px-2 py-0.5 text-[10px] font-bold leading-none ring-1 ring-inset shadow-sm animate-status-morph $container"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dot }} animate-subtle-pulse"></span>
    {{ $title }}
</span>