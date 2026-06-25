@props([
    'state' => 'max',
    'close' => 'toggleMaximize()',
    'class' => '',
])

<template x-if="{!! $state !!}">
    <div
        class="max-backdrop {{ $class }}"
        @click="{!! $close !!}">

    </div>
</template>
