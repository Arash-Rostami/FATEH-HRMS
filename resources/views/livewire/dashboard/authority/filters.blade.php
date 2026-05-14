<x-ui.forms.filters
    placeholder="جستجوی اعضا..."
    searchModel="search"
    activeCondition="this.$wire.get('search') !== ''"
    clearAction="this.$wire.set('search', '')"
    filterTitle="واحدها"
    :open="true"
>
    <div class="overflow-x-auto pb-2"
         style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 50%, transparent) transparent;">
        <div class="flex items-center gap-2 min-w-max">
            @foreach($this->departments as $dept)
                @php( $isActive = $activeDept === $dept->code)
                <button
                    wire:click="setDept('{{ $dept->code }}')"
                    @class([
                        'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border transition-all duration-200 whitespace-nowrap active:scale-[0.97]',
                        'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent shadow-sm' => $isActive,
                        'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:border-[var(--md-sys-color-outline)]' => !$isActive,
                    ])
                >
                    <span
                        class="material-symbols-rounded text-[18px]">{{ $isActive ? 'corporate_fare' : 'domain' }}</span>
                    {{ $dept->name }}
                    <span @class([
                        'text-[10px] font-bold font-mono px-1.5 py-0.5 rounded-md',
                        'bg-[var(--md-sys-color-on-primary)]/20 text-[var(--md-sys-color-on-primary)]' => $isActive,
                        'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]' => !$isActive,
                    ])>{{ $dept->code }}</span>
                </button>
            @endforeach
        </div>
    </div>
</x-ui.forms.filters>
