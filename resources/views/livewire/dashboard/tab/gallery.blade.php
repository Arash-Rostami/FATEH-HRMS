<div
        x-data="gallery()"
        ax-load="visible"
        class="animate-fade relative w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] h-screen overflow-hidden flex flex-col gap-6"
        dir="rtl"
>

    <x-ui.title
            icon="photo_library"
            title="گالری تصاویر"
            :count="$this->totalPhotos"
            countLabel="تصویر"/>

    @include('components.dashboard.header.focus-banner')


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
