<div x-data="feedCarousel" class="h-full w-full relative overflow-hidden bg-[var(--md-sys-color-background)]" dir="rtl">
    @include('livewire.dashboard.tab.feeds.scripts')

    <div x-ref="timeline"
         @scroll.debounce.50ms="checkScroll"
         class="h-full w-full flex flex-col md:flex-row md:overflow-x-auto md:snap-x md:snap-mandatory gap-6 p-4 scrollbar-hide items-center md:px-[20%]">

        @if($this->feeds && $this->feeds->count())
            @foreach($this->feeds as $feed)
                <div wire:key="feed-{{ $feed->id }}"
                     class="feed-item w-full md:w-[400px] shrink-0 transition-all duration-500 ease-out transform opacity-0 scale-90 blur-sm md:snap-center">
                    @include('livewire.dashboard.tab.feeds.item')
                </div>
            @endforeach
        @endif

        @if($hasMorePages)
            <div class="w-full md:w-[400px] shrink-0 flex items-center justify-center p-8 md:snap-center">
                <div class="w-8 h-8 border-4 border-[var(--md-sys-color-primary)] border-t-transparent rounded-full animate-spin"></div>
            </div>
        @endif

        <div class="w-1 md:w-[20%] shrink-0"></div>
    </div>

    <button @click="$refs.timeline.scrollBy({ left: -400, behavior: 'smooth' })"
            class="hidden md:flex absolute left-8 top-1/2 -translate-y-1/2 w-12 h-12 items-center justify-center rounded-full bg-[var(--md-sys-color-surface-container)] shadow-lg hover:scale-110 active:scale-95 transition-all text-[var(--md-sys-color-on-surface)] z-50">
        <span class="material-symbols-rounded">chevron_left</span>
    </button>

    <button @click="$refs.timeline.scrollBy({ left: 400, behavior: 'smooth' })"
            class="hidden md:flex absolute right-8 top-1/2 -translate-y-1/2 w-12 h-12 items-center justify-center rounded-full bg-[var(--md-sys-color-surface-container)] shadow-lg hover:scale-110 active:scale-95 transition-all text-[var(--md-sys-color-on-surface)] z-50">
        <span class="material-symbols-rounded">chevron_right</span>
    </button>
</div>
