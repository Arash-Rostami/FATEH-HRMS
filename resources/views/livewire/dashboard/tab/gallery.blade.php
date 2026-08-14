<div
        x-data="gallery()"
        ax-load="visible"
        class="animate-fade relative w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] h-screen overflow-hidden flex flex-col gap-6"
        dir="rtl"
>

    <x-ui.title
            icon="photo_library"
            title="گالری"
            :count="$this->totalPhotos"
            countLabel="مورد">
        <x-slot:actions>
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


    @include('livewire.dashboard.tab.gallery.timeline')


    <x-ui.buttons.toggle
            alpine="true"
            alpineState="showTimeline"
            @click="showTimeline = !showTimeline"
            bordered="true"
            xText="showTimeline ? 'مخفی کردن تایم‌لاین' : 'نمایش تایم‌لاین'"
            class="glass-panel !border-transparent mr-auto hidden md:block"
    />
</div>
