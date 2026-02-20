<!-- Modal for Full Details -->
<div x-show="showModal"
     class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-[var(--md-sys-color-scrim)]/60 backdrop-blur-sm"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display: none;"
     @click.self="showModal = false">

    <div class="bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)] rounded-3xl w-full max-w-2xl max-h-[90vh] overflow-y-auto relative shadow-2xl"
         x-show="showModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4">

         <!-- Close Button -->
         <button @click="showModal = false" class="absolute top-4 right-4 z-20 p-2 rounded-full bg-[var(--md-sys-color-surface-dim)]/50 hover:bg-[var(--md-sys-color-surface-dim)] text-[var(--md-sys-color-on-surface)] transition-colors backdrop-blur-md border border-[var(--md-sys-color-outline-variant)]">
            <span class="material-symbols-rounded text-xl">close</span>
         </button>

         <template x-if="activeReport">
             <div>
                <!-- Hero Image -->
                <div class="h-64 md:h-80 w-full relative">
                    <img :src="activeReport.thumbnail" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-surface-container)] to-transparent"></div>
                    <div class="absolute bottom-6 right-6 left-6" dir="rtl">
                         <h2 class="text-2xl md:text-3xl font-bold text-[var(--md-sys-color-on-surface)] drop-shadow-sm" x-text="activeReport.title"></h2>
                         <p class="text-[var(--md-sys-color-on-surface-variant)] text-sm mt-2 font-mono opacity-80" x-text="activeReport.created_at_formatted"></p>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 md:p-8 space-y-6" dir="rtl">
                    <div class="prose prose-lg max-w-none text-[var(--md-sys-color-on-surface)] leading-relaxed text-justify" x-html="activeReport.description"></div>

                    <div class="flex justify-center pt-6 border-t border-[var(--md-sys-color-outline-variant)]">
                        <button @click="$wire.download(activeReport.id)"
                                class="flex items-center gap-2 bg-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] text-[var(--md-sys-color-on-primary)] px-8 py-3 rounded-2xl font-bold transition-all shadow-md hover:shadow-lg transform hover:-translate-y-1">
                            <span class="material-symbols-rounded text-xl">download</span>
                            <span>دانلود فایل کامل</span>
                        </button>
                    </div>
                </div>
             </div>
         </template>
    </div>
</div>
