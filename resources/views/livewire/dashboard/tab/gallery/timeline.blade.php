@php($months = $presenter->months($this->photos))

@if($this->photos->isNotEmpty())

    @if($months->isNotEmpty())
        <div x-data="{ open: false }" @click.outside="open = false" class="absolute left-4 top-10 z-40 hidden md:block">
            <div
                class="hidden md:flex bg-[var(--md-sys-color-surface-container-high)] p-1.5 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm">
                <button
                    title="فیلتر تاریخ"
                    @click="open = !open"
                    class="flex items-center gap-2 h-10 px-3 rounded-xl text-xs font-medium transition-all duration-300 border shadow-sm"
                    :class="open || month
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent'
                    : 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-[var(--md-sys-color-outline-variant)]/40 hover:bg-[var(--md-sys-color-surface-variant)]'"
                >
                    <span class="material-symbols-rounded text-[18px]">calendar_month</span>
                    <span class="material-symbols-rounded text-[16px] transition-transform duration-200"
                          :class="open ? 'rotate-180' : ''">expand_more</span>
                </button>
            </div>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-cloak
                class="absolute top-full mt-0 left-0 min-w-[10rem] bg-[var(--md-sys-color-surface-container-highest)] rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 shadow-lg p-1 flex flex-col gap-0.5"
            >
                <button
                    @click="month = ''; open = false"
                    :class="!month ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'"
                    class="px-3 h-9 rounded-lg text-xs font-medium text-right transition-colors duration-150"
                >همه ماه‌ها
                </button>
                @foreach($months as $m)
                    <button
                        @click="month = @js($m['key']); open = false"
                        :class="month === @js($m['key']) ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'"
                        class="px-3 h-9 rounded-lg text-xs font-medium text-right transition-colors duration-150"
                    >{{ $m['key'] }}</button>
                @endforeach
            </div>
        </div>
    @endif

    <div x-show="month && visibleCount === 0" x-cloak
         class="absolute inset-0 z-30 flex flex-col items-center justify-center gap-3 text-[var(--md-sys-color-on-surface-variant)] pointer-events-none">
        <span class="material-symbols-rounded text-5xl opacity-40">filter_alt_off</span>
        <p class="text-sm font-medium opacity-80">تصویری در این ماه یافت نشد</p>
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
        @scroll.debounce.100ms="handleScroll"
        class="flex overflow-x-auto overflow-y-hidden snap-x snap-mandatory scrollbar-hide w-full h-full items-center gap-4 md:px-[5%] md:pr-[10%] md:pl-4 z-10"
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
                        'z-10 scale-95 opacity-100 md:opacity-80 md:grayscale-[30%]': activeId != {{ $photo->id }}
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
                        <div
                            class="absolute bottom-12 whitespace-nowrap px-2.5 py-1.5 rounded-lg bg-[var(--md-sys-color-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-2 group-hover:translate-y-0"
                            :class="activeId == {{ $photo->id }} ? '!opacity-100 !translate-y-0' : ''"
                        >
                            <span class="text-[10px] font-bold text-[var(--md-sys-color-primary)]">
                                {{ toJalali($photo->event_date, 'j F Y') }}
                            </span>
                            <div
                                class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-[var(--md-sys-color-surface-variant)] rotate-45 border-r border-b border-[var(--md-sys-color-outline-variant)]/20"></div>
                        </div>

                        <div
                            class="w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-variant)] border-4 border-[var(--md-sys-color-background)] shadow-sm flex items-center justify-center transition-all duration-500"
                            :class="activeId == {{ $photo->id }} ? 'scale-125 border-[var(--md-sys-color-primary)]' : ''"
                        >
                            <div class="w-2.5 h-2.5 rounded-full bg-[var(--md-sys-color-primary)]"></div>
                        </div>

                        @if($photo->description)
                            <div
                                class="absolute top-12 whitespace-nowrap opacity-60 group-hover:opacity-100 transition-opacity duration-300"
                                :class="activeId == {{ $photo->id }} ? '!opacity-100' : ''"
                            >
                                <span class="text-[9px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                                    {{ Str::limit(strip_tags($photo->description), 28) }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="relative z-20 h-full w-full md:scale-[0.9]">
                        @include('livewire.dashboard.tab.gallery.item', ['photo' => $photo])
                    </div>
                </div>
            @endforeach

            @if($hasMorePages)
                <div
                    x-ref="loadTrigger"
                    wire:key="loader-{{ count($photoIds) }}"
                    class="shrink-0 w-full md:w-24 h-24 md:h-full snap-center flex items-center justify-center opacity-60"
                >
                    <x-ui.loaders.spinner/>
                </div>
            @endif

            <div class="shrink-0 w-4 md:w-[20%] snap-align-none pointer-events-none h-1"></div>
        </div>
    </div>

    <button
        @click="scrollPrev"
        class="absolute top-1/2 right-4 sm:right-8 -translate-y-1/2 z-40 w-12 h-12 flex items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)]/80 text-[var(--md-sys-color-on-surface-variant)] shadow-sm hover:shadow-md hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95 transition-all duration-200"
        aria-label="Previous"
    >
        <span class="material-symbols-rounded text-2xl">chevron_right</span>
    </button>

    <button
        @click="scrollNext"
        class="absolute top-1/2 left-4 sm:left-8 -translate-y-1/2 z-40 w-12 h-12 flex items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)]/80 text-[var(--md-sys-color-on-surface-variant)] shadow-sm hover:shadow-md hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95 transition-all duration-200"
        aria-label="Next"
    >
        <span class="material-symbols-rounded text-2xl">chevron_left</span>
    </button>

@else
    <div class="w-full h-full">
        <x-ui.empty icon="photo_library" title="گالری هنوز خالی است" description="هیچ تصویری بارگذاری نشده است."
                    variant="list" :fill="true"/>
    </div>
@endif
