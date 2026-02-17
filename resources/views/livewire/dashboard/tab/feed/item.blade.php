<div class="flex flex-col h-full bg-[var(--md-sys-color-surface)] shadow-md rounded-2xl overflow-hidden relative border border-[var(--md-sys-color-outline-variant)]/20">

    <!-- Simple Header -->
    <div class="p-3 flex items-center justify-between border-b border-[var(--md-sys-color-outline-variant)]/10 shrink-0 bg-[var(--md-sys-color-surface-container)]/50">
        <div class="flex items-center gap-3">
            <div class="relative w-10 h-10 rounded-full overflow-hidden border border-[var(--md-sys-color-outline-variant)]/30">
                <img src="{{ $feed->user->profile_photo_url }}" class="w-full h-full object-cover">
            </div>
            <div class="flex flex-col">
                <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)] leading-tight">{{ $feed->user->name }}</span>
                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] leading-tight flex items-center gap-1.5 mt-0.5 font-medium opacity-80">
                    <span>{{ $feed->created_at->diffForHumans() }}</span>
                    @if($feed->category)
                        <span class="w-1 h-1 bg-[var(--md-sys-color-outline)] rounded-full opacity-40"></span>
                        <span class="bg-[var(--md-sys-color-secondary-container)] px-1.5 py-0.5 rounded-full text-[9px] text-[var(--md-sys-color-on-secondary-container)] font-bold tracking-wide uppercase">{{ $feed->category }}</span>
                    @endif
                </span>
            </div>
        </div>

        <button class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] transition-colors active:scale-95">
            <span class="material-symbols-rounded text-xl">more_horiz</span>
        </button>
    </div>

    <!-- Scrollable Content Area -->
    <div class="flex-1 overflow-y-auto feed-scrollbar px-4 py-3 space-y-4 relative pb-20">

        <!-- Text Content -->
        @if($feed->content)
            <div class="prose prose-sm prose-p:text-[var(--md-sys-color-on-surface)] prose-p:leading-relaxed max-w-none text-right font-normal text-sm" dir="rtl">
                {!! nl2br(e($feed->content)) !!}
            </div>
        @endif

        <!-- Rich Media Gallery -->
        @if($feed->media_paths && count($feed->media_paths) > 0)
            <div class="w-full rounded-xl overflow-hidden shadow-sm border border-[var(--md-sys-color-outline-variant)]/10 bg-black">
                @include('livewire.dashboard.tab.feed.media', ['media' => $feed->media_paths])
            </div>
        @endif

        <!-- Static Reactions (Performance) -->
        @if($feed->reactions->isNotEmpty())
            <div class="flex items-center gap-2 flex-wrap pb-2">
                @foreach($feed->reactions->groupBy('emoji') as $emoji => $reactions)
                    <div class="flex items-center gap-1.5 px-2.5 py-1 bg-[var(--md-sys-color-surface-container)] rounded-full text-xs shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 select-none cursor-default">
                        <span class="text-base">{{ $emoji }}</span>
                        <span class="font-bold text-[var(--md-sys-color-on-surface-variant)]">{{ $reactions->count() }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Collapsible Comments Section -->
        <div x-data="{ open: false }" class="border-t border-[var(--md-sys-color-outline-variant)]/10 pt-3">
            <button
                @click="open = !open"
                class="w-full flex items-center justify-between text-sm font-bold text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-surface-container)]/50 p-2 rounded-lg transition-colors mb-2"
            >
                <span class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-lg">chat_bubble_outline</span>
                    <span>نظرات ({{ $feed->comments_count ?? $feed->comments->count() }})</span>
                </span>
                <span class="material-symbols-rounded transform transition-transform duration-300" :class="open ? 'rotate-180' : ''">expand_more</span>
            </button>

            <div x-show="open" x-collapse>
                @include('livewire.dashboard.tab.feed.comments', ['comments' => $feed->comments, 'feed' => $feed])
            </div>
        </div>

    </div>

    <!-- Simple Footer Actions -->
    <div class="absolute bottom-0 left-0 right-0 p-3 shrink-0 flex flex-col gap-3 z-10 bg-[var(--md-sys-color-surface-container-low)] border-t border-[var(--md-sys-color-outline-variant)]/10">
        @include('livewire.dashboard.tab.feed.actions', ['feed' => $feed])
    </div>
</div>
