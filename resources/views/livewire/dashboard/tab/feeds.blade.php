<div @confirmation-confirmed.window="$wire.dispatch($event.detail.method, {commentId: $event.detail.params})"
     x-data="feed"
     class="animate-fade relative w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] h-screen overflow-hidden flex flex-col gap-6"
     dir="rtl"
>
    <x-ui.title
        icon="rss_feed"
        title="اخبار و فیدها"
        :count="$this->totalFeeds"/>

    @if($assetsLoaded)
        @include('livewire.dashboard.tab.feeds.styles')
    @endif

    @include('livewire.dashboard.tab.feeds.timeline')

    <x-ui.buttons.toggle
        alpine="true"
        alpineState="showTimeline"
        @click="showTimeline = !showTimeline"
        bordered="true"
        xText="showTimeline ? 'مخفی کردن تایم‌لاین' : 'نمایش تایم‌لاین'"
        class="glass-panel !border-transparent mr-auto hidden md:block"
    />
</div>
