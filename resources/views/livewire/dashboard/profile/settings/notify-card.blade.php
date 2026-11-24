<div wire:key="profile-settings-notify-card" class="p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container)]/40 space-y-3">
    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">اعلان‌ها در این دستگاه</p>

    <template x-for="row in appRows().filter((row) => row.id === 'notifications')" :key="row.id">
        <div class="p-3 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 space-y-2">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]" x-text="row.icon"></span>
                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)] truncate" x-text="row.label"></p>
                </div>
                <span class="shrink-0 rounded-lg px-2 py-0.5 text-[10px] font-bold"
                      :class="{
                          'bg-[var(--md-sys-color-success-container)] text-[var(--md-sys-color-on-success-container)]': row.status === 'ok',
                          'bg-[var(--md-sys-color-warning-container)] text-[var(--md-sys-color-on-warning-container)]': row.status === 'todo',
                          'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]': row.status === 'blocked',
                          'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]': row.status === 'unsupported'
                      }"
                      x-text="{ ok: 'فعال', todo: 'نیاز به اقدام', blocked: 'مسدود', unsupported: 'پشتیبانی نمی‌شود' }[row.status]"></span>
            </div>
            <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]" x-text="row.hint"></p>
            <x-ui.buttons.form x-show="row.status === 'todo'" style="display:none" variant="tonal" x-on:click="enableNotifications()" x-bind:disabled="appBusy">روشن کردن اعلان‌ها</x-ui.buttons.form>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.buttons.form variant="tonal" icon="notifications_active" x-on:click="sendLocalTest()" x-bind:disabled="!row.granted">ارسال اعلان آزمایشی</x-ui.buttons.form>
                <p x-show="!row.granted" style="display:none" class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">برای فرستادن اعلان آزمایشی، ابتدا به مرورگر اجازهٔ نمایش اعلان بدهید.</p>
            </div>
        </div>
    </template>
</div>
