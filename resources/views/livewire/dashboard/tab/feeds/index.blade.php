<div @confirmation-confirmed.window="$wire.dispatch($event.detail.method, {commentId: $event.detail.params})"
     x-data="feed"
     class="animate-fade relative w-full h-full bg-[var(--md-sys-color-background)] p-4 md:p-8"
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
