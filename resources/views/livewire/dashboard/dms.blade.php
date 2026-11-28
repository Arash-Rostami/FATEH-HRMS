<div dir="rtl"
     x-data="dms()"
     @keydown.escape.window="onEscape($event)"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
    >

    <div class="max-w-[88rem] mx-auto page-wrapper">

        <x-ui.title
            icon="folder_open"
            :title="$activeTab === 'systematic' ? 'مدیریت اسناد' : 'مدیریت اسناد (سوابق)'"
            :count="$this->totalDocs"
            countLabel="سند">
            <x-slot:actions>
                <x-ui.buttons.module-links :active="$activeTab" :tabs="[
                    ['id' => 'systematic', 'label' => 'اسناد', 'icon' => 'description', 'route' => route('dms', ['tab' => 'systematic'])],
                    ['id' => 'non_systematic', 'label' => 'سوابق', 'icon' => 'history', 'route' => route('dms', ['tab' => 'non_systematic'])],
                ]"/>
                <x-ui.buttons.title-more guide="dms">
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
                    <x-slot:menu>
                        <x-ui.modals.menu-row icon="notifications" label="راهنمای نشانگر اعلان" click="open = false; $dispatch('open-modal', { name: 'dms-badge-legend' })"/>
                        <x-ui.modals.menu-row icon="help" label="راهنمای وضعیت سند" click="open = false; $dispatch('open-modal', { name: 'dms-status-legend' })"/>
                    </x-slot:menu>
                </x-ui.buttons.title-more>
            </x-slot:actions>
        </x-ui.title>

        <x-dashboard.modal.badge-legend
            name="dms-badge-legend"
            :items="[\App\Services\Menu\BadgeLegendCatalog::get('dms-controller')]"
            title="راهنمای نشانگر اسناد"
        />

        <x-ui.modals.dialog name="dms-status-legend" title="راهنمای وضعیت سند">
            @include('livewire.dashboard.dms.legend')
        </x-ui.modals.dialog>

        @include('components.dashboard.header.focus-chip')

        <div class="mb-6 z-10 relative">

            @include('livewire.dashboard.dms.filters')

        </div>

        @include('livewire.dashboard.dms.recent-docs')

        @include('livewire.dashboard.dms.pdf-viewer')

        <div class="space-y-6 relative z-10">

            @include('livewire.dashboard.dms.pending-banner')

            <x-ui.modals.max-backdrop/>

            <div :class="{ 'max-widget': max || maxLeaving, 'max-widget-leaving': maxLeaving }">
                @include('livewire.dashboard.dms.table')
            </div>

        </div>
    </div>
</div>
