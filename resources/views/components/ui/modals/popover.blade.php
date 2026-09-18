@props(['alignment' => 'bottom-full left-0 origin-bottom-left', 'surface' => 'plain'])

@php
    $surfaceClasses = match ($surface) {
        'elevated' => 'bg-[var(--md-sys-color-surface-container-low)] rounded-2xl shadow-[0_12px_32px_color-mix(in_srgb,var(--md-sys-color-scrim)_22%,transparent)] border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden',
        default => 'bg-[var(--md-sys-color-surface)] rounded-xl shadow-xl border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden',
    };
    $above = str_contains($alignment, 'bottom-full');
    $side = str_contains($alignment, 'right-0') ? 'right' : 'left';
    $origin = collect(explode(' ', $alignment))->first(fn ($c) => str_starts_with($c, 'origin-'));
@endphp

<div class="relative animate-slide-down"
     x-data="{ open: false, pos: {}, h: 0, above: {{ $above ? 'true' : 'false' }}, side: '{{ $side }}' }">
    <div x-ref="trigger" class="cursor-pointer"
         @click="open = !open; if (open) { pos = $refs.trigger.getBoundingClientRect().toJSON(); if (above) $nextTick(() => h = $refs.panel.offsetHeight) }">
        {{ $trigger }}
    </div>

    <template x-teleport="body">
        <div
            x-ref="panel"
            x-show="open" x-cloak dir="rtl"
            @click.away="if (!$refs.trigger.contains($event.target)) open = false"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
            :style="side === 'right'
                ? { position: 'fixed', top: (above ? pos.top - h - 8 : pos.bottom + 8) + 'px', right: (window.innerWidth - pos.right) + 'px' }
                : { position: 'fixed', top: (above ? pos.top - h - 8 : pos.bottom + 8) + 'px', left: pos.left + 'px' }"
            class="w-48 {{ $surfaceClasses }} {{ $origin }} z-[110]"
        >
            <div class="flex flex-col py-1" @click="open = false">
                {{ $content }}
            </div>
        </div>
    </template>
</div>
