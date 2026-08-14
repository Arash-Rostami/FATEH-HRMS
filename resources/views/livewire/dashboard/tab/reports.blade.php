<div class="animate-fade w-full max-w-[88rem] mx-auto flex flex-col"
     dir="rtl"
     x-data="report()"
     wire:ignore.self>

    <x-ui.title icon="show_chart" title="گزارشات" :count="$this->totalReports" countLabel="گزارش">
        <x-slot:actions>
            <button
                type="button"
                @click="$dispatch('open-modal', { name: 'reports-badge-legend' })"
                title="راهنمای نشانگر اعلان"
                class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
            >
                <span class="material-symbols-rounded text-lg">notifications</span>
            </button>
            <button
                type="button"
                @click="$dispatch('open-modal', { name: 'reports-legend' })"
                title="راهنمای گزارشات"
                class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
            >
                <span class="material-symbols-rounded text-lg">help</span>
            </button>
        </x-slot:actions>
    </x-ui.title>

    <x-dashboard.modal.badge-legend
        name="reports-badge-legend"
        :items="[\App\Services\Menu\BadgeLegendCatalog::get('reports-controller')]"
    />

    <x-ui.modals.dialog name="reports-legend" title="راهنمای گزارشات">
        @include('livewire.dashboard.tab.reports.legend')
    </x-ui.modals.dialog>

    @include('components.dashboard.header.focus-chip')


    @include('livewire.dashboard.tab.reports.navigations')


    <div x-show="view === 'card'"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="w-full relative group/container hidden md:block"
         style="height: clamp(420px, calc(100svh - 200px), 800px);">

        @include('livewire.dashboard.tab.reports.cards')

    </div>

    <div x-show="view === 'list'"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="w-full px-4 md:px-12 pb-8">

        @include('livewire.dashboard.tab.reports.list')
    </div>

    @include('livewire.dashboard.tab.reports.modal')
</div>
