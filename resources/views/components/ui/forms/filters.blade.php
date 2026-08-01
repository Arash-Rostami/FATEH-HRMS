@props([
    'placeholder'     => 'جستجو...',
    'searchModel'     => 'search',
    'activeCondition' => 'this.$wire.get("activeFilter") !== "all"',
    'clearAction'     => 'this.$wire.set("activeFilter", "all"); this.$wire.set("search", "");',
    'filterTitle'     => 'فیلترها',
    'open'            => false,
])

<div
    x-data="filters(@js($open), '{{ $searchModel }}', function() { return {{ $activeCondition }}; }, function() { {{ $clearAction }}; })"
    dir="rtl"
    class="relative w-full"
>
    <template x-teleport="body">
        <div dir="rtl">
            <div
                x-show="isDocked"
                x-transition:enter="animate-pop"
                x-transition:leave="animate-fade-out"
                class="fixed bottom-24 left-6 md:bottom-10 md:left-10 z-50 group flex items-center justify-center"
                x-cloak
            >
                <button
                    @click="isDocked = false"
                    class="h-10 w-10 md:h-12 md:w-12 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_8px_20px_rgba(0,0,0,0.12)] hover:shadow-[0_12px_28px_rgba(0,0,0,0.2)] hover:-translate-y-1 transition-transform duration-300 flex items-center justify-center outline-none focus:ring-4 focus:ring-[var(--md-sys-color-primary)]/30 will-change-transform relative"
                >
                    <span class="material-symbols-rounded text-[22px] md:text-[24px]">tune</span>
                    <span x-show="hasActiveFilters" x-cloak
                          class="absolute top-2.5 right-2.5 w-3 h-3 rounded-full bg-[var(--md-sys-color-error)] border-2 border-[var(--md-sys-color-primary)] animate-pulse-ring"></span>
                </button>
                <x-ui.modals.tooltip text="فیلترها" position="right"/>
            </div>

            <div
                x-show="!isDocked"
                x-transition:enter="animate-slide-scale"
                x-transition:leave="animate-fade-out"
                :class="compact ? 'py-1.5' : 'py-3'"
                class="fixed inset-x-0 top-0 z-[60] bg-[var(--md-sys-color-surface)] rounded-b-2xl border-b border-[var(--md-sys-color-outline-variant)]/30 px-4 flex flex-col gap-3 shadow-xl will-change-transform max-h-screen overflow-y-auto"
                style="padding-top: max(1rem, env(safe-area-inset-top))"
                x-cloak
            >
                <div class="flex items-center gap-2 w-full max-w-7xl mx-auto">
                    <button
                        @click="isDocked = true"
                        class="h-12 w-12 shrink-0 rounded-xl transition-colors duration-200 flex items-center justify-center bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-error)] hover:text-white outline-none focus:ring-2 focus:ring-[var(--md-sys-color-error)]/50"
                    >
                        <span class="material-symbols-rounded text-[20px]">close_fullscreen</span>
                    </button>

                    <button
                        @click="showFilters = !showFilters"
                        class="h-12 px-4 sm:px-6 shrink-0 rounded-xl transition-colors duration-200 relative flex items-center justify-center gap-2 font-medium text-sm border outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/50"
                        :class="showFilters || hasActiveFilters
                            ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-transparent'
                            : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-container-low)] hover:text-[var(--md-sys-color-on-surface)]'"
                    >
                        <span class="material-symbols-rounded text-[20px]">tune</span>
                        <span
                            class="hidden sm:inline overflow-hidden transition-[max-width,opacity] duration-200 whitespace-nowrap"
                            :style="compact ? 'max-width:0;opacity:0' : 'max-width:80px;opacity:1'">فیلترها</span>
                        <span x-show="hasActiveFilters" x-cloak
                              class="absolute top-2 right-2 w-2.5 h-2.5 rounded-md bg-[var(--md-sys-color-error)] border-2 border-[var(--md-sys-color-surface)]"></span>
                    </button>

                    <div class="relative flex-1 group">
                        <span
                            class="material-symbols-rounded absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors text-[22px]">search</span>
                        <input
                            dir="rtl"
                            type="text"
                            wire:model.live.debounce.500ms="{{ $searchModel }}"
                            placeholder="{{ $placeholder }}"
                            class="w-full h-12 pl-12 pr-12 rounded-xl text-base outline-none bg-[var(--md-sys-color-surface-container-high)] border border-transparent text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)] focus:border-[var(--md-sys-color-primary)] focus:bg-[var(--md-sys-color-surface-container-highest)] transition-colors duration-200"
                        >
                        <button
                            x-show="hasSearchQuery"
                            @click="clearSearchOnly"
                            x-transition:enter="animate-pop"
                            x-transition:leave="animate-fade-out"
                            x-cloak
                            class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-variant)] transition-all flex items-center justify-center p-1.5 rounded-lg outline-none"
                        >
                            <span class="material-symbols-rounded text-[18px]">close</span>
                        </button>
                    </div>
                </div>

                <div
                    x-show="showFilters"
                    x-transition:enter="animate-slide-down"
                    x-transition:leave="animate-fade-out"
                    class="overflow-hidden w-full max-w-7xl mx-auto"
                    x-cloak
                >
                    <div class="pt-1 pb-2 flex flex-col gap-3 w-full">
                        <div class="flex items-center gap-4 px-1 w-full">
                            <button
                                x-show="hasActiveFilters"
                                @click="clearFilters"
                                x-transition:enter="animate-fade"
                                x-transition:leave="animate-fade-out"
                                x-cloak
                                class="text-xs font-medium text-[var(--md-sys-color-error)] border border-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error)]/10 px-3 py-1.5 rounded-xl flex items-center gap-1.5 transition-colors shrink-0 outline-none"
                            >
                                <span class="material-symbols-rounded text-[14px]">close</span>
                                حذف فیلترها
                            </button>
                            <span
                                class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $filterTitle }}</span>
                        </div>
                        <div class="w-full flex flex-col gap-3 max-h-[40vh] overflow-y-auto pl-1">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
