<div
    x-data="gallery()"
    ax-load="visible"
    class="relative w-full h-full bg-[var(--md-sys-color-background)] p-4 md:p-8 flex flex-col gap-4"
    dir="rtl"
>
    <x-dashboard.tab.title icon="photo_library" title="گالری تصاویر" :count="$this->totalPhotos" countLabel="تصویر" />

    <div class="flex-1 w-full relative">
        @if($assetsLoaded)
            @include('livewire.dashboard.tab.gallery.partials.styles')
        @endif

        @include('livewire.dashboard.tab.gallery.partials.timeline')

        <x-dashboard.modal.confirmation/>
    </div>
</div>
