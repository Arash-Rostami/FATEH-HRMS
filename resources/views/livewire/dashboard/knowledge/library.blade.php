<div dir="rtl" data-media-root x-data="media()" x-on:keydown.escape.window="onEscape($event)"
     data-library="{{ json_encode($this->library, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}"
     data-progress-url="{{ route('media.progress') }}" data-user-id="{{ auth()->id() }}" data-notes-url="{{ route('media.notes') }}"
     data-sync="{{ json_encode(config('media.sync')) }}" data-open="{{ $open }}"
     class="w-full h-[calc(100dvh-60px)] md:h-[max(1000px,calc(100dvh-80px))] relative overflow-hidden animate-fade"
     role="region" aria-label="کتابخانهٔ رسانه">
    <div class="max-w-[88rem] mx-auto page-wrapper h-full flex flex-col">
        <x-ui.title icon="video_library" title="رسانه" :count="count($this->library['items'])" countLabel="رسانه">
            <x-slot:actions>
                <x-ui.buttons.form variant="ghost" size="icon" x-on:click="showBadgeLegend()" title="راهنمای نشانگر اعلان" aria-label="راهنمای نشانگر اعلان"><span class="material-symbols-rounded">notifications</span></x-ui.buttons.form>
                <x-ui.buttons.form variant="ghost" size="icon" x-on:click="showLegend()" title="راهنمای رسانه" aria-label="راهنمای رسانه"><span class="material-symbols-rounded">help</span></x-ui.buttons.form>
            </x-slot:actions>
        </x-ui.title>
        <x-ui.modals.max-backdrop />
        <div class="chat-widget flex-1 min-h-0 flex flex-col" :class="{ 'max-widget': max || maxLeaving, 'max-widget-leaving': maxLeaving }">
            <div class="flex flex-1 min-h-0 overflow-hidden">
                @include('livewire.dashboard.knowledge.library.sidebar')
                @include('livewire.dashboard.knowledge.library.workspace')
            </div>
            <x-dashboard.media.player part="bar" />
        </div>
    </div>
    <x-dashboard.modal.badge-legend name="media-badge-legend" :items="array_filter([\App\Services\Menu\BadgeLegendCatalog::get('media-controller')])" />
    <div wire:ignore x-ref="mediaTracks" hidden></div>
    <x-ui.modals.dialog name="media-legend" title="راهنمای رسانه">
        @include('livewire.dashboard.knowledge.library.legend')
    </x-ui.modals.dialog>
</div>
