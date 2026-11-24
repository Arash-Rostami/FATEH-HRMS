<div wire:key="profile-settings-layout-card" x-init="refreshLayoutState()" class="p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container)]/40 space-y-3">
    <div class="space-y-1">
        <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">منو و چیدمان <span class="opacity-60">(این مرورگر)</span></p>
        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">برای دیدن تغییرهای منو، نوار پایین و ساعت، صفحه را تازه کنید.</p>
    </div>

    <template x-for="choice in layoutChoices" :key="choice.field">
        <div class="p-3 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 space-y-2">
            <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]" x-text="choice.title"></p>
            <div class="flex flex-wrap gap-2">
                <template x-for="option in choice.options" :key="option.value">
                    <button type="button" @click="$data[choice.setter](option.value)"
                            :aria-pressed="layout[choice.field] === option.value"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors"
                            :class="layout[choice.field] === option.value ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-variant)]/60 text-[var(--md-sys-color-on-surface-variant)]'"
                            x-text="option.label"></button>
                </template>
            </div>
        </div>
    </template>

    <div class="p-3 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 space-y-1">
        <x-dashboard.settings.toggle-row icon="sort_by_alpha" label="مرتب‌سازی الفبایی منو" color="indigo" active="layout.sortAlpha" action="toggleMenuSortAlpha()"/>
        <x-dashboard.settings.toggle-row icon="unfold_less" label="نمایش خلوت‌تر صفحه" color="teal" active="layout.declutter" action="setDeclutter(!layout.declutter)"/>
    </div>

    <x-ui.buttons.form @click="resetLayout()" variant="tonal" icon="restart_alt">
        بازنشانی منو و چیدمان
    </x-ui.buttons.form>
</div>
