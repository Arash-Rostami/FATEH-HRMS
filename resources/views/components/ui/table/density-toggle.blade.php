<button type="button"
        @click="$store.density.toggle()"
        :title="$store.density.compact ? 'نمایش عادی' : 'نمایش فشرده'"
        :aria-label="$store.density.compact ? 'نمایش عادی' : 'نمایش فشرده'"
        :class="{ 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]': $store.density.compact, 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]': !$store.density.compact }"
        class="inline-flex items-center justify-center p-1 rounded-lg transition-colors normal-case">
    <span class="material-symbols-rounded text-[18px]" x-text="$store.density.compact ? 'view_comfy' : 'view_compact'"></span>
</button>
