<div wire:key="profile-settings-app-card" class="p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container)]/40 space-y-3">
    <div class="space-y-1">
        <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">نصب برنامه و دسترسی‌ها</p>
        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">برنامه را نصب کنید تا از نماد آن روی دستگاه بازش کنید. هر دسترسی را می‌توانید جداگانه فعال کنید.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <template x-for="row in appRows().filter((row) => row.id !== 'notifications')" :key="row.id">
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
                <x-ui.buttons.form x-show="row.action && row.status === 'todo'" style="display:none" variant="tonal" x-on:click="row.action()" x-bind:disabled="appBusy">فعال‌سازی</x-ui.buttons.form>
            </div>
        </template>
    </div>

    <div x-show="$store.app.ios && !$store.app.installed" style="display:none" class="p-3 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 space-y-1">
        <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">نصب در آیفون</p>
        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">در مرورگر، «اشتراک‌گذاری» و سپس «افزودن به صفحهٔ اصلی» را بزنید. برنامه را از نماد تازه باز کنید.</p>
    </div>

    <div x-show="$store.app.windows && !$store.app.installed" style="display:none" class="p-3 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 space-y-2">
        <div class="flex items-center gap-2 min-w-0">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">desktop_windows</span>
            <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)] truncate">میان‌بر ویندوز</p>
        </div>
        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">اگر گزینهٔ نصب در مرورگر نیست، با این میان‌بر سامانه را در پنجره‌ای جدا باز کنید.</p>
        <div class="flex flex-wrap items-center gap-2">
            <input x-ref="shortcut" type="text" readonly dir="ltr" aria-label="دستور میان‌بر ویندوز" data-site-url="{{ rtrim(config('app.url'), '/') }}" :value="windowsShortcutCommand($el.dataset.siteUrl)" x-on:focus="$el.select()" class="min-w-0 flex-1 h-10 px-3 rounded-xl text-sm bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50">
            <x-ui.buttons.form variant="tonal" x-on:click="copyText($refs.shortcut.value)">کپی</x-ui.buttons.form>
        </div>
        <ol class="list-decimal ps-5 space-y-1 text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
            <li>روی دسکتاپ کلیک راست کنید و از «جدید» (New)، «میان‌بر» (Shortcut) را انتخاب کنید.</li>
            <li>دستور کپی‌شده را در کادر نشانی بچسبانید. برای میان‌بر نامی بگذارید و آن را بسازید.</li>
            <li>برای باز کردن برنامه، روی میان‌بر دو بار کلیک کنید.</li>
        </ol>
        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">برای Microsoft Edge مسیر برنامه را با <span dir="ltr">"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"</span> جایگزین کنید.</p>
    </div>

    <div class="flex flex-wrap gap-2">
        <x-ui.buttons.form variant="primary" icon="task_alt" x-on:click="enableEverything()" x-bind:disabled="appBusy">فعال‌سازی همه</x-ui.buttons.form>
    </div>
    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">اعلان‌ها را از بخش «اعلان‌ها» هم می‌توانید جداگانه تنظیم و آزمایش کنید.</p>
</div>
