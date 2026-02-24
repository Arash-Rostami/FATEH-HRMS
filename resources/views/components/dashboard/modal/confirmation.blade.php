<template x-teleport="body">
    <div
        x-data="{
            show: false,
            active: false,
            title: '',
            message: '',
            confirmMethod: '',
            confirmParams: null,
            type: 'livewire' // 'livewire' or 'js'
        }"
        @open-confirmation.window="
            show = true;
            title = $event.detail.title;
            message = $event.detail.message;
            confirmMethod = $event.detail.method;
            confirmParams = $event.detail.params;
            type = $event.detail.type || 'livewire';
        "
        @keydown.escape.window="show = false"
        x-init="
            $watch('show', value => {
                if (value) {
                    // Small delay to allow enter transition to start properly
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
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        dir="rtl"
    >
        <!-- Close Icon -->
        <div class="modal-close-icon" @click="show = false"></div>

        <!-- Content -->
        <div class="custom-modal-content">
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

                <button
                    type="button"
                    class="modal-btn modal-btn-confirm"
                    @click="
                        if (type === 'livewire') {
                            $wire.call(confirmMethod, confirmParams);
                        } else {
                            $dispatch('confirmation-confirmed', { method: confirmMethod, params: confirmParams });
                        }
                        show = false
                    "
                >
                    تایید
                </button>
            </div>
        </div>
    </div>
</template>
