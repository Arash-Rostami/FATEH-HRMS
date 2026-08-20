<div
    x-data="gallery()"
    ax-load="visible"
    class="animate-fade relative w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] h-screen overflow-hidden flex flex-col gap-6"
    dir="rtl"
>

    @php
        $months = $presenter->months($this->photos, 'event_date');
        $viewModes = [
            ['value' => 'filmstrip', 'icon' => 'view_carousel', 'title' => 'نوار فیلم'],
            ['value' => 'wall', 'icon' => 'grid_view', 'title' => 'نمای دیوار'],
        ];
    @endphp

    <x-ui.title
        icon="photo_library"
        title="گالری"
        :count="$this->totalPhotos"
        countLabel="مورد">
        <x-slot:actions>
            <x-ui.buttons.view-toggle :modes="$viewModes" />
            <x-ui.month-filter :months="$months" />
            <x-ui.buttons.icon-toggle state="showTimeline" icon="timeline" title="نمایش/مخفی تایم‌لاین" x-show="view === 'filmstrip'" x-cloak/>

            <button
                type="button"
                @click="$dispatch('open-modal', { name: 'gallery-badge-legend' })"
                title="راهنمای نشانگر اعلان"
                class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
            >
                <span class="material-symbols-rounded text-lg">notifications</span>
            </button>
            <button
                type="button"
                @click="$dispatch('open-modal', { name: 'gallery-legend' })"
                title="راهنمای گالری"
                class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
            >
                <span class="material-symbols-rounded text-lg">help</span>
            </button>
        </x-slot:actions>
    </x-ui.title>

    <x-dashboard.modal.badge-legend
        name="gallery-badge-legend"
        :items="[\App\Services\Menu\BadgeLegendCatalog::get('gallery-controller')]"
    />

    <x-ui.modals.dialog name="gallery-legend" title="راهنمای گالری">
        @include('livewire.dashboard.tab.gallery.legend')
    </x-ui.modals.dialog>

    @include('components.dashboard.header.focus-chip')


    <div x-show="view === 'filmstrip'" x-cloak class="flex-1 min-h-0">
        @include('livewire.dashboard.tab.gallery.timeline')
    </div>

    <div x-show="view === 'wall'" x-cloak class="flex-1 min-h-0 overflow-y-auto custom-scrollbar pb-6">
        @include('livewire.dashboard.tab.gallery.wall')
    </div>
</div>
