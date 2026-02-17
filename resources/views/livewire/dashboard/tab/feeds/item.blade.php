<div class="flex flex-col h-full bg-[var(--md-sys-color-surface)] shadow-lg hover:shadow-xl transition-shadow duration-300 rounded-3xl overflow-hidden backdrop-blur-3xl relative border border-[var(--md-sys-color-outline-variant)]/30 group">

    <div class="p-4 flex items-center justify-between border-b border-[var(--md-sys-color-outline-variant)]/10 shrink-0 bg-gradient-to-b from-[var(--md-sys-color-surface-container-high)]/60 to-transparent backdrop-blur-sm z-10">
        <div class="flex items-center gap-3">
            <div class="relative w-12 h-12 rounded-full overflow-hidden border-2 border-[var(--md-sys-color-surface)] shadow-md ring-1 ring-[var(--md-sys-color-outline-variant)]/30 group-hover:scale-105 transition-transform duration-300">
                <img src="{{ $feed->user->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($feed->user->name ?? 'U') }}"
                     class="w-full h-full object-cover"
                     alt="Profile">
                @if($feed->user?->is_online ?? false)
                    <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-[var(--md-sys-color-surface)]"></div>
                @endif
            </div>
            <div class="flex flex-col">
                <span class="text-base font-bold text-[var(--md-sys-color-on-surface)] leading-tight tracking-tight">
                    {{ $feed->user->name ?? 'کاربر حذف شده' }}
                </span>
                <span class="text-xs text-[var(--md-sys-color-on-surface-variant)] leading-tight flex items-center gap-1.5 mt-1 font-medium opacity-80">
                    <span class="material-symbols-rounded text-[14px]">schedule</span>
                    {{ $feed->created_at ? $feed->created_at->diffForHumans() : 'زمان نامشخص' }}
                    @if($feed->category)
                        <span class="w-1 h-1 bg-[var(--md-sys-color-outline)] rounded-full opacity-40"></span>
                        <span class="bg-[var(--md-sys-color-secondary-container)] px-2 py-0.5 rounded-full text-[10px] text-[var(--md-sys-color-on-secondary-container)] font-bold tracking-wide uppercase shadow-sm border border-[var(--md-sys-color-secondary-container)]/50">{{ $feed->category }}</span>
                    @endif
                </span>
            </div>
        </div>

        <button class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] transition-all active:scale-90 hover:rotate-90 duration-300 hover:text-[var(--md-sys-color-primary)]">
            <span class="material-symbols-rounded text-2xl">more_horiz</span>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto feed-scrollbar px-5 py-2 space-y-6 relative pb-20">

        @if($feed->content)
            <div class="prose prose-sm prose-p:text-[var(--md-sys-color-on-surface)] prose-p:leading-8 prose-a:text-[var(--md-sys-color-primary)] max-w-none text-right font-normal text-[15px] tracking-wide" dir="rtl">
                {!! nl2br(e($feed->content)) !!}
            </div>
        @endif

        @if(!empty($feed->media_paths) && count($feed->media_paths) > 0)
            <div class="w-full rounded-2xl overflow-hidden shadow-lg border border-[var(--md-sys-color-outline-variant)]/10 transform transition-transform duration-500 hover:scale-[1.01] bg-black">
                @include('livewire.dashboard.tab.feeds.media', ['media' => $feed->media_paths])
            </div>
        @endif

        @if($feed->reactions && $feed->reactions->isNotEmpty())
            <div class="flex items-center gap-2 flex-wrap pb-2">
                @foreach($feed->reactions->groupBy('emoji') as $emoji => $reactions)
                    <div class="flex items-center gap-1.5 px-3 py-1.5 bg-[var(--md-sys-color-surface-container)]/80 backdrop-blur-md rounded-full text-sm shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 animate-slide-in select-none cursor-default hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-all duration-300 group hover:-translate-y-1">
                        <span class="text-lg group-hover:scale-125 transition-transform duration-300 filter drop-shadow-sm">{{ $emoji }}</span>
                        <span class="font-bold text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-on-primary-container)]">{{ $reactions->count() }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <div x-data="{ open: false }" class="border-t border-[var(--md-sys-color-outline-variant)]/10 pt-4">
            <button
                @click="open = !open"
                class="w-full flex items-center justify-between text-sm font-bold text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)]/10 p-3 rounded-xl transition-all duration-300 mb-2 group border border-transparent hover:border-[var(--md-sys-color-primary)]/10"
            >
                <span class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-xl group-hover:scale-110 transition-transform">chat_bubble_outline</span>
                    <span>نظرات ({{ $feed->comments_count ?? ($feed->comments ? $feed->comments->count() : 0) }})</span>
                </span>
                <span class="material-symbols-rounded transform transition-transform duration-300" :class="open ? 'rotate-180' : ''">expand_more</span>
            </button>

            <div x-show="open" x-collapse>
                @include('livewire.dashboard.tab.feeds.comments', [
                    'comments' => $feed->comments ?? collect(),
                    'feed' => $feed
                ])
            </div>
        </div>

    </div>

    <div class="glass-footer absolute bottom-0 left-0 right-0 p-3 shrink-0 flex flex-col gap-3 z-20 transition-transform duration-300 translate-y-0 group-hover:translate-y-0">
        @include('livewire.dashboard.tab.feeds.actions', ['feed' => $feed])
    </div>
</div>
