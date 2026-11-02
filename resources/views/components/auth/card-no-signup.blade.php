@props(['title' => '', 'description' => '', 'imageContent' => null, 'icon' => null])

@php
    $lightLogo = asset(tenantLogo(false, 'user', true));
    $darkLogo  = asset(tenantLogo(true, 'user', true));
    $appName   = config('app.name_en', 'WinterrA');
@endphp

<div
    x-data="{ minimized: false }"
    data-auth-card
    @card-restore.window="minimized = false"
    @keydown.escape.window="if (!minimized && !$event.defaultPrevented) { minimized = true; $dispatch('card-minimize') }"
    class="flex w-full max-h-screen items-center justify-center bg-transparent p-0 animate-slide-in-top animate-delay-1250"
>
    {{-- Minimized Bubble --}}
    <template x-teleport="body">
        <div
            x-data="{ show: false }"
            x-show="show"
            @card-minimize.window="show = true"
            x-transition:enter="animate-bubble-in"
            x-transition:leave="animate-fade-out"
            @click="show = false; $dispatch('card-restore')"
            class="fixed bottom-8 left-8 z-[9999] flex cursor-pointer select-none items-center gap-3 rounded-2xl px-4 py-2.5 shadow-xl ring-1 ring-inset ring-[var(--md-sys-color-outline-variant)]/30 transition-all duration-300 hover:-translate-y-0.5"
            style="background:color-mix(in srgb,var(--md-sys-color-surface) 92%,transparent);backdrop-filter:blur(12px);"
            title="باز کردن فرم"
        >
            <img src="{{ $darkLogo }}"  alt="{{ $appName }}" class="h-5 w-auto rounded-lg hidden dark:block">
            <img src="{{ $lightLogo }}" alt="{{ $appName }}" class="h-5 w-auto rounded-lg dark:hidden">
            <span class="text-sm font-bold" style="color:var(--md-sys-color-on-surface-variant);">{{ $title }}</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-xl transition-colors hover:bg-[var(--md-sys-color-primary)]/10">
                <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">open_in_full</span>
            </div>
        </div>
    </template>

    {{-- Main Card --}}
    <div
        x-show="!minimized"
        x-transition:enter="animate-slide-scale"
        x-transition:leave="animate-fade-out"
        class="group relative isolate flex w-full max-w-[540px] flex-col overflow-hidden rounded-[32px] bg-gradient-to-bl from-[var(--md-sys-color-surface)]/80 via-[var(--md-sys-color-surface)]/98 to-[var(--md-sys-color-surface-container-low)] shadow-[0_8px_32px_rgba(0,0,0,0.06),0_2px_8px_rgba(0,0,0,0.04)] ring-1 ring-inset ring-[var(--md-sys-color-outline-variant)]/20 transition-all duration-500 ease-[cubic-bezier(0.2,0.8,0.2,1)] hover:-translate-y-1.5 hover:shadow-[0_32px_80px_-16px_rgba(0,0,0,0.15),0_8px_24px_-4px_rgba(0,0,0,0.1)] hover:ring-[var(--md-sys-color-outline-variant)]/40"
    >
        <div class="absolute inset-x-0 top-0 z-20 h-px bg-gradient-to-r from-transparent via-[var(--md-sys-color-primary)]/30 to-transparent"></div>

        {{-- Minimize Button --}}
        <button
            @click="minimized = true; $dispatch('card-minimize')"
            class="absolute top-5 left-5 z-30 flex h-9 w-9 items-center justify-center rounded-xl border transition-all duration-150 hover:-translate-y-px active:scale-95"
            style="background:color-mix(in srgb,var(--md-sys-color-primary) 8%,transparent);border-color:color-mix(in srgb,var(--md-sys-color-primary) 18%,transparent);"
            title="کوچک کردن"
            aria-label="کوچک کردن"
        >
            <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">remove</span>
        </button>

        {{-- Decorative Backgrounds --}}
        <div class="pointer-events-none absolute -right-32 top-0 h-64 w-64 bg-[image:radial-gradient(circle,var(--md-sys-color-primary)_1px,transparent_1px)] bg-[size:32px_32px] opacity-20 transition-opacity duration-700 ease-out group-hover:opacity-40"></div>
        <div class="pointer-events-none absolute -left-32 -top-32 h-64 w-64 overflow-hidden rounded-full bg-[image:repeating-linear-gradient(0deg,var(--md-sys-color-primary)_0,var(--md-sys-color-primary)_1px,transparent_1px,transparent_12px),repeating-linear-gradient(90deg,var(--md-sys-color-primary)_0,var(--md-sys-color-primary)_1px,transparent_1px,transparent_12px)] opacity-[0.06] transition-opacity duration-700 ease-out group-hover:opacity-[0.12] dark:hidden"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-32 h-64 w-64 bg-[image:repeating-linear-gradient(45deg,var(--md-sys-color-tertiary)_0,var(--md-sys-color-tertiary)_1px,transparent_1px,transparent_14px)] opacity-[0.06] transition-opacity duration-700 ease-out group-hover:opacity-[0.12] dark:hidden"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-32 h-64 w-64 overflow-hidden rounded-full bg-[image:repeating-linear-gradient(0deg,var(--md-sys-color-tertiary)_0,var(--md-sys-color-tertiary)_1px,transparent_1px,transparent_12px),repeating-linear-gradient(90deg,var(--md-sys-color-tertiary)_0,var(--md-sys-color-tertiary)_1px,transparent_1px,transparent_12px)] opacity-[0.06] transition-opacity duration-700 ease-out group-hover:opacity-[0.12] dark:hidden"></div>

        {{-- Logo --}}
        <div class="relative z-10 isolate flex w-full scale-[0.9] flex-col items-center justify-center px-8 pb-8 pt-14 transition-transform ease-out animate-scale-in animate-delay-2500">
            @if ($icon)
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 bg-[color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)] transition-transform duration-700 group-hover:-translate-y-0.5 group-hover:scale-105">
                    <span class="material-symbols-rounded !text-4xl text-[var(--md-sys-color-primary)]">{{ $icon }}</span>
                </div>
            @else
            <div class="relative block dark:hidden">
                <img src="{{ $lightLogo }}" alt="{{ $appName }}" title="{{ $appName }}" class="h-12 w-auto transition-all duration-700 group-hover:-translate-y-0.5 group-hover:scale-105">
                <div class="pointer-events-none absolute inset-0 animate-logo-shine" style="-webkit-mask-image:url('{{ $lightLogo }}');mask-image:url('{{ $lightLogo }}');-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;"></div>
            </div>
            <div class="relative hidden dark:block">
                <img src="{{ $darkLogo }}" alt="{{ $appName }}" title="{{ $appName }}" class="h-12 w-auto transition-all duration-700 group-hover:-translate-y-0.5 group-hover:scale-105">
                <div class="pointer-events-none absolute inset-0 animate-logo-shine" style="-webkit-mask-image:url('{{ $darkLogo }}');mask-image:url('{{ $darkLogo }}');-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;"></div>
            </div>
            @endif
        </div>

        {{-- Content --}}
        <div
            class="relative z-10 flex flex-1 flex-col px-8 pb-14 sm:px-14"
            x-init="window.matchMedia('(min-width: 1024px)').matches && $nextTick(() => $el.querySelector('input:not([type=hidden]), select, textarea')?.focus())"
        >
            <div class="mb-10 space-y-3.5 text-center">
                <h1class="font-display text-[1.75rem] font-bold leading-tight tracking-tight text-[var(--md-sys-color-on-surface)] sm:text-3xl">
                    {{ $title }}
                </h1>
                @if ($description)
                    <p class="mx-auto max-w-[90%] text-sm font-medium leading-relaxed text-[var(--md-sys-color-on-surface-variant)] sm:text-[0.95rem]">
                        {{ $description }}
                    </p>
                @endif
            </div>

            <div class="relative w-full space-y-7">
                {{ $slot }}
            </div>
        </div>

        {{-- Dark mode noise --}}
        <div class="pointer-events-none absolute inset-0 z-0 hidden bg-[image:radial-gradient(circle_at_25%_25%,var(--md-sys-color-on-surface)_1px,transparent_1px),radial-gradient(circle_at_75%_75%,var(--md-sys-color-on-surface)_1px,transparent_1px)] bg-[size:8px_8px] bg-[position:0_0,4px_4px] opacity-[0.06] dark:block"></div>
    </div>
</div>
