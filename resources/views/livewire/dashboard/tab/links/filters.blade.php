<x-ui.forms.filters
    placeholder="جستجو در عنوان یا توضیح لینک..."
    searchModel="search"
    activeCondition="this.$wire.get('activeFilter') !== 'all'"
    clearAction="this.$wire.resetFilters()"
    filterTitle="نوع لینک"
    :open="false"
>
    @php
        $filterOptions = [
            ['id' => 'all', 'icon' => 'apps', 'label' => 'همه'],
            ['id' => 'internal', 'icon' => 'dataset_linked', 'label' => 'داخلی'],
            ['id' => 'external', 'icon' => 'public', 'label' => 'خارجی'],
        ];
    @endphp
    <div class="flex items-center gap-2">
        @foreach($filterOptions as $option)
            @php($isActive = $activeFilter === $option['id'])
            <button
                wire:click="setFilter('{{ $option['id'] }}')"
                @class([
                    'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border transition-all duration-200 whitespace-nowrap active:scale-[0.97]',
                    'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent shadow-sm' => $isActive,
                    'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:border-[var(--md-sys-color-outline)]' => !$isActive,
                ])
            >
                <span class="material-symbols-rounded text-[18px]">{{ $option['icon'] }}</span>
                {{ $option['label'] }}
            </button>
        @endforeach
    </div>
</x-ui.forms.filters>
