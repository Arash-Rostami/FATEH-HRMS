<div
    :class="{'!bg-[var(--md-sys-color-primary-container)]': activeId == {{ $feed->id }}}"
    class="bg-[var(--md-sys-color-surface)] flex flex-col h-full bg-[var(--md-sys-color-surface)] rounded-[32px] overflow-hidden shadow-xl border border-[var(--md-sys-color-outline-variant)]/20 relative group">

    @include('livewire.dashboard.tab.feeds.partials.header', ['feed' => $feed])


    <div class="flex-1 overflow-y-auto feed-scrollbar p-5 space-y-6 pb-24">
        @if(!empty($feed?->content))
            <div class="text-[15px] leading-relaxed text-[var(--md-sys-color-on-surface)] text-right" dir="rtl">
                {!! superClean($feed->content) !!}
            </div>
        @endif

        @if(!empty($feed?->media_paths))
            <div
                class="rounded-2xl overflow-hidden border border-[var(--md-sys-color-outline-variant)]/10 bg-black shadow-lg">
                @include('livewire.dashboard.tab.feeds.partials.media', ['media' => $feed->media_paths])
            </div>
        @endif

        @if($feed?->reactions?->isNotEmpty())
            <div class="flex flex-wrap gap-2 pt-2 border-t border-[var(--md-sys-color-outline-variant)]/5">
                @foreach($feed->reactions->groupBy('emoji') as $emoji => $reactions)
                    <button wire:click="toggleReaction({{ $feed->id }}, '{{ $emoji }}')"
                            class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/20 text-xs transition-all hover:bg-[var(--md-sys-color-primary-container)]">
                        <span>{!! superClean($emoji) !!}</span>
                        <span class="font-bold">{{ $reactions?->count() ?? 0 }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        <div x-data="{ open: false }" class="mt-4">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between p-3 rounded-2xl bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-primary)] text-sm font-bold transition-all hover:bg-[var(--md-sys-color-primary-container)]/30">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-xl">forum</span>
                    <span>نظرات ({{ $feed?->comments?->count() ?? 0 }})</span>
                </div>
                <span class="material-symbols-rounded transition-transform" :class="open ? 'rotate-180' : ''">expand_more</span>
            </button>

            <div x-show="open" x-collapse>
                @include('livewire.dashboard.tab.feeds.partials.comments', ['comments' => $feed?->comments, 'feed' => $feed])
            </div>
        </div>
    </div>

    <div
        class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-[var(--md-sys-color-surface)] via-[var(--md-sys-color-surface)]/95 to-transparent pt-8">
        @include('livewire.dashboard.tab.feeds.partials.actions', ['feed' => $feed])
    </div>
</div>
