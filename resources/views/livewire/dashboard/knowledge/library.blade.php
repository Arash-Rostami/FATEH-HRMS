@use('App\Services\Menu\BadgeLegendCatalog')
<div dir="rtl" data-media-root x-data="media()" x-on:keydown.escape.window="onEscape($event)"
     data-library="{{ json_encode($this->library, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}"
     data-progress-url="{{ route('media.progress') }}" data-user-id="{{ auth()->id() }}" data-notes-url="{{ route('media.notes') }}"
     data-sync="{{ json_encode(config('media.sync') + ['completion_ratio' => config('media.completion_ratio'), 'position_tolerance' => config('media.position_tolerance')]) }}" data-open="{{ $open }}"
     class="w-full h-[calc(100dvh-60px)] md:h-[max(1000px,calc(100dvh-80px))] relative overflow-hidden animate-fade"
     role="region" aria-label="کتابخانهٔ رسانه">
    <div class="max-w-[88rem] mx-auto page-wrapper h-full flex flex-col">
        @include('components.dashboard.header.focus-chip')
        <template x-teleport="#knowledge-header-tools">
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.buttons.chat-scale/>
                <button type="button" x-on:click="showBadgeLegend()" title="راهنمای نشانگر اعلان" aria-label="راهنمای نشانگر اعلان"
                        class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"><span class="material-symbols-rounded text-lg">notifications</span></button>
                <button type="button" x-on:click="showLegend()" title="راهنمای رسانه" aria-label="راهنمای رسانه"
                        class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"><span class="material-symbols-rounded text-lg">help</span></button>
                <span x-text="number(visibleItems.length) + ' رسانه'" class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">{{ convertToPersian(count($this->library['items'])) }} رسانه</span>
            </div>
        </template>
        <x-ui.modals.max-backdrop />
        <div class="chat-widget flex-1 min-h-0 flex flex-col" :class="{ 'max-widget': max || maxLeaving, 'max-widget-leaving': maxLeaving }">
            <div class="flex flex-1 min-h-0 overflow-hidden">
                @include('livewire.dashboard.knowledge.library.sidebar')
                @include('livewire.dashboard.knowledge.library.workspace')
            </div>
            <x-dashboard.media.player part="bar" />
        </div>
    </div>
    <x-dashboard.modal.badge-legend name="media-badge-legend" :items="array_filter([BadgeLegendCatalog::get('media-controller')])" />
    <div wire:ignore x-ref="mediaTracks" hidden></div>
    <x-ui.modals.dialog name="media-legend" title="راهنمای رسانه">
        @include('livewire.dashboard.knowledge.library.legend')
    </x-ui.modals.dialog>
</div>
