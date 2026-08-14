<div
    x-data="feed"
    @record-focus.window="if ($event.detail.type === 'feeds') activeId = $event.detail.id"
    @confirmation-confirmed.window="$wire.dispatch($event.detail.method, {commentId: $event.detail.params})"
    @keydown.escape.window="maximizedFeed && toggleMaximize(null)"
    class="animate-fade relative w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] h-screen overflow-hidden flex flex-col gap-6"
    dir="rtl"
>
    <x-ui.title
        icon="rss_feed"
        title="اخبار و فیدها"
        :count="$this->totalFeeds">
        <x-slot:actions>
            <button
                type="button"
                @click="$dispatch('open-modal', { name: 'feeds-badge-legend' })"
                title="راهنمای نشانگر اعلان"
                class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
            >
                <span class="material-symbols-rounded text-lg">notifications</span>
            </button>
            <button
                type="button"
                @click="$dispatch('open-modal', { name: 'feeds-legend' })"
                title="راهنمای فیدها"
                class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
            >
                <span class="material-symbols-rounded text-lg">help</span>
            </button>
        </x-slot:actions>
    </x-ui.title>

    <x-dashboard.modal.badge-legend
        name="feeds-badge-legend"
        :items="[\App\Services\Menu\BadgeLegendCatalog::get('feeds')]"
    />

    <x-ui.modals.dialog name="feeds-legend" title="راهنمای فیدها">
        @include('livewire.dashboard.tab.feeds.legend')
    </x-ui.modals.dialog>

    <div :class="{ 'hidden': maximizedFeed }" class="flex flex-col gap-6">

        @include('components.dashboard.header.focus-chip')

        @include('livewire.dashboard.tab.feeds.filters')
    </div>


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
