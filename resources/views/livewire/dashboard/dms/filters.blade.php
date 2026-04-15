<x-ui.forms.filters
    placeholder="جستجو بر اساس عنوان، کد یا نسخه..."
    searchModel="search"
    filterTitle="دسته‌بندی‌ها"
>
    <div class="flex flex-wrap gap-2 pt-2 border-t border-[var(--md-sys-color-outline-variant)]/30">
        <button
            wire:click="$set('activeFilter', 'all')"
            class="px-4 py-2 rounded-xl text-sm font-medium transition-colors border"
            :class="activeFilter === 'all'
                ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent'
                : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-variant)]'"
        >
            همه
        </button>

        @foreach($this->types as $type)
            <button
                wire:click="$set('activeFilter', '{{ $type }}')"
                class="px-4 py-2 rounded-xl text-sm font-medium transition-colors border"
                :class="activeFilter === '{{ $type }}'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent'
                    : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-variant)]'"
            >
                {{ $type }}
            </button>
        @endforeach
    </div>
</x-ui.forms.filters>

