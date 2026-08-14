<div
        x-data="links"
        class="animate-fade h-full w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] relative overflow-y-auto overflow-x-hidden space-y-6 pb-6 custom-scrollbar"
        dir="rtl">

    <x-ui.title icon="open_in_new" title="لینک‌ها و مسیرهای دیجیتال سازمان" :count="$this->totalLinks" countLabel="لینک">
        <x-slot:actions>
            <button
                type="button"
                @click="$dispatch('open-modal', { name: 'links-legend' })"
                title="راهنمای لینک‌ها"
                class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
            >
                <span class="material-symbols-rounded text-lg">help</span>
            </button>
        </x-slot:actions>
    </x-ui.title>

    <x-ui.modals.dialog name="links-legend" title="راهنمای لینک‌ها">
        @include('livewire.dashboard.tab.links.legend')
    </x-ui.modals.dialog>

    @include('components.dashboard.header.focus-chip')

    @include('livewire.dashboard.tab.links.smart')

    @include('livewire.dashboard.tab.links.internal')

    @include('livewire.dashboard.tab.links.external')

</div>
