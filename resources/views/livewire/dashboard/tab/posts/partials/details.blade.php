    {{-- Slide-Over Panel --}}
    <x-dashboard.modal.slide-over
        show="panelOpen"
        onClose="panelOpen = false"
    >
        @if($this->selectedPost)
            <x-slot:header class="relative h-72 sm:h-96 w-full shrink-0 group">
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
            </x-slot:header>

            <x-slot:body>
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
            </x-slot:body>

            <x-slot:actions>
                <x-dashboard.modal.share-popover
                    :postTitle="$selectedPost->title"
                    :postBody="$selectedPost->body"
                >
                    <x-slot:trigger class="flex items-center gap-2 px-5 py-2.5 rounded-xl hover:bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-secondary-container)] transition-colors font-bold text-sm group">
                         <span class="material-symbols-rounded group-hover:scale-110 transition-transform">share</span>
                         <span>اشتراک‌گذاری</span>
                    </x-slot:trigger>
                </x-dashboard.modal.share-popover>

                <button
                    @click="togglePanel()"
                    class="px-8 py-2.5 rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] font-bold text-sm shadow-md hover:shadow-xl hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:-translate-y-0.5 transition-all active:scale-95 active:shadow-sm"
                >
                    بستن
                </button>
            </x-slot:actions>
        @else
            <x-slot:body>
                <x-dashboard.loader.spinner/>
            </x-slot:body>
        @endif
    </x-dashboard.modal.slide-over>
