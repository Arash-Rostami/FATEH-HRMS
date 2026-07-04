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
    :class="compact ? 'py-1.5' : 'py-3'"
    class="sticky top-0 z-20 bg-[var(--md-sys-color-surface)]/95 rounded-2xl border-b border-[var(--md-sys-color-outline-variant)]/30 px-4 flex flex-col gap-3 transition-[padding] duration-200 ease-out will-change-[padding]"
>
    <div class="flex items-center gap-2 w-full max-w-7xl mx-auto">
        <button
            @click="showFilters = !showFilters"
            class="h-12 px-4 sm:px-6 shrink-0 rounded-xl transition-colors duration-200 relative flex items-center justify-center gap-2 font-medium text-sm border"
            :class="showFilters || hasActiveFilters
                ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-transparent'
                : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-container-low)] hover:text-[var(--md-sys-color-on-surface)]'"
        >
            <span class="material-symbols-rounded text-[20px]">tune</span>
            <span class="hidden sm:inline overflow-hidden transition-[max-width,opacity] duration-200 whitespace-nowrap"
                  :style="compact ? 'max-width:0;opacity:0' : 'max-width:80px;opacity:1'">فیلترها</span>
            <span x-show="hasActiveFilters" x-cloak
                  class="absolute top-2 right-2 w-2.5 h-2.5 rounded-md bg-[var(--md-sys-color-error)] border-2 border-[var(--md-sys-color-surface)]"></span>
        </button>

        <div class="relative flex-1 group">
            <span class="material-symbols-rounded absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors text-[22px]">search</span>
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
                x-cloak
                class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-variant)] transition-all flex items-center justify-center p-1.5 rounded-lg"
            >
                <span class="material-symbols-rounded text-[18px]">close</span>
            </button>
        </div>
    </div>

    <div x-show="showFilters" x-transition.opacity.duration.200ms class="overflow-hidden w-full max-w-7xl mx-auto">
        <div class="pt-1 pb-2 flex flex-col gap-3 w-full">
            <div class="flex items-center gap-4 px-1 w-full">
                <button
                    x-show="hasActiveFilters"
                    @click="clearFilters"
                    x-cloak
                    class="text-xs font-medium text-[var(--md-sys-color-error)] border border-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error)]/10 px-3 py-1.5 rounded-xl flex items-center gap-1.5 transition-colors shrink-0"
                >
                    <span class="material-symbols-rounded text-[14px]">close</span>
                    حذف فیلترها
                </button>
                <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $filterTitle }}</span>
            </div>
            <div class="w-full flex flex-col gap-3">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
