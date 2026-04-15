<div
    x-data="gallery()"
    ax-load="visible"
    class="animate-fade relative w-full max-w-[88rem] mx-auto h-full flex flex-col gap-4"
    dir="rtl"
>
    <x-ui.title icon="photo_library" title="گالری تصاویر" :count="$this->totalPhotos" countLabel="تصویر" />

    <div class="flex-1 w-full relative">

        @if($assetsLoaded)
            @include('livewire.dashboard.tab.gallery.styles')
        @endif

        @include('livewire.dashboard.tab.gallery.timeline')
    </div>
</div>
