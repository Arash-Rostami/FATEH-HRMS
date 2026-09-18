    @if(empty($tabs))
        <div wire:key="reservation-avantgarde-no-permission" class="contents">
            <x-ui.empty icon="lock" title="دسترسی رزرو ندارید" description="مجوز رزرو هیچ نوع منبعی برای شما فعال نشده است. با مدیر سیستم تماس بگیرید." variant="list" />
        </div>
    @else
        <div wire:key="reservation-avantgarde-type-selector" x-data="{ open: false }" @click.outside="open = false" class="relative w-full">
            <button
                type="button"
                @click="open = !open"
                class="flex items-center justify-between gap-3 w-full h-14 px-4 rounded-2xl transition-all duration-200 border"
                :class="open ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent' : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] border-[var(--md-sys-color-outline-variant)]/50 hover:bg-[var(--md-sys-color-surface-variant)]/40'"
            >
                <span class="flex items-center gap-2 font-bold text-sm">
                    <span class="material-symbols-rounded text-[20px]">{{ $activeTypeMeta['icon'] ?? 'category' }}</span>
                    {{ $activeTypeMeta['label'] ?? '' }}
                </span>
                <span class="material-symbols-rounded text-[20px] transition-transform" :class="open ? 'rotate-180' : ''">expand_more</span>
            </button>

            <div
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute top-full mt-2 right-0 left-0 z-50 bg-[var(--md-sys-color-surface-container-highest)] rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 shadow-lg p-1.5 flex flex-col gap-0.5"
            >
                @foreach($tabs as $tab)
                    <button
                        type="button"
                        wire:click="switchTab('{{ $tab['id'] }}')"
                        @click="open = false"
                        {{ ($tab['disabled'] ?? false) ? 'disabled' : '' }}
                        @class([
                            'flex items-center gap-2.5 px-3.5 h-11 rounded-xl text-sm font-bold text-right transition-colors duration-150',
                            'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' => $activeTab === $tab['id'],
                            'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]' => $activeTab !== $tab['id'] && !($tab['disabled'] ?? false),
                            'opacity-40 cursor-not-allowed' => $tab['disabled'] ?? false,
                        ])
                    >
                        <span class="material-symbols-rounded text-[19px]">{{ $tab['icon'] }}</span>
                        {{ $tab['label'] }}
                        @if($tab['disabled'] ?? false)
                            <span class="material-symbols-rounded text-[15px] opacity-80 me-auto">lock</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)] p-5">
        <div class="flex items-center justify-between mb-4">
            <span class="flex items-center gap-2 text-[15px] font-black text-[var(--md-sys-color-on-surface)]">
                <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">calendar_month</span>
                {{ convertToPersian($this->currentMonthName) }}
            </span>
            @if($this->dateWindow !== null)
                <div wire:key="avantgarde-cal-controls" class="flex items-center gap-1.5">
                    <button type="button" wire:click="goToday"
                        class="flex h-9 items-center justify-center px-3 rounded-full text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-variant)]/60 hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                        امروز
                    </button>
                    <button type="button" wire:click="prevMonth" {{ $this->canPrevMonth ? '' : 'disabled' }}
                        class="flex w-9 h-9 items-center justify-center rounded-full text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors disabled:opacity-25 disabled:cursor-not-allowed disabled:hover:bg-transparent">
                        <span class="material-symbols-rounded text-[20px]">chevron_right</span>
                    </button>
                    <button type="button" wire:click="nextMonth" {{ $this->canNextMonth ? '' : 'disabled' }}
                        class="flex w-9 h-9 items-center justify-center rounded-full text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors disabled:opacity-25 disabled:cursor-not-allowed disabled:hover:bg-transparent">
                        <span class="material-symbols-rounded text-[20px]">chevron_left</span>
                    </button>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-7 mb-2 pb-2 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
            @foreach($weekDayLabels as $wi => $w)
                <div class="flex items-center justify-center py-1">
                    <span class="text-[11px] font-bold tracking-wider {{ $wi === 6 ? 'text-[var(--md-sys-color-error)]' : 'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_80%,transparent)]' }}">{{ $w }}</span>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-7 gap-1.5">
            @foreach($calendarCells as $i => $cell)
                @if($cell === null)
                    <div wire:key="avantgarde-cal-pad-{{ $i }}" class="aspect-[1/0.85] rounded-[14px] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_30%,transparent)]"></div>
                @else
                    <button
                        type="button"
                        wire:key="avantgarde-cal-day-{{ $cell['day'] }}"
                        wire:click="setDate('{{ $cell['value'] }}')"
                        {{ $cell['available'] ? '' : 'disabled' }}
                        title="{{ $cell['available'] ? '' : $blockedDayLabels[$cell['reason']] }}"
                        @class([
                            'relative aspect-[1/0.85] w-full rounded-[14px] transition-all duration-300 ease-out flex items-center justify-center text-[13px] font-bold outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]',
                            'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_10px_28px_color-mix(in_srgb,var(--md-sys-color-primary)_50%,transparent)] ring-1 ring-[var(--md-sys-color-primary)] z-10' => $cell['selected'] && $cell['available'],
                            'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] shadow-[0_8px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)] ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-primary)_60%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_80%,transparent)]' => !$cell['selected'] && $cell['available'] && $cell['isToday'],
                            'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] shadow-[0_6px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:ring-[color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' => !$cell['selected'] && $cell['available'] && !$cell['isToday'],
                            'bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_60%,transparent)] text-[var(--md-sys-color-on-surface-variant)]/40 ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)] cursor-default line-through' => !$cell['available'] && $cell['reason'] === 'day_off',
                            'bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_40%,transparent)] text-[var(--md-sys-color-on-surface-variant)]/30 ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_15%,transparent)] cursor-default' => !$cell['available'] && $cell['reason'] !== 'day_off',
                        ])
                    >
                        {{ convertToPersian((string) $cell['day']) }}
                    </button>
                @endif
            @endforeach
        </div>
    </div>

    @if(! $isFullDayTab)
        @include('livewire.dashboard.reservation.time-rail')
    @endif

    @if(count($this->facets) > 0)
        <div wire:key="resv-facets-panel-booking" class="contents">
            <x-ui.forms.filters
                placeholder="جستجوی منابع..."
                searchModel="resourceSearch"
                filterTitle="فیلتر منابع"
                activeCondition="Object.keys(this.$wire.get('facetFilters') || {}).length > 0"
                clearAction="this.$wire.call('resetFilters')"
            >
                @include('livewire.dashboard.reservation.filter')
            </x-ui.forms.filters>
        </div>
    @endif

    @include('livewire.dashboard.reservation.recurring')

    <div wire:key="reservation-avantgarde-hints" class="flex flex-wrap gap-2">
        @if($this->minNoticeHours > 0)
            <div wire:key="reservation-hint-notice" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[var(--md-sys-color-surface-container-low)] text-[11px] font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-[14px]">schedule</span>
                {{ convertToPersian((string) $this->minNoticeHours) }} ساعت مهلت رزرو
            </div>
        @endif

        @if($this->durationBounds !== null)
            <div wire:key="reservation-hint-bounds" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[var(--md-sys-color-surface-container-low)] text-[11px] font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-[14px]">timer</span>
                {{ $this->durationBounds }}
            </div>
        @endif

        @if($this->selectedDuration !== null)
            @php($dur = $this->selectedDuration)
            <div wire:key="reservation-hint-duration" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold {{ $dur['valid'] ? 'bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface-variant)]' : 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]' }}">
                <span class="material-symbols-rounded text-[14px]">hourglass_empty</span>
                {{ $dur['text'] }}
            </div>
        @endif

        @if($this->bookingBlockReason !== null)
            <div wire:key="reservation-hint-block" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] text-[11px] font-semibold">
                <span class="material-symbols-rounded text-[14px]">block</span>
                {{ $this->bookingBlockReason }}
            </div>
        @endif
    </div>
