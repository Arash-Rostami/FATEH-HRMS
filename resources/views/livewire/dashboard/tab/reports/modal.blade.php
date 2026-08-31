<x-ui.modals.slideover show="showModal">
    <template x-if="activeReport">
        <div
            x-data="{ imageViewer: false }"
            class="flex h-full min-h-0 flex-col overflow-hidden bg-transparent"
        >
            {{-- Hero Image --}}
            <div
                @click="imageViewer = true"
                class="relative w-full shrink-0 overflow-hidden group cursor-zoom-in h-[42vh]"
            >
                <img
                    :src="activeReport.thumbnail"
                    :alt="activeReport.title"
                    class="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-[1.03]"
                >

                {{-- Close --}}
                <button
                    @click.stop="showModal = false"
                    class="absolute left-6 top-6 z-20 flex h-10 w-10 items-center justify-center rounded-md border border-white/10 bg-black/40 text-white/90 shadow-lg transition-all hover:scale-110 hover:bg-black/60 hover:text-white"
                >
                    <span class="material-symbols-rounded font-bold">close</span>
                </button>

                {{-- Expand --}}
                <button
                    @click.stop="imageViewer = true"
                    class="absolute right-6 top-6 z-20 flex h-10 w-10 items-center justify-center rounded-md border border-white/10 bg-black/30 text-white/90 shadow-lg transition-all hover:bg-black/50 hover:text-white"
                >
                    <span class="material-symbols-rounded text-[20px]">open_in_full</span>
                </button>

                {{-- Badges + title --}}
                <div class="absolute inset-x-0 bottom-0 z-10 p-6 sm:p-8" dir="rtl">
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                        <span class="inline-flex items-center gap-2 rounded-md border border-white/10 bg-[var(--md-sys-color-secondary-container)]/80 px-3 py-1 text-xs font-bold text-[var(--md-sys-color-on-secondary-container)] shadow-sm">
                            <span class="material-symbols-rounded text-[14px]">description</span>
                            <span x-text="activeReport.file_type?.toUpperCase()"></span>
                        </span>

                        <span class="inline-flex items-center gap-2 rounded-md border border-white/10 bg-black/20 px-3 py-1 text-xs font-medium text-white/85 shadow-sm">
                            <span class="material-symbols-rounded text-[14px] text-white/90">calendar_month</span>
                            <span class="font-medium" x-text="activeReport.report_date_formatted ?? activeReport.created_at_formatted"></span>
                        </span>
                    </div>

                    <h2 class="mt-4 max-w-4xl text-3xl font-black leading-tight tracking-tight text-white drop-shadow-[0_2px_18px_rgba(0,0,0,0.35)] sm:text-4xl"
                        x-text="activeReport.title">
                    </h2>
                </div>
            </div>

            {{-- Body --}}
            <div class="min-h-0 flex-1 overflow-y-auto custom-scrollbar bg-transparent px-5 py-6 sm:px-8 sm:py-8" dir="rtl">
                <div class="prose prose-lg max-w-none leading-relaxed text-justify prose-headings:text-[var(--md-sys-color-on-surface)] prose-p:text-[var(--md-sys-color-on-surface)] rich-colors"
                     x-html="activeReport.description">
                </div>
            </div>

            {{-- Footer --}}
            <div class="shrink-0 border-t border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-container-low)] px-5 py-4 shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)] sm:px-8">
                <div class="flex flex-col gap-3 sm:flex-row items-center justify-center">

                    <button
                        @click="$wire.download(activeReport.id)"
                        class="inline-flex items-center justify-center gap-2 rounded-md bg-[var(--md-sys-color-primary)] px-8 py-2.5 text-sm font-bold text-[var(--md-sys-color-on-primary)] shadow-md transition-all hover:-translate-y-0.5 hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:shadow-xl active:scale-95 active:shadow-sm"
                    >
                        <span class="material-symbols-rounded text-xl">download</span>
                        <span>دانلود فایل کامل</span>
                    </button>
                </div>
            </div>

            {{-- Fullscreen viewer --}}
            <template x-teleport="body">
                <div
                    x-cloak
                    x-show="imageViewer"
                    x-transition.opacity.duration.200ms
                    class="fixed inset-0 z-[99999] bg-[var(--md-sys-color-primary)] animate-lightbox-in"
                    @keydown.escape.window="imageViewer = false"
                    @click.self="imageViewer = false"
                >
                    <div class="absolute inset-0 flex items-center justify-center p-4 sm:p-8">
                        <div class="relative w-full max-w-[min(96vw,1600px)]">
                            <img
                                :src="activeReport.thumbnail"
                                :alt="activeReport.title"
                                class="max-h-[92vh] w-full select-none object-contain rounded-xl"
                            >
                            <button
                                @click="imageViewer = false"
                                class="absolute right-3 top-3 flex h-11 w-11 items-center justify-center rounded-xl bg-black/55 text-white shadow-lg transition hover:scale-105 hover:bg-black/75"
                            >
                                <span class="material-symbols-rounded">close</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </template>
</x-ui.modals.slideover>
