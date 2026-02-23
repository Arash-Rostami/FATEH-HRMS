<div x-data="{
    enabled: localStorage.getItem('google_translate_enabled') === 'true',
    toggle() {
        this.enabled = !this.enabled;
        localStorage.setItem('google_translate_enabled', this.enabled);
        if (this.enabled) {
            // Trigger load via service if implemented globally, or simple reload for now as per plan
             window.location.reload();
        } else {
             window.location.reload();
        }
    }
}">
    <button
        @click="toggle()"
        class="group relative flex items-center gap-3 pl-1 pr-3 py-1.5 rounded-full transition-all duration-300 border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)] active:scale-95"
        :class="{
            'bg-[var(--md-sys-color-surface-container-high)]': !enabled,
            'bg-[var(--md-sys-color-primary-container)]': enabled
        }"
        :title="enabled ? 'غیرفعال کردن ترجمه' : 'فعال کردن ترجمه'"
    >
        <!-- Icon Container -->
        <div
            class="flex items-center justify-center w-8 h-8 rounded-full shadow-sm transition-all duration-300"
            :class="{
                'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]': !enabled,
                'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]': enabled
            }"
        >
            <span class="material-symbols-rounded text-[18px]">translate</span>
        </div>

        <!-- Text/Status -->
        <div class="flex items-center gap-2">
            <span
                class="text-xs font-medium transition-colors"
                :class="{
                    'text-[var(--md-sys-color-on-surface)]': !enabled,
                    'text-[var(--md-sys-color-on-primary-container)]': enabled
                }"
                x-text="enabled ? 'ترجمه فعال' : 'ترجمه'"
            ></span>

            <!-- Status Dot -->
            <span
                class="w-2 h-2 rounded-full transition-colors duration-300"
                :class="{
                    'bg-[var(--md-sys-color-outline)]': !enabled,
                    'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)]': enabled
                }"
            ></span>
        </div>
    </button>

    <!-- Hidden Google Element Container -->
    <div id="google_translate_element" class="hidden"></div>
</div>
