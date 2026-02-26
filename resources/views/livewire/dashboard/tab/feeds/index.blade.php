<div
    x-data="feed"
    class="relative w-full max-w-[88rem] mx-auto h-full bg-[var(--md-sys-color-background)]"
    dir="rtl"
>
    <x-dashboard.tab.title
        icon="rss_feed"
        title="اخبار و فیدها"
        :count="$this->totalFeeds"/>

    @if($assetsLoaded)
        @include('livewire.dashboard.tab.feeds.partials.styles')
    @endif

    @include('livewire.dashboard.tab.feeds.partials.timeline')

</div>
