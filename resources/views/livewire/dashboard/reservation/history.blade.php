<div class="sticky top-6 space-y-4 animate-slide-up-fade" style="animation-delay: 0.3s; height: calc(100vh - 8rem);">
    <div class="flex items-center gap-3 mb-2">
        <div class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center shrink-0">
            <span class="material-symbols-rounded text-[20px] font-fill">schedule</span>
        </div>
        <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)] whitespace-nowrap">
            تاریخچه من
        </h2>
        <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)] min-w-[20px]" style="opacity: 0.5;"></div>
    </div>

    <div class="bg-[var(--md-sys-color-surface)]">
        <x-ui.buttons.tab-selector
            :tabs="$historyTabs"
            :activeTab="$activeHistoryTab"
            buttonBaseClass="flex-1 relative flex items-center justify-center gap-1.5 px-3 sm:px-4 py-2 text-[12px] sm:text-[13px] font-bold rounded-lg transition-all duration-300 outline-none whitespace-nowrap"
            button-active-class="bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md shadow-[var(--md-sys-color-primary)]/20"
            button-inactive-class="text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)]"
            icon-base-class="material-symbols-rounded text-xl"
            icon-active-class="font-variation-fill"
            icon-inactive-class="opacity-70 group-hover:opacity-100"
            class="flex w-full mb-4 overflow-x-auto custom-scrollbar bg-[var(--md-sys-color-surface)]"
        />
    </div>

    <div
        @class(['bg-[var(--md-sys-color-surface)] rounded-2xl border border-[var(--md-sys-color-outline-variant)] p-2 flex flex-col gap-2 shadow-sm overflow-y-auto'])
        style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent); max-height: calc(100vh - 15rem);">

        @forelse($this->historyReservations as $reservation)
            <div wire:key="history-{{ $reservation->id }}"
                 @confirmation-confirmed.window="$wire.call($event.detail.method, $event.detail.params)"
                 class='group relative flex items-center justify-between p-4 rounded-xl transition-all shadow-[var(--md-sys-elevation-1)] hover:shadow-md border border-transparent hover:border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] hover:bg-[var(--md-sys-color-surface-container-high)]'
                 style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 20%, transparent);">

                @if($activeHistoryTab === 'upcoming')
                    <div class="absolute right-0 top-1/2 -translate-y-1/2 h-8 w-[4px] rounded-xl bg-[var(--md-sys-color-primary)] opacity-0 group-hover:opacity-100 transition-opacity shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_50%,transparent)]"></div>
                @endif

                <div @class([
                    'flex items-center gap-3 sm:gap-4 pr-2 min-w-0 flex-1',
                    'opacity-70' => $activeHistoryTab === 'cancelled'
                ])>
                    <div @class([
                        'w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center shrink-0 shadow-inner',
                        'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' => $activeHistoryTab === 'upcoming',
                        'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]' => $activeHistoryTab !== 'upcoming'
                    ])>
                        <span class="material-symbols-rounded text-[20px] sm:text-[24px] font-fill">
                            {{ $reservation->resource->icon }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-1 min-w-0 flex-1">
                        <span
                            @class([
                                'text-sm font-bold text-[var(--md-sys-color-on-surface)] tracking-tight truncate',
                                'line-through decoration-1' => $activeHistoryTab === 'cancelled'
                            ])
                            title="{{ $reservation->resource->name }}">
                            {{ $reservation->resource->name }}
                        </span>
                        <span class="text-[10.5px] sm:text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-1.5 truncate opacity-90" title="{{ $reservation->display_time }}">
                            <span class="material-symbols-rounded text-[14px] shrink-0">calendar_today</span>
                            <span class="truncate tracking-wide">{{ $reservation->display_time }}</span>
                        </span>
                    </div>
                </div>

                @if($activeHistoryTab === 'upcoming')
                    <button x-data
                            @click="$dispatch('open-confirmation', {
                                        title: 'لغو رزرو',
                                        message: 'آیا از لغو این رزرو اطمینان دارید؟',
                                        method: 'cancel',
                                        params: {{ $reservation->id }},
                                        type: 'dispatch'
                                    })"
                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-[var(--md-sys-color-error)] bg-[var(--md-sys-color-error-container)]/0 hover:bg-[var(--md-sys-color-error-container)] opacity-100 lg:opacity-0 group-hover:opacity-100 transition-all focus:opacity-100 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-error)] shrink-0 ml-1"
                            title="لغو رزرو">
                        <span class="material-symbols-rounded text-[18px] sm:text-[20px] font-fill">delete</span>
                    </button>
                @elseif($activeHistoryTab === 'previous')
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary-container)]/30 shrink-0 ml-1">
                        <span class="material-symbols-rounded text-[18px] sm:text-[20px]">check</span>
                    </div>
                @elseif($activeHistoryTab === 'cancelled')
                    <div class="flex flex-col items-center justify-center ml-1">
                        <span @class([
                            'text-[9px] font-bold px-2 py-0.5 rounded-md',
                            'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]' => $reservation->status === 'cancelled_admin',
                            'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]' => $reservation->status !== 'cancelled_admin'
                        ])>
                            {{ $reservation->status === 'cancelled_admin' ? 'مدیریت' : 'شخصی' }}
                        </span>
                        <span class="material-symbols-rounded text-[18px] sm:text-[20px] text-[var(--md-sys-color-error)] mt-1">block</span>
                    </div>
                @endif
            </div>
        @empty
            <div class="p-8 sm:p-10 text-center flex flex-col items-center">
                <div class="w-16 h-16 rounded-2xl bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] flex items-center justify-center mb-3 opacity-50">
                    <span class="material-symbols-rounded text-3xl">
                        {{ match($activeHistoryTab) { 'upcoming' => 'event_available', 'previous' => 'history_toggle_off', default => 'done_all' } }}
                    </span>
                </div>
                <h4 class="font-bold text-sm text-[var(--md-sys-color-on-surface)] mb-1">صندوق خالی است</h4>
                <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">موردی برای نمایش وجود ندارد.</p>
            </div>
        @endforelse

        @if($this->totalHistoryReservations > count($this->historyReservations))
            <div class="mt-2 flex justify-center w-full">
                <x-ui.buttons.load-more
                    action="loadMoreHistory"
                    text="موارد بیشتر"
                    loadingText="..."
                    icon="expand_more"
                    class="px-4 py-2 rounded-xl bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] text-xs font-semibold shadow-sm"
                />
            </div>
        @endif
    </div>
</div>
