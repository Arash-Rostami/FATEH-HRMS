<div
    class="h-full w-full relative overflow-hidden flex flex-col gap-6 p-4 md:p-8"
    dir="rtl"
    x-data="share"
    @open-post-panel.window="panelOpen = true"
>
    <x-dashboard.tab.title icon="campaign" title="پست‌ها" :count="$this->totalPosts" />

    <div class="flex-1 w-full relative overflow-hidden flex flex-col lg:flex-row gap-6">
        @include('livewire.dashboard.tab.posts.partials.pinned')

        @include('livewire.dashboard.tab.posts.partials.grid')

        @include('livewire.dashboard.tab.posts.partials.details')
    </div>
</div>
