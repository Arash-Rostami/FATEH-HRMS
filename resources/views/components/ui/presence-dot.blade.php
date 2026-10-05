@props([
    'online' => false,
    'position' => 'absolute -bottom-0.5 -end-0.5',
    'size' => 'w-3 h-3',
])

<span aria-hidden="true" title="{{ $online ? 'آنلاین' : 'آفلاین' }}"
      {{ $attributes->merge(['class' => $position . ' ' . $size . ' rounded-full border-2 border-[var(--md-sys-color-surface)] ' . ($online ? 'bg-emerald-500 dark:bg-emerald-400 animate-pulse' : 'bg-[var(--md-sys-color-outline)]')]) }}></span>