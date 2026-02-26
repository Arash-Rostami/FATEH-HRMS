<div
    x-data="feed"
    class="animate-fade relative w-full max-w-[88rem] mx-auto h-full"
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
