<template x-teleport="body">
    <div
        x-data="{
            show: false,
            title: '',
            message: '',
            confirmMethod: '',
            confirmParams: null
        }"
        @open-confirmation.window="
            show = true;
            title = $event.detail.title;
            message = $event.detail.message;
            confirmMethod = $event.detail.method;
            confirmParams = $event.detail.params;
        "
        @keydown.escape.window="show = false"
        x-cloak
        class="relative z-[9999]"
        x-show="show"
        dir="rtl"
    >
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-3xl bg-[var(--md-sys-color-surface)] text-right shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-[var(--md-sys-color-outline-variant)]/20"
                    @click.away="show = false"
                >
                    <div class="p-6 text-center">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-6 border-4"
                             style="background-color: color-mix(in srgb, var(--md-sys-color-error), transparent 88%); border-color: color-mix(in srgb, var(--md-sys-color-error), transparent 75%);">
                            <span class="material-symbols-rounded text-3xl" style="color: var(--md-sys-color-error)">warning</span>
                        </div>

                        <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)] mb-2" x-text="title"></h3>
                        <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] leading-relaxed px-4"
                           x-text="message"></p>
                    </div>

                    <div
                        class="bg-[var(--md-sys-color-surface-container-low)] px-6 py-4 flex flex-row-reverse gap-3 justify-center border-t border-[var(--md-sys-color-outline-variant)]/10">
                        <button type="button"
                                class="w-full inline-flex justify-center items-center rounded-full px-4 py-2.5 text-sm font-bold shadow-md transition-all active:scale-95 hover:shadow-lg"
                                style="background-color: var(--md-sys-color-error); color: var(--md-sys-color-on-error);"
                                @mouseenter="$el.style.filter='brightness(108%)'"
                                @mouseleave="$el.style.filter=''"
                                @click="$wire.call(confirmMethod, confirmParams); show = false">
                            تایید و حذف
                        </button>
                        <button type="button"
                                class="w-full inline-flex justify-center items-center rounded-full border border-[var(--md-sys-color-outline)]/20 px-4 py-2.5 bg-[var(--md-sys-color-surface)] text-sm font-bold text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-highest)] transition-all active:scale-95"
                                @click="show = false">
                            انصراف
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
