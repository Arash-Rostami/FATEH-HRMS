<div class="flex flex-col gap-8 p-4 md:p-8 w-full h-full overflow-y-auto custom-scrollbar" dir="rtl">
    {{-- Internal Links Section --}}
    @if($this->internalLinks->isNotEmpty())
        <section>
            <div class="flex items-center gap-2 mb-4 px-1">
                <span class="material-symbols-rounded text-[var(--md-sys-color-primary)]">apps</span>
                <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">سامانه‌های داخلی</h3>
            </div>
            <div class="flex overflow-x-auto snap-x snap-mandatory gap-4 pb-4 scrollbar-hide px-1" style="-webkit-overflow-scrolling: touch;">
                @foreach($this->internalLinks as $link)
                    <a href="{{ $link->internal_url ?: $link->url }}"
                       target="{{ $link->internal_url ? '_self' : '_blank' }}"
                       class="snap-center shrink-0 w-32 md:w-40 flex flex-col items-center gap-2 group cursor-pointer transition-transform hover:-translate-y-1"
                    >
                        <div class="w-full aspect-square rounded-[24px] bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)]/50 overflow-hidden relative shadow-sm group-hover:shadow-md transition-all">
                            {{-- Image Logic: Check if it looks like a URL/path --}}
                            @if($link->image_description)
                                <img src="{{ $link->image_description }}"
                                     alt="{{ $link->url_title }}"
                                     class="w-full h-full object-cover p-4 group-hover:scale-110 transition-transform duration-500"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >
                            @endif
                            <div class="absolute inset-0 flex items-center justify-center bg-[var(--md-sys-color-surface-container-high)] {{ $link->image_description ? 'hidden' : 'flex' }}">
                                <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-primary)]">{{ $link->icon_description ?: 'link' }}</span>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-[var(--md-sys-color-on-surface)] text-center line-clamp-2 group-hover:text-[var(--md-sys-color-primary)] transition-colors">
                            {{ $link->url_title }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- External Links Section --}}
    @if($this->externalLinks->isNotEmpty())
        <section>
             <div class="flex items-center gap-2 mb-4 px-1">
                <span class="material-symbols-rounded text-[var(--md-sys-color-secondary)]">public</span>
                <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">پیوندهای خارجی</h3>
            </div>
            <div class="flex overflow-x-auto snap-x snap-mandatory gap-4 pb-4 scrollbar-hide px-1" style="-webkit-overflow-scrolling: touch;">
                @foreach($this->externalLinks as $link)
                    <a href="{{ $link->url }}"
                       target="_blank"
                       class="snap-center shrink-0 w-32 md:w-40 flex flex-col items-center gap-2 group cursor-pointer transition-transform hover:-translate-y-1"
                    >
                        <div class="w-full aspect-square rounded-[24px] bg-[var(--md-sys-color-surface-container-low)] border border-[var(--md-sys-color-outline-variant)]/50 overflow-hidden relative shadow-sm group-hover:shadow-md transition-all">
                             @if($link->image_description)
                                <img src="{{ $link->image_description }}"
                                     alt="{{ $link->url_title }}"
                                     class="w-full h-full object-cover p-4 group-hover:scale-110 transition-transform duration-500"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >
                            @endif
                            <div class="absolute inset-0 flex items-center justify-center bg-[var(--md-sys-color-surface-container)] {{ $link->image_description ? 'hidden' : 'flex' }}">
                                <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-secondary)]">{{ $link->icon_description ?: 'open_in_new' }}</span>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-[var(--md-sys-color-on-surface)] text-center line-clamp-2 group-hover:text-[var(--md-sys-color-secondary)] transition-colors">
                            {{ $link->url_title }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
