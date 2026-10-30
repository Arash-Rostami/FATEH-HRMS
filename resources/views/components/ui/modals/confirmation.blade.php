<template x-teleport="body">
    <div
        x-data="{
            show: false,
            active: false,
            firing: false,
            title: '',
            message: '',
            confirmMethod: '',
            confirmParams: null,
            type: 'livewire',
            tone: '',
            get rows() {
                return this.message.split('\n').map(l => l.trim()).filter(Boolean).map(l => {
                    const k = l.indexOf(': ');
                    return k > 0 ? { label: l.slice(0, k), value: l.slice(k + 2) } : { label: '', value: l };
                });
            }
        }"
        @open-confirmation.window="
            let payload = $event.detail;
            if (Array.isArray(payload) && payload.length === 1) {
                payload = payload[0];
            }
            show = true;
            firing = false;
            title = payload.title || '';
            message = payload.message || '';
            confirmMethod = payload.method || '';
            confirmParams = payload.params;
            type = payload.type || 'livewire';
            tone = payload.tone || '';
        "
        @keydown.escape.window="show = false"
        x-init="
            if (show) setTimeout(() => active = show, 50);
            $watch('show', value => {
                if (value) {
                    setTimeout(() => active = show, 50);
                } else {
                    active = false;
                }
            })
        "
        class="custom-modal"
        :class="{ 'active': active }"
        style="display: none;"
        x-show="show"
        x-transition:enter="transition duration-0"
        x-transition:leave="transition duration-1000 delay-1000"
        dir="rtl"
    >
        <!-- Close Icon -->
        <x-ui.modals.close-button close="show = false" tone="on-primary" size="lg"
            class="!absolute top-10 right-10 z-[10002]"
            x-bind:class="active ? 'opacity-100 duration-500 delay-1000' : 'opacity-0 duration-500'"/>

        <!-- Content -->
        <div class="custom-modal-content scrollbar-hover-reveal">
            <div class="modal-inner-card !w-full !max-w-md !p-6 space-y-5 text-start" dir="rtl" role="dialog" aria-modal="true" aria-labelledby="confirmation-title" x-trap="active">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                          :class="tone === 'danger' ? 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]' : 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'">
                        <span class="material-symbols-rounded text-[24px]" aria-hidden="true" x-text="tone === 'danger' ? 'warning' : 'help'">help</span>
                    </span>
                    <h3 id="confirmation-title" class="min-w-0 break-words text-lg font-black leading-tight text-[var(--md-sys-color-on-surface)]" x-text="title"></h3>
                </div>

                <div x-show="rows.length > 1" class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-low)] divide-y divide-[var(--md-sys-color-outline-variant)]/40">
                    <template x-for="(row, i) in rows" :key="i">
                        <div class="flex items-start justify-between gap-4 px-4 py-2.5 text-sm">
                            <span x-show="row.label" class="shrink-0 font-semibold text-[var(--md-sys-color-on-surface-variant)]" x-text="row.label"></span>
                            <span dir="auto" class="min-w-0 break-words font-bold text-[var(--md-sys-color-on-surface)]" x-text="row.value"></span>
                        </div>
                    </template>
                </div>
                <p x-show="rows.length <= 1" dir="auto" class="break-words text-sm leading-7 text-[var(--md-sys-color-on-surface-variant)]" x-text="message"></p>

                <div class="flex gap-3 pt-1">
                    <button
                        type="button"
                        class="flex-1 h-11 rounded-xl text-sm font-bold bg-transparent border border-[var(--md-sys-color-outline-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/40 transition-colors active:scale-[0.97]"
                        @click="show = false"
                    >
                        انصراف
                    </button>

                    <template x-if="type !== 'livewire'">
                        <button
                            type="button"
                            class="flex-1 h-11 rounded-xl text-sm font-bold shadow-md hover:brightness-110 transition-all active:scale-[0.97]"
                            :class="tone === 'danger' ? 'bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)]' : 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[var(--md-sys-color-primary)]/20'"
                            @click="if (firing) return; firing = true; $dispatch('confirmation-confirmed', { method: confirmMethod, params: confirmParams }); show = false"
                        >
                            تایید
                        </button>
                    </template>

{{--                <template x-if="type === 'livewire'">--}}
{{--                    <button--}}
{{--                        type="button"--}}
{{--                        class="modal-btn modal-btn-confirm"--}}
{{--                        @click="$wire.call(confirmMethod, confirmParams); show = false"--}}
{{--                    >--}}
{{--                        تایید--}}
{{--                    </button>--}}
{{--                </template>--}}

                    <template x-if="type === 'livewire'">
                        <button
                            type="button"
                            class="flex-1 h-11 rounded-xl text-sm font-bold shadow-md hover:brightness-110 transition-all active:scale-[0.97]"
                            :class="tone === 'danger' ? 'bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)]' : 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[var(--md-sys-color-primary)]/20'"
                            @click="if (firing) return; firing = true; Livewire.dispatch('confirmation-confirmed', { method: confirmMethod, params: confirmParams }); show = false"
                        >
                            تایید
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
