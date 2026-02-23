<div
    x-data="feed"
    class="relative w-full h-full bg-[var(--md-sys-color-background)] p-4 md:p-8 flex flex-col gap-4"
    dir="rtl"
>
    <x-dashboard.tab.title icon="rss_feed" title="اخبار و فیدها" :count="$this->totalFeeds" />

    <div class="flex-1 w-full relative">
        @if($assetsLoaded)
            @include('livewire.dashboard.tab.feeds.partials.styles')
        @endif

        @include('livewire.dashboard.tab.feeds.partials.timeline')

        <x-dashboard.modal.confirmation/>
    </div>
</div>
