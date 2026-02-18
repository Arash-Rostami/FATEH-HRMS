<div
    x-data="{
        calendarOpen: true
    }"
    class="relative w-full h-full bg-[var(--md-sys-color-background)] overflow-hidden flex flex-col md:flex-row group/calendar"
    dir="rtl"
>
    {{-- Ambient Background Effects --}}
    <div class="absolute top-[-20%] right-[-10%] w-[50%] h-[50%] bg-[var(--md-sys-color-primary-container)]/20 rounded-full blur-[120px] pointer-events-none mix-blend-multiply dark:mix-blend-screen animate-pulse duration-[10s]"></div>
    <div class="absolute bottom-[-20%] left-[-10%] w-[50%] h-[50%] bg-[var(--md-sys-color-tertiary-container)]/20 rounded-full blur-[120px] pointer-events-none mix-blend-multiply dark:mix-blend-screen animate-pulse duration-[15s]"></div>

    {{-- Main Calendar Area --}}
    <div class="flex-1 h-full flex flex-col p-4 md:p-6 overflow-hidden relative z-10 transition-all duration-500">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6 shrink-0 relative z-20">
            <div class="flex items-center gap-6">
                <div class="flex flex-col">
                    <h2 class="text-[var(--md-sys-color-on-surface)] text-3xl font-black font-yekan tracking-tight flex items-center gap-3">
                        {{ \Morilog\Jalali\Jalalian::fromFormat('Y-m-d', $currentYear.'-'.$currentMonth.'-01')->format('F') }}
                        <span class="text-[var(--md-sys-color-outline)] text-xl font-medium">{{ \Morilog\Jalali\Jalalian::fromFormat('Y-m-d', $currentYear.'-'.$currentMonth.'-01')->format('Y') }}</span>
                    </h2>
                </div>

                <div class="flex items-center bg-[var(--md-sys-color-surface-container)] rounded-full p-1.5 border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm backdrop-blur-md">
                    <button wire:click="prevMonth" class="p-2 hover:bg-[var(--md-sys-color-surface-container-high)] rounded-full transition-all active:scale-95 group">
                        <span class="material-symbols-rounded text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)]">chevron_right</span>
                    </button>
                    <button wire:click="goToToday" class="px-5 py-1.5 text-sm font-bold text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-surface-container-high)] rounded-full transition-all mx-1 relative overflow-hidden">
                        <span class="relative z-10">امروز</span>
                    </button>
                    <button wire:click="nextMonth" class="p-2 hover:bg-[var(--md-sys-color-surface-container-high)] rounded-full transition-all active:scale-95 group">
                        <span class="material-symbols-rounded text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)]">chevron_left</span>
                    </button>
                </div>
            </div>

            <button
                wire:click="openCreateModal"
                class="hidden md:flex items-center gap-3 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] pl-5 pr-4 py-3 rounded-2xl shadow-lg shadow-[var(--md-sys-color-primary)]/30 hover:shadow-xl hover:shadow-[var(--md-sys-color-primary)]/40 hover:-translate-y-1 hover:scale-[1.02] active:scale-95 transition-all duration-300 group"
            >
                <span class="material-symbols-rounded text-[24px] group-hover:rotate-90 transition-transform duration-500">add</span>
                <span class="font-bold text-sm tracking-wide">رویداد جدید</span>
            </button>
        </div>

        {{-- Grid Container --}}
        <div class="flex-1 overflow-y-auto pr-1 relative z-10 container-scrollbar custom-scrollbar bg-[var(--md-sys-color-surface)]/30 backdrop-blur-sm rounded-[32px] border border-[var(--md-sys-color-outline-variant)]/20 shadow-inner p-4">
            @include('livewire.dashboard.tab.calendar.partials.grid')
        </div>
    </div>

    {{-- Sidebar (Events List) --}}
    <div class="w-full md:w-[420px] h-[45vh] md:h-full bg-[var(--md-sys-color-surface-container-low)]/80 backdrop-blur-xl border-t md:border-t-0 md:border-r border-[var(--md-sys-color-outline-variant)]/50 flex flex-col shadow-2xl z-30 relative transition-transform duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] translate-y-0">
        {{-- Mobile Drag Handle --}}
        <div class="md:hidden w-full flex justify-center pt-3 pb-1 cursor-grab active:cursor-grabbing">
            <div class="w-12 h-1.5 bg-[var(--md-sys-color-outline-variant)] rounded-full opacity-50"></div>
        </div>

        @include('livewire.dashboard.tab.calendar.partials.events-list')
    </div>

    {{-- Modals --}}
    @include('livewire.dashboard.tab.calendar.partials.create-modal')
    @include('livewire.dashboard.tab.calendar.partials.delete-modal')

    {{-- Floating Action Button (Mobile) --}}
    <button
        wire:click="openCreateModal"
        class="md:hidden absolute bottom-6 left-6 z-50 w-16 h-16 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-[20px] shadow-2xl shadow-[var(--md-sys-color-primary)]/40 flex items-center justify-center active:scale-90 transition-transform duration-300 hover:rotate-90"
    >
        <span class="material-symbols-rounded text-4xl">add</span>
    </button>
</div>
