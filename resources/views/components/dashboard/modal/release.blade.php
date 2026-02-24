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

            <div class="custom-modal-content text-right overflow-y-auto custom-scrollbar container-scrollbar max-h-[85vh]">

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
                            <div class="rounded-xl p-4 border border-[var(--md-sys-color-outline-variant)]/50
                                        bg-[var(--md-sys-color-surface-variant)]/40
                                        hover:bg-[var(--md-sys-color-surface-variant)]/70 transition-colors group">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0
                                                bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                                        <span class="material-symbols-rounded text-sm"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">rocket_launch</span>
                                    </div>
                                    <h5 class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)]">بهینه‌سازی نهایی و ابزارها</h5>
                                </div>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-primary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span>ویژگیهای اضافی شامل پیش بینی آب و هوا، نمایش زمان و میانبرهای آسان به سایر بخش‌های برنامه است.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-primary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span>شمارش معکوس تولد و سالگرد کاری به همراه سایر ابزارهای کوچک از جمله ماشین حساب و تایمر و ابزارهای هوشمند.</span>
                                    </li>
                                     <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-primary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span>پیاده‌سازی <strong>View Transitions API</strong> برای جابجایی سینمایی و بهینه‌سازی کامل <strong>Service Worker</strong>.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Beta 3 (Productivity) -->
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
                                             bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">Beta 3</span>
                                <span class="text-[10px] uppercase tracking-widest font-bold px-2 py-0.5 rounded-lg
                                             bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">بهره‌وری</span>
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
                                    <h5 class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)]">ابزارها و گزارشات</h5>
                                </div>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-secondary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span><strong>وضعیت کاربر (پروفایل):</strong> پنل وضعیت زنده که حضور و غیاب را نشان می‌دهد و امکان ارسال پیام فوری و جستجوی اطلاعات تماس را فراهم می‌کند.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-secondary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span><strong>تقویم:</strong> رویدادهای شرکت، تولد کارکنان و سالگردهای کاری را نمایش میدهد. شمارش معکوس برای تولدها انجام میشود و جلوه‌های تبریک فعال می‌گردد.</span>
                                    </li>
                                     <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-secondary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span><strong>گزارشات:</strong> امکان صدور گزارش به صورت سند یا PDF توسط بخش‌ها و نمایش همه اسناد در پنل کاربر.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-secondary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span><strong>لینک‌ها و ابزارها:</strong> دسترسی متمرکز به تمام لینک‌های ابزارهای مرتبط با کار با قابلیت تشخیص شبکه داخلی و خارجی.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-secondary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span><strong>FAQ:</strong> گردآوری سؤالات متداول برای دسترسی آسان‌تر کارکنان، به ویژه برای استخدام‌شدگان جدید.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Beta 2 (Social) -->
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
                                             bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">Beta 2</span>
                                <span class="text-[10px] uppercase tracking-widest font-bold px-2 py-0.5 rounded-lg
                                             bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">اجتماعی</span>
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
                                    <h5 class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)]">ارتباطات و رسانه</h5>
                                </div>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-tertiary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span><strong>پست و اعلانات:</strong> ارسال اطلاعیه‌های رسمی توسط منابع انسانی، ذخیره در پنل کاربر و اطلاع‌رسانی ایمیلی. منبع پیام رسانی مرکزی شرکت.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-tertiary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span><strong>اخبار و فیدها:</strong> جمع‌آوری آخرین اخبار صنعت و موضوعات مورد علاقه کاربران از منابع مختلف در یک فید یکپارچه.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-tertiary)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span><strong>گالری تصاویر:</strong> نمایش تصاویر و ویدیوهای مرتبط با رویدادها، فعالیت‌ها و دستاوردهای شرکت برای اشتراک‌گذاری خاطرات سازمانی.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Beta 1 (Foundation) -->
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
                                             bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">Beta 1</span>
                                <span class="text-[10px] uppercase tracking-widest font-bold px-2 py-0.5 rounded-lg
                                             bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]">پایه</span>
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
                                    <h5 class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)]">زیرساخت و تنظیمات</h5>
                                </div>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-error)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span>بازطراحی کامل <strong>سیستم احراز هویت</strong> با جلوه‌های بصری سه بعدی و موتور تم جدید (Material You).</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-[var(--md-sys-color-error)] shrink-0"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <span>این اپ حالتهای تاریک و روشن را ارائه میدهد و شامل قابلیت ترجمه کامل از طریق ادغام با Google Translate است.</span>
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
