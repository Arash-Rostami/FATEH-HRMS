<div
    x-data="feed"
    @record-focus.window="if ($event.detail.type === 'feeds') activeId = $event.detail.id"
    @confirmation-confirmed.window="$wire.dispatch($event.detail.method, {commentId: $event.detail.params})"
    class="animate-fade relative w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] h-screen overflow-hidden flex flex-col gap-6"
    dir="rtl"
>
    <x-ui.title
        icon="rss_feed"
        title="اخبار و فیدها"
        :count="$this->totalFeeds"/>

    @include('components.dashboard.header.focus-banner')

    @include('livewire.dashboard.tab.feeds.filters')

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
