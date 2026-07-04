<x-ui.forms.filters
    searchModel="search"
    activeCondition="this.$wire.get('selectedCategory') !== null || this.$wire.get('selectedDepartment') !== null"
    clearAction="this.$wire.resetFilters()"
    filterTitle=" "
    placeholder="جستجو در سوالات..."
    open="{{ false }}"
>
    <div class="flex flex-col gap-6 w-full pt-1">
        <div class="flex flex-col gap-3">
            <span class="text-[11px] font-bold tracking-widest text-[var(--md-sys-color-primary)] px-1 uppercase">دسته‌بندی‌ها</span>
            <div class="flex flex-wrap items-center gap-2 justify-start w-full transform origin-right scale-[0.85] md:scale-100">
                <button
                    wire:click="filterByCategory(null)"
                    class="flex shrink-0 items-center gap-2 h-9 px-5 rounded-[14px] text-[13px] font-semibold border transition-all duration-[var(--theme-transition-duration)] ease-[var(--theme-transition-easing)] hover:-translate-y-[1px]"
                    :class="$wire.selectedCategory === null
                        ? 'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] text-[var(--md-sys-color-primary)] border-[var(--md-sys-color-primary)]/30 shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)] dark:shadow-[0_4px_12px_rgba(0,0,0,0.6)]'
                        : 'bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] dark:bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] hover:border-[var(--md-sys-color-primary)]/40 hover:text-[var(--md-sys-color-primary)] hover:shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_5%,transparent)]'"
                >
                    همه
                </button>
                @foreach($this->categories as $category)
                    <button
                        wire:click="filterByCategory('{{ $category }}')"
                        class="flex shrink-0 items-center gap-2 h-9 px-5 rounded-[14px] text-[13px] font-semibold border transition-all duration-[var(--theme-transition-duration)] ease-[var(--theme-transition-easing)] hover:-translate-y-[1px]"
                        :class="$wire.selectedCategory === '{{ $category }}'
                            ? 'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] text-[var(--md-sys-color-primary)] border-[var(--md-sys-color-primary)]/30 shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)] dark:shadow-[0_4px_12px_rgba(0,0,0,0.6)]'
                            : 'bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] dark:bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] hover:border-[var(--md-sys-color-primary)]/40 hover:text-[var(--md-sys-color-primary)] hover:shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_5%,transparent)]'"
                    >
                        {{ $category }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="flex flex-col gap-3">
            <span class="text-[11px] font-bold tracking-widest text-[var(--md-sys-color-primary)] px-1 uppercase">واحدها</span>
            <div class="flex flex-wrap items-center gap-2 justify-start w-full transform origin-right scale-[0.85] md:scale-100">
                <button
                    wire:click="filterByDepartment(null)"
                    class="flex shrink-0 items-center gap-2 h-9 px-5 rounded-[14px] text-[13px] font-semibold border transition-all duration-[var(--theme-transition-duration)] ease-[var(--theme-transition-easing)] hover:-translate-y-[1px]"
                    :class="$wire.selectedDepartment === null
                        ? 'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] text-[var(--md-sys-color-primary)] border-[var(--md-sys-color-primary)]/30 shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)] dark:shadow-[0_4px_12px_rgba(0,0,0,0.6)]'
                        : 'bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] dark:bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] hover:border-[var(--md-sys-color-primary)]/40 hover:text-[var(--md-sys-color-primary)] hover:shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_5%,transparent)]'"
                >
                    همه
                </button>
                @foreach($this->departments as $code => $label)
                    <button
                        wire:click="filterByDepartment('{{ $code }}')"
                        title="{{ $this->departmentTooltips[$code] ?? '' }}"
                        class="flex shrink-0 items-center gap-2 h-9 px-5 rounded-[14px] text-[13px] font-semibold border transition-all duration-[var(--theme-transition-duration)] ease-[var(--theme-transition-easing)] hover:-translate-y-[1px]"
                        :class="$wire.selectedDepartment === '{{ $code }}'
                            ? 'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] text-[var(--md-sys-color-primary)] border-[var(--md-sys-color-primary)]/30 shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)] dark:shadow-[0_4px_12px_rgba(0,0,0,0.6)]'
                            : 'bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] dark:bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] hover:border-[var(--md-sys-color-primary)]/40 hover:text-[var(--md-sys-color-primary)] hover:shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_5%,transparent)]'"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</x-ui.forms.filters>
