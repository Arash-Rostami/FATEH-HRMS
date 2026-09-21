<div class="space-y-5 animate-[fade-in_0.4s_ease-out]" dir="rtl"
     x-data="settings()"
     data-current-presence="{{ $presence->value }}"
     data-focus-until="{{ $focusUntil ? $focusUntil->getTimestamp() * 1000 : '' }}">

    <x-ui.buttons.tab-selector
        :tabs="[
            ['id' => 'appearance', 'icon' => 'palette', 'label' => 'ظاهر'],
            ['id' => 'notifications', 'icon' => 'notifications_active', 'label' => 'اعلان‌ها'],
            ['id' => 'tables', 'icon' => 'view_column', 'label' => 'جدول‌ها'],
            ['id' => 'focus', 'icon' => 'self_improvement', 'label' => 'تمرکز'],
        ]"
        :active-tab="$activeSection"
        class="!mb-0"
    />

    @if($activeSection === 'appearance')
    <div wire:key="profile-settings-section-appearance" class="space-y-5 mt-5">

        <div class="p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container)]/40">
            <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] mb-3">رنگ و حالت نمایش <span class="opacity-60">(این مرورگر)</span></p>
            <div class="overflow-x-auto">
                <x-dashboard.settings.theme-swatches :per-page="8" orientation="horizontal"/>
            </div>
        </div>

        <div class="p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container)]/40 space-y-1">
            <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] mb-1">فشردگی و خوانایی <span class="opacity-60">(این مرورگر)</span></p>
            <x-dashboard.settings.font-size-control/>
            <x-dashboard.settings.toggle-row icon="format_ink_highlighter" label="خط‌کش خواندن" color="violet" active="readingRuler" action="toggleReadingRuler()"/>
            <x-dashboard.settings.toggle-row icon="content_copy" label="کپی خودکار انتخاب" color="emerald" active="doubleClickCopy" action="toggleDoubleClickCopy()"/>
            <x-dashboard.settings.toggle-row icon="density_medium" label="نمایش فشرده جدول‌ها" color="teal" active="$store.density.compact" action="$store.density.toggle()"/>
        </div>

        <div class="p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container)]/40">
            <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] mb-1">پس‌زمینه <span class="opacity-60">(این مرورگر)</span></p>
            <x-dashboard.settings.background-picker/>
        </div>

        <x-ui.buttons.form @click="resetAppearance()" variant="tonal" icon="restart_alt">
            بازنشانی ظاهر
        </x-ui.buttons.form>
    </div>
    @endif

    @if($activeSection === 'notifications')
    <div wire:key="profile-settings-section-notifications" class="space-y-5 mt-5"
         x-data="{
             names: { channel: {}, contact: {}, project: {} },
             async refresh(scope, ids) {
                 this.names[scope] = ids.length ? await this.$wire.resolveMuted(scope, ids) : {};
             }
         }"
         x-init="
             refresh('channel', $store.sound.mutedChannels);
             refresh('contact', $store.sound.mutedContacts);
             refresh('project', $store.sound.mutedProjects);
             $watch('$store.sound.mutedChannels', (ids) => refresh('channel', ids));
             $watch('$store.sound.mutedContacts', (ids) => refresh('contact', ids));
             $watch('$store.sound.mutedProjects', (ids) => refresh('project', ids));
         ">

        <div class="p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container)]/40 space-y-3">
            <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">ردپای شما در این مرورگر</p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="p-3 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 space-y-2">
                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">چنل‌ها</p>
                    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
                        <span x-text="$store.push.isEnabled('channel') ? 'اعلان فعال' : 'اعلان غیرفعال'"></span>
                    </p>
                    <div class="space-y-1 max-h-32 overflow-y-auto">
                        <template x-for="[id, name] in Object.entries(names.channel)" :key="id">
                            <div class="flex items-center justify-between gap-2 text-[11px] text-[var(--md-sys-color-on-surface)]">
                                <span class="truncate" x-text="name"></span>
                                <button type="button" @click="$store.sound.toggleMute(id, 'channel')"
                                        aria-label="باصدا کردن" title="باصدا کردن"
                                        class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                                    <span class="material-symbols-rounded text-[14px]">volume_off</span>
                                </button>
                            </div>
                        </template>
                        <p x-show="Object.keys(names.channel).length === 0" class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">هیچ موردی بی‌صدا نشده</p>
                    </div>
                </div>
                <div class="p-3 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 space-y-2">
                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">پیام‌های خصوصی</p>
                    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
                        <span x-text="$store.push.isEnabled('contact') ? 'اعلان فعال' : 'اعلان غیرفعال'"></span>
                    </p>
                    <div class="space-y-1 max-h-32 overflow-y-auto">
                        <template x-for="[id, name] in Object.entries(names.contact)" :key="id">
                            <div class="flex items-center justify-between gap-2 text-[11px] text-[var(--md-sys-color-on-surface)]">
                                <span class="truncate" x-text="name"></span>
                                <button type="button" @click="$store.sound.toggleMute(id, 'contact')"
                                        aria-label="باصدا کردن" title="باصدا کردن"
                                        class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                                    <span class="material-symbols-rounded text-[14px]">volume_off</span>
                                </button>
                            </div>
                        </template>
                        <p x-show="Object.keys(names.contact).length === 0" class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">هیچ موردی بی‌صدا نشده</p>
                    </div>
                </div>
                <div class="p-3 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 space-y-2">
                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">پروژه‌ها</p>
                    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
                        <span x-text="$store.push.isEnabled('project') ? 'اعلان فعال' : 'اعلان غیرفعال'"></span>
                    </p>
                    <div class="space-y-1 max-h-32 overflow-y-auto">
                        <template x-for="[id, name] in Object.entries(names.project)" :key="id">
                            <div class="flex items-center justify-between gap-2 text-[11px] text-[var(--md-sys-color-on-surface)]">
                                <span class="truncate" x-text="name"></span>
                                <button type="button" @click="$store.sound.toggleMute(id, 'project')"
                                        aria-label="باصدا کردن" title="باصدا کردن"
                                        class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                                    <span class="material-symbols-rounded text-[14px]">volume_off</span>
                                </button>
                            </div>
                        </template>
                        <p x-show="Object.keys(names.project).length === 0" class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">هیچ موردی بی‌صدا نشده</p>
                    </div>
                </div>
            </div>
        </div>

        <x-ui.buttons.form @click="resetNotifications()" variant="tonal" icon="notifications_off">
            لغو سکوت همه
        </x-ui.buttons.form>
    </div>
    @endif

    @if($activeSection === 'tables')
    <div wire:key="profile-settings-section-tables" class="space-y-5 mt-5">

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="p-3 rounded-xl bg-[var(--md-sys-color-surface-container)]/40 border border-[var(--md-sys-color-outline-variant)]/30 flex items-center justify-between gap-2">
                <div>
                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">مدیریت اسناد</p>
                    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]"><span x-text="$store.colVisibility.maps.dms.length"></span> ستون مخفی</p>
                </div>
                <button type="button" @click="$store.colVisibility.reset('dms')"
                        class="text-[11px] px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/20 transition-colors whitespace-nowrap">
                    بازنشانی ستون‌ها
                </button>
            </div>
            <div class="p-3 rounded-xl bg-[var(--md-sys-color-surface-container)]/40 border border-[var(--md-sys-color-outline-variant)]/30 flex items-center justify-between gap-2">
                <div>
                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">تیکت‌های پشتیبانی</p>
                    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]"><span x-text="$store.colVisibility.maps.ths.length"></span> ستون مخفی</p>
                </div>
                <button type="button" @click="$store.colVisibility.reset('ths')"
                        class="text-[11px] px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/20 transition-colors whitespace-nowrap">
                    بازنشانی ستون‌ها
                </button>
            </div>
            <div class="p-3 rounded-xl bg-[var(--md-sys-color-surface-container)]/40 border border-[var(--md-sys-color-outline-variant)]/30 flex items-center justify-between gap-2">
                <div>
                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">رزرو امکانات</p>
                    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]"><span x-text="$store.colVisibility.maps.reservation.length"></span> ستون مخفی</p>
                </div>
                <button type="button" @click="$store.colVisibility.reset('reservation')"
                        class="text-[11px] px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/20 transition-colors whitespace-nowrap">
                    بازنشانی ستون‌ها
                </button>
            </div>
        </div>

        <x-ui.buttons.form @click="resetTables()" variant="tonal" icon="restart_alt">
            بازنشانی همه ستون‌ها
        </x-ui.buttons.form>
    </div>
    @endif

    @if($activeSection === 'focus')
    <div wire:key="profile-settings-section-focus" class="space-y-5 mt-5">

        <div class="p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container)]/40 space-y-3">
            <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">حالت تمرکز <span class="opacity-60">(همگام و برای همکاران قابل مشاهده)</span></p>

            <div x-show="!$store.focus.active" class="space-y-3">
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="setFocusDuration(25)"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors"
                            :class="focusDuration === 25 ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-variant)]/60 text-[var(--md-sys-color-on-surface-variant)]'">
                        ۲۵ دقیقه
                    </button>
                    <button type="button" @click="setFocusDuration(50)"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors"
                            :class="focusDuration === 50 ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-variant)]/60 text-[var(--md-sys-color-on-surface-variant)]'">
                        ۵۰ دقیقه
                    </button>
                    <button type="button" @click="setFocusDuration(90)"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors"
                            :class="focusDuration === 90 ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-variant)]/60 text-[var(--md-sys-color-on-surface-variant)]'">
                        ۹۰ دقیقه
                    </button>
                    <button type="button" @click="setFocusDuration(null)"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors"
                            :class="focusDuration === null ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-variant)]/60 text-[var(--md-sys-color-on-surface-variant)]'">
                        تا خاموش کردن دستی
                    </button>
                </div>
                <x-ui.buttons.form @click="toggleFocus()" variant="primary" icon="self_improvement">
                    شروع تمرکز
                </x-ui.buttons.form>
            </div>

            <div x-show="$store.focus.active" style="display:none" class="space-y-3">
                <div class="flex items-center gap-2 text-sm font-bold text-[var(--md-sys-color-primary)]">
                    <span class="material-symbols-rounded text-[20px]">timer</span>
                    <span x-text="$store.focus.until ? 'پایان تا ' + new Date($store.focus.until).toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' }) : 'تا خاموش کردن دستی'"></span>
                </div>
                <x-ui.buttons.form @click="toggleFocus()" variant="tonal" icon="stop_circle">
                    پایان تمرکز
                </x-ui.buttons.form>
            </div>
        </div>

        <div class="p-4 rounded-2xl border-2 border-rose-500/30 bg-rose-500/5 space-y-2">
            <p class="text-xs font-bold text-rose-500">بازنشانی کامل برنامه</p>
            <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">تمام تنظیمات محلی این مرورگر (ظاهر، اعلان‌ها، ستون‌ها) حذف و به حالت پیش‌فرض بازمی‌گردد.</p>
            <button type="button" @click="resetApp()"
                    class="inline-flex items-center gap-2 px-4 h-10 rounded-xl text-sm font-bold bg-rose-500 text-white hover:brightness-110 transition-all active:scale-95">
                <span class="material-symbols-rounded text-[20px]">warning</span>
                بازنشانی کامل برنامه
            </button>
        </div>
    </div>
    @endif

</div>
