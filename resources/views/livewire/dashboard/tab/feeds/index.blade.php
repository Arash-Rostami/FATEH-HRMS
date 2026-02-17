<div
        x-data="{
        scrollNext() {
            this.$refs.timeline.scrollBy({ left: -this.$refs.timeline.offsetWidth, behavior: 'smooth' });
        },
        scrollPrev() {
            this.$refs.timeline.scrollBy({ left: this.$refs.timeline.offsetWidth, behavior: 'smooth' });
        },
        handleScroll() {
            const el = this.$refs.timeline;
            const scrollLeft = Math.abs(el.scrollLeft);
            const maxScroll = el.scrollWidth - el.clientWidth;

            // Increased sensitivity to 500px as you requested
            if (scrollLeft >= maxScroll - 500 && @js($hasMorePages)) {
                $wire.loadMore();
            }
        }
    }"
        class="relative w-full h-full flex flex-col items-center justify-center bg-[var(--md-sys-color-background)] overflow-hidden"
        dir="rtl"
>
    @if($assetsLoaded)
        @include('livewire.dashboard.tab.feeds.styles')
    @endif

    {{-- The Timeline Track --}}
    <div class="absolute top-1/2 left-0 right-0 h-0.5 bg-[var(--md-sys-color-outline-variant)] opacity-20 -translate-y-1/2 z-0 hidden sm:block"></div>

    <div
            x-ref="timeline"
            @scroll.debounce.100ms="handleScroll"
            class="flex overflow-x-auto snap-x snap-mandatory scrollbar-hide w-full h-full items-center gap-8 px-[10%] sm:px-[20%] z-10"
            style="scroll-behavior: smooth; -webkit-overflow-scrolling: touch;"
    >
        @foreach($this->feeds as $feed)
            <div class="flex-shrink-0 w-full max-w-md h-[70vh] snap-center flex flex-col justify-center relative group"
                 wire:key="timeline-card-{{ $feed->id }}"
            >
                {{-- Timeline Node Container --}}
                <div class="absolute top-1/2 -right-4 z-20 hidden sm:flex flex-col items-center justify-center -translate-y-1/2 translate-x-1/2">

                    {{-- NEW: Time Label (Floating Above Dot) --}}
                    <div class="absolute bottom-8 whitespace-nowrap px-2 py-1 rounded-md bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span class="text-[10px] font-bold text-[var(--md-sys-color-primary)]">
                            {{ $feed->created_at->format('H:i') }}
                        </span>
                        {{-- Little triangle pointing down --}}
                        <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-[var(--md-sys-color-surface-container-high)] rotate-45 border-r border-b border-[var(--md-sys-color-outline-variant)]/20"></div>
                    </div>

                    {{-- The Dot --}}
                    <div class="w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-container-high)] border-4 border-[var(--md-sys-color-background)] shadow-sm flex items-center justify-center">
                        <div class="w-2 h-2 rounded-full bg-[var(--md-sys-color-primary)]"></div>
                    </div>

                    {{-- Optional: Date Label (Floating Below Dot) --}}
                    <div class="absolute top-8 whitespace-nowrap opacity-60 group-hover:opacity-100 transition-opacity duration-300">
                        <span class="text-[9px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                            {{ $feed->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>

                @include('livewire.dashboard.tab.feeds.item', ['feed' => $feed])
            </div>
        @endforeach

        {{-- FIXED: Loader Container --}}
        @if($hasMorePages)
            <div class="flex-shrink-0 w-full max-w-md h-[70vh] snap-center flex items-center justify-center" wire:key="timeline-loader">
                <x-dashboard.loader.spinner />
            </div>
        @endif
    </div>

    {{-- Navigation Buttons --}}
    <button
            @click="scrollPrev"
            class="timeline-nav-btn right-4 sm:right-8"
            aria-label="Previous"
    >
        <span class="material-symbols-rounded text-3xl">chevron_right</span>
    </button>

    <button
            @click="scrollNext"
            class="timeline-nav-btn left-4 sm:left-8"
            aria-label="Next"
    >
        <span class="material-symbols-rounded text-3xl">chevron_left</span>
    </button>
</div>
