<div class="h-full w-full relative overflow-hidden flex flex-col lg:flex-row gap-6 p-4 md:p-6"
     x-data="{
         panelOpen: false,
         shareModalOpen: false,
         shareText: '',
         shareTitle: '',
         init() {
             $watch('panelOpen', value => {
                 if (value) {
                     document.body.style.overflow = 'hidden';
                 } else {
                     document.body.style.overflow = '';
                 }
             });
         },
         openShare(title, body) {
             this.shareTitle = title;
             this.shareText = body;
             this.shareModalOpen = true;
         },
         copyToClipboard() {
             navigator.clipboard.writeText(this.shareTitle + '\n\n' + this.shareText).then(() => {
                 // You might want to show a toast here
                 this.shareModalOpen = false;
             });
         },
         sendEmail() {
             window.location.href = 'mailto:?subject=' + encodeURIComponent(this.shareTitle) + '&body=' + encodeURIComponent(this.shareText);
             this.shareModalOpen = false;
         }
     }"
     @open-post-panel.window="panelOpen = true">

    {{-- Left Column: Sticky Pinned Post (RTL: actually Right visually if dir=rtl) --}}
    <aside class="w-full lg:w-1/3 xl:w-2/5 flex-shrink-0 flex flex-col gap-4">

        <div class="sticky top-0 z-10 h-full flex flex-col">
            <div class="flex items-center gap-2 mb-3 px-1 shrink-0">
                 <span class="material-symbols-rounded text-[var(--md-sys-color-primary)]">keep</span>
                 <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">ویژه</h3>
            </div>

            @if($this->pins->isNotEmpty())
                @foreach($this->pins as $pin)
                    <div class="relative group cursor-pointer rounded-[32px] overflow-hidden bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)]/40 shadow-xl transition-all duration-300 hover:shadow-2xl hover:scale-[1.01] flex-grow flex flex-col"
                         wire:click="selectPost({{ $pin->id }})">

                        {{-- Image with Overlay --}}
                        <div class="relative h-64 lg:h-80 xl:h-96 w-full overflow-hidden shrink-0">
                            <img src="{{ $pin->image }}" alt="{{ $pin->title }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>

                            <div class="absolute top-4 right-4 bg-[var(--md-sys-color-tertiary)]/90 backdrop-blur-md text-[var(--md-sys-color-on-tertiary)] text-sm font-bold px-4 py-1.5 rounded-full shadow-lg border border-white/20">
                                مهم
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="absolute bottom-0 inset-x-0 p-6 lg:p-8 text-white flex flex-col gap-3 bg-gradient-to-t from-black/80 to-transparent pt-24">
                            <h2 class="text-2xl lg:text-3xl font-bold leading-tight drop-shadow-md">{{ $pin->title }}</h2>
                            <p class="text-white/90 text-sm lg:text-base line-clamp-2 leading-relaxed opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-100 transform translate-y-2 group-hover:translate-y-0">
                                {!! Str::limit(strip_tags($pin->body), 150) !!}
                            </p>
                            <div class="flex items-center gap-3 text-white/80 text-xs font-medium mt-1">
                                <div class="flex items-center gap-1 bg-white/10 px-2 py-1 rounded-md backdrop-blur-sm">
                                    <span class="material-symbols-rounded text-[16px]">calendar_today</span>
                                    <span>{{ $pin->created_at->format('Y/m/d') }}</span>
                                </div>
                                <div class="flex items-center gap-1 bg-white/10 px-2 py-1 rounded-md backdrop-blur-sm">
                                    <span class="material-symbols-rounded text-[16px]">person</span>
                                    <span>ادمین</span>
                                </div>
                            </div>
                        </div>

                        {{-- Hover Effect Glow --}}
                        <div class="absolute inset-0 rounded-[32px] ring-1 ring-white/0 group-hover:ring-white/20 transition-all duration-500 pointer-events-none"></div>
                    </div>
                @endforeach
            @else
                <div class="flex-grow flex flex-col items-center justify-center p-8 text-center bg-gradient-to-br from-[var(--md-sys-color-surface-container)] to-[var(--md-sys-color-surface-container-low)] rounded-[32px] border border-[var(--md-sys-color-outline-variant)]/40 shadow-inner">
                    <div class="w-20 h-20 rounded-full bg-[var(--md-sys-color-secondary-container)] flex items-center justify-center mb-4 shadow-sm animate-pulse-slow">
                        <span class="material-symbols-rounded text-[40px] text-[var(--md-sys-color-on-secondary-container)]">campaign</span>
                    </div>
                    <h4 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] mb-2">خوش آمدید</h4>
                    <p class="text-[var(--md-sys-color-on-surface-variant)] text-sm max-w-xs leading-relaxed">
                        در حال حاضر اعلان مهمی پین نشده است. <br> اخبار جدید را در بخش تازه‌ترین‌ها دنبال کنید.
                    </p>
                </div>
            @endif
        </div>
    </aside>

    {{-- Right Column: Scrollable Feed --}}
    <section class="flex-1 min-w-0 h-full overflow-y-auto custom-scrollbar pr-1 pl-1 pb-20">

        <div class="flex items-center gap-2 mb-3 px-1">
             <span class="material-symbols-rounded text-[var(--md-sys-color-secondary)]">feed</span>
             <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">تازه ترین‌ها</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3 gap-4">
            {{-- Island for Feed --}}
            @island(name: 'feed')
                @foreach($this->posts as $post)
                    <article class="group relative flex flex-col bg-[var(--md-sys-color-surface-container-low)] rounded-[24px] overflow-hidden border border-[var(--md-sys-color-outline-variant)]/30 transition-all duration-300 hover:bg-[var(--md-sys-color-surface-container)] hover:shadow-lg hover:-translate-y-1"
                             wire:key="post-{{ $post->id }}">

                        {{-- Image --}}
                        <div class="relative h-48 overflow-hidden cursor-pointer" wire:click="selectPost({{ $post->id }})">
                            <img src="{{ $post->image }}" loading="lazy" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="{{ $post->title }}">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-300"></div>
                        </div>

                        {{-- Body --}}
                        <div class="p-5 flex flex-col flex-grow">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[10px] font-bold px-2 py-1 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] tracking-wide">
                                    اخبار
                                </span>
                                <span class="text-[11px] text-[var(--md-sys-color-outline)] font-mono dir-ltr">
                                    {{ $post->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <h4 class="text-lg font-bold text-[var(--md-sys-color-on-surface)] mb-2 line-clamp-1 cursor-pointer hover:text-[var(--md-sys-color-primary)] transition-colors"
                                wire:click="selectPost({{ $post->id }})">
                                {{ $post->title }}
                            </h4>

                            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] line-clamp-3 mb-5 flex-grow leading-relaxed">
                                {!! Str::limit(strip_tags($post->body), 100) !!}
                            </p>

                            <div class="pt-4 mt-auto border-t border-[var(--md-sys-color-outline-variant)]/20 flex items-center justify-between">
                                <button wire:click="selectPost({{ $post->id }})"
                                        class="text-xs font-bold text-[var(--md-sys-color-primary)] flex items-center gap-1.5 hover:gap-2.5 transition-all bg-[var(--md-sys-color-surface)]/50 hover:bg-[var(--md-sys-color-secondary-container)]/30 px-3 py-1.5 rounded-full">
                                    <span>ادامه مطلب</span>
                                    <span class="material-symbols-rounded text-[16px] flip-rtl">arrow_right_alt</span>
                                </button>

                                <button @click.stop="openShare('{{ addslashes($post->title) }}', '{{ addslashes(strip_tags($post->body)) }}')"
                                        class="w-8 h-8 rounded-full flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-secondary-container)] hover:text-[var(--md-sys-color-on-secondary-container)] transition-colors"
                                        title="اشتراک گذاری">
                                    <span class="material-symbols-rounded text-[18px]">share</span>
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            @endisland
        </div>

        {{-- Load More Button --}}
        <div class="mt-8 mb-12 flex justify-center">
             <button wire:click="loadMore" wire:island.append="feed"
                     class="group px-6 py-2.5 rounded-full bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-primary)] font-bold text-sm border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm hover:shadow-md hover:bg-[var(--md-sys-color-surface-container-highest)] hover:scale-105 active:scale-95 transition-all duration-300 flex items-center gap-2">
                 <span>نمایش بیشتر</span>
                 <span class="material-symbols-rounded text-[20px] group-hover:translate-y-0.5 transition-transform" wire:loading.remove target="loadMore">expand_more</span>
                 <span class="w-4 h-4 border-2 border-[var(--md-sys-color-primary)] border-t-transparent rounded-full animate-spin" wire:loading target="loadMore"></span>
             </button>
        </div>

    </section>

    {{-- Slide-Over Panel (Off-Canvas) --}}
    <div class="fixed inset-0 z-[100]"
         x-show="panelOpen"
         x-cloak
         style="display: none;">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity duration-500"
             x-show="panelOpen"
             x-transition:enter="ease-out duration-500"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="panelOpen = false"></div>

        {{-- Panel Content --}}
        <div class="absolute inset-y-0 left-0 max-w-3xl w-full bg-[var(--md-sys-color-surface)] shadow-2xl flex flex-col transform transition-transform duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] border-r border-[var(--md-sys-color-outline-variant)]/20"
             x-show="panelOpen"
             x-transition:enter="translate-x-full rtl:-translate-x-full"
             x-transition:enter-start="translate-x-full rtl:-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="translate-x-full rtl:-translate-x-full"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full rtl:-translate-x-full">

            @if($selectedPost)
                {{-- Header Image --}}
                <div class="relative h-72 sm:h-96 w-full shrink-0 group">
                    <img src="{{ $selectedPost->image }}" class="w-full h-full object-cover">

                    {{-- Close Button --}}
                    <button @click="panelOpen = false"
                            class="absolute top-6 left-6 w-10 h-10 rounded-full bg-black/40 text-white/90 backdrop-blur-md flex items-center justify-center hover:bg-black/60 hover:scale-110 hover:text-white transition-all shadow-lg z-20 border border-white/10">
                        <span class="material-symbols-rounded font-bold">close</span>
                    </button>

                    <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-surface)] via-black/20 to-transparent"></div>

                    <div class="absolute bottom-6 right-8 left-8 text-[var(--md-sys-color-on-surface)] z-10">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[var(--md-sys-color-secondary-container)]/80 backdrop-blur-sm text-[var(--md-sys-color-on-secondary-container)] text-xs font-bold mb-3 shadow-sm">
                            <span class="material-symbols-rounded text-[14px]">new_releases</span>
                            <span>خبر</span>
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-black leading-tight drop-shadow-lg tracking-tight mb-2 text-white">{{ $selectedPost->title }}</h1>
                    </div>
                </div>

                {{-- Scrollable Body --}}
                <div class="flex-1 overflow-y-auto custom-scrollbar p-8 sm:p-10 bg-[var(--md-sys-color-surface)]">
                     <div class="flex items-center gap-6 text-sm text-[var(--md-sys-color-on-surface-variant)] mb-8 pb-6 border-b border-[var(--md-sys-color-outline-variant)]/20">
                         <div class="flex items-center gap-2 bg-[var(--md-sys-color-surface-container)] px-3 py-1.5 rounded-lg">
                             <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">calendar_month</span>
                             <span class="font-medium">{{ $selectedPost->created_at->format('Y/m/d H:i') }}</span>
                         </div>
                         <div class="flex items-center gap-2 bg-[var(--md-sys-color-surface-container)] px-3 py-1.5 rounded-lg">
                             <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">person</span>
                             <span class="font-medium">ادمین سیستم</span>
                         </div>
                     </div>

                     {{-- Content with CSS Reset for HTML from DB --}}
                     <div class="prose prose-lg prose-p:text-[var(--md-sys-color-on-surface)] prose-headings:text-[var(--md-sys-color-on-surface)] max-w-none leading-relaxed [&_*]:!bg-transparent [&_*]:!text-inherit">
                         {!! $selectedPost->body !!}
                     </div>
                </div>

                {{-- Footer Actions --}}
                <div class="p-5 border-t border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-container-low)] flex justify-between items-center shrink-0 shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)]">
                    <button @click="openShare('{{ addslashes($selectedPost->title) }}', '{{ addslashes(strip_tags($selectedPost->body)) }}')"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-xl hover:bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-secondary-container)] transition-colors font-bold text-sm group">
                        <span class="material-symbols-rounded group-hover:scale-110 transition-transform">share</span>
                        <span>اشتراک‌گذاری</span>
                    </button>
                     <button @click="panelOpen = false"
                             class="px-8 py-2.5 rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] font-bold text-sm shadow-md hover:shadow-xl hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:-translate-y-0.5 transition-all active:scale-95 active:shadow-sm">
                        بستن
                    </button>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center gap-4 text-[var(--md-sys-color-outline)]">
                    <span class="w-12 h-12 border-4 border-current border-t-transparent rounded-full animate-spin opacity-50"></span>
                    <span class="animate-pulse text-sm font-medium">در حال بارگذاری محتوا...</span>
                </div>
            @endif

        </div>
    </div>

    {{-- Share Modal (Material 3 Dialog) --}}
    <div class="fixed inset-0 z-[110] flex items-center justify-center p-4"
         x-show="shareModalOpen"
         x-cloak
         style="display: none;">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-300"
             x-show="shareModalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="shareModalOpen = false"></div>

        {{-- Dialog --}}
        <div class="relative w-full max-w-sm bg-[var(--md-sys-color-surface-container-high)] rounded-[28px] shadow-2xl overflow-hidden transform transition-all duration-300"
             x-show="shareModalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-90 translate-y-4">

            <div class="p-6 flex flex-col items-center text-center">
                <div class="w-12 h-12 rounded-full bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center mb-4">
                    <span class="material-symbols-rounded text-[24px]">share</span>
                </div>

                <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] mb-2">اشتراک‌گذاری</h3>
                <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] mb-6">
                    نحوه اشتراک‌گذاری این مطلب را انتخاب کنید
                </p>

                <div class="w-full flex flex-col gap-2">
                    <button @click="copyToClipboard()"
                            class="w-full py-3 px-4 rounded-full border border-[var(--md-sys-color-outline)]/20 hover:bg-[var(--md-sys-color-surface-container-highest)] flex items-center justify-center gap-3 transition-colors text-[var(--md-sys-color-on-surface)] font-medium">
                        <span class="material-symbols-rounded text-[20px]">content_copy</span>
                        <span>کپی متن</span>
                    </button>

                    <button @click="sendEmail()"
                            class="w-full py-3 px-4 rounded-full border border-[var(--md-sys-color-outline)]/20 hover:bg-[var(--md-sys-color-surface-container-highest)] flex items-center justify-center gap-3 transition-colors text-[var(--md-sys-color-on-surface)] font-medium">
                        <span class="material-symbols-rounded text-[20px]">mail</span>
                        <span>ارسال با ایمیل</span>
                    </button>
                </div>
            </div>

            <div class="p-2 flex justify-end">
                 <button @click="shareModalOpen = false"
                         class="w-full py-3 text-[var(--md-sys-color-primary)] font-bold text-sm hover:bg-[var(--md-sys-color-primary)]/10 rounded-full transition-colors">
                     لغو
                 </button>
            </div>
        </div>
    </div>
</div>
