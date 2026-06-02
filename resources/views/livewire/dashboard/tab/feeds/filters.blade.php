@php($categoryLabels = ['General' => '📢 عمومی', 'Event' => '📅 رویداد', 'Birthday' => '🎂 تولد', 'Work Anniversary' => '🏆 سالگرد کاری', 'Poll' => '📊 نظرسنجی'])
<x-ui.forms.filters
    searchModel="search"
    activeCondition="this.$wire.get('selectedCategory') !== null"
    clearAction="this.$wire.resetFilters()"
    filterTitle=" "
    placeholder="جستجو در فیدها..."
    :open="false"
>
    <div class="flex flex-col gap-2">
        <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] px-1">دسته‌بندی‌ها</span>
        <div class="flex flex-wrap items-center gap-2 justify-start w-full scale-[0.8] md:scale-[1]">
            <button
                wire:click="filterByCategory(null)"
                class="flex shrink-0 items-center gap-2 h-8 px-4 rounded-xl text-sm font-medium border transition-all duration-200"
                :class="$wire.selectedCategory === null ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-transparent shadow-sm' : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-container-low)]'"
            >همه
            </button>
            @foreach($this->categories as $category)
                <button
                    wire:click="filterByCategory('{{ $category }}')"
                    class="flex shrink-0 items-center gap-2 h-8 px-4 rounded-xl text-sm font-medium border transition-all duration-200"
                    :class="$wire.selectedCategory === '{{ $category }}' ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-transparent shadow-sm' : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-container-low)]'"
                >{{ $categoryLabels[$category] ?? $category }}</button>
            @endforeach
        </div>
    </div>
</x-ui.forms.filters>
