<div
    class="animate-fade h-full w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] relative overflow-hidden flex flex-col gap-6"
    dir="rtl"
    x-data="share()"
    @open-post-panel.window="panelOpen = true"
>
    <x-ui.title icon="campaign" title="پست‌ها" :count="$this->totalPosts"/>

    @include('components.dashboard.header.focus-chip')


    <div class="flex-1 w-full relative overflow-hidden flex flex-col lg:flex-row gap-6">
        @include('livewire.dashboard.tab.posts.pinned')

        @include('livewire.dashboard.tab.posts.grid')

        @include('livewire.dashboard.tab.posts.details')
    </div>
</div>
