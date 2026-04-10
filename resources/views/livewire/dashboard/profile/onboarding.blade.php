<div class="space-y-6 animate-[fade-in_0.4s_ease-out]" dir="rtl"
     x-data="{
         openVideoModal: false,
         videoSrc: '',
         videoTitle: '',
         handleVideoClose() {
             const player = this.$refs.videoPlayer;
             if (player) { player.pause(); player.currentTime = 0; }
             this.openVideoModal = false;
             this.videoSrc = '';
             this.videoTitle = '';
         }
     }"
     @keydown.escape.window="handleVideoClose()">

    @once
        <style>
            .onb-prose {
                line-height: 2;
                font-size: .875rem;
                color: var(--md-sys-color-on-surface-variant);
            }

            .onb-prose h1, .onb-prose h2, .onb-prose h3, .onb-prose h4 {
                font-weight: 800;
                color: var(--md-sys-color-on-surface);
                margin-top: 1.25rem;
                margin-bottom: .5rem;
                line-height: 1.4;
            }

            .onb-prose h1 {
                font-size: 1.5rem
            }

            .onb-prose h2 {
                font-size: 1.25rem
            }

            .onb-prose h3 {
                font-size: 1.1rem
            }

            .onb-prose p {
                margin-bottom: .875rem;
            }

            .onb-prose ul, .onb-prose ol {
                padding-right: 1.5rem;
                margin-bottom: .875rem;
            }

            .onb-prose a {
                color: var(--md-sys-color-primary);
                text-decoration: underline;
            }

            .onb-prose strong, .onb-prose b {
                font-weight: 700;
                color: var(--md-sys-color-on-surface);
            }

            .onb-prose *:first-child {
                margin-top: 0
            }

            .onb-prose *:last-child {
                margin-bottom: 0
            }

            /* Rich content color mappings */
            .onb-prose [class*="text-yellow"] {
                color: #b45309 !important
            }

            .onb-prose [class*="bg-yellow"] {
                background: color-mix(in srgb, #f59e0b 10%, var(--md-sys-color-surface)) !important
            }

            .onb-prose [class*="text-green"] {
                color: #15803d !important
            }

            .onb-prose [class*="bg-green"] {
                background: color-mix(in srgb, #22c55e 10%, var(--md-sys-color-surface)) !important
            }

            .onb-prose [class*="text-red"] {
                color: var(--md-sys-color-error) !important
            }

            .onb-prose [class*="bg-blue"] {
                background: color-mix(in srgb, var(--md-sys-color-primary) 10%, var(--md-sys-color-surface)) !important
            }

            /* Video thumbnail hover effect */
            .video-thumb-container:hover .video-play-overlay {
                opacity: 1;
                transform: scale(1);
            }

            .video-thumb-container:hover img {
                transform: scale(1.05);
            }
        </style>
    @endonce

    {{-- Empty State --}}
    @if(!$presenter->hasContent($onboarding))
        <div
            class="flex flex-col items-center justify-center py-24 text-center bg-[var(--md-sys-color-surface)] rounded-2xl border border-dashed border-[var(--md-sys-color-outline-variant)]">
            <span class="material-symbols-rounded text-[44px] text-[var(--md-sys-color-on-surface-variant)] mb-4">apartment</span>
            <h3 class="text-base font-bold text-[var(--md-sys-color-on-surface)] mb-2">محتوایی بارگذاری نشده است</h3>
            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] max-w-xs">
                اطلاعات آنبوردینگ (آشنایی با شرکت) توسط مدیر سیستم هنوز تنظیم نشده است.
            </p>
        </div>
    @else

        {{-- Card 1: Welcome --}}
        @if($onboarding->welcome)
            <section
                class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden">
                <div
                    class="flex items-center gap-4 p-6 bg-[var(--md-sys-color-primary-container)]/30 border-b border-[var(--md-sys-color-outline-variant)]">
                    <div
                        class="w-12 h-12 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] flex items-center justify-center shadow-md">
                        <span class="material-symbols-rounded text-2xl">waving_hand</span>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">پیام خوش‌آمدگویی</h2>
                        <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] mt-0.5">آغاز همکاری شما با
                            ما</p>
                    </div>
                </div>
                <div class="p-6 onb-prose">
                    {!! $onboarding->welcome !!}
                </div>
            </section>
        @endif

        {{-- Card 2: Videos --}}
        @if($videos = $presenter->videos($onboarding->videos))
            <section
                class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden">
                <header
                    class="flex items-center justify-between px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-low)]">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center">
                            <span class="material-symbols-rounded text-[20px]">play_circle</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">ویدیوهای آموزشی</h3>
                            <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">مشاهده کلیپ‌های
                                خوش‌آمدگویی</p>
                        </div>
                    </div>
                    <span
                        class="px-3 py-1 rounded-lg text-xs font-bold bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        {{ count($videos) }} مورد
                    </span>
                </header>

                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($videos as $video)
                            <div class="video-thumb-container group cursor-pointer"
                                 @click="openVideoModal = true; videoSrc = '{{ $video['url'] }}'; videoTitle = '{{ addslashes($video['title']) }}'">

                                <div
                                    class="relative aspect-video rounded-xl overflow-hidden bg-[var(--md-sys-color-surface-variant)] shadow-md">

                                    {{-- Thumbnail or placeholder --}}
                                    @if($video['thumbnail'])
                                        <img src="{{ $video['thumbnail'] }}"
                                             alt="{{ $video['title'] }}"
                                             loading="lazy"
                                             class="w-full h-full object-cover transition-transform duration-500">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <span
                                                class="material-symbols-rounded text-[56px] text-[var(--md-sys-color-on-surface-variant)] opacity-20">smart_display</span>
                                        </div>
                                    @endif

                                    {{-- Play overlay (CSS-driven via .video-thumb-container:hover) --}}
                                    <div
                                        class="video-play-overlay absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 transition-all duration-300 transform scale-90">
                                        <div
                                            class="w-16 h-16 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] flex items-center justify-center shadow-xl">
                                            <span class="material-symbols-rounded text-3xl mr-0.5">play_arrow</span>
                                        </div>
                                    </div>

                                    @if($video['duration'])
                                        <span
                                            class="absolute bottom-2 left-2 px-2 py-1 rounded-md bg-black/70 text-white text-xs font-mono"
                                            dir="ltr">
                                            {{ $video['duration'] }}
                                        </span>
                                    @endif
                                </div>

                                <h4 class="mt-3 text-sm font-bold text-[var(--md-sys-color-on-surface)] line-clamp-2 text-center">
                                    {{ $video['title'] }}
                                </h4>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <x-dashboard.modal.video/>

        @endif

        {{-- Card 3: Mission & Vision (Combined or Separate) --}}
        @if($onboarding->mission || $onboarding->vision)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($onboarding->mission)
                    <section
                        class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden">
                        <header
                            class="flex items-center gap-3 px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-low)]">
                            <div
                                class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center">
                                <span class="material-symbols-rounded text-[20px]">rocket_launch</span>
                            </div>
                            <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">ماموریت ما</h3>
                        </header>
                        <div class="p-6 onb-prose">
                            {!! $onboarding->mission !!}
                        </div>
                    </section>
                @endif

                @if($onboarding->vision)
                    <section
                        class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden">
                        <header
                            class="flex items-center gap-3 px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-low)]">
                            <div
                                class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center">
                                <span class="material-symbols-rounded text-[20px]">visibility</span>
                            </div>
                            <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">چشم‌انداز ما</h3>
                        </header>
                        <div class="p-6 onb-prose">
                            {!! $onboarding->vision !!}
                        </div>
                    </section>
                @endif
            </div>
        @endif

        {{-- Card 4: Guides --}}
        @if($guides = $presenter->guides($onboarding->guides))
            <section
                class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden">
                <header
                    class="flex items-center justify-between px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-low)]">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center">
                            <span class="material-symbols-rounded text-[20px]">menu_book</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">راهنماها و مستندات</h3>
                            <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">فایل‌های قابل
                                دانلود</p>
                        </div>
                    </div>
                    <span
                        class="px-3 py-1 rounded-lg text-xs font-bold bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                    {{ count($guides) }} فایل
                </span>
                </header>

                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($guides as $guide)
                        <div
                            class="group flex items-center gap-4 p-4 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)] hover:border-[var(--md-sys-color-primary)]/40 hover:shadow-md transition-all">
                            <div class="relative flex-shrink-0 w-12 h-12">
                                <div
                                    class="w-12 h-12 rounded-xl {{ $guide['icon_class'] }} flex items-center justify-center transition-transform group-hover:scale-110">
                                    <span class="material-symbols-rounded text-[24px]">{{ $guide['icon'] }}</span>
                                </div>
                                <span
                                    class="absolute -bottom-1 -left-1 text-[10px] font-bold px-1.5 py-0.5 rounded {{ $guide['badge_class'] }}">
                                {{ strtoupper($guide['ext']) }}
                            </span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs font-bold text-[var(--md-sys-color-on-surface)] truncate"
                                    title="{{ $guide['title'] }}">
                                    {{ $guide['title'] }}
                                </h4>
                                @if($guide['size'])
                                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5"
                                       dir="ltr">{{ $guide['size'] }}</p>
                                @endif
                            </div>

                            <a href="{{ $guide['url'] }}"
                               target="{{ $guide['is_pdf'] ? '_blank' : '_self' }}"
                               @if(!$guide['is_pdf']) download @endif
                               class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-all active:scale-90">
                                <span
                                    class="material-symbols-rounded text-lg">{{ $guide['is_pdf'] ? 'open_in_new' : 'download' }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Card 5: First Day Schedule --}}
        @if($onboarding->schedule)
            <section
                class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden">
                <header
                    class="flex items-center gap-3 px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-low)]">
                    <div
                        class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center">
                        <span class="material-symbols-rounded text-[20px]">today</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">برنامه روز اول</h3>
                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">برنامه زمانی آنبوردینگ</p>
                    </div>
                </header>
                <div class="p-6 onb-prose">
                    {!! $onboarding->schedule !!}
                </div>
            </section>
        @endif

        {{-- Card 6: Extras (Uniform Card Design) --}}
        @if($extras = $presenter->formatExtras($onboarding->extras))
            <section
                class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden">
                <header
                    class="flex items-center gap-3 px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-low)]">
                    <div
                        class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center">
                        <span class="material-symbols-rounded text-[20px]">campaign</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">اطلاعات تکمیلی</h3>
                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">سایر نکات مهم</p>
                    </div>

                    <span
                        class="px-3 py-1 rounded-lg text-xs font-bold bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        {{ count($extras) }} مورد
                    </span>
                </header>
                <div class="p-6 space-y-4">
                    @foreach($extras as $extra)
                        <div
                            class="group relative flex gap-4 p-5 rounded-xl border {{ $extra['container_class'] }} bg-[var(--md-sys-color-surface)] hover:shadow-md transition-all">
                            @if(!$extra['is_predefined'])
                                <span
                                    class="absolute top-3 left-3 text-[10px] px-2 py-0.5 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]">
                                سفارشی
                            </span>
                            @endif

                            <div
                                class="flex-shrink-0 w-10 h-10 rounded-xl {{ $extra['icon_bg_class'] }} flex items-center justify-center transition-transform group-hover:scale-110">
                                <span class="material-symbols-rounded text-[20px]">{{ $extra['icon'] }}</span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2 flex items-center gap-2">
                                    {{ $extra['title'] }}
                                    @if(!$extra['is_predefined'])
                                        <span class="text-[10px] font-normal text-[var(--md-sys-color-outline)]">({{ $extra['key'] }})</span>
                                    @endif
                                </h4>
                                <div class="onb-prose">{!! $extra['content'] !!}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

    @endif
</div>
