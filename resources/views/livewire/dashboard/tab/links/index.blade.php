<div class="flex flex-col gap-10 p-4 md:p-8 w-full h-full overflow-y-auto custom-scrollbar" dir="rtl">

    @if($this->internalLinks->isNotEmpty())
        <section x-data="links" class="relative group/section" @resize.window="checkScroll">
            <div class="flex items-center justify-between mb-6 px-2">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-2xl">apps</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)]">سامانه‌های داخلی</h3>
                        <p class="text-xs text-[var(--md-sys-color-outline)] mt-0.5">دسترسی سریع به ابزارهای سازمانی</p>
                    </div>
                </div>

                <div class="flex gap-2 opacity-0 group-hover/section:opacity-100 transition-opacity duration-300" x-show="hasOverflow">
                    <button @click="scrollRight" class="p-2 rounded-xl bg-[var(--md-sys-color-primary-container)] hover:bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-surface)] transition-colors shadow-sm">
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                    <button @click="scrollLeft" class="p-2 rounded-xl bg-[var(--md-sys-color-primary-container)] hover:bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-surface)] transition-colors shadow-sm">
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                </div>
            </div>

            <div x-ref="container"
                 class="flex overflow-x-auto snap-x snap-mandatory gap-6 pb-6 scrollbar-hide px-2 pt-2"
                 style="-webkit-overflow-scrolling: touch;"
                 @scroll.debounce.100ms="checkScroll"
            >
                @foreach($this->internalLinks as $link)
                    <a href="{{ $link->internal_url ?: $link->url }}"
                       target="{{ $link->internal_url ? '_self' : '_blank' }}"
                       class="snap-start shrink-0 w-40 md:w-48 group/card cursor-pointer focus:outline-none"
                    >
                        <div class="relative w-full aspect-[4/3] rounded-[28px] bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden shadow-sm group-hover/card:shadow-xl group-hover/card:scale-[1.02] group-hover/card:-translate-y-1 transition-all duration-300 ease-out">

                            <div class="absolute inset-0 flex items-center justify-center bg-[var(--md-sys-color-surface-container-low)]">
                                @if($link->image_description)
                                    <img src="{{ $link->image_description }}"
                                         alt="{{ $link->url_title }}"
                                         class="w-full h-full object-cover transition-transform duration-700 group-hover/card:scale-110"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    >
                                @endif

                                <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-[var(--md-sys-color-primary)] {{ $link->image_description ? 'hidden' : 'flex' }}">
                                    <div class="p-4 rounded-full bg-[var(--md-sys-color-primary-container)]/30 backdrop-blur-sm">
                                        <span class="material-symbols-rounded text-4xl md:text-5xl">{{ $link->icon_description ?: 'link' }}</span>
                                    </div>
                                </div>

                                <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-surface-container-highest)]/80 via-transparent to-transparent opacity-0 group-hover/card:opacity-100 transition-opacity duration-300"></div>
                            </div>

                            <div class="absolute top-3 left-3 bg-[var(--md-sys-color-surface)]/80 backdrop-blur-md rounded-full p-1.5 opacity-0 group-hover/card:opacity-100 translate-y-2 group-hover/card:translate-y-0 transition-all duration-300 shadow-sm">
                                <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">arrow_outward</span>
                            </div>
                        </div>

                        <div class="mt-3 text-center px-1">
                            <h4 class="text-sm md:text-base font-bold text-[var(--md-sys-color-on-surface)] group-hover/card:text-[var(--md-sys-color-primary)] transition-colors line-clamp-1">
                                {{ $link->url_title }}
                            </h4>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($this->externalLinks->isNotEmpty())
        <section x-data="links" class="relative group/section" @resize.window="checkScroll">
            <div class="flex items-center justify-between mb-6 px-2">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-2xl">public</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)]">سامانه‌های خارجی</h3>
                        <p class="text-xs text-[var(--md-sys-color-outline)] mt-0.5">وب‌سایت‌ها و منابع اینترنتی</p>
                    </div>
                </div>

                <div class="flex gap-2 opacity-0 group-hover/section:opacity-100 transition-opacity duration-300" x-show="hasOverflow">
                    <button @click="scrollRight" class="p-2 rounded-full bg-[var(--md-sys-color-surface-container-high)] hover:bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-surface)] transition-colors shadow-sm">
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                    <button @click="scrollLeft" class="p-2 rounded-full bg-[var(--md-sys-color-surface-container-high)] hover:bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-surface)] transition-colors shadow-sm">
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                </div>
            </div>

            <div x-ref="container"
                 class="flex overflow-x-auto snap-x snap-mandatory gap-6 pb-6 scrollbar-hide px-2 pt-2"
                 style="-webkit-overflow-scrolling: touch;"
                 @scroll.debounce.100ms="checkScroll"
            >
                @foreach($this->externalLinks as $link)
                    <a href="{{ $link->url }}"
                       target="_blank"
                       class="snap-start shrink-0 w-40 md:w-48 group/card cursor-pointer focus:outline-none"
                    >
                        <div class="relative w-full aspect-[4/3] rounded-[28px] bg-[var(--md-sys-color-surface-container-low)] border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden shadow-sm group-hover/card:shadow-xl group-hover/card:scale-[1.02] group-hover/card:-translate-y-1 transition-all duration-300 ease-out">

                            <div class="absolute inset-0 flex items-center justify-center bg-[var(--md-sys-color-surface-container)]">
                                @if($link->image_description)
                                    <img src="{{ $link->image_description }}"
                                         alt="{{ $link->url_title }}"
                                         class="w-full h-full object-cover transition-transform duration-700 group-hover/card:scale-110"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    >
                                @endif

                                <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-[var(--md-sys-color-secondary)] {{ $link->image_description ? 'hidden' : 'flex' }}">
                                    <div class="p-4 rounded-full bg-[var(--md-sys-color-secondary-container)]/30 backdrop-blur-sm">
                                        <span class="material-symbols-rounded text-4xl md:text-5xl">{{ $link->icon_description ?: 'open_in_new' }}</span>
                                    </div>
                                </div>

                                <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-surface-container-highest)]/80 via-transparent to-transparent opacity-0 group-hover/card:opacity-100 transition-opacity duration-300"></div>
                            </div>

                            <div class="absolute top-3 left-3 bg-[var(--md-sys-color-surface)]/80 backdrop-blur-md rounded-full p-1.5 opacity-0 group-hover/card:opacity-100 translate-y-2 group-hover/card:translate-y-0 transition-all duration-300 shadow-sm">
                                <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-secondary)]">arrow_outward</span>
                            </div>
                        </div>

                        <div class="mt-3 text-center px-1">
                            <h4 class="text-sm md:text-base font-bold text-[var(--md-sys-color-on-surface)] group-hover/card:text-[var(--md-sys-color-secondary)] transition-colors line-clamp-1">
                                {{ $link->url_title }}
                            </h4>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
