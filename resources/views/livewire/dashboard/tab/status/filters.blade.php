<x-ui.forms.filters
    searchModel="search"
    activeCondition="this.$wire.get('activeFilter') !== 'all' || this.$wire.get('activeClassifier') !== 'all'"
    clearAction="this.$wire.set('activeFilter', 'all'); this.$wire.set('activeClassifier', 'all'); this.$wire.set('search', '');"
    filterTitle="وضعیت حضور"
    placeholder="جستجو..."
    open="{{false}}"
>

    <div class="flex flex-wrap items-center gap-2 w-full justify-start scale-[0.8] md:scale-[1.0]">
        <button
            @click="$wire.set('activeFilter', 'all')"
            :class="$wire.activeFilter === 'all'
                ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-transparent shadow-sm'
                : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-container-low)]'"
            class="flex shrink-0 items-center gap-2 h-8 px-4 rounded-xl text-sm font-medium border transition-all duration-200"
        >
            <span class="material-symbols-rounded text-[18px]">groups</span>
            <span>همه</span>
        </button>

        @foreach(presenceCases() as $status)
            <button
                @click="$wire.set('activeFilter', '{{ $status->value }}')"
                :class="$wire.activeFilter === '{{ $status->value }}'
                    ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-transparent shadow-sm'
                    : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-low)]'"
                class="flex shrink-0 items-center gap-2 h-8 px-4 rounded-xl text-sm font-medium border transition-all duration-200 {{ $status->ringClass() }}"
            >
               <span
                   class="material-symbols-rounded text-[18px] transition-colors"
                   :style="$wire.activeFilter === '{{ $status->value }}' ? '' : 'color: {{ $status->hex() }}'"
               >
                    {{ $status->icon() }}
                </span>
                <span>{{ $status->label() }}</span>
                <span
                    class="flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[11px] font-bold rounded-md transition-colors"
                    :class="$wire.activeFilter === '{{ $status->value }}'
                        ? 'bg-white text-{{ $status->color() }}-700 shadow-sm'
                        : 'bg-{{ $status->color() }}-500 text-white'"
                >
                    {{ $this->stats[$status->value] ?? 0 }}
                </span>
            </button>
        @endforeach
    </div>

    @if($this->classifierGroups)
        <div class="flex flex-col gap-3 mt-3 pt-3 border-t border-[var(--md-sys-color-outline-variant)]/30">
            <div class="flex flex-wrap gap-2">
                <button
                    @click="$wire.set('activeClassifier', 'all')"
                    :class="$wire.activeClassifier === 'all'
                        ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-transparent shadow-sm'
                        : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-container-low)]'"
                    class="flex shrink-0 items-center h-8 px-4 rounded-xl text-sm font-medium border transition-all duration-200"
                >همه</button>
            </div>

            @foreach($this->classifierGroups as $norm => $group)
                <div class="flex flex-col gap-2">
                    <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $group['label'] }}</span>
                    <div class="flex flex-wrap items-center gap-2 w-full justify-start scale-[0.8] md:scale-[1.0]">
                        @foreach($group['values'] as $value)
                            <button
                                @click="$wire.set('activeClassifier', {{ \Illuminate\Support\Js::from($norm . '|' . $value) }})"
                                :class="$wire.activeClassifier === {{ \Illuminate\Support\Js::from($norm . '|' . $value) }}
                                    ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-transparent shadow-sm'
                                    : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-container-low)]'"
                                class="flex shrink-0 items-center h-8 px-4 rounded-xl text-sm font-medium border transition-all duration-200"
                            >{{ $value }}</button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-ui.forms.filters>
