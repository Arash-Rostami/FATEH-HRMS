<div
    dir="rtl"
    class="animate-fade relative w-full max-w-[88rem] mx-auto h-screen overflow-hidden flex flex-col gap-6"
    x-data="{
        calendarOpen: true
    }"
    @confirmation-confirmed.window="$wire.call($event.detail.method, $event.detail.params)">

    <x-ui.title icon="calendar_month" title="تقویم کاری"/>

    <div class="flex-1 w-full overflow-hidden flex flex-col-reverse md:flex-row gap-6 relative">
        <div class="mx-auto w-full md:scale-[.90] md:w-[35%] h-[45vh] md:h-full flex flex-col gap-4 md:gap-6 shrink-0">
            <button
                wire:click="openCreateModal"
                class="flex items-center justify-center gap-3 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] w-full py-4 md:py-5 rounded-2xl hover:shadow-2xl hover:shadow-[var(--md-sys-color-primary)]/40 transition-all active:scale-[0.98] group shrink-0"
            >
                <span class="material-symbols-rounded text-2xl group-hover:rotate-90 transition-transform duration-300">add</span>
                <span class="font-bold text-base md:text-lg">رویداد جدید</span>
            </button>

            <div
                class="flex-1 bg-[var(--md-sys-color-surface-container-low)]/60 border border-[var(--md-sys-color-outline-variant)]/40 rounded-[2rem] flex flex-col overflow-hidden shadow-sm">
                <div class="flex-1 overflow-y-auto">
                    @include('livewire.dashboard.tab.calendar.events')
                </div>
            </div>
        </div>

        <div
            class="mx-auto w-full flex-1 md:scale-[.90] md:w-[65%] h-full flex flex-col overflow-hidden bg-[var(--md-sys-color-surface)] rounded-[2rem] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm transition-all duration-300 hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]">
            @include('livewire.dashboard.tab.calendar.navigations')


            <div class="flex-1 overflow-y-auto px-4 md:px-8 pb-8 scrollbar-hide">
                @include('livewire.dashboard.tab.calendar.grid')
            </div>
        </div>
    </div>

    @include('livewire.dashboard.tab.calendar.create')
</div>
