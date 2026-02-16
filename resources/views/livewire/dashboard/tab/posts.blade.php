<div
    class="h-full w-full relative overflow-hidden flex flex-col lg:flex-row gap-6 p-4 md:p-6"
    dir="rtl"
    x-data="posts"
    @open-post-panel.window="panelOpen = true"
>

    {{-- Left Column: Sticky Pinned Post --}}
    <aside class="w-full lg:w-1/3 xl:w-2/5 flex-shrink-0 flex flex-col gap-4">
        <div class="sticky top-0 z-10 h-full flex flex-col">

            {{-- Header --}}
            <div class="flex items-center gap-2 mb-3 px-1 shrink-0">
                <span class="material-symbols-rounded text-[var(--md-sys-color-primary)]">keep</span>
                <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">ویژه</h3>
            </div>

            @if($this->pins->isNotEmpty())
                @foreach($this->pins as $pin)
                    <div
                        class="relative group cursor-pointer rounded-[32px] overflow-hidden bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)]/40 shadow-xl transition-all duration-300 hover:shadow-2xl hover:scale-[1.01] flex-grow flex flex-col"
                        wire:click="selectPost({{ $pin->id }})"
                    >
                        {{-- Image with Overlay --}}
                        <div class="relative h-64 lg:h-80 xl:h-96 w-full overflow-hidden shrink-0">
                            <img
                                src="{{ $pin->image }}"
                                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                            >
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent opacity-80 group-hover:opacity-70 transition-opacity"></div>

                            {{-- Badge --}}
                            <div class="absolute top-4 right-4">
                                <div
                                    class="bg-[var(--md-sys-color-primary)]/90 backdrop-blur-md text-[var(--md-sys-color-on-primary)] text-xs font-bold px-3 py-1.5 rounded-full shadow-lg flex items-center gap-1">
                                    <span class="material-symbols-rounded text-[14px]">push_pin</span>
                                    <span>مهم</span>
                                </div>
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="absolute bottom-0 left-0 right-0 p-6 text-white z-10">
                            <h2 class="text-2xl font-black mb-2 leading-tight group-hover:text-[var(--md-sys-color-primary-container)] transition-colors line-clamp-2">
                                {{ superClean($pin->title, 100) }}
                            </h2>
                            <div class="flex items-center justify-between text-white/70 text-sm mt-3">
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-rounded text-[16px]">calendar_today</span>
                                    {{ $pin->created_at->format('Y/m/d') }}
                                </span>
                                <span
                                    class="bg-white/10 hover:bg-white/20 px-3 py-1 rounded-full transition-colors backdrop-blur-sm text-xs">
                                    ادامه مطلب &larr;
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                {{-- Empty State for Pins --}}
                <div
                    class="flex-grow flex flex-col items-center justify-center bg-[var(--md-sys-color-surface-container-low)] rounded-[32px] border-2 border-dashed border-[var(--md-sys-color-outline-variant)]/30 text-[var(--md-sys-color-on-surface-variant)] opacity-60">
                    <span class="material-symbols-rounded text-6xl mb-4">push_pin</span>
                    <span class="font-bold">هیچ پست پین شده‌ای وجود ندارد</span>
                </div>
            @endif
        </div>
    </aside>

    {{-- Right Column: Feed --}}
    <section class="flex-1 flex flex-col h-full overflow-hidden rounded-[32px] bg-[var(--md-sys-color-surface)]/50 border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm relative">

        {{-- Scrollable Feed Area --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar p-1" id="feed-container">
            <div class="flex flex-col gap-3 p-2">
                @foreach($this->posts as $post)
                    <div
                        wire:key="post-{{ $post->id }}"
                        class="group relative bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] rounded-2xl p-4 transition-all duration-200 border border-transparent hover:border-[var(--md-sys-color-outline-variant)]/30 cursor-pointer flex gap-4 items-start"
                        wire:click="selectPost({{ $post->id }})"
                    >
                        {{-- Thumbnail --}}
                        <div class="w-24 h-24 sm:w-32 sm:h-32 shrink-0 rounded-xl overflow-hidden bg-[var(--md-sys-color-surface-variant)] shadow-sm">
                            <img
                                src="{{ $post->image }}"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                loading="lazy"
                            >
                        </div>

                        {{-- Text --}}
                        <div class="flex-1 min-w-0 py-1">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-[var(--md-sys-color-secondary)] bg-[var(--md-sys-color-secondary-container)] px-2 py-0.5 rounded-md">
                                    {{ $post->category ?? 'اخبار' }}
                                </span>
                                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">
                                    {{ $post->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <h3 class="text-base sm:text-lg font-bold text-[var(--md-sys-color-on-surface)] mb-2 line-clamp-1 group-hover:text-[var(--md-sys-color-primary)] transition-colors">
                                {{ superClean($post->title) }}
                            </h3>

                            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] line-clamp-2 leading-relaxed opacity-90">
                                {{ superClean($post->body, 150) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Load More Trigger --}}
            <div class="py-6 flex justify-center">
                <button
                    wire:click="loadMore; $refresh"
                    wire:island.append="feed"
                    class="group px-6 py-2.5 rounded-full bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-primary)] font-bold text-sm border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm hover:shadow-md hover:bg-[var(--md-sys-color-surface-container-highest)] hover:scale-105 active:scale-95 transition-all duration-300 flex items-center gap-2"
                >
                    <span>نمایش بیشتر</span>
                    <span class="material-symbols-rounded text-[20px] group-hover:translate-y-0.5 transition-transform"
                          wire:loading.remove target="loadMore">expand_more</span>
                    <span
                        class="w-4 h-4 border-2 border-[var(--md-sys-color-primary)] border-t-transparent rounded-full animate-spin"
                        wire:loading target="loadMore"></span>
                </button>
            </div>
        </div>
    </section>

    {{-- Slide-Over Panel --}}
    <div
        class="fixed inset-0 z-[100]"
        x-show="panelOpen"
        x-cloak
        style="display: none;"
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity duration-500"
            x-show="panelOpen"
            x-transition:enter="ease-out duration-500"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="panelOpen = false"
        ></div>

        {{-- Panel Content --}}
        <div
            class="absolute inset-y-0 left-0 max-w-3xl w-full bg-[var(--md-sys-color-surface)] shadow-2xl flex flex-col transform transition-transform duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] border-r border-[var(--md-sys-color-outline-variant)]/20"
            x-show="panelOpen"
            x-transition:enter="translate-x-full rtl:-translate-x-full"
            x-transition:enter-start="translate-x-full rtl:-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="translate-x-full rtl:-translate-x-full"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full rtl:-translate-x-full"
        >
            @if($selectedPost)
                {{-- Header Image --}}
                <div class="relative h-72 sm:h-96 w-full shrink-0 group">
                    <img src="{{ $selectedPost->image }}" class="w-full h-full object-cover">

                    {{-- Close Button --}}
                    <button
                        @click="panelOpen = false"
                        class="absolute top-6 left-6 w-10 h-10 rounded-full bg-black/40 text-white/90 backdrop-blur-md flex items-center justify-center hover:bg-black/60 hover:scale-110 hover:text-white transition-all shadow-lg z-20 border border-white/10"
                    >
                        <span class="material-symbols-rounded font-bold">close</span>
                    </button>

                    <div
                        class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-surface)] via-black/20 to-transparent"></div>

                    <div class="absolute bottom-6 right-8 left-8 text-[var(--md-sys-color-on-surface)] z-10">
                        <div
                            class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[var(--md-sys-color-secondary-container)]/80 backdrop-blur-sm text-[var(--md-sys-color-on-secondary-container)] text-xs font-bold mb-3 shadow-sm">
                            <span class="material-symbols-rounded text-[14px]">new_releases</span>
                            <span>خبر</span>
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-black leading-tight drop-shadow-lg tracking-tight mb-2 text-white">
                            {{ superClean($selectedPost->title, 200) }}
                        </h1>
                    </div>
                </div>

                {{-- Scrollable Body --}}
                <div class="flex-1 overflow-y-auto custom-scrollbar p-8 sm:p-10 bg-[var(--md-sys-color-surface)]">
                    <div
                        class="flex items-center gap-6 text-sm text-[var(--md-sys-color-on-surface-variant)] mb-8 pb-6 border-b border-[var(--md-sys-color-outline-variant)]/20">
                        <div
                            class="flex items-center gap-2 bg-[var(--md-sys-color-surface-container)] px-3 py-1.5 rounded-lg">
                            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">calendar_month</span>
                            <span class="font-medium">{{ $selectedPost->created_at->format('Y/m/d H:i') }}</span>
                        </div>
                        <div
                            class="flex items-center gap-2 bg-[var(--md-sys-color-surface-container)] px-3 py-1.5 rounded-lg">
                            <span
                                class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">person</span>
                            <span class="font-medium">ادمین سیستم</span>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div
                        class="prose prose-lg prose-p:text-[var(--md-sys-color-on-surface)] prose-headings:text-[var(--md-sys-color-on-surface)] max-w-none leading-relaxed [&_*]:!bg-transparent [&_*]:!text-inherit">
                        {!! $selectedPost->body !!}
                    </div>
                </div>

                {{-- Footer Actions --}}
                <div
                    class="p-5 border-t border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-container-low)] flex justify-between items-center shrink-0 shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)] relative">
                    <div class="relative" @click.away="closeShare()">
                        <button
                            @click="openShare('{{ addslashes(superClean($selectedPost->title, 200)) }}', '{{ addslashes(superClean($selectedPost->body, 300)) }}')"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-xl hover:bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-secondary-container)] transition-colors font-bold text-sm group"
                        >
                            <span
                                class="material-symbols-rounded group-hover:scale-110 transition-transform">share</span>
                            <span>اشتراک‌گذاری</span>
                        </button>

                        {{-- Share Popover --}}
                        <div
                            class="absolute bottom-full left-0 mb-2 w-48 bg-[var(--md-sys-color-surface)] rounded-xl shadow-xl border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden transform origin-bottom-left transition-all duration-200"
                            x-show="sharePopoverOpen"
                            x-transition:enter="ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                            style="display: none;"
                        >
                            <div class="flex flex-col py-1">
                                <button
                                    @click="copyToClipboard()"
                                    class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-on-surface)] text-sm transition-colors text-right"
                                >
                                    <span
                                        class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">content_copy</span>
                                    <span>کپی متن</span>
                                </button>
                                <button
                                    @click="sendEmail()"
                                    class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-on-surface)] text-sm transition-colors text-right border-t border-[var(--md-sys-color-outline-variant)]/20"
                                >
                                    <span
                                        class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">mail</span>
                                    <span>ایمیل</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button
                        @click="panelOpen = false"
                        class="px-8 py-2.5 rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] font-bold text-sm shadow-md hover:shadow-xl hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:-translate-y-0.5 transition-all active:scale-95 active:shadow-sm"
                    >
                        بستن
                    </button>
                </div>
            @else
                <x-dashboard.loader.spinner/>
            @endif
        </div>
    </div>
</div>
