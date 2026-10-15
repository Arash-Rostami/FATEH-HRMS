@props([
    'online' => false,
    'position' => 'absolute -top-0.5 -left-0.5',
    'size' => 'w-3 h-3',
    'pulse' => false,
    'label' => null,
])

<span aria-hidden="true" title="{{ $label ?? ($online ? 'آنلاین' : 'آفلاین') }}"
      {{ $attributes->merge(['class' => $position . ' ' . $size . ' rounded-full border-2 border-[var(--md-sys-color-surface)] ' . ($online ? 'bg-emerald-500 dark:bg-emerald-400' . ($pulse ? ' motion-safe:animate-pulse' : '') : 'bg-[var(--md-sys-color-surface)] shadow-[inset_0_0_0_2px_var(--md-sys-color-outline)]')]) }}></span>