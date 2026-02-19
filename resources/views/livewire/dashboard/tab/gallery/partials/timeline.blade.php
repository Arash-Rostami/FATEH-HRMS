<div class="absolute top-1/2 left-0 right-0 h-0.5 bg-[var(--md-sys-color-outline-variant)] opacity-20 -translate-y-1/2 z-0 hidden sm:block"></div>

<div
    x-ref="timeline"
    @scroll.debounce.100ms="handleScroll"
    class="flex overflow-x-auto overflow-y-hidden snap-x snap-mandatory scrollbar-hide w-full h-full items-center gap-8 px-[5%] md:pr-[10%] md:pl-4 z-10"
    style="scroll-behavior: smooth; -webkit-overflow-scrolling: touch;"
>
    <div
        x-ref="galleryContainer"
        class="w-full h-full flex flex-col md:flex-row overflow-y-auto md:overflow-y-hidden md:overflow-x-auto md:snap-x md:snap-mandatory gap-12 p-4 md:p-8 scrollbar-hide items-center md:items-stretch"
    >
        @foreach($this->photos as $photo)
            <div
                wire:key="photo-{{ $photo->id }}"
                data-photo-id="{{ $photo->id }}"
                class="shrink-0 w-full max-w-md h-[70vh] md:h-[80vh] md:w-[400px] snap-center transition-all duration-500 ease-out relative"
                :class="{
                        'z-30 scale-105': activeId == {{ $photo->id }},
                        'z-10 scale-95 opacity-80': activeId != {{ $photo->id }}
                    }"
            >
                {{-- Timeline Marker --}}
                <div class="absolute top-1/2 -right-10 z-0 hidden md:flex flex-col items-center justify-center -translate-y-1/2 translate-x-1/2 pointer-events-none">
                    <div class="absolute bottom-10 whitespace-nowrap px-2 py-1 rounded-md bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span class="text-[10px] font-bold text-[var(--md-sys-color-primary)]">
                            {{ jdate($photo->event_date)->format('%d %B %Y') }}
                        </span>
                        <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-[var(--md-sys-color-surface-container-high)] rotate-45 border-r border-b border-[var(--md-sys-color-outline-variant)]/20"></div>
                    </div>

                    <div
                        class="w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-container-high)] border-4 border-[var(--md-sys-color-background)] shadow-sm flex items-center justify-center transition-transform duration-300"
                        :class="activeId == {{ $photo->id }} ? 'scale-125 border-[var(--md-sys-color-primary)]' : ''"
                    >
                        <div class="w-2 h-2 rounded-full bg-[var(--md-sys-color-primary)]"></div>
                    </div>
                </div>

                <div class="relative z-20 h-full w-full">
                    @include('livewire.dashboard.tab.gallery.partials.item', ['photo' => $photo])
                </div>
            </div>
        @endforeach

        @if($hasMorePages)
            <div
                x-ref="loadTrigger"
                wire:key="loader-{{ count($photoIds) }}"
                class="shrink-0 w-full md:w-24 h-24 md:h-full snap-center flex items-center justify-center"
            >
                <x-dashboard.loader.spinner/>
            </div>
        @endif

        <div class="shrink-0 w-4 md:w-[20%] snap-align-none pointer-events-none h-1"></div>
    </div>
</div>

<button
    @click="scrollPrev"
    class="timeline-nav-btn right-4 sm:right-8 rounded-xl"
    aria-label="Previous"
>
    <span class="material-symbols-rounded text-3xl">chevron_right</span>
</button>

<button
    @click="scrollNext"
    class="timeline-nav-btn left-4 sm:left-8 rounded-xl"
    aria-label="Next"
>
    <span class="material-symbols-rounded text-3xl">chevron_left</span>
</button>
