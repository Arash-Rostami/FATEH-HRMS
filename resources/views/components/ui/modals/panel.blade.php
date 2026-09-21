@props([
    'state' => 'show',
    'close' => 'show = false',
    'icon' => null,
    'title',
    'subtitle' => null,
    'maxWidth' => 'max-w-2xl',
    'height' => 'h-[85dvh] sm:h-[680px]',
])

<template x-teleport="body">
    <div x-show="{!! $state !!}" x-cloak
         x-on:keydown.escape.window="{!! $close !!}">
        <div x-on:click.self="{!! $close !!}"
             class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-6 md:p-8 bg-[var(--md-sys-color-primary)]/60 animate-slide-down"
             role="dialog" aria-modal="true" aria-label="{{ $title }}">

            <div dir="rtl" x-on:click.stop
                 class="w-full {{ $maxWidth }} {{ $height }} bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] rounded-2xl shadow-2xl dark:shadow-[0_12px_40px_rgba(0,0,0,0.6)] overflow-hidden flex flex-col">

                <div class="px-4 sm:px-6 py-2.5 sm:py-4 bg-gradient-to-l from-[var(--md-sys-color-primary)] to-[color-mix(in_srgb,var(--md-sys-color-primary)_85%,transparent)] text-[var(--md-sys-color-on-primary)] border-b border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_15%,transparent)] shrink-0 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                        @isset($iconBadge)
                            {{ $iconBadge }}
                        @elseif($icon)
                            <span class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_15%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_25%,transparent)] flex items-center justify-center shadow-md shrink-0">
                                <span class="material-symbols-rounded text-lg sm:text-xl">{{ $icon }}</span>
                            </span>
                        @endif
                        <div class="flex flex-col flex-1 min-w-0">
                            <h2 class="text-[15px] sm:text-base font-bold leading-tight truncate">{{ $title }}</h2>
                            @if($subtitle)
                                <p class="text-[11px] sm:text-xs text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_80%,transparent)] truncate mt-0.5">{{ $subtitle }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                        {{ $headerActions ?? '' }}

                        <x-ui.modals.close-button :close="$close" label="بستن پنجره" tone="on-primary" size="sm"/>
                    </div>
                </div>

                @isset($search)
                    <div class="p-4 shrink-0 border-b border-[var(--md-sys-color-outline-variant)]/20">
                        {{ $search }}
                    </div>
                @endisset

                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    {{ $slot }}
                </div>

                @isset($footer)
                    <div class="shrink-0 border-t border-[var(--md-sys-color-outline-variant)]/20">
                        {{ $footer }}
                    </div>
                @endisset
            </div>
        </div>
    </div>
</template>
