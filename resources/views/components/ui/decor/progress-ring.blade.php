@props([
    'percent' => 0,
    'size' => 40,
    'stroke' => 4,
    'label' => null,
    'color' => 'var(--md-sys-color-primary)',
    'trackColor' => 'var(--md-sys-color-surface-variant)',
])

@php
    $radius = ($size - $stroke) / 2;
    $circumference = 2 * M_PI * $radius;
    $clamped = min(max((float) $percent, 0), 100);
    $offset = $circumference * (1 - $clamped / 100);
@endphp

<div {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center shrink-0']) }} style="width: {{ $size }}px; height: {{ $size }}px;">
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" class="-rotate-90">
        <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" fill="none" stroke="{{ $trackColor }}" stroke-width="{{ $stroke }}"/>
        <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" fill="none" stroke="{{ $color }}" stroke-width="{{ $stroke }}"
                stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" stroke-linecap="round"
                class="transition-all duration-500"/>
    </svg>
    <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold tabular-nums" style="color: {{ $color }};">
        {{ $label ?? round($clamped) . '٪' }}
    </span>
</div>
