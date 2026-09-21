<template x-teleport="body">
    <div
        x-data="{
            show: false,
            active: false,
            title: '',
            message: '',
            confirmMethod: '',
            confirmParams: null,
            type: 'livewire'
        }"
        @open-confirmation.window="
            let payload = $event.detail;
            if (Array.isArray(payload) && payload.length === 1) {
                payload = payload[0];
            }
            show = true;
            title = payload.title || '';
            message = payload.message || '';
            confirmMethod = payload.method || '';
            confirmParams = payload.params;
            type = payload.type || 'livewire';
        "
        @keydown.escape.window="show = false"
        x-init="
            if (show) setTimeout(() => active = true, 50);
            $watch('show', value => {
                if (value) {
                    setTimeout(() => active = true, 50);
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
            <h3 class="modal-title" x-text="title"></h3>

            <div class="modal-message" x-text="message"></div>

            <div class="modal-actions">
                <button
                    type="button"
                    class="modal-btn modal-btn-cancel"
                    @click="show = false"
                >
                    انصراف
                </button>

                <template x-if="type !== 'livewire'">
                    <button
                        type="button"
                        class="modal-btn modal-btn-confirm"
                        @click="$dispatch('confirmation-confirmed', { method: confirmMethod, params: confirmParams }); show = false"
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
                        class="modal-btn modal-btn-confirm"
                        @click="Livewire.dispatch('confirmation-confirmed', { method: confirmMethod, params: confirmParams }); show = false"
                    >
                        تایید
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>
