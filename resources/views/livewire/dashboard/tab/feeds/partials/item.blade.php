<div
    class="flex flex-col h-full bg-[var(--md-sys-color-surface)] rounded-2xl overflow-hidden shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 relative group transition-all duration-300"
    :class="{
        'shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] ring-1 ring-[var(--md-sys-color-primary)]/30': activeId == {{ $feed->id }}
    }">

    {{-- Active Stripe Indicator --}}
    <div
        x-show="activeId == {{ $feed->id }}"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-x-0"
        x-transition:enter-end="opacity-100 scale-x-100"
        class="absolute top-0 left-0 right-0 h-[3px] bg-[var(--md-sys-color-primary)] z-20"
        style="box-shadow: 0 2px 8px color-mix(in srgb, var(--md-sys-color-primary) 60%, transparent);">
    </div>

    @include('livewire.dashboard.tab.feeds.partials.header', ['feed' => $feed])

    <div class="flex-1 overflow-y-auto feed-scrollbar p-5 md:p-6 space-y-5 pb-24">
        @if(!empty($feed?->content))
            <div class="text-sm leading-[2] text-[var(--md-sys-color-on-surface)] text-right text-justify" dir="rtl">
                {!! superClean($feed->content) !!}
            </div>
        @endif

        @if(!empty($feed?->media_paths))
            <div class="rounded-xl overflow-hidden border border-[var(--md-sys-color-outline-variant)]/10 bg-[var(--md-sys-color-surface-variant)] shadow-sm">
                @include('livewire.dashboard.tab.feeds.partials.media', ['media' => $feed->media_paths])
            </div>
        @endif

        @if($feed?->reactions?->isNotEmpty())
            <div class="flex flex-wrap gap-2 pt-2 border-t border-[var(--md-sys-color-outline-variant)]/20">
                @foreach($feed->reactions->groupBy('emoji') as $emoji => $reactions)
                    <button wire:click="toggleReaction({{ $feed->id }}, '{{ $emoji }}')"
                            class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-[var(--md-sys-color-surface-variant)]/50 border border-[var(--md-sys-color-outline-variant)]/20 text-xs transition-all hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]">
                        <span>{!! superClean($emoji) !!}</span>
                        <span class="font-bold">{{ $reactions?->count() ?? 0 }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        <div x-data="{ open: false }" class="mt-4">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between p-3 rounded-xl bg-[var(--md-sys-color-surface-variant)]/30 text-[var(--md-sys-color-primary)] text-sm font-bold transition-all hover:bg-[var(--md-sys-color-primary-container)]/30">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-xl">forum</span>
                    <span>نظرات ({{ $feed?->comments?->count() ?? 0 }})</span>
                </div>
                <span class="material-symbols-rounded transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
            </button>

            <div x-show="open" x-collapse>
                @include('livewire.dashboard.tab.feeds.partials.comments', ['comments' => $feed?->comments, 'feed' => $feed])
            </div>
        </div>
    </div>

    <div class="absolute bottom-0 left-0 right-0 p-4 md:p-5 bg-gradient-to-t from-[var(--md-sys-color-surface)] via-[var(--md-sys-color-surface)]/95 to-transparent pt-8 rounded-b-2xl">
        @include('livewire.dashboard.tab.feeds.partials.actions', ['feed' => $feed])
    </div>
</div>
