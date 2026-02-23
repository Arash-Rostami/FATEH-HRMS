<div x-data="googleTranslate">
    <button
        @click="toggle()"
        class="group relative flex items-center gap-2 px-3 py-1.5 rounded-xl
                transition-all duration-200 active:scale-[0.97]
               focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40"
        :class="enabled
            ? 'bg-[var(--md-sys-color-primary-container)] border-[var(--md-sys-color-primary)]/30 text-[var(--md-sys-color-on-primary-container)]'
            : 'bg-[var(--md-sys-color-surface-variant)]/40 border-[var(--md-sys-color-outline-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:border-[var(--md-sys-color-primary)]/40 hover:bg-[var(--md-sys-color-primary)]/5'"
        :title="enabled ? 'غیرفعال کردن ترجمه' : 'فعال کردن ترجمه'"
    >
        {{-- Icon --}}
        <span class="material-symbols-rounded text-[16px] transition-colors duration-200"
              :class="enabled ? 'text-[var(--md-sys-color-primary)]' : 'opacity-60 group-hover:opacity-100'">
            translate
        </span>

        {{-- Label --}}
        <span class="text-[11px] font-semibold tracking-wide transition-colors duration-200"
              x-text="enabled ? 'ترجمه فعال' : 'ترجمه'"></span>

        {{-- Status dot --}}
        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 transition-all duration-300"
              :class="enabled
                ? 'bg-green-500 shadow-[0_0_6px_rgba(34,197,94,0.7)]'
                : 'bg-[var(--md-sys-color-outline)] opacity-50'"></span>
    </button>

    <div id="google_translate_element" class="hidden"></div>
</div>
