@props(['alignment' => 'bottom-full left-0 origin-bottom-left', 'surface' => 'plain'])

@php
    $surfaceClasses = match ($surface) {
        'elevated' => 'bg-[var(--md-sys-color-surface-container-low)] rounded-2xl shadow-[0_12px_32px_color-mix(in_srgb,var(--md-sys-color-scrim)_22%,transparent)] border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden',
        default => 'bg-[var(--md-sys-color-surface)] rounded-xl shadow-xl border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden',
    };
@endphp

<div class="relative animate-slide-down" x-data="{ open: false }" @click.away="open = false">
    <div @click="open = !open" class="cursor-pointer">
        {{ $trigger }}
    </div>

    <div
        class="absolute {{ $alignment }} mb-2 w-48 {{ $surfaceClasses }} transform transition-all duration-200 z-50"
        x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        style="display: none;"
    >
        <div class="flex flex-col py-1" @click="open = false">
            {{ $content }}
        </div>
    </div>
</div>
