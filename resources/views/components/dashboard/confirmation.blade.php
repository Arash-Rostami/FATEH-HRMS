<div
    x-data="{
        open: false,
        title: '',
        message: '',
        method: '',
        params: null
    }"
    @open-confirmation.window="
        open = true;
        title = $event.detail.title;
        message = $event.detail.message;
        method = $event.detail.method;
        params = $event.detail.params;
    "
    x-show="open"
    class="fixed inset-0 z-[100] flex items-center justify-center px-4"
    style="display: none;"
    x-cloak
>
    <div
        x-show="open"
        x-transition.opacity
        @click="open = false"
        class="absolute inset-0 bg-black/40 backdrop-blur-sm"
    ></div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-90 translate-y-4"
        @click.stop
        x-trap.noscroll="open"
        class="relative w-full max-w-sm bg-[var(--md-sys-color-surface)] rounded-[28px] p-6 shadow-2xl overflow-hidden border border-[var(--md-sys-color-outline-variant)]/20 text-right"
        dir="rtl"
    >
            <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] mb-2" x-text="title"></h3>
            <p class="text-[var(--md-sys-color-on-surface-variant)] mb-6 text-sm leading-relaxed" x-text="message"></p>

            <div class="flex items-center justify-end gap-2">
            <button
                @click="open = false"
                class="px-5 py-2.5 rounded-full text-[var(--md-sys-color-primary)] font-bold text-sm hover:bg-[var(--md-sys-color-surface-variant)] transition-colors"
            >
                انصراف
            </button>
            <button
                @click="
                    $wire.call(method, params);
                    open = false;
                "
                class="px-5 py-2.5 rounded-full bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)] font-bold text-sm shadow-md hover:shadow-lg hover:scale-105 transition-all"
            >
                تایید
            </button>
            </div>
    </div>
</div>
