@if($this->internalLinks->isNotEmpty())
    <section class="relative group/section" x-data="{
            hasOverflow: false,
            checkScroll() {
                const el = this.$refs.container;
                this.hasOverflow = el.scrollWidth > el.clientWidth;
            },
            scrollLeft() { this.$refs.container.scrollBy({ left: -300, behavior: 'smooth' }); },
            scrollRight() { this.$refs.container.scrollBy({ left: 300, behavior: 'smooth' }); }
        }"
             x-init="checkScroll()"
             @resize.window="checkScroll">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6 px-1">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shadow-sm">
                    <span class="material-symbols-rounded text-xl">dataset_linked</span>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]"> داخلی</h3>
                </div>
            </div>

            {{-- Navigation Buttons --}}
            <div class="flex gap-2 transition-opacity duration-300" x-show="hasOverflow" x-cloak>
                <button @click="scrollRight" class="w-8 h-8 rounded-lg bg-[var(--md-sys-color-surface-container-high)] hover:bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors shadow-sm flex items-center justify-center">
                    <span class="material-symbols-rounded text-xl">chevron_right</span>
                </button>
                <button @click="scrollLeft" class="w-8 h-8 rounded-lg bg-[var(--md-sys-color-surface-container-high)] hover:bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors shadow-sm flex items-center justify-center">
                    <span class="material-symbols-rounded text-xl">chevron_left</span>
                </button>
            </div>
        </div>

        {{-- Cards Container --}}
        <div x-ref="container"
             class="flex overflow-x-auto snap-x snap-mandatory gap-5 pb-4 scrollbar-hide px-1 pt-1"
             style="-webkit-overflow-scrolling: touch;"
             @scroll.debounce.100ms="checkScroll"
        >
            @php
                $ip = request()->ip();
            @endphp
            @foreach($this->internalLinks as $link)
                @php
                    $resolved = $link->resolvedUrl($ip);
                    $internal = $link->resolvedIsInternal($ip);
                @endphp
                <a wire:key="link-{{ $link->id }}"
                   data-rf="links-{{ $link->id }}"
                   href="{{ $resolved }}"
                   target="{{ $internal ? '_self' : '_blank' }}"
                   @click="recordClick({ id: @js($link->id), title: @js($link->url_title), url: @js($resolved), icon: @js($link->icon_description), internal: @js($internal) })"
                   class="snap-start shrink-0 w-36 md:w-40 group/card cursor-pointer focus:outline-none"
                >
                    <div class="relative w-full aspect-[4/3] rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden shadow-sm transition-all duration-300 group-hover/card:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] group-hover/card:-translate-y-1 group-hover/card:border-[var(--md-sys-color-primary)]/30">

                        {{-- Card Content --}}
                        <div class="absolute inset-0 flex items-center justify-center bg-[var(--md-sys-color-surface-variant)]/30">
                            @if($link->image)
                                <img src="{{ $link->image_url }}"
                                     alt="{{ $link->url_title }}"
                                     class="w-full h-full object-contain transition-transform duration-700 group-hover/card:scale-110"
                                     onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden'); this.nextElementSibling.classList.add('flex');"
                                >
                            @endif

                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-[var(--md-sys-color-primary)] {{ $link->image ? 'hidden' : 'flex' }}">
                                <div class="p-4 rounded-2xl bg-[var(--md-sys-color-primary-container)]/50 group-hover/card:bg-[var(--md-sys-color-primary-container)] transition-colors duration-300">
                                    <span class="material-symbols-rounded text-xl leading-none">{{ $link->icon_description ?: 'link' }}</span>
                                </div>
                            </div>

                            {{-- Gradient Overlay --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-surface)]/60 to-transparent opacity-0 group-hover/card:opacity-100 transition-opacity duration-300"></div>
                        </div>

                        {{-- Floating Icon --}}
                        <div class="absolute top-3 left-3 w-8 h-8 bg-[var(--md-sys-color-surface)]/90 rounded-lg flex items-center justify-center opacity-0 group-hover/card:opacity-100 translate-y-2 group-hover/card:translate-y-0 transition-all duration-300 shadow-sm">
                            <span class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]">arrow_outward</span>
                        </div>
                    </div>

                    <div class="mt-3 text-center px-1">
                        <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] group-hover/card:text-[var(--md-sys-color-primary)] transition-colors line-clamp-1">
                            {{ $link->url_title }}
                        </h4>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@else
    <x-ui.empty icon="link_off" title="هیچ سامانه داخلی تعریف نشده" description="سامانه‌های درون‌سازمانی توسط مدیر تعریف می‌شوند." variant="list" />
@endif
