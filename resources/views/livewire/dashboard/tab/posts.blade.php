<div
    class="relative w-full h-full flex flex-col overflow-hidden"
    x-data="{
        panelOpen: false,
        openShare(title, text) {
            if (navigator.share) {
                navigator.share({
                    title: title,
                    text: text,
                    url: window.location.href,
                });
            } else {
               // Fallback or custom share modal logic handled inline
            }
        },
        copyToClipboard() {
            // ... implementation
        },
        sendEmail() {
             // ... implementation
        },
        togglePanel() {
            this.panelOpen = !this.panelOpen;
        }
    }"
    @open-post-panel.window="panelOpen = true"
>
    {{-- Header / Pinned Post Area (Optional) --}}
    @if($this->pins->isNotEmpty())
        <section class="shrink-0 px-4 pt-4 pb-2">
            @foreach($this->pins as $pin)
                 <div wire:click="selectPost({{ $pin->id }})" class="cursor-pointer bg-[var(--md-sys-color-primary-container)] rounded-2xl p-4 shadow-md hover:shadow-lg transition-all">
                    <h3 class="font-bold text-[var(--md-sys-color-on-primary-container)]">{{ $pin->title }}</h3>
                 </div>
            @endforeach
        </section>
    @endif

    {{-- Main Feed List --}}
    <section class="flex-1 overflow-y-auto custom-scrollbar px-4 pb-20 space-y-4">

        {{-- Standard Livewire Loop --}}
        @foreach($this->posts as $post)
            <div
                wire:key="post-{{ $post->id }}"
                wire:click="selectPost({{ $post->id }})"
                class="bg-[var(--md-sys-color-surface-container)] rounded-2xl p-4 shadow-sm hover:scale-[1.01] transition-transform cursor-pointer"
            >
                @if($post->image)
                    <img src="{{ $post->image }}" class="w-full h-48 object-cover rounded-xl mb-3">
                @endif
                <h4 class="font-bold text-lg text-[var(--md-sys-color-on-surface)] mb-2">{{ $post->title }}</h4>
                <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] line-clamp-3">{{ Str::limit(strip_tags($post->body), 150) }}</p>
                <div class="mt-3 flex justify-between items-center text-xs text-[var(--md-sys-color-outline)]">
                    <span>{{ $post->created_at->diffForHumans() }}</span>
                    <span>ادمین</span>
                </div>
            </div>
        @endforeach

        {{-- Load More Button / Trigger --}}
        @if($hasMorePages)
            <div class="py-6 flex justify-center">
                <button
                    wire:click="loadMore"
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
        @endif
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
            @if($this->selectedPost)
                {{-- Header Image --}}
                <div class="relative h-72 sm:h-96 w-full shrink-0 group">
                    <img src="{{ $selectedPost->image }}" class="w-full h-full object-cover">

                    {{-- Close Button --}}
                    <button
                        @click="togglePanel()"
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
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button
                            @click="open = !open; openShare('{{ addslashes(superClean($selectedPost->title, 200)) }}', '{{ addslashes(superClean($selectedPost->body, 300)) }}')"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-xl hover:bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-secondary-container)] transition-colors font-bold text-sm group"
                        >
                            <span
                                class="material-symbols-rounded group-hover:scale-110 transition-transform">share</span>
                            <span>اشتراک‌گذاری</span>
                        </button>

                        {{-- Share Popover --}}
                        <div
                            class="absolute bottom-full left-0 mb-2 w-48 bg-[var(--md-sys-color-surface)] rounded-xl shadow-xl border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden transform origin-bottom-left transition-all duration-200"
                            x-show="open"
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
                                    @click="copyToClipboard();"
                                    class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-on-surface)] text-sm transition-colors text-right"
                                >
                                    <span
                                        class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">content_copy</span>
                                    <span>کپی متن</span>
                                </button>
                                <button
                                    @click="sendEmail(); open = false"
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
                        @click="togglePanel()"
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
