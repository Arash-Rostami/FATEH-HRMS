<div class="flex flex-col h-full bg-[var(--md-sys-color-surface)] shadow-lg hover:shadow-xl transition-shadow duration-300 rounded-3xl overflow-hidden backdrop-blur-3xl relative border border-[var(--md-sys-color-outline-variant)]/30">

    <div class="p-4 flex items-center justify-between border-b border-[var(--md-sys-color-outline-variant)]/10 shrink-0 bg-gradient-to-b from-[var(--md-sys-color-surface-container-high)]/50 to-transparent">
        <div class="flex items-center gap-3">
            <div class="relative w-10 h-10 rounded-full overflow-hidden border border-[var(--md-sys-color-outline-variant)]/30 shadow-sm ring-2 ring-[var(--md-sys-color-surface)]">
                <img src="{{ $feed->user->profile_photo_url }}" class="w-full h-full object-cover">
            </div>
            <div class="flex flex-col">
                <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)] leading-tight">{{ $feed->user->name }}</span>
                <span class="text-xs text-[var(--md-sys-color-on-surface-variant)] leading-tight flex items-center gap-1 mt-0.5">
                    {{ $feed->created_at->diffForHumans() }}
                    @if($feed->category)
                        <span class="w-1 h-1 bg-[var(--md-sys-color-outline)] rounded-full mx-1 opacity-50"></span>
                        <span class="bg-[var(--md-sys-color-secondary-container)] px-2 py-0.5 rounded-full text-[10px] text-[var(--md-sys-color-on-secondary-container)] font-bold tracking-wide">{{ $feed->category }}</span>
                    @endif
                </span>
            </div>
        </div>

        <button class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] transition-colors active:scale-95">
            <span class="material-symbols-rounded text-[20px]">more_horiz</span>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto custom-scrollbar px-4 py-3 space-y-4">

        @if($feed->content)
            <div class="prose prose-sm prose-p:text-[var(--md-sys-color-on-surface)] prose-p:leading-relaxed prose-a:text-[var(--md-sys-color-primary)] max-w-none text-justify font-normal" dir="auto">
                {!! nl2br(e($feed->content)) !!}
            </div>
        @endif

        @if($feed->media_paths && count($feed->media_paths) > 0)
            <div class="w-full rounded-2xl overflow-hidden border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm">
                @include('livewire.dashboard.tab.feed.media', ['media' => $feed->media_paths])
            </div>
        @endif

        @if($feed->reactions->isNotEmpty())
            <div class="flex items-center gap-2 flex-wrap pb-2 border-b border-[var(--md-sys-color-outline-variant)]/10">
                @foreach($feed->reactions->groupBy('emoji') as $emoji => $reactions)
                    <div class="flex items-center gap-1 px-2 py-1 bg-[var(--md-sys-color-surface-container)] rounded-full text-xs shadow-sm border border-[var(--md-sys-color-outline-variant)]/10 animate-fade-in select-none cursor-default hover:bg-[var(--md-sys-color-surface-container-high)] transition-colors">
                        <span class="text-base">{{ $emoji }}</span>
                        <span class="font-bold text-[var(--md-sys-color-on-surface-variant)]">{{ $reactions->count() }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @include('livewire.dashboard.tab.feed.comments', ['comments' => $feed->comments, 'feed' => $feed])

    </div>

    <div class="p-3 border-t border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface-container-low)]/80 backdrop-blur-md shrink-0 flex flex-col gap-3 rounded-b-3xl">
        @include('livewire.dashboard.tab.feed.actions', ['feed' => $feed])
    </div>
</div>
