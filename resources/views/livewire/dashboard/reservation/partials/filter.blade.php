<div wire:key="floor-filter-blocks"
     class="w-full">
    <label
        class="text-[11px] uppercase tracking-widest font-bold text-[var(--md-sys-color-primary)] flex items-center gap-2 mb-3">
        <span class="material-symbols-rounded text-[16px]">filter_alt</span>
        فیلتر طبقه (اختیاری)
    </label>
    <div class="flex gap-2 flex-wrap w-full">
        @foreach($this->availableFloors as $floor)
            <button
                wire:key="floor-{{ $floor['value'] }}"
                wire:click="setFloor('{{ $floor['value'] }}')"
                @class([
                    'px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 border outline-none flex items-center gap-2',
                    'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] border-transparent shadow-sm' => $filterFloor === $floor['value'],
                    'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-variant)]' => $filterFloor !== $floor['value'],
                ])
                style="{{ $filterFloor !== $floor['value'] ? 'border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);' : '' }}"
            >
                @if($filterFloor === $floor['value'])
                    <span class="material-symbols-rounded text-[18px]">check</span>
                @else
                    <span class="material-symbols-rounded text-[18px]">layers</span>
                @endif
                <span class="whitespace-nowrap">{{ $floor['label'] }}</span>
            </button>
        @endforeach

        @if($filterFloor !== null)
            <button wire:click="resetFilters"
                    class="px-4 py-2.5 rounded-xl text-sm font-bold text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)] transition-colors flex items-center gap-1 whitespace-nowrap">
                <span class="material-symbols-rounded text-[16px]">close</span>
                پاک کردن فیلتر
            </button>
        @endif
    </div>
</div>
