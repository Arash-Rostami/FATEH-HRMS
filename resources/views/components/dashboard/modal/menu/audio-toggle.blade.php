@props(['title' => 'پخش‌کننده موسیقی'])

<button type="button"
        @click="playerOn = !playerOn; localStorage.setItem('audio_player_visible', playerOn ? '1' : '0'); if (!playerOn) window.dispatchEvent(new CustomEvent('audio-pause-request'))"
        :title="playerOn ? 'پنهان‌کردن پخش‌کننده' : '{{ $title }}'"
        class="flex h-8 shrink-0 items-center justify-center rounded-lg border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_40%,transparent)] px-3 text-[var(--md-sys-color-on-surface-variant)] transition-all duration-300 hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] active:scale-95"
        :class="playerOn ? 'shadow-md' : ''"
        :style="playerOn ? 'background-color: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container); border-color: transparent;' : ''">
    <span class="material-symbols-rounded text-[16px] leading-none">headphones</span>
</button>
