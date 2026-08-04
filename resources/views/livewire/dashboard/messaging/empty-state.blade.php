@php
    $icon = $icon ?? '';
    $title = $title ?? '';
    $subtitle = $subtitle ?? '';
    $mobileIcon = $mobileIcon ?? $icon;
    $mobileText = $mobileText ?? '';
@endphp

<div class="flex-1 hidden md:flex flex-col items-center justify-center gap-6 text-center px-8 relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none bg-[radial-gradient(circle_at_20%_30%,color-mix(in_srgb,var(--md-sys-color-primary-container)_8%,transparent)_0%,transparent_40%),radial-gradient(circle_at_80%_70%,color-mix(in_srgb,var(--md-sys-color-tertiary-container)_6%,transparent)_0%,transparent_40%),radial-gradient(circle,var(--md-sys-color-outline-variant)_1px,transparent_1px)] bg-[length:100%_100%,100%_100%,32px_32px] opacity-40" aria-hidden="true"></div>
    <div class="relative z-10 w-28 h-28 rounded-xl flex items-center justify-center shadow-2xl bg-[linear-gradient(135deg,var(--md-sys-color-primary-container),var(--md-sys-color-secondary-container)_50%,var(--md-sys-color-tertiary-container)_100%)] text-[var(--md-sys-color-on-primary-container)]" aria-hidden="true">
        <span class="material-symbols-rounded text-6xl font-fill">{{ $icon }}</span>
    </div>
    <div class="relative z-10 max-w-sm">
        <h2 class="text-lg font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ $title }}</h2>
        <p class="text-sm mt-3 leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">{{ $subtitle }}</p>
    </div>
    {{ $slot ?? '' }}
</div>

<div class="flex-1 flex md:hidden flex-col items-center justify-center gap-4 text-center px-8">
    <div class="w-16 h-16 rounded-lg flex items-center justify-center bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]" aria-hidden="true">
        <span class="material-symbols-rounded text-3xl">{{ $mobileIcon }}</span>
    </div>
    <p class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $mobileText }}</p>
</div>