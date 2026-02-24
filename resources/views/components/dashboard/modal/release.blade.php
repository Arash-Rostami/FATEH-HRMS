<div x-data="{ open: false, active: false }"
     x-init="$watch('open', value => { if(value) { requestAnimationFrame(() => requestAnimationFrame(() => active = true)) } else { active = false } })"
     class="relative">

    <button @click="open = true"
            class="w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)]/50 active:bg-[var(--md-sys-color-surface-container-high)] active:scale-95 transition-all duration-200 flex items-center justify-center relative group"
            title="یادداشت‌های انتشار">
        <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100 transition-opacity">new_releases</span>
        <span class="absolute top-2 right-2 w-2 h-2 bg-yellow-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.6)]"></span>
    </button>

    <template x-teleport="body">
        <div class="custom-modal"
             :class="{ 'active': active }"
             style="display: none;"
             x-show="open"
             x-transition:enter="transition duration-0"
             x-transition:leave="transition duration-1000 delay-1000"
             dir="rtl">

            <div class="modal-close-icon" @click="open = false"></div>

            <div class="custom-modal-content text-right">

                {{-- Hero Header --}}
                <div class="relative mt-10 rounded-2xl mb-6 overflow-hidden bg-[var(--md-sys-color-primary-container)] p-5">
                    <div class="absolute inset-0 opacity-[0.07] pointer-events-none"
                         style="background-image:radial-gradient(circle,var(--md-sys-color-on-primary-container) 1px,transparent 1px);background-size:28px 28px"></div>
                    <div class="absolute -left-12 -top-12 w-40 h-40 rounded-full blur-3xl opacity-20 pointer-events-none bg-[var(--md-sys-color-primary)]"></div>
                    <span class="material-symbols-rounded absolute -left-2 -bottom-4 opacity-[0.07] pointer-events-none select-none text-[var(--md-sys-color-on-primary-container)]"
                          style="font-size:120px">new_releases</span>
                    <div class="relative flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 bg-[var(--md-sys-color-primary)]">
                            <span class="material-symbols-rounded text-xl text-[var(--md-sys-color-on-primary)]"
                                  style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">new_releases</span>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-[var(--md-sys-color-on-primary-container)]">یادداشت‌های انتشار</h3>
                            <p class="text-[11px] text-[var(--md-sys-color-on-primary-container)]/70 mt-0.5">آخرین تغییرات و بهبودها</p>
                        </div>
                    </div>
                </div>

                <div class="text-right space-y-8 pr-4">

                    <!-- Version 3.15 (Latest) -->
                    <div class="relative border-r-2 border-[var(--md-sys-color-outline-variant)]/40 pr-6 pb-8 last:border-0 last:pb-0">
                        <div class="absolute -right-[9px] top-0 w-4 h-4 rounded-full z-10
                                    bg-[var(--md-sys-color-primary-container)]
                                    border-2 border-[var(--md-sys-color-primary)]
                                    flex items-center justify-center
                                    shadow-[0_0_8px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]">
                            <div class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-primary)]"></div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-mono font-bold px-2.5 py-1 rounded-lg
                                             bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">v3.15</span>
                                <span class="text-[10px] uppercase tracking-widest font-bold px-2 py-0.5 rounded-lg
                                             bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">جدیدترین</span>
                            </div>
                            <span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] font-mono tracking-wider" dir="ltr">{{ date('F j, Y') }}</span>
                        </div>

                        <div class="space-y-4 text-sm text-[var(--md-sys-color-on-surface)] leading-relaxed">
                            <!-- User Panel -->
                            <div class="rounded-xl p-4 border border-[var(--md-sys-color-outline-variant)]/50
                                        bg-[var(--md-sys-color-surface-variant)]/40
                                        hover:bg-[var(--md-sys-color-surface-variant)]/70 transition-colors group">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0
                                                bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                                        <span class="material-symbols-rounded text-sm"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">rocket_launch</span>
                                    </div>
                                    <h5 class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)]">بهبود عملکرد</h5>
                                </div>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-primary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span>پیاده‌سازی <strong>View Transitions API</strong> برای جابجایی سینمایی بین صفحات.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-primary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span>بهینه‌سازی کامل <strong>Service Worker</strong> و استراتژی کش PWA.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Version 3.14 (Beta 3) -->
                    <div class="relative border-r-2 border-[var(--md-sys-color-outline-variant)]/40 pr-6 pb-8 last:border-0 last:pb-0">
                        <div class="absolute -right-[9px] top-0 w-4 h-4 rounded-full z-10
                                    bg-[var(--md-sys-color-surface-variant)]
                                    border-2 border-[var(--md-sys-color-outline-variant)]
                                    flex items-center justify-center">
                            <div class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-outline)]"></div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-mono font-bold px-2.5 py-1 rounded-lg
                                             bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">v3.14</span>
                                <span class="text-[10px] uppercase tracking-widest font-bold px-2 py-0.5 rounded-lg
                                             bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">Beta 3</span>
                            </div>
                            <span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] font-mono tracking-wider" dir="ltr">October 15, 2025</span>
                        </div>

                        <div class="space-y-4 text-sm text-[var(--md-sys-color-on-surface)] leading-relaxed">
                            <div class="rounded-xl p-4 border border-[var(--md-sys-color-outline-variant)]/50
                                        bg-[var(--md-sys-color-surface-variant)]/40
                                        hover:bg-[var(--md-sys-color-surface-variant)]/70 transition-colors group">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0
                                                bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                                        <span class="material-symbols-rounded text-sm"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">widgets</span>
                                    </div>
                                    <h5 class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)]">ابزارهای کاربردی</h5>
                                </div>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-secondary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span>افزوده شدن <strong>Command Palette</strong> (جستجوی پیشرفته) با کلید میانبر Ctrl+K.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-secondary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span>ماژول <strong>تقویم رویدادها</strong> با قابلیت ثبت و مدیریت جلسات.</span>
                                    </li>
                                     <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-secondary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span>پنل <strong>تنظیمات سریع</strong> در هدر برای دسترسی آسان‌تر.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Version 3.13 (Beta 2) -->
                    <div class="relative border-r-2 border-[var(--md-sys-color-outline-variant)]/40 pr-6 pb-8 last:border-0 last:pb-0">
                        <div class="absolute -right-[9px] top-0 w-4 h-4 rounded-full z-10
                                    bg-[var(--md-sys-color-surface-variant)]
                                    border-2 border-[var(--md-sys-color-outline-variant)]
                                    flex items-center justify-center">
                            <div class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-outline)]"></div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-mono font-bold px-2.5 py-1 rounded-lg
                                             bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">v3.13</span>
                                <span class="text-[10px] uppercase tracking-widest font-bold px-2 py-0.5 rounded-lg
                                             bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">Beta 2</span>
                            </div>
                            <span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] font-mono tracking-wider" dir="ltr">October 01, 2025</span>
                        </div>

                        <div class="space-y-4 text-sm text-[var(--md-sys-color-on-surface)] leading-relaxed">
                            <div class="rounded-xl p-4 border border-[var(--md-sys-color-outline-variant)]/50
                                        bg-[var(--md-sys-color-surface-variant)]/40
                                        hover:bg-[var(--md-sys-color-surface-variant)]/70 transition-colors group">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0
                                                bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                                        <span class="material-symbols-rounded text-sm"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">groups</span>
                                    </div>
                                    <h5 class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)]">تعاملات اجتماعی</h5>
                                </div>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-tertiary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span>راه اندازی <strong>فید سازمانی (Feed)</strong> با قابلیت ثبت نظر و واکنش.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-tertiary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span>بخش <strong>گالری تصاویر</strong> و مدیریت اسناد در پروفایل کاربری.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Version 3.12 (Beta 1) -->
                    <div class="relative border-r-2 border-[var(--md-sys-color-outline-variant)]/40 pr-6 pb-8 last:border-0 last:pb-0">
                        <div class="absolute -right-[9px] top-0 w-4 h-4 rounded-full z-10
                                    bg-[var(--md-sys-color-surface-variant)]
                                    border-2 border-[var(--md-sys-color-outline-variant)]
                                    flex items-center justify-center">
                            <div class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-outline)]"></div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-mono font-bold px-2.5 py-1 rounded-lg
                                             bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">v3.12</span>
                                <span class="text-[10px] uppercase tracking-widest font-bold px-2 py-0.5 rounded-lg
                                             bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]">Beta 1</span>
                            </div>
                            <span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] font-mono tracking-wider" dir="ltr">September 15, 2025</span>
                        </div>

                        <div class="space-y-4 text-sm text-[var(--md-sys-color-on-surface)] leading-relaxed">
                            <div class="rounded-xl p-4 border border-[var(--md-sys-color-outline-variant)]/50
                                        bg-[var(--md-sys-color-surface-variant)]/40
                                        hover:bg-[var(--md-sys-color-surface-variant)]/70 transition-colors group">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0
                                                bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]">
                                        <span class="material-symbols-rounded text-sm"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">architecture</span>
                                    </div>
                                    <h5 class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)]">زیرساخت جدید</h5>
                                </div>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-error)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span>بازطراحی کامل <strong>سیستم احراز هویت</strong> با جلوه‌های بصری سه بعدی.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-error)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span>موتور تم جدید با پشتیبانی از پالت‌های رنگی داینامیک Material You.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="mt-8 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/50 text-center text-[11px] text-[var(--md-sys-color-on-surface-variant)]/60">
                    تمام حقوق محفوظ است &copy; {{ date('Y') }}
                </div>

            </div>
        </div>
    </template>
</div>
