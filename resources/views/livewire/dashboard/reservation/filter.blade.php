@foreach($this->facets as $facet)
    <div wire:key="resv-facet-{{ $facet['key'] }}" class="w-full {{ $loop->first ? '' : 'mt-4' }}">
        <label
            class="text-[11px] uppercase tracking-widest font-bold text-[var(--md-sys-color-primary)] flex items-center gap-2 mb-3">
            <span class="material-symbols-rounded text-[16px]">{{ $facet['icon'] }}</span>
            فیلتر {{ $facet['label'] }} (اختیاری)
        </label>
        <div class="flex gap-2 flex-wrap w-full">
            @foreach($facet['options'] as $opt)
                @php($active = ($facetFilters[$facet['key']] ?? null) === $opt['value'])
                <button
                    wire:key="resv-facet-{{ $facet['key'] }}-{{ $opt['value'] }}"
                    wire:click="setFacet('{{ $facet['key'] }}', '{{ $opt['value'] }}')"
                    @class([
                        'px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 border outline-none flex items-center gap-2',
                        'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] border-transparent shadow-sm' => $active,
                        'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-variant)]' => !$active,
                    ])
                    style="{{ !$active ? 'border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);' : '' }}"
                >
                    <span class="material-symbols-rounded text-[18px]">{{ $active ? 'check' : $facet['icon'] }}</span>
                    <span class="whitespace-nowrap">{{ $opt['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>
@endforeach
