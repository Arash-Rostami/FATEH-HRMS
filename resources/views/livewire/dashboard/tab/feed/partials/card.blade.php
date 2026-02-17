@props(['feed'])

{{--
    This is the content of a swiper-slide.
    The swiper-slide class is applied on the wrapper in index.blade.php
--}}
<div class="w-full h-full p-6 sm:p-8 flex flex-col justify-between rounded-[32px] bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)]/30 shadow-[0_8px_32px_0_rgba(0,0,0,0.37)] backdrop-blur-xl transition-all duration-500 overflow-hidden relative group">

    {{-- Decorative Gradient Background --}}
    <div class="absolute inset-0 bg-gradient-to-br from-[var(--md-sys-color-primary)]/5 via-transparent to-[var(--md-sys-color-tertiary)]/5 opacity-50 group-hover:opacity-80 transition-opacity duration-700"></div>

    {{-- Header --}}
    <div class="relative z-10 flex items-start gap-4 mb-4">
        <div class="relative">
            <img
                src="{{ $feed->user->profile_photo_url ?? 'https://ui-avatars.com/api/?name='.urlencode($feed->user->name ?? 'User') }}"
                alt="{{ $feed->user->name ?? 'User' }}"
                class="w-12 h-12 rounded-full object-cover border-2 border-[var(--md-sys-color-surface)] shadow-md"
            >
            <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-[var(--md-sys-color-primary)] border-2 border-[var(--md-sys-color-surface)] flex items-center justify-center text-[10px] text-[var(--md-sys-color-on-primary)] font-bold shadow-sm">
                <span class="material-symbols-rounded text-[12px]">verified</span>
            </div>
        </div>

        <div class="flex-1 min-w-0">
            <h3 class="text-[var(--md-sys-color-on-surface)] font-bold text-lg truncate leading-tight">
                {{ $feed->user->name ?? 'Unknown User' }}
            </h3>
            <div class="flex items-center gap-2 mt-0.5 text-xs text-[var(--md-sys-color-on-surface-variant)]">
                <span class="opacity-80">{{ $feed->created_at->diffForHumans() }}</span>
                <span class="w-1 h-1 rounded-full bg-[var(--md-sys-color-outline)]"></span>
                <span class="font-medium text-[var(--md-sys-color-primary)]">{{ $feed->category ?? 'General' }}</span>
            </div>
        </div>

        <button class="p-2 -mr-2 rounded-full hover:bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] transition-colors">
            <span class="material-symbols-rounded">more_horiz</span>
        </button>
    </div>

    {{-- Content Body --}}
    <div class="relative z-10 flex-1 overflow-y-auto custom-scrollbar pr-1 -mr-1">
        <p class="text-[var(--md-sys-color-on-surface)] text-base leading-relaxed whitespace-pre-line font-normal tracking-wide">
            {{ $feed->content }}
        </p>

        @if($feed->media_paths)
            @include('livewire.dashboard.tab.feed.partials.media', ['paths' => $feed->media_paths])
        @endif

        @if($feed->poll_options)
             {{-- Future Poll Implementation Placeholder --}}
             <div class="mt-4 p-4 rounded-xl bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/50 text-center text-sm text-[var(--md-sys-color-on-surface-variant)]">
                 <span class="material-symbols-rounded align-middle mr-1">poll</span> Poll Feature Coming Soon
             </div>
        @endif
    </div>

    {{-- Footer / Actions --}}
    <div class="relative z-10 pt-4 mt-2 border-t border-[var(--md-sys-color-outline-variant)]/20">
        @include('livewire.dashboard.tab.feed.partials.actions', ['feed' => $feed])
    </div>
</div>
