<div x-data="{
        documentContent: '',
        docId: '',
        isPdf: false,
        confirmMethod: 'confirmRead',
        type: 'livewire'
    }"
    @open-pdf-viewer.window="
        documentContent = $event.detail.url;
        docId = $event.detail.docId;
        isPdf = $event.detail.isPdf;
        type = $event.detail.type || 'livewire';
        confirmMethod = $event.detail.method || 'confirmRead';
        $dispatch('dms-modal');
    "
    @dms-modal-closed.window="
        documentContent = '';
        docId = '';
    "
>
    <x-dashboard.modal.general
        eventName="dms-modal"
        maxWidth="6xl"
        icon="description"
        title="مشاهده و تأیید سند"
    >

        {{-- Body (Iframe) --}}
        <div class="w-full bg-[#525659] relative overflow-hidden" style="min-height: 60vh;">
            <template x-if="documentContent && isPdf">
                <iframe :src="documentContent"
                        class="w-full h-full absolute inset-0 border-none shadow-inner"
                        loading="lazy"
                        type="application/pdf">
                </iframe>
            </template>
            <template x-if="documentContent && !isPdf">
                <div class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center bg-[var(--md-sys-color-surface-container)]">
                    <div class="w-24 h-24 rounded-3xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] flex items-center justify-center mb-6 shadow-sm">
                        <span class="material-symbols-rounded text-6xl">file_present</span>
                    </div>
                    <h4 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] mb-3">سند غیر PDF</h4>
                    <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] mb-8 max-w-md leading-relaxed">این فایل قابل نمایش در مرورگر نیست. لطفاً برای مشاهده و بررسی، آن را دانلود کنید.</p>

                    <a :href="documentContent" target="_blank" class="h-12 px-8 rounded-lg bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-bold flex items-center gap-2 hover:bg-[var(--md-sys-color-secondary)] hover:text-[var(--md-sys-color-on-secondary)] hover:shadow-md transition-all">
                        <span class="material-symbols-rounded text-[20px]">download</span>
                        <span>دانلود فایل</span>
                    </a>
                </div>
            </template>

            {{-- Loading Overlay --}}
            <div x-show="!documentContent" class="absolute inset-0 bg-[var(--md-sys-color-surface-container)] flex items-center justify-center z-10">
                <div class="flex flex-col items-center gap-4">
                    <div class="animate-spin rounded-lg h-10 w-10 border-b-4 border-[var(--md-sys-color-primary)]"></div>
                    <span class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)] animate-pulse">در حال بارگذاری سند...</span>
                </div>
            </div>
        </div>

        {{-- Footer / Action Area --}}
        <x-slot:footer>
            <div class="flex items-start gap-3 bg-[var(--md-sys-color-error-container)]/50 p-3 rounded-xl border border-[var(--md-sys-color-error)]/20 w-full md:w-auto">
                <span class="material-symbols-rounded text-[var(--md-sys-color-error)] text-[20px] shrink-0 mt-0.5">verified_user</span>
                <p class="text-[11px] md:text-xs text-[var(--md-sys-color-on-error-container)] font-medium leading-relaxed max-w-2xl text-justify">
                    با کلیک بر روی دکمه <span class="font-bold border-b border-[var(--md-sys-color-error)] border-dashed pb-0.5">تأیید</span>، شما رسماً تأیید می‌کنید که این سند را به طور کامل مطالعه کرده و از مفاد آن مطلع شده‌اید. این اقدام در سیستم ثبت خواهد شد.
                </p>
            </div>

            <div class="flex items-center gap-3 w-full md:w-auto justify-end shrink-0">
                <button type="button" @click="$dispatch('dms-modal-closed'); window.dispatchEvent(new CustomEvent('keydown', { key: 'Escape' }))" class="h-11 px-6 rounded-lg text-sm font-bold text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/10 transition-colors">
                    انصراف
                </button>
                <button type="button"
                        @click="
                            if (type === 'livewire') {
                                $wire.call(confirmMethod, docId);
                            } else {
                                $dispatch('pdf-viewer-confirmed', { method: confirmMethod, docId: docId });
                            }
                            window.dispatchEvent(new CustomEvent('keydown', { key: 'Escape' }));
                        "
                        class="h-11 px-8 rounded-lg text-sm font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] hover:bg-[var(--md-sys-color-primary)] transition-all flex items-center gap-2">
                    <span class="material-symbols-rounded text-[18px]">done_all</span>
                    <span>تأیید و ثبت مطالعه</span>
                </button>
            </div>
        </x-slot:footer>

    </x-dashboard.modal.general>
</div>
