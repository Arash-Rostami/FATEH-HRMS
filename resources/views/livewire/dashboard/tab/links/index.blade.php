<div class="h-full w-full relative overflow-y-auto overflow-x-hidden p-6 md:p-8 space-y-12 pb-24 custom-scrollbar" dir="rtl">

    <x-dashboard.tab.title icon="open_in_new" title="لینک‌ها و ابزارها" :count="$this->totalLinks" countLabel="لینک" />

    {{-- Internal Links Section --}}
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
                        <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">سامانه‌های داخلی</h3>
                        <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] mt-0.5">منابع و ابزارهای درون‌سازمانی</p>
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
                @foreach($this->internalLinks as $link)
                    <a href="{{ $link->internal_url ?: $link->url }}"
                       target="{{ $link->internal_url ? '_self' : '_blank' }}"
                       class="snap-start shrink-0 w-44 md:w-52 group/card cursor-pointer focus:outline-none"
                    >
                        <div class="relative w-full aspect-[4/3] rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden shadow-sm transition-all duration-300 group-hover/card:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] group-hover/card:-translate-y-1 group-hover/card:border-[var(--md-sys-color-primary)]/30">

                            {{-- Card Content --}}
                            <div class="absolute inset-0 flex items-center justify-center bg-[var(--md-sys-color-surface-variant)]/30">
                                @if($link->image_description)
                                    <img src="{{ $link->image_description }}"
                                         alt="{{ $link->url_title }}"
                                         class="w-full h-full object-cover transition-transform duration-700 group-hover/card:scale-110"
                                         onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden'); this.nextElementSibling.classList.add('flex');"
                                    >
                                @endif

                                <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-[var(--md-sys-color-primary)] {{ $link->image_description ? 'hidden' : 'flex' }}">
                                    <div class="p-4 rounded-2xl bg-[var(--md-sys-color-primary-container)]/50 group-hover/card:bg-[var(--md-sys-color-primary-container)] transition-colors duration-300">
                                        <span class="material-symbols-rounded text-4xl">{{ $link->icon_description ?: 'link' }}</span>
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
    @endif

    {{-- External Links Section --}}
    @if($this->externalLinks->isNotEmpty())
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
                    <div class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center shadow-sm">
                        <span class="material-symbols-rounded text-xl">public</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">سامانه‌های خارجی</h3>
                        <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] mt-0.5">وب‌سایت‌ها و منابع اینترنتی</p>
                    </div>
                </div>

                {{-- Navigation Buttons --}}
                <div class="flex gap-2 transition-opacity duration-300" x-show="hasOverflow" x-cloak>
                    <button @click="scrollRight" class="w-8 h-8 rounded-lg bg-[var(--md-sys-color-surface-container-high)] hover:bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-surface)] hover:text-[var(--md-sys-color-on-secondary-container)] transition-colors shadow-sm flex items-center justify-center">
                        <span class="material-symbols-rounded text-xl">chevron_right</span>
                    </button>
                    <button @click="scrollLeft" class="w-8 h-8 rounded-lg bg-[var(--md-sys-color-surface-container-high)] hover:bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-surface)] hover:text-[var(--md-sys-color-on-secondary-container)] transition-colors shadow-sm flex items-center justify-center">
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
                @foreach($this->externalLinks as $link)
                    <a href="{{ $link->url }}"
                       target="_blank"
                       class="snap-start shrink-0 w-44 md:w-52 group/card cursor-pointer focus:outline-none"
                    >
                        <div class="relative w-full aspect-[4/3] rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden shadow-sm transition-all duration-300 group-hover/card:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-secondary)_12%,transparent)] group-hover/card:-translate-y-1 group-hover/card:border-[var(--md-sys-color-secondary)]/30">

                            {{-- Card Content --}}
                            <div class="absolute inset-0 flex items-center justify-center bg-[var(--md-sys-color-surface-variant)]/30">
                                @if($link->image_description)
                                    <img src="{{ $link->image_description }}"
                                         alt="{{ $link->url_title }}"
                                         class="w-full h-full object-cover transition-transform duration-700 group-hover/card:scale-110"
                                         onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden'); this.nextElementSibling.classList.add('flex');"
                                    >
                                @endif

                                <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-[var(--md-sys-color-secondary)] {{ $link->image_description ? 'hidden' : 'flex' }}">
                                    <div class="p-4 rounded-2xl bg-[var(--md-sys-color-secondary-container)]/50 group-hover/card:bg-[var(--md-sys-color-secondary-container)] transition-colors duration-300">
                                        <span class="material-symbols-rounded text-4xl">{{ $link->icon_description ?: 'open_in_new' }}</span>
                                    </div>
                                </div>

                                {{-- Gradient Overlay --}}
                                <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-surface)]/60 to-transparent opacity-0 group-hover/card:opacity-100 transition-opacity duration-300"></div>
                            </div>

                            {{-- Floating Icon --}}
                            <div class="absolute top-3 left-3 w-8 h-8 bg-[var(--md-sys-color-surface)]/90  rounded-lg flex items-center justify-center opacity-0 group-hover/card:opacity-100 translate-y-2 group-hover/card:translate-y-0 transition-all duration-300 shadow-sm">
                                <span class="material-symbols-rounded text-lg text-[var(--md-sys-color-secondary)]">arrow_outward</span>
                            </div>
                        </div>

                        <div class="mt-3 text-center px-1">
                            <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] group-hover/card:text-[var(--md-sys-color-secondary)] transition-colors line-clamp-1">
                                {{ $link->url_title }}
                            </h4>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
