<div class="h-full w-full relative overflow-hidden flex flex-col lg:flex-row gap-6 p-4 md:p-6"
     x-data="{
         panelOpen: false,
         selectedPost: null
     }"
     @open-post-panel.window="panelOpen = true">

    {{-- Left Column: Sticky Pinned Post (RTL: actually Right visually if dir=rtl, but logical start) --}}
    {{-- In RTL, 'flex-row' puts the first item on the Right. --}}
    {{-- We want Pin on one side, Feed on the other. --}}

    <aside class="w-full lg:w-1/3 xl:w-1/4 flex-shrink-0 flex flex-col gap-4">

        <div class="sticky top-0 z-10">
            <div class="flex items-center gap-2 mb-3 px-1">
                 <span class="material-symbols-rounded text-[var(--md-sys-color-primary)]">keep</span>
                 <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">ویژه</h3>
            </div>

            @if($this->pins->isNotEmpty())
                @foreach($this->pins as $pin)
                    <div class="relative group cursor-pointer rounded-[24px] overflow-hidden bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)]/40 shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-[1.02]"
                         wire:click="selectPost({{ $pin->id }})">

                        {{-- Image with Overlay --}}
                        <div class="relative h-64 w-full overflow-hidden">
                            <img src="{{ $pin->image }}" alt="{{ $pin->title }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

                            <div class="absolute top-3 right-3 bg-[var(--md-sys-color-tertiary)]/90 backdrop-blur-md text-[var(--md-sys-color-on-tertiary)] text-xs font-bold px-3 py-1 rounded-full shadow-lg">
                                مهم
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="absolute bottom-0 inset-x-0 p-5 text-white">
                            <h2 class="text-xl font-bold mb-2 line-clamp-2 leading-tight drop-shadow-md">{{ $pin->title }}</h2>
                            <div class="flex items-center gap-2 text-white/80 text-xs">
                                <span class="material-symbols-rounded text-[14px]">calendar_today</span>
                                <span>{{ $pin->created_at->format('Y/m/d') }}</span>
                            </div>
                        </div>

                        {{-- Hover Effect Glow --}}
                        <div class="absolute inset-0 rounded-[24px] ring-2 ring-white/0 group-hover:ring-white/20 transition-all duration-500"></div>
                    </div>
                @endforeach
            @else
                <div class="p-6 text-center text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-container-low)] rounded-[24px] border border-dashed border-[var(--md-sys-color-outline-variant)]">
                    پست پین شده‌ای وجود ندارد
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

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            {{-- Island for Feed --}}
            @island(name: 'feed')
                @foreach($this->posts as $post)
                    <article class="group relative flex flex-col bg-[var(--md-sys-color-surface-container-low)] rounded-[20px] overflow-hidden border border-[var(--md-sys-color-outline-variant)]/30 transition-all duration-300 hover:bg-[var(--md-sys-color-surface-container)] hover:shadow-lg hover:-translate-y-1"
                             wire:key="post-{{ $post->id }}">

                        {{-- Image --}}
                        <div class="relative h-48 overflow-hidden cursor-pointer" wire:click="selectPost({{ $post->id }})">
                            <img src="{{ $post->image }}" loading="lazy" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" alt="{{ $post->title }}">
                        </div>

                        {{-- Body --}}
                        <div class="p-4 flex flex-col flex-grow">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-medium px-2 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                                    اخبار
                                </span>
                                <span class="text-[10px] text-[var(--md-sys-color-outline)] font-mono">
                                    {{ $post->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <h4 class="text-base font-bold text-[var(--md-sys-color-on-surface)] mb-2 line-clamp-1 cursor-pointer hover:text-[var(--md-sys-color-primary)] transition-colors"
                                wire:click="selectPost({{ $post->id }})">
                                {{ $post->title }}
                            </h4>

                            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] line-clamp-3 mb-4 flex-grow">
                                {!! Str::limit(strip_tags($post->body), 100) !!}
                            </p>

                            <div class="pt-3 mt-auto border-t border-[var(--md-sys-color-outline-variant)]/20 flex items-center justify-between">
                                <button wire:click="selectPost({{ $post->id }})"
                                        class="text-xs font-bold text-[var(--md-sys-color-primary)] flex items-center gap-1 hover:gap-2 transition-all">
                                    <span>ادامه مطلب</span>
                                    <span class="material-symbols-rounded text-[14px] flip-rtl">arrow_right_alt</span>
                                </button>

                                <button class="w-8 h-8 rounded-full flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-secondary-container)] hover:text-[var(--md-sys-color-on-secondary-container)] transition-colors">
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
                     class="px-6 py-2.5 rounded-full bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-primary)] font-bold text-sm border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm hover:shadow-md hover:bg-[var(--md-sys-color-surface-container-highest)] hover:scale-105 active:scale-95 transition-all duration-300 flex items-center gap-2">
                 <span>نمایش بیشتر</span>
                 <span class="material-symbols-rounded text-[18px]" wire:loading.remove target="loadMore">expand_more</span>
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
        <div class="absolute inset-y-0 left-0 max-w-2xl w-full bg-[var(--md-sys-color-surface)] shadow-2xl flex flex-col transform transition-transform duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] border-r border-[var(--md-sys-color-outline-variant)]/20"
             x-show="panelOpen"
             x-transition:enter="translate-x-full rtl:-translate-x-full"
             x-transition:enter-start="translate-x-full rtl:-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="translate-x-full rtl:-translate-x-full"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full rtl:-translate-x-full">

            @if($selectedPost)
                {{-- Header Image --}}
                <div class="relative h-64 sm:h-80 w-full shrink-0">
                    <img src="{{ $selectedPost->image }}" class="w-full h-full object-cover">
                    <button @click="panelOpen = false"
                            class="absolute top-4 left-4 w-10 h-10 rounded-full bg-black/50 text-white backdrop-blur-md flex items-center justify-center hover:bg-black/70 transition-colors shadow-lg z-10">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                    <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-surface)] to-transparent"></div>

                    <div class="absolute bottom-4 right-6 text-[var(--md-sys-color-on-surface)]">
                        <h1 class="text-2xl sm:text-3xl font-bold leading-tight drop-shadow-sm">{{ $selectedPost->title }}</h1>
                    </div>
                </div>

                {{-- Scrollable Body --}}
                <div class="flex-1 overflow-y-auto custom-scrollbar p-6 sm:p-8">
                     <div class="flex items-center gap-4 text-sm text-[var(--md-sys-color-on-surface-variant)] mb-6 pb-6 border-b border-[var(--md-sys-color-outline-variant)]/20">
                         <div class="flex items-center gap-1.5">
                             <span class="material-symbols-rounded text-[18px]">calendar_month</span>
                             <span>{{ $selectedPost->created_at->format('Y/m/d H:i') }}</span>
                         </div>
                         <div class="flex items-center gap-1.5">
                             <span class="material-symbols-rounded text-[18px]">person</span>
                             <span>ادمین سیستم</span>
                         </div>
                     </div>

                     <div class="prose prose-lg prose-p:text-[var(--md-sys-color-on-surface-variant)] prose-headings:text-[var(--md-sys-color-on-surface)] max-w-none">
                         {!! $selectedPost->body !!}
                     </div>
                </div>

                {{-- Footer Actions --}}
                <div class="p-4 border-t border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface-container-low)] flex justify-between items-center shrink-0">
                    <button class="flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-primary)] transition-colors">
                        <span class="material-symbols-rounded">share</span>
                        <span class="text-sm font-bold">اشتراک‌گذاری</span>
                    </button>
                     <button @click="panelOpen = false"
                             class="px-6 py-2 rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] font-bold shadow-md hover:shadow-lg hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-all">
                        بستن
                    </button>
                </div>
            @else
                <div class="flex-1 flex items-center justify-center">
                    <span class="animate-pulse text-[var(--md-sys-color-outline)]">در حال بارگذاری...</span>
                </div>
            @endif

        </div>
    </div>
</div>
