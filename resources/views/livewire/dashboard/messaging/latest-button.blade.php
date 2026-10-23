<button type="button"
        @if(!empty($show)) x-show="{{ $show }}" x-cloak x-transition.opacity.scale.origin.bottom.duration.200ms @endif
        x-on:click="{{ $before ?? '' }}backToLatest()"
        class="{{ $position ?? '' }} w-8 h-8 rounded-lg flex items-center justify-center shrink-0 transition-all hover:-translate-y-0.5 active:scale-90 bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] text-[var(--md-sys-color-primary)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_45%,transparent)] shadow-[0_2px_10px_color-mix(in_srgb,var(--md-sys-color-shadow)_14%,transparent)] hover:bg-[var(--md-sys-color-primary-container)]"
        aria-label="اسکرول به پایین" title="رفتن به آخرین پیام">
    <span class="material-symbols-rounded text-[16px]" aria-hidden="true">keyboard_arrow_down</span>
    <span x-show="newBelow" x-cloak x-text="newBelow" class="absolute -top-2 -right-2 min-w-4 h-4 px-1 rounded-md flex items-center justify-center text-[10px] font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]"></span>
</button>
