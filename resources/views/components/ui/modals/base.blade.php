@props(['title' => null, 'actions' => null, 'contentClass' => ''])

<template x-teleport="body">
    <div
        x-data="{ show: @entangle($attributes->wire('model')), active: false }"
        x-init="
        if (show) requestAnimationFrame(() => requestAnimationFrame(() => active = true))
        $watch('show', v => {
            if (v) requestAnimationFrame(() => requestAnimationFrame(() => active = true))
            else active = false
        })"
        x-on:keydown.escape.window="if(show) show = false"
        class="custom-modal"
        :class="{ 'active': active }"
        style="display: none;"
        x-show="show"
        x-transition:enter="transition duration-0"
        x-transition:leave="transition duration-1000 delay-1000"
    >
        <x-ui.modals.close-button close="show = false" tone="on-primary" size="lg"
            class="!absolute top-10 right-10 z-[10002]"
            x-bind:class="active ? 'opacity-100 duration-500 delay-1000' : 'opacity-0 duration-500'"/>

        <div class="custom-modal-content scrollbar-hover-reveal {{ $contentClass }}">
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
