@props(['title' => 'منوی اصلی'])

<button x-data="{ audioPlaying: false }"
        @audio-play-state.window="audioPlaying = $event.detail"
        @click="toggleMenu"
        :aria-expanded="menuOpen.toString()"
        :aria-label="menuOpen ? 'بستن منو' : '{{ $title }}'"
        class="group relative w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-on-primary)]/10 active:bg-[var(--md-sys-color-on-primary)]/20 active:scale-95 transition-all duration-200 flex items-center justify-center"
        :class="menuOpen && 'bg-[var(--md-sys-color-on-primary)]/10'">
    <span x-show="menuOpen" x-cloak class="material-symbols-rounded text-[24px]">close</span>
    <span x-show="!menuOpen && !audioPlaying" class="material-symbols-rounded text-[24px]">menu</span>
    <div x-show="!menuOpen && audioPlaying" x-cloak class="inline-flex h-4 items-end gap-0.5 drop-shadow-[0_0_6px_color-mix(in_srgb,var(--md-sys-color-on-primary)_70%,transparent)]">
        <template x-for="d in [0,120,240,360,480]" :key="d">
            <span :style="`animation-delay:${d}ms`"
                  class="w-0.5 h-3 bg-current rounded origin-bottom animate-[eq_1s_ease-in-out_infinite]"></span>
        </template>
    </div>
    <x-ui.modals.tooltip :text="$title" position="bottom" />
</button>