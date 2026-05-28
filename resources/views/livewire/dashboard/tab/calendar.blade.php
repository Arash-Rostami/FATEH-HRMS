<div
    dir="rtl"
    class="animate-fade relative w-full max-w-[88rem] mx-auto flex flex-col gap-4 h-auto"
    x-data="{ calendarOpen: true }"
    @confirmation-confirmed.window="$wire.call($event.detail.method, $event.detail.params)">

    <x-ui.title icon="calendar_month" title="تقویم کاری"/>

    <div class="w-full grid grid-cols-1 md:grid-cols-[30%_1fr] gap-6 items-stretch min-h-0">

        {{-- Sidebar --}}
        <div class="flex flex-col gap-4 min-h-0 h-auto">
            <button
                wire:click="openCreateModal"
                class="flex items-center justify-center gap-3 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] w-full py-3.5 rounded-2xl hover:shadow-2xl hover:shadow-[var(--md-sys-color-primary)]/40 transition-all active:scale-[0.98] group shrink-0"
            >
                <span class="material-symbols-rounded text-2xl group-hover:rotate-90 transition-transform duration-300">add</span>
                <span class="font-bold text-base">رویداد جدید</span>
            </button>

            <div
                class="min-h-0 flex-1 bg-[var(--md-sys-color-surface-container-low)]/60 border border-[var(--md-sys-color-outline-variant)]/40 rounded-[2rem] overflow-hidden shadow-sm">
                @include('livewire.dashboard.tab.calendar.events')
            </div>
        </div>

        {{-- Calendar --}}
        <div class="min-h-0 flex-1 overflow-y-auto">
            @include('livewire.dashboard.tab.calendar.grid')
        </div>
    </div>

    @include('livewire.dashboard.tab.calendar.create')
</div>
