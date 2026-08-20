<div
    dir="rtl"
    class="relative w-full max-w-[88rem] mx-auto flex flex-col gap-4 h-auto"
    x-data="{ ...calendarView() }"
    x-cloak
    @refresh-calendar.window="$wire.$refresh"
    @keydown.escape.window="if(max) toggleMaximize()"
    @confirmation-confirmed.window="if (($event.detail || {}).method === 'deleteEvent') $wire.call('deleteEvent', $event.detail.params)">

    <x-ui.title icon="calendar_month" title="تقویم کاری">
        <x-slot:actions>
            <div class="flex items-center gap-2 flex-wrap justify-end">
                <div class="hidden md:flex bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-high)_60%,transparent)] rounded-2xl p-1.5 border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                    <button
                        type="button"
                        title="نمای ماهانه"
                        @click="$wire.toggleView('month')"
                        :class="$wire.view === 'month' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]'"
                        class="flex items-center justify-center w-9 h-9 rounded-xl transition-all duration-200"
                    >
                        <span class="material-symbols-rounded text-[20px]">calendar_month</span>
                    </button>
                    <button
                        type="button"
                        title="نمای هفتگی"
                        @click="$wire.toggleView('week')"
                        :class="$wire.view === 'week' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]'"
                        class="flex items-center justify-center w-9 h-9 rounded-xl transition-all duration-200"
                    >
                        <span class="material-symbols-rounded text-[20px]">view_week</span>
                    </button>
                    <button
                        type="button"
                        title="نمای روزانه"
                        @click="$wire.toggleView('day')"
                        :class="$wire.view === 'day' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]'"
                        class="flex items-center justify-center w-9 h-9 rounded-xl transition-all duration-200"
                    >
                        <span class="material-symbols-rounded text-[20px]">calendar_today</span>
                    </button>
                </div>

                <button
                    type="button"
                    x-show="$wire.view === 'week'"
                    x-cloak
                    @click="$wire.set('hideFriday', !$wire.hideFriday)"
                    title="مخفی کردن جمعه"
                    :class="$wire.hideFriday ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-high)_60%,transparent)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]'"
                    class="flex items-center justify-center w-9 h-9 rounded-xl transition-all duration-200"
                >
                    <span class="material-symbols-rounded text-[20px]">visibility_off</span>
                </button>

                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'calendar-badge-legend' })"
                    title="راهنمای نشانگر اعلان"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:text-[var(--md-sys-color-primary)] transition-colors duration-200"
                >
                    <span class="material-symbols-rounded text-lg">notifications</span>
                </button>

                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'calendar-legend' })"
                    title="راهنمای دیدن و ویرایش رویدادها"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:text-[var(--md-sys-color-primary)] transition-colors duration-200"
                >
                    <span class="material-symbols-rounded text-lg">help</span>
                </button>
            </div>
        </x-slot:actions>
    </x-ui.title>

    <x-dashboard.modal.badge-legend
        name="calendar-badge-legend"
        :items="[\App\Services\Menu\BadgeLegendCatalog::get('shared-events'), \App\Services\Menu\BadgeLegendCatalog::get('special-days')]"
    />

    <x-ui.modals.dialog name="calendar-legend" title="راهنمای دیدن و ویرایش رویدادها">
        @include('livewire.dashboard.tab.calendar.legend')
    </x-ui.modals.dialog>

    @include('components.dashboard.header.focus-chip')

    <x-ui.modals.max-backdrop state="max" close="toggleMaximize()"/>

    <div
        x-show="$wire.view === 'month'"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        :class="{ 'max-widget': max }"
        class="w-full flex flex-col min-h-0 bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)] dark:bg-[var(--md-sys-color-surface)] dark:shadow-[0_12px_40px_rgba(0,0,0,0.6)] backdrop-blur-xl rounded-[2rem] overflow-hidden slide-up"
    >
        <div class="w-full grid grid-cols-1 md:grid-cols-[30%_1fr] items-stretch min-h-0 flex-1">
            <div class="flex flex-col border-b md:border-b-0 md:border-l border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_60%,transparent)] p-4 md:p-6 gap-6 min-h-0 h-auto">
                <button
                    wire:click="openCreateModal"
                    class="flex items-center justify-center gap-3 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] w-full py-3.5 rounded-2xl shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_60%,transparent)] transition-all duration-200 active:scale-[0.98] group shrink-0"
                >
                    <span class="material-symbols-rounded text-2xl group-hover:rotate-90 transition-transform duration-300">add</span>
                    <span class="font-bold text-base">رویداد جدید</span>
                </button>

                <div class="min-h-0 flex-1 overflow-y-auto rounded-2xl bg-[color-mix(in_srgb,var(--md-sys-color-surface)_50%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                    @include('livewire.dashboard.tab.calendar.events')
                </div>
            </div>

            <div class="min-h-0 overflow-y-auto p-4 md:p-6 bg-[color-mix(in_srgb,var(--md-sys-color-surface)_40%,transparent)]">
                @include('livewire.dashboard.tab.calendar.view-header')
                @include('livewire.dashboard.tab.calendar.month')
            </div>
        </div>
    </div>

    <div
        x-show="$wire.view === 'week'"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        :class="{ 'max-widget': max }"
        class="w-full flex flex-col min-h-0 bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)] dark:bg-[var(--md-sys-color-surface)] dark:shadow-[0_12px_40px_rgba(0,0,0,0.6)] backdrop-blur-xl rounded-[2rem] overflow-hidden slide-up"
    >
        @include('livewire.dashboard.tab.calendar.view-header')

        <div class="hidden md:block min-h-0 overflow-y-auto scrollbar-hover-reveal p-4 md:p-6" :class="max ? 'h-[calc(100svh-12rem)]' : 'h-[calc(100svh-19rem)]'">
            @include('livewire.dashboard.tab.calendar.week')
        </div>
        <div class="md:hidden p-4">
            @include('livewire.dashboard.tab.calendar.mobile-list', ['scope' => 'week'])
        </div>
    </div>

    <div
        x-show="$wire.view === 'day'"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        :class="{ 'max-widget': max }"
        class="w-full flex flex-col min-h-0 bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)] dark:bg-[var(--md-sys-color-surface)] dark:shadow-[0_12px_40px_rgba(0,0,0,0.6)] backdrop-blur-xl rounded-[2rem] overflow-hidden slide-up"
    >
        @include('livewire.dashboard.tab.calendar.view-header')

        <div class="hidden md:block min-h-0 overflow-y-auto scrollbar-hover-reveal p-4 md:p-6" :class="max ? 'h-[calc(100svh-12rem)]' : 'h-[calc(100svh-19rem)]'">
            @include('livewire.dashboard.tab.calendar.day')
        </div>
        <div class="md:hidden p-4">
            @include('livewire.dashboard.tab.calendar.mobile-list', ['scope' => 'day'])
        </div>
    </div>

    @include('livewire.dashboard.tab.calendar.create')
    @include('livewire.dashboard.tab.calendar.share')
</div>
