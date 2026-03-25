<template x-teleport="body">
    <div
        x-data="{
            show: false,
            active: false,
            documentContent: '',
            docId: '',
            isPdf: false,
            confirmMethod: 'confirmRead',
            type: 'livewire'
        }"
        @open-pdf-viewer.window="
            show = true;
            documentContent = $event.detail.url;
            docId = $event.detail.docId;
            isPdf = $event.detail.isPdf;
            type = $event.detail.type || 'livewire';
            confirmMethod = $event.detail.method || 'confirmRead';
        "
        @keydown.escape.window="show = false"
        x-init="
            $watch('show', value => {
                if (value) {
                    setTimeout(() => active = true, 50);
                } else {
                    active = false;
                    documentContent = '';
                    docId = '';
                }
            })
        "
        class="fixed inset-0 z-[100] flex items-center justify-center bg-[var(--md-sys-color-scrim)]/50 backdrop-blur-sm transition-opacity duration-300"
        :class="active ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        style="display: none;"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        dir="rtl"
    >
        <!-- Content -->
        <div class="bg-[var(--md-sys-color-surface-container-high)] w-full max-w-6xl h-[90vh] md:h-[95vh] rounded-[28px] md:rounded-[32px] shadow-[0_8px_32px_rgba(0,0,0,0.12)] border border-[var(--md-sys-color-outline-variant)]/30 flex flex-col overflow-hidden transition-all duration-300 transform"
             :class="active ? 'scale-100 translate-y-0' : 'scale-95 translate-y-4'"
             @click.away="show = false">

            <!-- Header -->
            <div class="shrink-0 h-16 px-6 flex items-center justify-between border-b border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shrink-0">
                        <span class="material-symbols-rounded text-[22px]" x-text="isPdf ? 'picture_as_pdf' : 'description'"></span>
                    </div>
                    <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)] truncate max-w-sm md:max-w-xl">مشاهده و تأیید سند</h3>
                </div>

                <button @click="show = false"
                        class="w-10 h-10 rounded-full flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] transition-colors group">
                    <span class="material-symbols-rounded text-[22px] group-hover:rotate-90 transition-transform duration-300">close</span>
                </button>
            </div>

            <!-- Body (Iframe) -->
            <div class="flex-1 w-full bg-[#525659] relative overflow-hidden">
                <template x-if="show && isPdf">
                    <iframe :src="documentContent" class="w-full h-full border-none shadow-inner" loading="lazy" type="application/pdf"></iframe>
                </template>
                <template x-if="show && !isPdf">
                    <div class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center bg-[var(--md-sys-color-surface-container)]">
                        <div class="w-24 h-24 rounded-3xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] flex items-center justify-center mb-6 shadow-sm">
                            <span class="material-symbols-rounded text-6xl">file_present</span>
                        </div>
                        <h4 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] mb-3">سند غیر PDF</h4>
                        <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] mb-8 max-w-md leading-relaxed">این فایل قابل نمایش در مرورگر نیست. لطفاً برای مشاهده و بررسی، آن را دانلود کنید.</p>

                        <a :href="documentContent" target="_blank" class="h-12 px-8 rounded-full bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-bold flex items-center gap-2 hover:bg-[var(--md-sys-color-secondary)] hover:text-[var(--md-sys-color-on-secondary)] hover:shadow-md transition-all">
                            <span class="material-symbols-rounded text-[20px]">download</span>
                            <span>دانلود فایل</span>
                        </a>
                    </div>
                </template>

                <!-- Loading Overlay -->
                <div x-show="!documentContent" class="absolute inset-0 bg-[var(--md-sys-color-surface-container)] flex items-center justify-center z-10">
                    <div class="flex flex-col items-center gap-4">
                        <div class="animate-spin rounded-full h-10 w-10 border-b-4 border-[var(--md-sys-color-primary)]"></div>
                        <span class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)] animate-pulse">در حال بارگذاری سند...</span>
                    </div>
                </div>
            </div>

            <!-- Footer / Action Area -->
            <div class="shrink-0 p-5 bg-[var(--md-sys-color-surface)] border-t border-[var(--md-sys-color-outline-variant)]/50 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-start gap-3 bg-[var(--md-sys-color-error-container)]/50 p-3 rounded-xl border border-[var(--md-sys-color-error)]/20 w-full md:w-auto">
                    <span class="material-symbols-rounded text-[var(--md-sys-color-error)] text-[20px] shrink-0 mt-0.5">verified_user</span>
                    <p class="text-[11px] md:text-xs text-[var(--md-sys-color-on-error-container)] font-medium leading-relaxed max-w-2xl text-justify">
                        با کلیک بر روی دکمه <span class="font-bold border-b border-[var(--md-sys-color-error)] border-dashed pb-0.5">تأیید</span>، شما رسماً تأیید می‌کنید که این سند را به طور کامل مطالعه کرده و از مفاد آن مطلع شده‌اید. این اقدام در سیستم ثبت خواهد شد.
                    </p>
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto justify-end shrink-0">
                    <button type="button" @click="show = false" class="h-11 px-6 rounded-full text-sm font-bold text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/10 transition-colors">
                        انصراف
                    </button>
                    <button type="button"
                            @click="
                                if (type === 'livewire') {
                                    $wire.call(confirmMethod, docId);
                                } else {
                                    $dispatch('pdf-viewer-confirmed', { method: confirmMethod, docId: docId });
                                }
                                show = false;
                            "
                            class="h-11 px-8 rounded-full text-sm font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] hover:bg-[var(--md-sys-color-primary)] transition-all flex items-center gap-2">
                        <span class="material-symbols-rounded text-[18px]">done_all</span>
                        <span>تأیید و ثبت مطالعه</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
