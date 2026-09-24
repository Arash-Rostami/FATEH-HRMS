@props([
    'state' => 'max',
    'close' => 'toggleMaximize()',
    'class' => '',
])

<template x-if="{!! $state !!}">
    <div
        class="max-backdrop {{ $class }}"
        x-bind:class="{ 'max-backdrop--leaving': typeof maxLeaving !== 'undefined' && maxLeaving }"
        @click="{!! $close !!}">

    </div>
</template>
