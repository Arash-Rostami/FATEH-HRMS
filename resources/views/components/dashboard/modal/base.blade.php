@props(['title', 'actions' => null])

<template x-teleport="body">
    <div
        x-data="{ show: @entangle($attributes->wire('model')), active: false }"
        x-init="
        $watch('show', v => {
            if (v) requestAnimationFrame(() => requestAnimationFrame(() => active = true))
            else active = false
        })"
        class="custom-modal"
        :class="{ 'active': active }"
        style="display: none;"
        x-show="show"
        x-transition:enter="transition duration-0"
        x-transition:leave="transition duration-1000 delay-1000"
    >
        <!-- Close Icon -->
        <div
            class="modal-close-icon"
            @click="show = false"
        ></div>

        <!-- Content -->
        <div class="custom-modal-content">
            @if($title)
                <h3 class="modal-title">{{ $title }}</h3>
            @endif

            <div class="modal-message">
                {{ $slot }}
            </div>

            @if($actions)
                <div class="modal-actions">
                    {{ $actions }}
                </div>
            @endif
        </div>
    </div>
</template>
