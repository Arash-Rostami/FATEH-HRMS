<template x-teleport="body">
    <div x-show="menuOpen"
         @click.self="toggleMenu"
         x-transition:enter="transition-all duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-all duration-200 delay-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/40 z-[10000] flex justify-start lg:hidden">

        <div
            class="relative w-[85%] max-w-[320px] h-full bg-[var(--md-sys-color-surface)] shadow-2xl flex flex-col"
            x-show="menuOpen"
            x-transition:enter="transition-transform duration-300 ease-[cubic-bezier(0.2,0,0,1)]"
            x-transition:enter-start="translate-x-full rtl:-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition-transform duration-300 ease-[cubic-bezier(0.2,0,0,1)]"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full rtl:-translate-x-full">

            <!-- Header -->
            <div class="p-6 pb-4 flex items-center justify-between border-b border-[var(--md-sys-color-outline-variant)]/20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center">
                        <span class="material-symbols-rounded">menu</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg leading-tight">{{ config('app.name') }}</h3>
                        <p class="text-xs opacity-60">ناوبری اصلی</p>
                    </div>
                </div>
                <button @click="toggleMenu" class="w-8 h-8 rounded-full hover:bg-black/5 flex items-center justify-center transition-colors">
                    <span class="material-symbols-rounded text-[20px] opacity-60">close</span>
                </button>
            </div>

            <!-- Menu Items -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-1">
                <template x-for="item in items" :key="item.title">
                    <a :href="item.href"
                       class="flex items-center gap-4 px-4 py-3 rounded-xl transition-all duration-200 group active:scale-[0.98]"
                       :class="window.location.pathname === item.href
                            ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]'
                            : 'hover:bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]'">

                        <div class="relative w-10 h-10 rounded-lg flex items-center justify-center transition-colors shrink-0"
                             :class="window.location.pathname === item.href
                                ? 'bg-[var(--md-sys-color-secondary)]/20 text-[var(--md-sys-color-secondary)]'
                                : 'bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] group-hover:bg-[var(--md-sys-color-primary)]/10 group-hover:text-[var(--md-sys-color-primary)]'">
                            <span class="material-symbols-rounded text-[22px]" x-text="item.icon"></span>

                            @if(\App\Models\Ad::active()->exists())
                            <template x-if="item.id === 'ads-controller'">
                                <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-error opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-error border border-[var(--md-sys-color-surface)]"></span>
                                </span>
                            </template>
                            @endif
                        </div>

                        <div class="flex flex-col flex-1">
                            <span class="font-bold text-sm" x-text="item.title"></span>
                            <span class="text-[10px] opacity-60 font-medium" x-text="item.sub"></span>
                        </div>

                        <span class="material-symbols-rounded text-[18px] opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all text-[var(--md-sys-color-primary)]">
                            chevron_left
                        </span>
                    </a>
                </template>
            </div>

            <!-- Footer -->


        </div>
    </div>
</template>
