@if($this->feeds->isNotEmpty())
    <div x-show="month && visibleCount === 0" x-cloak
         class="absolute inset-0 z-30 flex flex-col items-center justify-center gap-3 text-[var(--md-sys-color-on-surface-variant)] pointer-events-none">
        <span class="material-symbols-rounded text-5xl opacity-40">filter_alt_off</span>
        <p class="text-sm font-medium opacity-80">موردی در این ماه یافت نشد</p>
    </div>

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
        class="flex overflow-x-auto overflow-y-hidden snap-x snap-mandatory scrollbar-hide w-full h-full items-center gap-4 px-4 md:px-12 relative"
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
                    x-show="!month || month === @js(toJalali($feed->created_at, 'F Y'))"
                    class="shrink-0 w-full max-w-md h-full md:w-[400px] snap-center transition-all duration-500 ease-out relative group"
                    :class="{
                        'scale-100 md:scale-[1.08]': activeId == {{ $feed->id }},
                        'scale-95 opacity-100': activeId != {{ $feed->id }},
                        'max-widget-column !opacity-100': maximizedFeed === feed($el)
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
                        <x-dashboard.timeline-date-bubble :id="$feed->id" :date="toJalali($feed->created_at, 'H:i')"/>

                        <div
                            class="w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-variant)] border-4 border-[var(--md-sys-color-background)] shadow-sm flex items-center justify-center transition-all duration-500"
                            :class="activeId == {{ $feed->id }} ? 'scale-125 border-[var(--md-sys-color-primary)]' : ''"
                        >
                            <div class="w-2.5 h-2.5 rounded-full bg-[var(--md-sys-color-primary)]"></div>
                        </div>

                        <x-dashboard.timeline-caption :id="$feed->id" :text="toJalaliRelative($feed->created_at)" :title="toJalali($feed->created_at)"/>
                    </div>

                    <div class="relative z-20 h-full w-full">
                        @include('livewire.dashboard.tab.feeds.item', ['feed' => $feed])
                    </div>
                </div>
            @endforeach

            @if($hasMorePages)
                <div
                    wire:key="loader-{{ count($feedIds) }}"
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
    <div wire:key="feeds-empty-timeline" class="w-full h-full flex items-center justify-center px-8">
        <x-ui.empty icon="feed" title="هیچ خبری برای نمایش وجود ندارد" description="هنوز هیچ پستی در فید منتشر نشده است." variant="welcome" />
    </div>
@endif
