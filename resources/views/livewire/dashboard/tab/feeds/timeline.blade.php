@if($this->feeds->isNotEmpty())
    <div class="absolute top-1/2 left-0 right-0 h-px bg-[var(--md-sys-color-outline-variant)] opacity-20 -translate-y-1/2 z-0 hidden md:block"></div>

    <div
        x-show="showTimeline"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-20"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-20"
        x-transition:leave-end="opacity-0"
        class="absolute top-1/2 left-0 right-0 h-px bg-[var(--md-sys-color-outline-variant)] opacity-20 -translate-y-1/2 z-0 hidden md:block"
    ></div>

    <div
        x-ref="timeline"
        @scroll.debounce.100ms="handleScroll"
        class="flex overflow-x-auto overflow-y-hidden snap-x snap-mandatory scrollbar-hide w-full h-full items-center gap-4 md:px-[5%] md:pr-[10%] md:pl-4 relative"
        style="scroll-behavior: smooth; -webkit-overflow-scrolling: touch;"
    >
        <x-ui.modals.max-backdrop state="maximizedFeed" close="toggleMaximize(null)" class="max-backdrop--sync"/>

        <div
            x-ref="feedContainer"
            class="w-full h-full flex flex-col md:flex-row overflow-y-auto md:overflow-y-visible md:overflow-x-visible md:snap-x md:snap-mandatory gap-6 scrollbar-hide items-center md:items-stretch transition-all duration-500 ease-in-out"
            :class="showTimeline ? 'md:gap-18 md:py-16' : 'md:gap-12 md:py-8 md:p-4'"
        >
            @foreach($this->feeds as $feed)
                <div
                    wire:key="feed-{{ $feed->id }}"
                    data-feed-id="{{ $feed->id }}"
                    data-feed="{{ $feed->id }}"
                    class="shrink-0 w-full max-w-md h-full md:w-[400px] snap-center transition-all duration-500 ease-out relative group"
                    :class="{
                        'scale-100 md:scale-105': activeId == {{ $feed->id }},
                        'scale-95 opacity-100 md:opacity-80 md:grayscale-[30%]': activeId != {{ $feed->id }},
                        'max-widget-column !grayscale-0 !opacity-100': maximizedFeed === feed($el)
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
                        class="absolute top-1/2 -right-12 z-0 hidden md:flex flex-col items-center justify-center -translate-y-1/2 pointer-events-none"
                    >
                        <div
                            class="absolute bottom-12 whitespace-nowrap px-2.5 py-1.5 rounded-lg bg-[var(--md-sys-color-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-2 group-hover:translate-y-0"
                            :class="activeId == {{ $feed->id }} ? '!opacity-100 !translate-y-0' : ''"
                        >
                            <span class="text-[10px] font-bold text-[var(--md-sys-color-primary)] font-mono">
                                {{ $feed->created_at->format('H:i') }}
                            </span>
                            <div
                                class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-[var(--md-sys-color-surface-variant)] rotate-45 border-r border-b border-[var(--md-sys-color-outline-variant)]/20"
                            ></div>
                        </div>

                        <div
                            class="w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-variant)] border-4 border-[var(--md-sys-color-background)] shadow-sm flex items-center justify-center transition-all duration-500"
                            :class="activeId == {{ $feed->id }} ? 'scale-125 border-[var(--md-sys-color-primary)]' : ''"
                        >
                            <div class="w-2.5 h-2.5 rounded-full bg-[var(--md-sys-color-primary)]"></div>
                        </div>

                        <div
                            class="absolute top-12 whitespace-nowrap opacity-60 group-hover:opacity-100 transition-opacity duration-300"
                            :class="activeId == {{ $feed->id }} ? '!opacity-100' : ''"
                        >
                            <span class="text-[9px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                                {{ $feed->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>

                    <div class="relative z-20 h-full w-full">
                        @include('livewire.dashboard.tab.feeds.item', ['feed' => $feed])
                    </div>
                </div>
            @endforeach

            @if($hasMorePages)
                <div
                    x-ref="loadTrigger"
                    wire:key="loader-{{ count($feedIds) }}"
                    class="shrink-0 w-full md:w-24 h-24 md:h-full snap-center flex items-center justify-center opacity-60"
                >
                    <x-ui.loaders.spinner/>
                </div>
            @endif

            <div class="shrink-0 w-4 md:w-[20%] snap-align-none pointer-events-none h-1"></div>
        </div>
    </div>

    <button
        x-show="!maximizedFeed"
        @click="scrollPrev"
        class="absolute top-1/2 right-4 sm:right-8 -translate-y-1/2 z-40 w-12 h-12 flex items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)]/80 text-[var(--md-sys-color-on-surface-variant)] shadow-sm hover:shadow-md hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95 transition-all duration-200"
        aria-label="Previous"
    >
        <span class="material-symbols-rounded text-2xl">chevron_right</span>
    </button>

    <button
        x-show="!maximizedFeed"
        @click="scrollNext"
        class="absolute top-1/2 left-4 sm:left-8 -translate-y-1/2 z-40 w-12 h-12 flex items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)]/80 text-[var(--md-sys-color-on-surface-variant)] shadow-sm hover:shadow-md hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95 transition-all duration-200"
        aria-label="Next"
    >
        <span class="material-symbols-rounded text-2xl">chevron_left</span>
    </button>
@else
    <div class="w-full h-full flex flex-col items-center justify-center gap-5 text-center px-8">
        <div class="w-24 h-24 rounded-3xl flex items-center justify-center shadow-inner bg-[var(--md-sys-color-surface-container-high)]">
            <span class="material-symbols-rounded text-5xl text-[var(--md-sys-color-outline)] opacity-60">feed</span>
        </div>

        <div>
            <p class="text-base font-bold text-[var(--md-sys-color-on-surface)]">هیچ خبری برای نمایش وجود ندارد</p>
            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] mt-2 opacity-70">هنوز هیچ پستی در فید منتشر نشده است.</p>
        </div>
    </div>
@endif
