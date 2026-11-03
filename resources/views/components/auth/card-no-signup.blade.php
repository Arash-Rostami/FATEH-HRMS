@props(['title' => '', 'description' => '', 'imageContent' => null, 'icon' => null, 'siblings' => false])

@php
    $lightLogo = asset(tenantLogo(false, 'user', true));
    $darkLogo  = asset(tenantLogo(true, 'user', true));
    $appName   = config('app.name_en', 'WinterrA');
@endphp

<div
    x-data="{ minimized: false, flipped: false, seen: false }"
    data-auth-card
    @card-restore.window="minimized = false"
    @keydown.escape.window="if (flipped) { flipped = false; $refs.flip.checked = false } else if (!minimized && !$event.defaultPrevented) { minimized = true; $dispatch('card-minimize') }"
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
            <img src="{{ $darkLogo }}" alt="{{ $appName }}" class="h-5 w-auto rounded-lg hidden dark:block">
            <img src="{{ $lightLogo }}" alt="{{ $appName }}" class="h-5 w-auto rounded-lg dark:hidden">
            <span class="text-sm font-bold" style="color:var(--md-sys-color-on-surface-variant);">{{ $title }}</span>
            <div
                class="flex h-8 w-8 items-center justify-center rounded-xl transition-colors hover:bg-[var(--md-sys-color-primary)]/10">
                <span
                    class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">open_in_full</span>
            </div>
        </div>
    </template>

    {{-- Main Card --}}
    <div
        x-show="!minimized"
        x-transition:enter="animate-slide-scale"
        x-transition:leave="animate-fade-out"
        class="ad-flip-card w-full max-w-[540px]"
    >
        <input x-ref="flip" type="checkbox" autocomplete="off" class="ad-flip-toggle hidden" tabindex="-1" aria-hidden="true">
        <div class="ad-flip-card-inner grid w-full">
            <div
                :inert="flipped"
        class="ad-flip-card-front col-start-1 row-start-1 group relative isolate flex w-full flex-col overflow-hidden rounded-[32px] bg-gradient-to-bl from-[var(--md-sys-color-surface)]/80 via-[var(--md-sys-color-surface)]/98 to-[var(--md-sys-color-surface-container-low)] shadow-[0_8px_32px_rgba(0,0,0,0.06),0_2px_8px_rgba(0,0,0,0.04)] ring-1 ring-inset ring-[var(--md-sys-color-outline-variant)]/20 transition-all duration-500 ease-[cubic-bezier(0.2,0.8,0.2,1)] hover:-translate-y-1.5 hover:shadow-[0_32px_80px_-16px_rgba(0,0,0,0.15),0_8px_24px_-4px_rgba(0,0,0,0.1)] hover:ring-[var(--md-sys-color-outline-variant)]/40"
            >
                <div
                    class="absolute inset-x-0 top-0 z-20 h-px bg-gradient-to-r from-transparent via-[var(--md-sys-color-primary)]/30 to-transparent"></div>

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

                @if ($siblings)
                    <button
                        type="button"
                        x-ref="flipBtn"
                        @click="flipped = seen = true; $refs.flip.checked = true; $nextTick(() => $refs.backBtn?.focus())"
                        class="absolute top-5 right-5 z-30 flex h-9 w-9 items-center justify-center rounded-xl border transition-all duration-150 hover:-translate-y-px active:scale-95"
                        style="background:color-mix(in srgb,var(--md-sys-color-primary) 8%,transparent);border-color:color-mix(in srgb,var(--md-sys-color-primary) 18%,transparent);"
                        title="سامانه‌های مرتبط"
                        aria-label="سامانه‌های مرتبط"
                    >
                        <span
                            class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">apps</span>
                    </button>
                @endif

                {{-- Decorative Backgrounds --}}
                <div
                    class="pointer-events-none absolute -right-32 top-0 h-64 w-64 bg-[image:radial-gradient(circle,var(--md-sys-color-primary)_1px,transparent_1px)] bg-[size:32px_32px] opacity-20 transition-opacity duration-700 ease-out group-hover:opacity-40"></div>
                <div
                    class="pointer-events-none absolute -left-32 -top-32 h-64 w-64 overflow-hidden rounded-full bg-[image:repeating-linear-gradient(0deg,var(--md-sys-color-primary)_0,var(--md-sys-color-primary)_1px,transparent_1px,transparent_12px),repeating-linear-gradient(90deg,var(--md-sys-color-primary)_0,var(--md-sys-color-primary)_1px,transparent_1px,transparent_12px)] opacity-[0.06] transition-opacity duration-700 ease-out group-hover:opacity-[0.12] dark:hidden"></div>
                <div
                    class="pointer-events-none absolute -bottom-32 -right-32 h-64 w-64 bg-[image:repeating-linear-gradient(45deg,var(--md-sys-color-tertiary)_0,var(--md-sys-color-tertiary)_1px,transparent_1px,transparent_14px)] opacity-[0.06] transition-opacity duration-700 ease-out group-hover:opacity-[0.12] dark:hidden"></div>
                <div
                    class="pointer-events-none absolute -bottom-32 -left-32 h-64 w-64 overflow-hidden rounded-full bg-[image:repeating-linear-gradient(0deg,var(--md-sys-color-tertiary)_0,var(--md-sys-color-tertiary)_1px,transparent_1px,transparent_12px),repeating-linear-gradient(90deg,var(--md-sys-color-tertiary)_0,var(--md-sys-color-tertiary)_1px,transparent_1px,transparent_12px)] opacity-[0.06] transition-opacity duration-700 ease-out group-hover:opacity-[0.12] dark:hidden"></div>

                {{-- Logo --}}
                <div
                    class="relative z-10 isolate flex w-full scale-[0.9] flex-col items-center justify-center px-8 pb-8 pt-14 transition-transform ease-out animate-scale-in animate-delay-2500">
                    @if ($icon)
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 bg-[color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)] transition-transform duration-700 group-hover:-translate-y-0.5 group-hover:scale-105">
                            <span
                                class="material-symbols-rounded !text-4xl text-[var(--md-sys-color-primary)]">{{ $icon }}</span>
                        </div>
                    @else
                        <div class="relative block dark:hidden">
                            <img src="{{ $lightLogo }}" alt="{{ $appName }}" title="{{ $appName }}"
                                 class="h-12 w-auto transition-all duration-700 group-hover:-translate-y-0.5 group-hover:scale-105">
                            <div class="pointer-events-none absolute inset-0 animate-logo-shine"
                                 style="-webkit-mask-image:url('{{ $lightLogo }}');mask-image:url('{{ $lightLogo }}');-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;"></div>
                        </div>
                        <div class="relative hidden dark:block">
                            <img src="{{ $darkLogo }}" alt="{{ $appName }}" title="{{ $appName }}"
                                 class="h-12 w-auto transition-all duration-700 group-hover:-translate-y-0.5 group-hover:scale-105">
                            <div class="pointer-events-none absolute inset-0 animate-logo-shine"
                                 style="-webkit-mask-image:url('{{ $darkLogo }}');mask-image:url('{{ $darkLogo }}');-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;"></div>
                        </div>
                    @endif
                </div>

                {{-- Content --}}
                <div
                    class="relative z-10 flex flex-1 flex-col px-8 pb-14 sm:px-14"
                    x-init="window.matchMedia('(min-width: 1024px)').matches && $nextTick(() => $el.querySelector('input:not([type=hidden]), select, textarea')?.focus())"
                >
                    <div class="mb-10 space-y-3.5 text-center">
                        <h1 class="font-display text-[1.75rem] font-bold leading-tight tracking-tight text-[var(--md-sys-color-on-surface)] sm:text-3xl">
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
                <div
                    class="pointer-events-none absolute inset-0 z-0 hidden bg-[image:radial-gradient(circle_at_25%_25%,var(--md-sys-color-on-surface)_1px,transparent_1px),radial-gradient(circle_at_75%_75%,var(--md-sys-color-on-surface)_1px,transparent_1px)] bg-[size:8px_8px] bg-[position:0_0,4px_4px] opacity-[0.06] dark:block"></div>
            </div>

            @if ($siblings)
                <div
                    inert :inert="!flipped"
            class="ad-flip-card-back group col-start-1 row-start-1 relative isolate flex w-full flex-col overflow-hidden rounded-[32px] bg-gradient-to-bl from-[var(--md-sys-color-surface)]/80 via-[var(--md-sys-color-surface)]/98 to-[var(--md-sys-color-surface-container-low)] shadow-[0_8px_32px_rgba(0,0,0,0.06),0_2px_8px_rgba(0,0,0,0.04)] ring-1 ring-inset ring-[var(--md-sys-color-outline-variant)]/20">
                    <div
                        class="absolute inset-x-0 top-0 z-20 h-px bg-gradient-to-r from-transparent via-[var(--md-sys-color-primary)]/30 to-transparent"></div>

                    <div
                        class="pointer-events-none absolute -right-32 top-0 h-64 w-64 bg-[image:radial-gradient(circle,var(--md-sys-color-primary)_1px,transparent_1px)] bg-[size:32px_32px] opacity-20 transition-opacity duration-700 ease-out group-hover:opacity-40"></div>
                    <div
                        class="pointer-events-none absolute -left-32 -top-32 h-64 w-64 overflow-hidden rounded-full bg-[image:repeating-linear-gradient(0deg,var(--md-sys-color-primary)_0,var(--md-sys-color-primary)_1px,transparent_1px,transparent_12px),repeating-linear-gradient(90deg,var(--md-sys-color-primary)_0,var(--md-sys-color-primary)_1px,transparent_1px,transparent_12px)] opacity-[0.06] transition-opacity duration-700 ease-out group-hover:opacity-[0.12] dark:hidden"></div>
                    <div
                        class="pointer-events-none absolute -bottom-32 -right-32 h-64 w-64 bg-[image:repeating-linear-gradient(45deg,var(--md-sys-color-tertiary)_0,var(--md-sys-color-tertiary)_1px,transparent_1px,transparent_14px)] opacity-[0.06] transition-opacity duration-700 ease-out group-hover:opacity-[0.12] dark:hidden"></div>
                    <div
                        class="pointer-events-none absolute -bottom-32 -left-32 h-64 w-64 overflow-hidden rounded-full bg-[image:repeating-linear-gradient(0deg,var(--md-sys-color-tertiary)_0,var(--md-sys-color-tertiary)_1px,transparent_1px,transparent_12px),repeating-linear-gradient(90deg,var(--md-sys-color-tertiary)_0,var(--md-sys-color-tertiary)_1px,transparent_1px,transparent_12px)] opacity-[0.06] transition-opacity duration-700 ease-out group-hover:opacity-[0.12] dark:hidden"></div>

                    <button
                        type="button"
                        x-ref="backBtn"
                        @click="flipped = false; $refs.flip.checked = false; $nextTick(() => $refs.flipBtn?.focus())"
                        class="absolute top-5 right-5 z-30 flex h-9 w-9 items-center justify-center rounded-xl border transition-all duration-150 hover:-translate-y-px active:scale-95"
                        style="background:color-mix(in srgb,var(--md-sys-color-primary) 8%,transparent);border-color:color-mix(in srgb,var(--md-sys-color-primary) 18%,transparent);"
                        title="بازگشت به ورود"
                        aria-label="بازگشت به ورود"
                    >
                        <span
                            class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">flip_to_front</span>
                    </button>

                    <div class="relative z-10 flex flex-col items-center px-8 pb-4 pt-10 text-center">
                        <div
                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 bg-[color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)] shadow-[0_8px_24px_-8px_color-mix(in_srgb,var(--md-sys-color-primary)_45%,transparent)]">
                            <span
                                class="material-symbols-rounded !text-[32px] text-[var(--md-sys-color-primary)]">apps</span>
                        </div>
                        <h2 class="font-display text-[1.5rem] font-bold leading-tight tracking-tight text-[var(--md-sys-color-on-surface)] sm:text-[1.75rem]">
                            سامانه‌های مرتبط</h2>
                        <p class="mx-auto mt-2 max-w-[90%] text-sm font-medium leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">
                            برای ورود به هر سامانه روی آن بزنید؛ در تب جدید باز می‌شود.</p>
                        <div class="mt-3 inline-flex items-center gap-2" aria-hidden="true">
                            <span
                                class="h-px w-8 bg-gradient-to-r from-transparent to-[var(--md-sys-color-primary)]/40"></span>
                            <span
                                class="rounded-full border px-3 py-1 text-[11px] font-bold text-[var(--md-sys-color-primary)]"
                                style="background:color-mix(in srgb,var(--md-sys-color-primary) 8%,transparent);border-color:color-mix(in srgb,var(--md-sys-color-primary) 18%,transparent);">{{ convertToPersian(count($siblings)) }} سامانه</span>
                            <span
                                class="h-px w-8 bg-gradient-to-l from-transparent to-[var(--md-sys-color-primary)]/40"></span>
                        </div>
                    </div>

                    <div class="relative z-10 flex-1 px-8 pb-8 sm:px-12">
                        <div class="grid max-h-[20rem] grid-cols-2 gap-3 overflow-y-auto p-1 custom-scrollbar sm:gap-4">
                            @foreach ($siblings as $sibling)
                                <a href="{{ $sibling['url'] }}" target="_blank" rel="noopener noreferrer"
                                   :class="seen ? 'animate-scale-in' : 'opacity-0'"
                                   :style="seen ? 'animation-delay:{{ 250 + $loop->index * 90 }}ms' : ''"
                                   class="group/tile relative flex flex-col items-center justify-center gap-3 overflow-hidden rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)]/70 px-3 py-5 text-center backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-[var(--md-sys-color-primary)]/50 hover:shadow-[0_16px_32px_-12px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]/50 [&:last-child:nth-child(odd)]:col-span-2">
                                    <span
                                        class="pointer-events-none absolute inset-0 bg-[image:radial-gradient(circle_at_50%_0%,color-mix(in_srgb,var(--md-sys-color-primary)_14%,transparent),transparent_70%)] opacity-0 transition-opacity duration-300 group-hover/tile:opacity-100"></span>
                                    <span
                                        class="material-symbols-rounded pointer-events-none absolute right-2.5 top-2.5 !text-[16px] text-[var(--md-sys-color-primary)] -translate-x-1 translate-y-1 opacity-0 transition-all duration-300 group-hover/tile:translate-x-0 group-hover/tile:translate-y-0 group-hover/tile:opacity-100">north_east</span>
                                    <span
                                        class="relative flex h-16 w-16 items-center justify-center rounded-2xl bg-white/80 shadow-sm ring-1 ring-[var(--md-sys-color-outline-variant)]/30 transition-transform duration-300 group-hover/tile:scale-105 dark:bg-white/90">
                                @if (!empty($sibling['logo']))
                                            <img :src="seen ? @js(asset($sibling['logo'])) : null"
                                                 alt="{{ $sibling['title'] ?? $sibling['name'] }}" width="44"
                                                 height="44" loading="lazy" decoding="async" fetchpriority="low"
                                                 class="h-11 w-11 shrink-0 object-contain">
                                        @else
                                            <span
                                                class="material-symbols-rounded !text-[30px] text-[var(--md-sys-color-primary)]">web_asset</span>
                                        @endif
                            </span>
                                    <span
                                        class="relative text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $sibling['title'] ?? $sibling['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div
                        class="pointer-events-none absolute inset-0 z-0 hidden bg-[image:radial-gradient(circle_at_25%_25%,var(--md-sys-color-on-surface)_1px,transparent_1px),radial-gradient(circle_at_75%_75%,var(--md-sys-color-on-surface)_1px,transparent_1px)] bg-[size:8px_8px] bg-[position:0_0,4px_4px] opacity-[0.06] dark:block"></div>
                </div>
            @endif

        </div>
    </div>
</div>
