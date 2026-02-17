<div class="flex flex-col h-full bg-[var(--md-sys-color-surface)] rounded-[32px] overflow-hidden shadow-xl border border-[var(--md-sys-color-outline-variant)]/20 relative group">
    <div class="p-5 flex items-center justify-between border-b border-[var(--md-sys-color-outline-variant)]/10 bg-[var(--md-sys-color-surface-container-low)] z-10">
        <div class="flex items-center gap-3">
            <div class="relative w-12 h-12 rounded-2xl overflow-hidden shadow-sm">
                <img src="{{ $feed?->user?->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($feed?->user?->name ?? 'U') }}"
                     class="w-full h-full object-cover"
                     alt="Avatar">
                @if($feed?->user?->is_online)
                    <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-[var(--md-sys-color-surface)] rounded-full"></div>
                @endif
            </div>
            <div class="flex flex-col">
                <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">
                    {!! superClean($feed?->user?->name) ?? 'کاربر' !!}
                </span>
                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-1">
                    <span class="material-symbols-rounded text-xs">history</span>
                    {{ $feed?->created_at?->diffForHumans() ?? '' }}
                </span>
            </div>
        </div>
        <button class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] transition-colors">
            <span class="material-symbols-rounded text-xl">more_vert</span>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto feed-scrollbar p-5 space-y-6 pb-24">
        @if(!empty($feed?->content))
            <div class="text-[15px] leading-relaxed text-[var(--md-sys-color-on-surface)] text-right" dir="rtl">
                {!! superClean($feed->content) !!}
            </div>
        @endif

        @if(!empty($feed?->media_paths))
            <div class="rounded-2xl overflow-hidden border border-[var(--md-sys-color-outline-variant)]/10 bg-black shadow-lg">
                @include('livewire.dashboard.tab.feeds.media', ['media' => $feed->media_paths])
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
                @include('livewire.dashboard.tab.feeds.comments', ['comments' => $feed?->comments, 'feed' => $feed])
            </div>
        </div>
    </div>

    <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-[var(--md-sys-color-surface)] via-[var(--md-sys-color-surface)]/95 to-transparent pt-8">
        @include('livewire.dashboard.tab.feeds.actions', ['feed' => $feed])
    </div>
</div>
