<div
        class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
        style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;"
        dir="rtl">

    <div class="max-w-[88rem] mx-auto page-wrapper">
        <x-ui.title
                icon="work"
                title="فرصت‌های شغلی"
                :count="$this->stats['active']"
                countLabel="فرصت فعال"
        >
            <x-slot:actions>
                <button
                        type="button"
                        @click="$dispatch('open-modal', { name: 'ads-badge-legend' })"
                        title="راهنمای نشانگر اعلان"
                        class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">notifications</span>
                </button>
                <button
                        type="button"
                        @click="$dispatch('open-modal', { name: 'ads-legend' })"
                        title="راهنمای فرصت‌های شغلی"
                        class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">help</span>
                </button>
            </x-slot:actions>
        </x-ui.title>

        <x-dashboard.modal.badge-legend
            name="ads-badge-legend"
            :items="[\App\Services\Menu\BadgeLegendCatalog::get('ads-controller')]"
        />

        <x-ui.modals.dialog name="ads-legend" title="راهنمای فرصت‌های شغلی">
            @include('livewire.dashboard.ads.legend')
        </x-ui.modals.dialog>

        @include('components.dashboard.header.focus-chip')

        <div class="mb-6">
            @include('livewire.dashboard.ads.filters')
        </div>

        @include('livewire.dashboard.ads.grid')
    </div>
</div>
