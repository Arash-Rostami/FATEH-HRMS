<div
    x-data="{
        calendarOpen: true
    }"
    class="relative w-full h-full bg-[var(--md-sys-color-background)] overflow-hidden flex flex-col md:flex-row"
    dir="rtl"
>
    {{-- Main Calendar Area --}}
    <div class="flex-1 h-full flex flex-col p-4 md:p-6 overflow-hidden relative z-10">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6 shrink-0">
            <div class="flex items-center gap-4">
                <h2 class="text-[var(--md-sys-color-on-surface)] text-2xl font-bold font-yekan">
                    {{ \Morilog\Jalali\Jalalian::fromFormat('Y-m-d', $currentYear.'-'.$currentMonth.'-01')->format('F Y') }}
                </h2>

                <div class="flex items-center bg-[var(--md-sys-color-surface-container)] rounded-full p-1 border border-[var(--md-sys-color-outline-variant)]">
                    <button wire:click="prevMonth" class="p-2 hover:bg-[var(--md-sys-color-surface-container-high)] rounded-full transition-colors">
                        <span class="material-symbols-rounded text-[var(--md-sys-color-on-surface-variant)]">chevron_right</span>
                    </button>
                    <button wire:click="goToToday" class="px-4 py-1 text-sm font-medium text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)] transition-colors">
                        امروز
                    </button>
                    <button wire:click="nextMonth" class="p-2 hover:bg-[var(--md-sys-color-surface-container-high)] rounded-full transition-colors">
                        <span class="material-symbols-rounded text-[var(--md-sys-color-on-surface-variant)]">chevron_left</span>
                    </button>
                </div>
            </div>

            <button
                wire:click="openCreateModal"
                class="hidden md:flex items-center gap-2 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] px-4 py-2.5 rounded-xl hover:shadow-lg hover:shadow-[var(--md-sys-color-primary)]/30 transition-all active:scale-95"
            >
                <span class="material-symbols-rounded">add</span>
                <span class="font-medium">رویداد جدید</span>
            </button>
        </div>

        {{-- Grid --}}
        <div class="flex-1 overflow-y-auto pr-1">
            @include('livewire.dashboard.tab.calendar.partials.grid')
        </div>
    </div>

    {{-- Sidebar (Events List) --}}
    <div class="w-full md:w-[380px] h-[40vh] md:h-full bg-[var(--md-sys-color-surface-container-low)] border-t md:border-t-0 md:border-r border-[var(--md-sys-color-outline-variant)] flex flex-col shadow-xl z-20 relative transition-transform duration-300">
        @include('livewire.dashboard.tab.calendar.partials.events-list')
    </div>

    {{-- Modals --}}
    @include('livewire.dashboard.tab.calendar.partials.create-modal')
    @include('livewire.dashboard.tab.calendar.partials.delete-modal')

    {{-- Floating Action Button (Mobile) --}}
    <button
        wire:click="openCreateModal"
        class="md:hidden absolute bottom-6 left-6 z-50 w-14 h-14 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-2xl shadow-xl shadow-[var(--md-sys-color-primary)]/40 flex items-center justify-center active:scale-90 transition-transform"
    >
        <span class="material-symbols-rounded text-3xl">add</span>
    </button>
</div>
