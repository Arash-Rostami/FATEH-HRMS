<x-ui.forms.filters
    searchModel="search"
    activeCondition="this.$wire.get('activeFilter') !== 'all' || this.$wire.get('activeClassifier') !== 'all' || this.$wire.get('skillId') !== null || this.$wire.get('mentorOnly')"
    clearAction="this.$wire.set('activeFilter', 'all'); this.$wire.set('activeClassifier', 'all'); this.$wire.set('search', ''); this.$wire.set('skillId', null); this.$wire.set('skillSearch', ''); this.$wire.set('mentorOnly', false);"
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

    <div class="flex flex-col gap-3 mt-3 pt-3 border-t border-[var(--md-sys-color-outline-variant)]/30" dir="rtl">
        <span class="flex items-center gap-1.5 text-xs font-medium text-[var(--md-sys-color-on-surface-variant)]">
            <span class="material-symbols-rounded text-[15px]">workspace_premium</span>
            جستجوی مهارت
        </span>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[180px] group">
                <span class="material-symbols-rounded absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors text-[18px]">search</span>
                <input
                    dir="rtl"
                    type="text"
                    wire:model.live.debounce.500ms="skillSearch"
                    wire:keydown.enter="searchSkill"
                    placeholder="نام مهارت..."
                    class="w-full h-9 pr-9 pl-3 rounded-xl text-sm outline-none bg-[var(--md-sys-color-surface-container-high)] border border-transparent text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)] focus:border-[var(--md-sys-color-primary)] focus:bg-[var(--md-sys-color-surface-container-highest)] transition-colors duration-200"
                >
            </div>

            @if($this->selectedSkill)
                <span class="flex items-center gap-1.5 h-8 pr-3 pl-1.5 rounded-xl text-sm font-medium bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border border-transparent shadow-sm">
                    <span class="material-symbols-rounded text-[15px]">workspace_premium</span>
                    {{ $this->selectedSkill->name }}
                    <button type="button" wire:click="clearSkillFilter" title="حذف فیلتر مهارت"
                            class="material-symbols-rounded text-[14px] p-1 rounded-lg hover:bg-[var(--md-sys-color-on-secondary-container)]/10 transition-colors">close</button>
                </span>

                <button
                    @click="$wire.set('mentorOnly', !$wire.mentorOnly)"
                    :class="$wire.mentorOnly
                        ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-transparent shadow-sm'
                        : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-container-low)]'"
                    class="flex shrink-0 items-center gap-2 h-8 px-4 rounded-xl text-sm font-medium border transition-all duration-200"
                    title="نمایش فقط همکاران آمادهٔ راهنمایی در این مهارت"
                >
                    <span class="material-symbols-rounded text-[18px]">school</span>
                    <span>آماده راهنمایی</span>
                </button>
            @endif
        </div>

        @if($this->skillCandidates->isNotEmpty())
            <div class="flex flex-col gap-1.5">
                <span class="flex items-center gap-1 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]/80">
                    <span class="material-symbols-rounded text-[13px]">auto_awesome</span>
                    پیشنهادها
                </span>
                <div class="flex flex-wrap items-center gap-1.5">
                    @foreach($this->skillCandidates as $candidate)
                        <button type="button" wire:click="selectSkill({{ $candidate->id }})"
                                class="flex items-center h-7 px-3 rounded-lg text-xs font-medium border border-dashed border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface-variant)] hover:border-solid hover:border-[var(--md-sys-color-primary)]/50 hover:bg-[var(--md-sys-color-secondary-container)]/60 hover:text-[var(--md-sys-color-on-secondary-container)] transition-all duration-200">
                            {{ $candidate->name }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
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
