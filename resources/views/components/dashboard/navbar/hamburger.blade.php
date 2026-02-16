<button @click="toggleMenu"
        :aria-expanded="menuOpen.toString()"
        title="باز کردن منو (Menu)"
        class="w-10 h-10 rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 active:bg-[var(--md-sys-color-on-primary)]/20 active:scale-95 transition-all duration-200 flex items-center justify-center relative overflow-hidden"
        :class="menuOpen && 'bg-[var(--md-sys-color-on-primary)]/10'">
    <span class="material-symbols-rounded text-[24px]" x-text="menuOpen ? 'close' : 'menu'"></span>
</button>
