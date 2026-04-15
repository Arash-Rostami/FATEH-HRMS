@props(['title' => 'منوی اصلی'])

<button @click="toggleMenu"
        :aria-expanded="menuOpen.toString()"
        class="group relative w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-on-primary)]/10 active:bg-[var(--md-sys-color-on-primary)]/20 active:scale-95 transition-all duration-200 flex items-center justify-center"
        :class="menuOpen && 'bg-[var(--md-sys-color-on-primary)]/10'"
    {{ $attributes->merge(['class' => '']) }}>
    <span class="material-symbols-rounded text-[24px]" x-text="menuOpen ? 'close' : 'menu'"></span>
    <x-ui.modals.tooltip :text="$title" position="bottom" />
</button>
