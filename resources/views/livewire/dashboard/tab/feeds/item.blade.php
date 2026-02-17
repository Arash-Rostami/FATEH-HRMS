<div class="h-full w-full bg-[var(--md-sys-color-surface-container-low)] rounded-3xl shadow-lg border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden flex flex-col transition-transform hover:shadow-xl group">
    <div class="p-4 flex items-center justify-between border-b border-[var(--md-sys-color-outline-variant)]/10 bg-[var(--md-sys-color-surface-container)]/50 backdrop-blur-sm">
        <div class="flex items-center gap-3">
            <div class="relative w-10 h-10 rounded-full overflow-hidden border border-[var(--md-sys-color-outline)]/20 shadow-sm">
                <img src="{{ $feed->user->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($feed->user->name ?? 'User') }}" alt="{{ $feed->user->name ?? 'User' }}" class="w-full h-full object-cover">
                @if($feed->user->is_online ?? false)
                    <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-[var(--md-sys-color-surface)] rounded-full"></div>
                @endif
            </div>
            <div class="flex flex-col">
                <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $feed->user->name ?? 'User' }}</span>
                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">{{ $feed->created_at?->diffForHumans() ?? 'Just now' }}</span>
            </div>
        </div>
        <button class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] transition-colors">
            <span class="material-symbols-rounded text-xl">more_vert</span>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto p-4 space-y-4 scrollbar-thin scrollbar-thumb-[var(--md-sys-color-outline-variant)]/20">
        @if($feed->content)
            <div class="text-[14px] leading-relaxed text-[var(--md-sys-color-on-surface)] text-right" dir="rtl">
                {!! superClean($feed->content) !!}
            </div>
        @endif

        @if($feed->media_paths)
            <div class="rounded-xl overflow-hidden shadow-sm border border-[var(--md-sys-color-outline-variant)]/10">
                @include('livewire.dashboard.tab.feeds.media', ['media' => $feed->media_paths])
            </div>
        @endif

        @if(($feed->reactions ?? collect())->isNotEmpty())
            <div class="flex flex-wrap gap-2 pt-2">
                @foreach($feed->reactions->groupBy('emoji') as $emoji => $reactions)
                    <button wire:click="toggleReaction({{ $feed->id }}, '{{ $emoji }}')"
                            class="flex items-center gap-1 px-2 py-1 rounded-full bg-[var(--md-sys-color-surface-container-high)] text-[10px] border border-[var(--md-sys-color-outline-variant)]/20 hover:bg-[var(--md-sys-color-primary-container)] transition-colors">
                        <span>{{ $emoji }}</span>
                        <span class="font-bold">{{ $reactions->count() }}</span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <div class="p-3 bg-[var(--md-sys-color-surface-container)]/30 border-t border-[var(--md-sys-color-outline-variant)]/10">
        @include('livewire.dashboard.tab.feeds.actions', ['feed' => $feed])
    </div>
</div>
