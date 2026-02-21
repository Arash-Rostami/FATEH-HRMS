<x-dashboard.modal.slideover show="showModal">
    <template x-if="activeReport">
        <div class="h-full flex flex-col">
            <!-- Hero Image -->
            <div class="h-64 md:h-80 w-full relative shrink-0">
                <img :src="activeReport.thumbnail" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-surface)] to-transparent"></div>

                <!-- Close Button Overlay -->
                <button @click="showModal = false" class="absolute top-4 right-4 z-20 p-2 rounded-xl bg-black/30 hover:bg-black/50 text-white transition-colors border border-white/10">
                    <span class="material-symbols-rounded text-xl">close</span>
                </button>


                <div class="absolute bottom-6 right-6 left-6" dir="rtl">
                    <h2 class="text-2xl md:text-3xl font-bold text-[var(--md-sys-color-on-surface)] drop-shadow-sm" x-text="activeReport.title"></h2>
                    <p class="text-[var(--md-sys-color-on-surface-variant)] text-sm mt-2 font-mono opacity-80" x-text="activeReport.created_at_formatted"></p>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6 text-right" dir="rtl">
                <div class="prose prose-lg max-w-none text-[var(--md-sys-color-on-surface)] leading-relaxed text-justify" x-html="activeReport.description"></div>

                <div class="flex justify-center pt-6 border-t border-[var(--md-sys-color-outline-variant)]">
                    <button @click="$wire.download(activeReport.id)"
                            class="flex items-center gap-2 bg-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] text-[var(--md-sys-color-on-primary)] px-8 py-3 rounded-xl font-bold transition-all shadow-md hover:shadow-lg transform hover:-translate-y-1">
                        <span class="material-symbols-rounded text-xl">download</span>
                        <span>دانلود فایل کامل</span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</x-dashboard.modal.slideover>
