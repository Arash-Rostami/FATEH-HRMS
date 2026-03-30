<div class="sticky top-6 space-y-6 animate-slide-up-fade" style="animation-delay: 0.3s;">
    <div class="flex items-center gap-3 mb-2">
        <div
            class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center shrink-0">
            <span class="material-symbols-rounded text-[20px] font-fill">history</span>
        </div>
        <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)] whitespace-nowrap">
            تاریخچه من
        </h2>
        <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)] min-w-[20px]"
             style="opacity: 0.5;"></div>
        <span
            class="text-[11px] font-bold px-3 py-1.5 rounded-xl bg-[var(--md-sys-color-surface-variant)]
            text-[var(--md-sys-color-on-surface-variant)] whitespace-nowrap">
            {{ convertToPersian(count($this->userReservations)) }} مورد
        </span>
    </div>

    <div
        class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl border border-[var(--md-sys-color-outline-variant)] p-2 flex flex-col gap-2 shadow-sm"
        style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);">
        @forelse($this->userReservations as $reservation)
            <div
                wire:key="reservation-{{ $reservation->id }}"
                class="group relative flex items-center justify-between p-4 rounded-xl bg-[var(--md-sys-color-surface)] hover:bg-[var(--md-sys-color-surface-container-high)] border border-transparent hover:border-[var(--md-sys-color-outline-variant)] transition-all shadow-[var(--md-sys-elevation-1)] hover:shadow-md"
                style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 20%, transparent);"
            >
                <div
                    class="absolute right-0 top-1/2 -translate-y-1/2 h-8 w-[4px] rounded-xl bg-[var(--md-sys-color-primary)] opacity-0 group-hover:opacity-100 transition-opacity shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_50%,transparent)]"></div>

                <div class="flex items-center gap-3 sm:gap-4 pr-2 min-w-0 flex-1">
                    <div
                        class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shrink-0 shadow-inner">
                            <span class="material-symbols-rounded text-[20px] sm:text-[24px] font-fill">
                                {{ $reservation->resource->type === 'seat' ? 'desk' : ($reservation->resource->type === 'spot' ? 'local_parking' : ($reservation->resource->type === 'car' ? 'directions_car' : 'meeting_room')) }}
                            </span>
                    </div>
                    <div class="flex flex-col gap-1 min-w-0 flex-1">
                        <span class="text-sm font-black text-[var(--md-sys-color-on-surface)] tracking-tight truncate"
                              title="{{ $reservation->resource->name }}">
                            {{ $reservation->resource->name }}
                        </span>

                        <span
                            class="text-[10.5px] sm:text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-1.5 truncate opacity-90"
                            title="{{ $reservation->display_time }}"
                        >
                            <span class="material-symbols-rounded text-[14px] shrink-0">calendar_today</span>
                            <span class="truncate tracking-wide">{{ $reservation->display_time }}</span>
                        </span>
                    </div>
                </div>

                <button wire:click="cancel({{ $reservation->id }})"
                        wire:confirm="آیا از لغو این رزرو اطمینان دارید؟"
                        class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-[var(--md-sys-color-error)] bg-[var(--md-sys-color-error-container)]/0 hover:bg-[var(--md-sys-color-error-container)] opacity-100 lg:opacity-0 group-hover:opacity-100 transition-all focus:opacity-100 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-error)] shrink-0 ml-1"
                        title="لغو رزرو">
                    <span class="material-symbols-rounded text-[18px] sm:text-[20px] font-fill">delete</span>
                </button>
            </div>
        @empty
            <div class="p-8 sm:p-10 text-center flex flex-col items-center">
                <div
                    class="w-16 h-16 rounded-2xl bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] flex items-center justify-center mb-3"
                    style="opacity: 0.5;">
                    <span class="material-symbols-rounded text-3xl">event_available</span>
                </div>
                <h4 class="font-bold text-sm text-[var(--md-sys-color-on-surface)] mb-1">صندوق خالی
                    است</h4>
                <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">شما هیچ رزرو فعالی
                    ندارید.</p>
            </div>
        @endforelse
    </div>
</div>
