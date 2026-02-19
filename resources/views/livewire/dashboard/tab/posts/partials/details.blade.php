<x-dashboard.modal.slideover show="panelOpen">
    @if($this->selectedPost)
        <div class="relative h-72 sm:h-96 w-full shrink-0 group">
            <img src="{{ $selectedPost->image }}" class="w-full h-full object-cover">

            <button
                @click="togglePanel()"
                class="absolute top-6 left-6 w-10 h-10 rounded-full bg-black/40 text-white/90  flex items-center justify-center hover:bg-black/60 hover:scale-110 hover:text-white transition-all shadow-lg z-20 border border-white/10"
            >
                <span class="material-symbols-rounded font-bold">close</span>
            </button>

            <div
                class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-surface)] via-black/20 to-transparent"></div>

            <div class="absolute bottom-6 right-8 left-8 text-[var(--md-sys-color-on-surface)] z-10">
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[var(--md-sys-color-secondary-container)]/80  text-[var(--md-sys-color-on-secondary-container)] text-xs font-bold mb-3 shadow-sm">
                    <span class="material-symbols-rounded text-[14px]">new_releases</span>
                    <span>خبر</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-black leading-tight drop-shadow-lg tracking-tight mb-2 text-white">
                    {{ superClean($selectedPost->title, 200) }}
                </h1>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-8 sm:p-10 bg-[var(--md-sys-color-surface)]">
            <div
                class="flex items-center gap-6 text-sm text-[var(--md-sys-color-on-surface-variant)] mb-8 pb-6 border-b border-[var(--md-sys-color-outline-variant)]/20">
                <div class="flex items-center gap-2 bg-[var(--md-sys-color-surface-container)] px-3 py-1.5 rounded-lg">
                    <span
                        class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">calendar_month</span>
                    <span class="font-medium">{{ $selectedPost->created_at->format('Y/m/d H:i') }}</span>
                </div>
                <div class="flex items-center gap-2 bg-[var(--md-sys-color-surface-container)] px-3 py-1.5 rounded-lg">
                    <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">person</span>
                    <span class="font-medium">ادمین سیستم</span>
                </div>
            </div>

            <div
                class="prose prose-lg prose-p:text-[var(--md-sys-color-on-surface)] prose-headings:text-[var(--md-sys-color-on-surface)] max-w-none leading-relaxed [&_*]:!bg-transparent [&_*]:!text-inherit">
                {!! $selectedPost->body !!}
            </div>
        </div>

        <div
            class="p-5 border-t border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-container-low)] flex justify-between items-center shrink-0 shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)] relative">
            <x-dashboard.modal.popover>
                <x-slot:trigger>
                    <button
                        @click="openShare('{{ addslashes(superClean($selectedPost->title, 200)) }}', '{{ addslashes(superClean($selectedPost->body, 300)) }}')"
                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl hover:bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-secondary-container)] transition-colors font-bold text-sm group"
                    >
                        <span class="material-symbols-rounded group-hover:scale-110 transition-transform">share</span>
                        <span>اشتراک‌گذاری</span>
                    </button>
                </x-slot:trigger>

                <x-slot:content>
                    <button
                        @click="copyToClipboard();"
                        class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-on-surface)] text-sm transition-colors text-right w-full"
                    >
                        <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">content_copy</span>
                        <span>کپی متن</span>
                    </button>
                    <button
                        @click="sendEmail();"
                        class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-on-surface)] text-sm transition-colors text-right border-t border-[var(--md-sys-color-outline-variant)]/20 w-full"
                    >
                        <span
                            class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">mail</span>
                        <span>ایمیل</span>
                    </button>
                </x-slot:content>
            </x-dashboard.modal.popover>

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
</x-dashboard.modal.slideover>
