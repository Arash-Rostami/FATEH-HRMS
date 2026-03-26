@props([
    'title' => null,
    'icon' => null,
    'eventName' => null,
    'maxWidth' => '4xl',
    'footer' => null,
    'actions' => null,
    'description' => null,
    'subtext' => null,
    'hasPattern' => true
])

@php
    $maxWidthClass = match ($maxWidth) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        '6xl' => 'max-w-6xl',
        '7xl' => 'max-w-7xl',
        default => 'max-w-4xl',
    };
@endphp

<template x-teleport="body">
    <div
        x-data="{
            show: false,
            active: false,
            initGeneralPattern() {
                if ({{ $hasPattern ? 'true' : 'false' }}) {
                    const settingInstance = window.Alpine.store('settings') || null;
                    if(settingInstance && typeof settingInstance.initPattern === 'function'){
                        settingInstance.initPattern();
                    }
                }
            }
        }"
        @if($eventName)
            @{{ $eventName }}.window="show = true"
        @endif
        @keydown.escape.window="show = false"
        x-init="
            $watch('show', value => {
                if (value) {
                    setTimeout(() => active = true, 50);
                    initGeneralPattern();
                } else {
                    active = false;
                    setTimeout(() => $dispatch('{{ $eventName }}-closed'), 300);
                }
            })
        "
        class="fixed inset-0 z-[100] flex items-center justify-center bg-[var(--md-sys-color-scrim)]/50 backdrop-blur-sm transition-opacity duration-300"
        :class="active ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        style="display: none;"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        dir="rtl"
    >

        <!-- Modal Backdrop Click -->
        <div class="absolute inset-0" @click="show = false"></div>

        <!-- Content -->
        <div class="relative w-full {{ $maxWidthClass }} bg-[var(--md-sys-color-surface-container-highest)] md:rounded-3xl shadow-[0_16px_48px_rgba(0,0,0,0.15)] border border-[var(--md-sys-color-outline-variant)]/40 flex flex-col h-full md:h-auto max-h-[100vh] md:max-h-[90vh] overflow-hidden transition-all duration-300 transform"
             :class="active ? 'scale-100 translate-y-0' : 'scale-95 translate-y-8'"
             @click.stop>

            <!-- Background Pattern Canvas if needed (optional overlay) -->
            @if($hasPattern)
               <div class="absolute inset-0 z-0 opacity-20 pointer-events-none overflow-hidden rounded-3xl" aria-hidden="true">
                   <canvas id="general-modal-pattern" class="w-full h-full"></canvas>
               </div>
            @endif

            <!-- Header -->
            @if($title || $icon)
            <div class="shrink-0 relative z-10 px-6 py-4 flex items-center justify-between border-b border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-low)]">
                <div class="flex items-center gap-3">
                    @if($icon)
                        <div class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shrink-0 shadow-sm">
                            <span class="material-symbols-rounded text-[22px]">{{ $icon }}</span>
                        </div>
                    @endif
                    <div>
                        @if($title)
                            <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-base tracking-wide flex items-center gap-2">
                                {{ $title }}
                            </h3>
                        @endif
                        @if($subtext)
                            <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">{{ $subtext }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if($actions)
                        <div class="hidden sm:flex items-center gap-2 ml-4">
                            {{ $actions }}
                        </div>
                    @endif
                    <button @click="show = false"
                            class="p-2 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] transition-colors focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)] group">
                        <span class="material-symbols-rounded text-[22px] group-hover:rotate-90 transition-transform duration-300">close</span>
                    </button>
                </div>
            </div>
            @endif

            <!-- Body -->
            <div class="flex-1 w-full relative z-10 overflow-y-auto bg-[var(--md-sys-color-surface-container-lowest)] custom-scrollbar">
                {{ $slot }}
            </div>

            <!-- Footer -->
            @if($footer)
                <div class="shrink-0 relative z-10 p-5 bg-[var(--md-sys-color-surface-container-low)] border-t border-[var(--md-sys-color-outline-variant)]/50 flex flex-col md:flex-row items-center justify-between gap-4">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</template>
