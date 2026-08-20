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
            @php
                $months = $presenter->months($this->feeds, 'created_at');
                $viewModes = [
                    ['value' => 'filmstrip', 'icon' => 'view_carousel', 'title' => 'نوار فیلم'],
                    ['value' => 'magazine', 'icon' => 'dashboard', 'title' => 'نمای مجله'],
                ];
            @endphp
            <x-ui.buttons.view-toggle :modes="$viewModes" />
            <x-ui.month-filter :months="$months" />
            <x-ui.buttons.icon-toggle state="showTimeline" icon="timeline" title="نمایش/مخفی تایم‌لاین" x-show="view === 'filmstrip'" x-cloak/>

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


    <div x-show="view === 'filmstrip'" x-cloak class="flex-1 min-h-0">
        @include('livewire.dashboard.tab.feeds.timeline')
    </div>

    <div x-show="view === 'magazine'" x-cloak class="flex-1 min-h-0 overflow-y-auto custom-scrollbar pb-6">
        @include('livewire.dashboard.tab.feeds.magazine')
    </div>
</div>
