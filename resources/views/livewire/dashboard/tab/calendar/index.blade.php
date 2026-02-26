<div
    dir="rtl"
    x-data="{
        calendarOpen: true
    }"
    class="animate-fade relative w-full max-w-[88rem] mx-auto h-screen overflow-hidden flex flex-col gap-6"
>
    <x-dashboard.tab.title icon="calendar_month" title="تقویم کاری" />

    <div class="flex-1 w-full overflow-hidden flex flex-col-reverse md:flex-row gap-6 relative">
        <div class="mx-auto w-full md:scale-[.90] md:w-[35%] h-[45vh] md:h-full flex flex-col gap-4 md:gap-6 shrink-0">
            <button
                wire:click="openCreateModal"
                class="flex items-center justify-center gap-3 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] w-full py-4 md:py-5 rounded-2xl hover:shadow-2xl hover:shadow-[var(--md-sys-color-primary)]/40 transition-all active:scale-[0.98] group shrink-0"
            >
                <span class="material-symbols-rounded text-2xl group-hover:rotate-90 transition-transform duration-300">add</span>
                <span class="font-bold text-base md:text-lg">رویداد جدید</span>
            </button>

            <div class="flex-1 bg-[var(--md-sys-color-surface-container-low)]/60 border border-[var(--md-sys-color-outline-variant)]/40 rounded-[2rem] flex flex-col overflow-hidden shadow-sm">
                <div class="flex-1 overflow-y-auto">
                    @include('livewire.dashboard.tab.calendar.partials.events')
                </div>
            </div>
        </div>

        <div class="mx-auto w-full flex-1 md:scale-[.90] md:w-[65%] h-full flex flex-col overflow-hidden bg-[var(--md-sys-color-surface)] rounded-[2rem] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm transition-all duration-300 hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]">
            <div class="flex items-center justify-between shrink-0 px-6 py-6 md:px-10">
                <div class="flex flex-col gap-1">
                    <h2 class="text-[var(--md-sys-color-on-surface)] text-2xl md:text-4xl font-black font-yekan tracking-tight">
                        {{ $this->currentMonthName }}
                    </h2>
                </div>

                <div class="flex items-center bg-[var(--md-sys-color-surface-container-high)] rounded-2xl p-1.5 border border-[var(--md-sys-color-outline-variant)]/30">
                    <button
                        wire:click="prevMonth"
                        class="w-10 h-10 flex items-center justify-center hover:bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface-variant)] rounded-xl transition-all active:scale-90"
                    >
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>

                    <button
                        wire:click="goToToday"
                        class="px-4 text-sm font-bold text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/5 rounded-lg py-2 transition-colors"
                    >
                        امروز
                    </button>

                    <button
                        wire:click="nextMonth"
                        class="w-10 h-10 flex items-center justify-center hover:bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface-variant)] rounded-xl transition-all active:scale-90"
                    >
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-4 md:px-8 pb-8 scrollbar-hide">
                @include('livewire.dashboard.tab.calendar.partials.grid')
            </div>
        </div>
    </div>

    @include('livewire.dashboard.tab.calendar.partials.create')
    @include('livewire.dashboard.tab.calendar.partials.delete')
</div>
