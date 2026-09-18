@if($this->photos->isNotEmpty())

    <div x-show="month && visibleCount === 0" x-cloak
         class="absolute inset-0 z-30 flex flex-col items-center justify-center gap-3 text-[var(--md-sys-color-on-surface-variant)] pointer-events-none">
        <span class="material-symbols-rounded text-5xl opacity-40">filter_alt_off</span>
        <p class="text-sm font-medium opacity-80">موردی در این ماه یافت نشد</p>
    </div>

    <div
        class="absolute top-1/2 left-0 right-0 h-px bg-[var(--md-sys-color-outline-variant)] opacity-20 -translate-y-1/2 z-0 hidden md:block"></div>

    <div
        x-show="showTimeline"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-20"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-20"
        x-transition:leave-end="opacity-0"
        class="absolute top-1/2 left-0 right-0 h-px bg-[var(--md-sys-color-outline-variant)] opacity-20 -translate-y-1/2 z-0 hidden md:block"></div>

    <div
        x-ref="timeline"
        class="flex overflow-x-auto overflow-y-hidden snap-x snap-mandatory scrollbar-hide w-full h-full items-center gap-4 px-4 md:px-12 z-10"
        style="scroll-behavior: smooth; -webkit-overflow-scrolling: touch;"
    >
        <div
            x-ref="galleryContainer"
            class="w-full h-full flex flex-col md:flex-row overflow-y-auto md:overflow-y-visible md:overflow-x-visible md:snap-x md:snap-mandatory gap-6 scrollbar-hide items-center md:items-stretch transition-all duration-500 ease-in-out"
            :class="showTimeline ? 'md:gap-18 md:py-16' : 'md:gap-12 md:py-8 md:p-4'"
        >
            @foreach($this->photos as $photo)
                <div
                    wire:key="photo-{{ $photo->id }}"
                    data-photo-id="{{ $photo->id }}"
                    x-show="!month || month === @js(toJalali($photo->event_date, 'F Y'))"
                    class="shrink-0 w-full max-w-md h-[70vh] md:h-[80vh] md:w-[400px] snap-center transition-all duration-500 ease-out relative group"
                    :class="{
                        'z-30 scale-100 md:scale-[1.15]': activeId == {{ $photo->id }},
                        'z-10 scale-95 opacity-100': activeId != {{ $photo->id }}
                    }"
                >
                    <div
                        x-show="showTimeline"
                        x-transition:enter="transition ease-out duration-300 delay-100"
                        x-transition:enter-start="opacity-0 scale-90 translate-x-4"
                        x-transition:enter-end="opacity-100 scale-100 translate-x-1/2"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100 translate-x-1/2"
                        x-transition:leave-end="opacity-0 scale-90 translate-x-4"
                        class="absolute top-1/2 -right-10 z-0 hidden md:flex flex-col items-center justify-center -translate-y-1/2 translate-x-1/2 pointer-events-none">
                        <x-dashboard.timeline-date-bubble :id="$photo->id" :date="toJalali($photo->event_date, 'j F Y')"/>

                        <div
                            class="w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-variant)] border-4 border-[var(--md-sys-color-background)] shadow-sm flex items-center justify-center transition-all duration-500"
                            :class="activeId == {{ $photo->id }} ? 'scale-125 border-[var(--md-sys-color-primary)]' : ''"
                        >
                            <div class="w-2.5 h-2.5 rounded-full bg-[var(--md-sys-color-primary)]"></div>
                        </div>

                        @if($photo->description)
                            <x-dashboard.timeline-caption :id="$photo->id" :text="Str::limit(strip_tags($photo->description), 28)"/>
                        @endif
                    </div>

                    <div class="relative z-20 h-full w-full md:scale-[0.9]">
                        @include('livewire.dashboard.tab.gallery.item', ['photo' => $photo])
                    </div>
                </div>
            @endforeach

            @if($hasMorePages)
                <div
                    wire:key="loader-{{ count($photoIds) }}"
                    class="shrink-0 w-full md:w-32 h-24 md:h-full snap-center flex items-center justify-center"
                >
                    <x-ui.buttons.load-more
                        action="loadMore"
                        text="بارگذاری بیشتر"
                        loading-text="در حال دریافت..."
                        icon="expand_more"
                        icon-size="text-sm"
                        class="whitespace-nowrap text-[11px] font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-3 py-2 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] shadow-sm hover:shadow-md"
                    />
                </div>
            @endif

            <div class="shrink-0 w-4 md:w-[20%] snap-align-none pointer-events-none h-1"></div>
        </div>
    </div>

@else
    <div class="w-full h-full">
        <x-ui.empty icon="photo_library" title="گالری هنوز خالی است" description="هنوز هیچ محتوایی بارگذاری نشده است."
                    variant="list" :fill="true"/>
    </div>
@endif
