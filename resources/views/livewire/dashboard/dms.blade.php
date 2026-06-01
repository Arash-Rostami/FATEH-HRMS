<div dir="rtl"
     x-data="dms()"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
     style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">

    <div class="max-w-[88rem] mx-auto page-wrapper">

        <x-ui.title
                icon="folder_open"
                title="مستندات"
                :count="$this->totalDocs"
                countLabel="سند"/>

        @include('components.dashboard.header.focus-banner')

        <div class="mb-6 z-10 relative">

            @include('livewire.dashboard.dms.filters')

        </div>

        @include('livewire.dashboard.dms.pdf-viewer')

        <div class="space-y-6 relative z-10">

            @include('livewire.dashboard.dms.legend')
            @include('livewire.dashboard.dms.table')

        </div>
    </div>
</div>
