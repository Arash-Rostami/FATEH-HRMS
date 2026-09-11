<a :href="item.disabled ? '#' : (item.href === '-' ? '#' : item.href)"
   :data-module="item.module"
   :target="item.disabled || item.href === '-' ? '_self' : '_blank'"
   rel="noopener"
   :aria-disabled="item.disabled ? 'true' : 'false'"
   :title="item.disabled ? 'این گزینه در حال حاضر توسط مدیریت غیرفعال شده است' : ''"
   @click="handleItemClick(item, $event)"
   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 focus-visible:outline-none"
   :class="[
       item.disabled
           ? 'bg-[var(--md-sys-color-surface-container-low)] opacity-40 grayscale-[40%] cursor-not-allowed'
           : 'bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] cursor-pointer',
       (typeof focusIndex !== 'undefined' && focusIndex === item.id)
           ? 'ring-2 ring-inset ring-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface-container)]'
           : ''
   ]">
    <div class="relative w-9 h-9 shrink-0 rounded-lg flex items-center justify-center bg-[color-mix(in_srgb,var(--md-sys-color-primary)_8%,var(--md-sys-color-surface))] border border-[color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)]"
         :class="pinEditMode ? 'animate-shiver' : ''">
        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]" x-text="item.icon"></span>
        <template x-if="@js($menuState)[item.id]">
            <x-ui.notification-badge />
        </template>
        <template x-if="item.disabled">
            <span class="absolute -bottom-1 -left-1 w-4 h-4 rounded-md bg-[var(--md-sys-color-surface-container-highest)] flex items-center justify-center shadow-sm">
                <span class="material-symbols-rounded text-[10px] text-[var(--md-sys-color-on-surface-variant)]">lock</span>
            </span>
        </template>
    </div>
    <div class="min-w-0 flex-1 text-right">
        <div class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)] truncate transition-colors duration-200"
             :class="item.disabled ? '' : 'group-hover:text-[var(--md-sys-color-primary)]'"
             x-html="highlight(item.title, search)"></div>
        <div class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] truncate opacity-75" x-html="highlight(item.sub, search)"></div>
    </div>
    <button x-show="pinEditMode || isPinned(item)"
            @click="togglePin(item, $event)"
            class="ms-1 shrink-0 w-7 h-7 rounded-full flex items-center justify-center transition-all duration-200 active:scale-95 focus-visible:outline-none"
            :class="isPinned(item)
                ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'
                : 'text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-surface-container)]'"
            :aria-pressed="isPinned(item)"
            :title="isPinned(item) ? 'برداشتن سنجاق از صفحه اول' : 'سنجاق در صفحه اول'"
            :aria-label="isPinned(item) ? 'برداشتن سنجاق از صفحه اول' : 'سنجاق در صفحه اول'">
        <span class="material-symbols-rounded text-[15px] inline-block transition-transform duration-200"
              :class="isPinned(item) ? 'font-fill rotate-45' : ''">push_pin</span>
    </button>
</a>