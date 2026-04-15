@props(['variant' => 'neutral'])

@php
    $classes = match($variant) {
        'success' => 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20',
        'warning' => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-400 dark:ring-yellow-500/20',
        'danger' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] ring-[var(--md-sys-color-error)]/10',
        'info' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] ring-[var(--md-sys-color-primary)]/10',
        default => 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] ring-[var(--md-sys-color-outline)]/10',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset $classes"]) }}>
    {{ $slot }}
</span>
