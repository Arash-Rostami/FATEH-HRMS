<div dir="rtl"
     x-data="dms()"
     @keydown.escape.window="if(max) toggleMaximize(null)"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
     style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">

    <div class="max-w-[88rem] mx-auto page-wrapper">

        <x-ui.title
            icon="folder_open"
            :title="$activeTab === 'systematic' ? 'مستندات سیستمی' : 'مستندات غیر سیستمی'"
            :count="$this->totalDocs"
            countLabel="سند">
            <x-slot:actions>
                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'dms-badge-legend' })"
                    title="راهنمای نشانگر اعلان"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">notifications</span>
                </button>
                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'dms-status-legend' })"
                    title="راهنمای وضعیت سند"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">help</span>
                </button>
            </x-slot:actions>
        </x-ui.title>

        <x-dashboard.modal.badge-legend
            name="dms-badge-legend"
            :items="[\App\Services\Menu\BadgeLegendCatalog::get('dms-controller')]"
            title="راهنمای نشانگر اسناد"
        />

        <x-ui.modals.dialog name="dms-status-legend" title="راهنمای وضعیت سند">
            @include('livewire.dashboard.dms.status-legend')
        </x-ui.modals.dialog>

        @include('components.dashboard.header.focus-chip')

        <div class="w-fit z-1 bg-[var(--md-sys-color-surface)] mb-6">
            <x-ui.buttons.tab-selector
                wire:key="tab-selector-{{ $activeTab }}"
                :active-tab="$activeTab"
                :has-a11y="true"
                :tabs="[
                    ['id' => 'systematic', 'label' => 'سیستمی', 'icon' => 'account_tree'],
                    ['id' => 'non_systematic', 'label' => 'غیر سیستمی', 'icon' => 'description']
                ]"
            />
        </div>

        <div class="mb-6 z-10 relative">

            @include('livewire.dashboard.dms.filters')

        </div>

        @include('livewire.dashboard.dms.recent-docs')

        @include('livewire.dashboard.dms.pdf-viewer')

        <div class="space-y-6 relative z-10">

            @include('livewire.dashboard.dms.legend')

            <x-ui.modals.max-backdrop/>

            <div :class="{ 'max-widget': max }">
                @include('livewire.dashboard.dms.table')
            </div>

        </div>
    </div>
</div>
