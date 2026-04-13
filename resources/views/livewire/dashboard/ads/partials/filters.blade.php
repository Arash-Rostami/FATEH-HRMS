<x-dashboard.form.filters
    searchModel="search"
    activeCondition="this.$wire.get('activeFilter') !== 'active'"
    clearAction="this.$wire.set('activeFilter', 'active'); this.$wire.set('search', '');"
    filterTitle="وضعیت"
    placeholder="جستجو در عنوان، مهارت‌ها..."
    :open="true"
>
    <div class="flex flex-wrap items-center gap-2 w-full justify-start scale-[0.8] md:scale-[1.0]">
        <button
            @click="$wire.set('activeFilter', 'active')"
            :class="$wire.activeFilter === 'active'
                ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border-transparent shadow-sm'
                : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-container-low)]'"
            class="flex shrink-0 items-center gap-2 h-8 px-4 rounded-xl text-sm font-medium border transition-all duration-200"
        >
            <span class="material-symbols-rounded text-[18px]">verified</span>
            <span>فعال</span>
            <span
                class="flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[11px] font-bold rounded-md transition-colors"
                :class="$wire.activeFilter === 'active'
                    ? 'bg-white text-[var(--md-sys-color-primary)] shadow-sm'
                    : 'bg-[var(--md-sys-color-primary)] text-white'"
            >
                {{ $this->stats['active'] }}
            </span>
        </button>

        <button
            @click="$wire.set('activeFilter', 'archived')"
            :class="$wire.activeFilter === 'archived'
                ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-transparent shadow-sm'
                : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-low)] border-[var(--md-sys-color-outline-variant)]'"
            class="flex shrink-0 items-center gap-2 h-8 px-4 rounded-xl text-sm font-medium border transition-all duration-200"
        >
            <span
                class="material-symbols-rounded text-[18px] transition-colors"
                :class="$wire.activeFilter === 'archived' ? '' : 'text-[var(--md-sys-color-outline)]'"
            >
                archive
            </span>
            <span>بایگانی شده</span>
            <span
                class="flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[11px] font-bold rounded-md transition-colors"
                :class="$wire.activeFilter === 'archived'
                    ? 'bg-white text-[var(--md-sys-color-secondary)] shadow-sm'
                    : 'bg-[var(--md-sys-color-secondary)] text-white'"
            >
                {{ $this->stats['archived'] }}
            </span>
        </button>
    </div>
</x-dashboard.form.filters>
