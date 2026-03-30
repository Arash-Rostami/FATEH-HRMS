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

    @php
        $historyTabs = [
            ['id' => 'upcoming', 'icon' => 'event_upcoming', 'label' => 'پیش‌رو'],
            ['id' => 'previous', 'icon' => 'history', 'label' => 'قبلی'],
            ['id' => 'cancelled', 'icon' => 'event_busy', 'label' => 'لغو شده']
        ];
    @endphp

    <div class="flex bg-[var(--md-sys-color-surface-container-low)] p-1 rounded-2xl shadow-sm border border-[var(--md-sys-color-outline-variant)]/40 w-full mb-4 overflow-x-auto custom-scrollbar">
        @foreach($historyTabs as $tab)
            <button wire:click="switchHistoryTab('{{ $tab['id'] }}')"
                    class="flex-1 relative flex items-center justify-center gap-1.5 px-3 sm:px-4 py-2 text-[12px] sm:text-[13px] font-bold rounded-xl transition-all duration-300 outline-none whitespace-nowrap {{ $activeHistoryTab === $tab['id'] ? 'text-[var(--md-sys-color-on-primary-container)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-on-surface)]' }}">
                @if($activeHistoryTab === $tab['id'])
                    <span class="absolute inset-0 rounded-xl bg-[var(--md-sys-color-primary-container)] shadow-sm shadow-[var(--md-sys-color-primary)]/10"></span>
                @endif
                <span class="material-symbols-rounded text-[16px] sm:text-[18px] relative z-10 {{ $activeHistoryTab === $tab['id'] ? 'font-fill' : 'opacity-70' }}">
                    {{ $tab['icon'] }}
                </span>
                <span class="relative z-10">{{ $tab['label'] }}</span>
            </button>
        @endforeach
    </div>

    <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl border border-[var(--md-sys-color-outline-variant)] p-2 flex flex-col gap-2 shadow-sm overflow-y-auto"
         style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent); max-height: calc(100vh - 15rem);">
        @forelse($this->historyReservations as $reservation)
            <div wire:key="history-{{ $reservation->id }}"
                 class="group relative flex items-center justify-between p-4 rounded-xl bg-[var(--md-sys-color-surface)] hover:bg-[var(--md-sys-color-surface-container-high)] border border-transparent hover:border-[var(--md-sys-color-outline-variant)] transition-all shadow-[var(--md-sys-elevation-1)] hover:shadow-md"
                 style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 20%, transparent);">

                @if($activeHistoryTab === 'upcoming')
                    <div class="absolute right-0 top-1/2 -translate-y-1/2 h-8 w-[4px] rounded-xl bg-[var(--md-sys-color-primary)] opacity-0 group-hover:opacity-100 transition-opacity shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_50%,transparent)]"></div>
                @endif

                <div class="flex items-center gap-3 sm:gap-4 pr-2 min-w-0 flex-1 {{ $activeHistoryTab === 'cancelled' ? 'opacity-70' : '' }}">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl {{ $activeHistoryTab === 'upcoming' ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]' }} flex items-center justify-center shrink-0 shadow-inner">
                        <span class="material-symbols-rounded text-[20px] sm:text-[24px] font-fill">
                            {{ $reservation->resource->type === 'seat' ? 'desk' : ($reservation->resource->type === 'spot' ? 'local_parking' : ($reservation->resource->type === 'car' ? 'directions_car' : 'meeting_room')) }}
                        </span>
                    </div>
                    <div class="flex flex-col gap-1 min-w-0 flex-1">
                        <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)] tracking-tight truncate {{ $activeHistoryTab === 'cancelled' ? 'line-through decoration-1' : '' }}"
                              title="{{ $reservation->resource->name }}">
                            {{ $reservation->resource->name }}
                        </span>
                        <span class="text-[10.5px] sm:text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-1.5 truncate opacity-90"
                              title="{{ $reservation->display_time }}">
                            <span class="material-symbols-rounded text-[14px] shrink-0">calendar_today</span>
                            <span class="truncate tracking-wide">{{ $reservation->display_time }}</span>
                        </span>
                    </div>
                </div>

                @if($activeHistoryTab === 'upcoming')
                    <button wire:click="cancel({{ $reservation->id }})"
                            wire:confirm="آیا از لغو این رزرو اطمینان دارید؟"
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
                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-md {{ $reservation->status === 'cancelled_admin' ? 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]' }}">
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
                        {{ $activeHistoryTab === 'upcoming' ? 'event_available' : ($activeHistoryTab === 'previous' ? 'history_toggle_off' : 'done_all') }}
                    </span>
                </div>
                <h4 class="font-bold text-sm text-[var(--md-sys-color-on-surface)] mb-1">صندوق خالی است</h4>
                <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">موردی برای نمایش وجود ندارد.</p>
            </div>
        @endforelse

        @if($this->totalHistoryReservations > count($this->historyReservations))
            <div class="mt-2 flex justify-center w-full">
                <x-dashboard.button.load-more
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
