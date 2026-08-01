<x-ui.forms.filters
    placeholder="جستجو بر اساس عنوان، کد یا نسخه..."
    searchModel="search"
    filterTitle="فیلترها"
>
    <div class="flex flex-col gap-4 pt-2 border-t border-[var(--md-sys-color-outline-variant)]/30">
        <div class="flex flex-wrap gap-2">
            <button
                wire:click="$set('activeFilter', 'all')"
                class="px-4 py-2 rounded-xl text-sm font-medium transition-colors border"
                :class="$wire.activeFilter === 'all'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent'
                    : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-variant)]'"
            >همه</button>
        </div>

        @foreach($this->filterGroups as $key => $values)
            <div class="flex flex-col gap-2">
                <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)]">
                    {{ $this->filterGroupLabel($key) }}
                </span>
                <div class="flex flex-wrap gap-2">
                    @foreach($values as $value)
                        <button
                            wire:click="$set('activeFilter', '{{ $key }}|{{ $value }}')"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition-colors border"
                            :class="$wire.activeFilter === '{{ $key }}|{{ $value }}'
                                ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent'
                                : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-variant)]'"
                        >{{ $value }}</button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-ui.forms.filters>
