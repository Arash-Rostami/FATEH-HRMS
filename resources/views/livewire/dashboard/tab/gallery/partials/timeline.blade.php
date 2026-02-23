<div class="absolute top-1/2 left-0 right-0 h-px bg-[var(--md-sys-color-outline-variant)] opacity-20 -translate-y-1/2 z-0 hidden md:block"></div>

<div
    x-ref="timeline"
    @scroll.debounce.100ms="handleScroll"
    class="flex overflow-x-auto overflow-y-hidden snap-x snap-mandatory scrollbar-hide w-full h-full items-center gap-4 px-[5%] md:pr-[10%] md:pl-4 z-10"
    style="scroll-behavior: smooth; -webkit-overflow-scrolling: touch;"
>
    <div
        x-ref="galleryContainer"
        class="w-full h-full flex flex-col md:flex-row overflow-y-auto md:overflow-y-hidden md:overflow-x-auto md:snap-x md:snap-mandatory gap-6 md:gap-12 p-4 md:p-8 scrollbar-hide items-center md:items-stretch"
    >
        @foreach(->photos as )
            <div
                wire:key="photo-{{ ->id }}"
                data-photo-id="{{ ->id }}"
                class="shrink-0 w-full max-w-md h-[70vh] md:h-[80vh] md:w-[400px] snap-center transition-all duration-500 ease-out relative group"
                :class="{
                        'z-30 scale-100 md:scale-105': activeId == {{ ->id }},
                        'z-10 scale-95 opacity-80 blur-[1px] grayscale-[30%]': activeId != {{ ->id }}
                    }"
            >
                {{-- Timeline Marker (Desktop only) --}}
                <div class="absolute top-1/2 -right-10 z-0 hidden md:flex flex-col items-center justify-center -translate-y-1/2 translate-x-1/2 pointer-events-none">
                    <div class="absolute bottom-12 whitespace-nowrap px-2.5 py-1.5 rounded-lg bg-[var(--md-sys-color-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-2 group-hover:translate-y-0">
                        <span class="text-[10px] font-bold text-[var(--md-sys-color-primary)] font-mono">
                            {{ jdate(->event_date)->format('%d %B %Y') }}
                        </span>
                        <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-[var(--md-sys-color-surface-variant)] rotate-45 border-r border-b border-[var(--md-sys-color-outline-variant)]/20"></div>
                    </div>

                    <div
                        class="w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-variant)] border-4 border-[var(--md-sys-color-background)] shadow-sm flex items-center justify-center transition-all duration-500"
                        :class="activeId == {{ ->id }} ? 'scale-125 border-[var(--md-sys-color-primary)]' : ''"
                    >
                        <div class="w-2.5 h-2.5 rounded-full bg-[var(--md-sys-color-primary)]"></div>
                    </div>

                    <div class="absolute top-12 whitespace-nowrap opacity-60 group-hover:opacity-100 transition-opacity duration-300">
                        <span class="text-[9px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                            {{ ->event_location ?? 'مکان نامشخص' }}
                        </span>
                    </div>
                </div>

                <div class="relative z-20 h-full w-full">
                    @include('livewire.dashboard.tab.gallery.partials.item', ['photo' => ])
                </div>
            </div>
        @endforeach

        @if()
            <div
                x-ref="loadTrigger"
                wire:key="loader-{{ count() }}"
                class="shrink-0 w-full md:w-24 h-24 md:h-full snap-center flex items-center justify-center opacity-60"
            >
                <x-dashboard.loader.spinner/>
            </div>
        @endif

        <div class="shrink-0 w-4 md:w-[20%] snap-align-none pointer-events-none h-1"></div>
    </div>
</div>

<button
    @click="scrollPrev"
    class="absolute top-1/2 right-4 sm:right-8 -translate-y-1/2 z-40 w-12 h-12 flex items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)]/80 backdrop-blur-md text-[var(--md-sys-color-on-surface-variant)] shadow-sm hover:shadow-md hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95 transition-all duration-200"
    aria-label="Previous"
>
    <span class="material-symbols-rounded text-2xl">chevron_right</span>
</button>

<button
    @click="scrollNext"
    class="absolute top-1/2 left-4 sm:left-8 -translate-y-1/2 z-40 w-12 h-12 flex items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)]/80 backdrop-blur-md text-[var(--md-sys-color-on-surface-variant)] shadow-sm hover:shadow-md hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95 transition-all duration-200"
    aria-label="Next"
>
    <span class="material-symbols-rounded text-2xl">chevron_left</span>
</button>
