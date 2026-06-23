@props(['class' => 'inset-y-0 left-0'])

<button type="button"
        title="تمام صفحه ↔↕"
        @click="fullscreen = true"
        class="absolute {{ $class }} flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:scale-110 transition-all z-10 cursor-pointer">
    <span class="material-symbols-rounded text-[18px]">fullscreen</span>
</button>
