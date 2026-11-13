@props([
    'online' => false,
    'position' => 'absolute -top-0.5 -left-0.5',
    'size' => 'w-3 h-3',
    'pulse' => false,
    'label' => null,
])

<span aria-hidden="true" data-dot @if($online) data-on @endif title="{{ $label ?? ($online ? 'آنلاین' : 'آفلاین') }}"
      {{ $attributes->merge(['class' => $position . ' ' . $size . ' rounded-full border-2 border-[var(--md-sys-color-surface)] bg-[var(--md-sys-color-surface)] shadow-[inset_0_0_0_2px_var(--md-sys-color-outline)] data-[on]:bg-emerald-500 dark:data-[on]:bg-emerald-400 data-[on]:shadow-none' . ($pulse ? ' data-[on]:motion-safe:animate-pulse' : '')]) }}></span>