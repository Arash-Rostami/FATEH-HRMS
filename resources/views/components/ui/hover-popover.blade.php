@props(['alignment' => 'top-full right-0 mt-2 origin-top-right', 'surface' => 'elevated', 'width' => 'w-56'])

@php
    $surfaceClasses = match ($surface) {
        'elevated' => 'bg-[var(--md-sys-color-surface-container-low)] rounded-2xl shadow-[0_12px_32px_color-mix(in_srgb,var(--md-sys-color-scrim)_22%,transparent)] border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden',
        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] rounded-2xl shadow-[0_12px_32px_color-mix(in_srgb,var(--md-sys-color-scrim)_22%,transparent)] border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden',
        default => 'bg-[var(--md-sys-color-surface)] rounded-xl shadow-xl border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden',
    };
    $side = str_contains($alignment, 'left-0') ? 'left' : 'right';
    preg_match('/^w-(\d+)$/', $width, $__widthMatch);
    $widthPx = isset($__widthMatch[1]) ? ((int) $__widthMatch[1]) * 4 : 224;
@endphp

<div
    {{ $attributes->merge(['class' => 'relative inline-flex']) }}
    x-data="{ open: false, closeTimer: null, pos: {}, side: '{{ $side }}', w: {{ $widthPx }} }"
    @mouseenter="clearTimeout(closeTimer); pos = $refs.trigger.getBoundingClientRect().toJSON(); open = true"
    @mouseleave="closeTimer = setTimeout(() => open = false, 180)"
>
    <div x-ref="trigger" @click="pos = $refs.trigger.getBoundingClientRect().toJSON(); open = !open" class="cursor-pointer">
        {{ $trigger }}
    </div>

    <template x-teleport="body">
        <div
            x-show="open" x-cloak dir="rtl"
            @click.away="if (!$refs.trigger.contains($event.target)) open = false"
            @mouseenter="clearTimeout(closeTimer)"
            @mouseleave="closeTimer = setTimeout(() => open = false, 180)"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            :style="{
                position: 'fixed',
                top: (pos.bottom + 8) + 'px',
                left: Math.min(Math.max((side === 'left' ? pos.left : pos.right - w), 8), window.innerWidth - w - 8) + 'px'
            }"
            class="{{ $width }} {{ $surfaceClasses }} z-50"
        >
            {{ $body }}
        </div>
    </template>
</div>
