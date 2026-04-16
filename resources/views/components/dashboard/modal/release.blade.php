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
                            <p class="text-[11px] text-[var(--md-sys-color-on-primary-container)]/70 mt-0.5">نسخه استاندارد شده با پوشش کامل ماژول‌ها و جزئیات کاربردی</p>
                        </div>
                    </div>
                </div>

                <div class="text-right space-y-8 pr-4">

                    {{-- accordion items --}}
                    @php
                        $allModules = collect(config('modules', []));
                        function releaseItem(string $icon, string $color, string $title, string $desc): string {
                            $dynamicIconClass = 'text-[var(--md-sys-color-' . $color . ')]';

                            return '<li class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden bg-[var(--md-sys-color-surface)]/70">' .
                                   '<div class="w-full flex items-start gap-2 p-3 text-right">' .
                                       '<span class="material-symbols-rounded text-sm shrink-0 ' . $dynamicIconClass . '"' .
                                             ' style="font-variation-settings:\'FILL\' 1,\'wght\' 400,\'GRAD\' 0,\'opsz\' 24">check_circle</span>' .
                                       '<strong class="text-xs flex-1 text-[var(--md-sys-color-on-surface)] text-right">' . $title . '</strong>' .
                                   '</div>' .
                                   '<div class="px-3 pb-3 pt-1 text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)] border-t border-[var(--md-sys-color-outline-variant)]/30 pr-8">' .
                                       $desc .
                                   '</div>' .
                               '</li>';
                        }
                    @endphp

                    {{-- ─── β.5 ─── --}}
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
                                <span class="text-sm font-mono font-bold px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">β.5</span>
                                <span class="text-[10px] uppercase tracking-widest font-bold px-2 py-0.5 rounded-lg bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">جدیدترین</span>
                            </div>
                            <span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] font-mono tracking-wider" dir="ltr">{{ date('F j, Y') }}</span>
                        </div>

                        <div class="rounded-xl p-4 border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-variant)]/40 hover:bg-[var(--md-sys-color-surface-variant)]/70 transition-colors">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                                    <span class="material-symbols-rounded text-sm" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">hub</span>
                                </div>
                                <h5 class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)]">بازطراحی Release Notes</h5>
                            </div>
                            <ul class="space-y-2">
                                <li class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden bg-[var(--md-sys-color-surface)]/70">
                                    <div class="w-full flex items-start gap-2 p-3 text-right">
                                        <span class="material-symbols-rounded text-sm shrink-0 text-[var(--md-sys-color-primary)]"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <strong class="text-xs flex-1 text-[var(--md-sys-color-on-surface)] text-right">ساختار یادداشت‌ها</strong>
                                    </div>
                                    <div class="px-3 pb-3 pt-1 text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)] border-t border-[var(--md-sys-color-outline-variant)]/30 pr-8">
                                        قالب از حالت کلی و سطحی به ساختار حرفه‌ای ماژول‌محور ارتقا یافت؛ هر بخش بر اساس خروجی واقعی ماژول‌ها نوشته شده است.
                                    </div>
                                </li>
                                <li class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden bg-[var(--md-sys-color-surface)]/70">
                                    <div class="w-full flex items-start gap-2 p-3 text-right">
                                        <span class="material-symbols-rounded text-sm shrink-0 text-[var(--md-sys-color-primary)]"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <strong class="text-xs flex-1 text-[var(--md-sys-color-on-surface)] text-right">پوشش کامل ماژول‌ها</strong>
                                    </div>
                                    <div class="px-3 pb-3 pt-1 text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)] border-t border-[var(--md-sys-color-outline-variant)]/30 pr-8">
                                        تمام ماژول‌های سامانه شامل اعلان‌ها، فید، تقویم، گالری، گزارشات، لینک‌ها و ابزارها، FAQ، پروفایل، آنبوردینگ، مدارک، اطلاعات کاربری، فرصت‌های شغلی، پیشنهادات، تسک‌بورد، DMS، تیکت، رزرو، چت داخلی، انرژی، اختیارات، رادیو و سایر ابزارها در یادداشت‌ها پوشش داده شدند.
                                    </div>
                                </li>
                                <li class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden bg-[var(--md-sys-color-surface)]/70">
                                    <div class="w-full flex items-start gap-2 p-3 text-right">
                                        <span class="material-symbols-rounded text-sm shrink-0 text-[var(--md-sys-color-primary)]"
                                              style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                        <strong class="text-xs flex-1 text-[var(--md-sys-color-on-surface)] text-right">استانداردسازی متون سازمانی</strong>
                                    </div>
                                    <div class="px-3 pb-3 pt-1 text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)] border-t border-[var(--md-sys-color-outline-variant)]/30 pr-8">
                                        متن‌ها برای استفاده رسمی در محیط سازمانی بازنویسی شدند تا در ارائه به مدیران، منابع انسانی و کاربران نهایی قابل اتکا باشند.
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    {{-- ─── β.4 ─── --}}
                    <div class="relative border-r-2 border-[var(--md-sys-color-outline-variant)]/40 pr-6 pb-8 last:border-0 last:pb-0">
                        <div class="absolute -right-[9px] top-0 w-4 h-4 rounded-full z-10
                                    bg-[var(--md-sys-color-surface-variant)]
                                    border-2 border-[var(--md-sys-color-outline-variant)]
                                    flex items-center justify-center">
                            <div class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-outline)]"></div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-mono font-bold px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">β.4</span>
                                <span class="text-[10px] uppercase tracking-widest font-bold px-2 py-0.5 rounded-lg bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">هسته منابع انسانی</span>
                            </div>
                            <span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] font-mono tracking-wider" dir="ltr">Mar 30, 2026</span>
                        </div>

                        <div class="rounded-xl p-4 border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-variant)]/40 hover:bg-[var(--md-sys-color-surface-variant)]/70 transition-colors">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                                    <span class="material-symbols-rounded text-sm" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">badge</span>
                                </div>
                                <h5 class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)]">پروفایل، آنبوردینگ و مدارک</h5>
                            </div>
                            <ul class="space-y-2">
                                @foreach([
                                    ['وضعیت کاریر (پروفایل)', 'داشبورد وضعیت زنده کارکنان، جستجوی اطلاعات تماس، مشاهده پروفایل همکاران و تغییر وضعیت حضور.'],
                                    ['آنبوردینگ', 'تجمیع سیاست‌ها، ویدیوها، مستندات نقش شغلی و بسته‌های راهنمای شروع برای نیروهای جدید.'],
                                    ['مدیریت و بارگذاری مدارک', 'بارگذاری مدارک استاندارد/سفارشی با وضعیت «در انتظار تایید» و «تایید شده» و امکان دانلود فایل‌های نهایی.'],
                                    ['اطلاعات کاربری سازمانی', 'خزانه امن نام کاربری/رمز عبور سامانه‌ها با قابلیت جستجو، کپی سریع و لینک مستقیم ورود.'],
                                    ['فرصت‌های شغلی', 'انتشار، مشاهده و آرشیو موقعیت‌های استخدامی برای اطلاع‌رسانی داخلی و معرفی نیروهای مستعد.'],
                                    ['تقویم سازمانی', 'نمایش رویدادها، تولدها و سالگردها با قابلیت تبریک و برنامه‌ریزی منابع انسانی.'],
                                ] as [$title, $desc])
                                    <li class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden bg-[var(--md-sys-color-surface)]/70">
                                        <div class="w-full flex items-start gap-2 p-3 text-right">
                                            <span class="material-symbols-rounded text-sm shrink-0 text-[var(--md-sys-color-secondary)]"
                                                  style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                            <strong class="text-xs flex-1 text-[var(--md-sys-color-on-surface)] text-right">{{ $title }}</strong>
                                        </div>
                                        <div class="px-3 pb-3 pt-1 text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)] border-t border-[var(--md-sys-color-outline-variant)]/30 pr-8">
                                            {{ $desc }}
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    {{-- ─── β.3 ─── --}}
                    <div class="relative border-r-2 border-[var(--md-sys-color-outline-variant)]/40 pr-6 pb-8 last:border-0 last:pb-0">
                        <div class="absolute -right-[9px] top-0 w-4 h-4 rounded-full z-10
                                    bg-[var(--md-sys-color-surface-variant)]
                                    border-2 border-[var(--md-sys-color-outline-variant)]
                                    flex items-center justify-center">
                            <div class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-outline)]"></div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-mono font-bold px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">β.3</span>
                                <span class="text-[10px] uppercase tracking-widest font-bold px-2 py-0.5 rounded-lg bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">عملیات و گردش‌کار</span>
                            </div>
                            <span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] font-mono tracking-wider" dir="ltr">Mar 12, 2026</span>
                        </div>

                        <div class="rounded-xl p-4 border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-variant)]/40 hover:bg-[var(--md-sys-color-surface-variant)]/70 transition-colors">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                                    <span class="material-symbols-rounded text-sm" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">workspaces</span>
                                </div>
                                <h5 class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)]">مدیریت فرآیندها و همکاری بین واحدی</h5>
                            </div>
                            <ul class="space-y-2">
                                @foreach([
                                    ['پیشنهادات', 'گردش رسمی پیشنهاد از کاربر تا مدیر واحد و تصمیم نهایی مدیریت، همراه با اعلان مرحله‌ای.'],
                                    ['تسک بورد', 'مدیریت وظایف تیمی مبتنی بر کانبان برای ایجاد، ارجاع و رهگیری وضعیت انجام کار.'],
                                    ['مدیریت اسناد (DMS)', 'صدور، امضا، ردیابی بازدید و تمرکز اسناد رسمی فرآیندی در سطح سازمان.'],
                                    ['سیستم تیکت', 'ثبت و پیگیری درخواست‌ها با تعیین مسئول، وضعیت رسیدگی و تاریخچه پاسخ.'],
                                    ['رزرو فضا و منابع', 'زمان‌بندی استفاده از اتاق‌ها و منابع سازمانی با مدیریت تعارض زمانی.'],
                                    ['گزارشات + لینک‌ها و ابزارها', 'تولید گزارش PDF/سندی، اشتراک بین واحدی و دسترسی هوشمند به ابزارهای داخلی/خارجی بر اساس شبکه.'],
                                ] as [$title, $desc])
                                    <li class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden bg-[var(--md-sys-color-surface)]/70">
                                        <div class="w-full flex items-start gap-2 p-3 text-right">
                                            <span class="material-symbols-rounded text-sm shrink-0 text-[var(--md-sys-color-tertiary)]"
                                                  style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                            <strong class="text-xs flex-1 text-[var(--md-sys-color-on-surface)] text-right">{{ $title }}</strong>
                                        </div>
                                        <div class="px-3 pb-3 pt-1 text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)] border-t border-[var(--md-sys-color-outline-variant)]/30 pr-8">
                                            {{ $desc }}
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    {{-- ─── β.2 ─── --}}
                    <div class="relative border-r-2 border-[var(--md-sys-color-outline-variant)]/40 pr-6 pb-8 last:border-0 last:pb-0">
                        <div class="absolute -right-[9px] top-0 w-4 h-4 rounded-full z-10
                                    bg-[var(--md-sys-color-surface-variant)]
                                    border-2 border-[var(--md-sys-color-outline-variant)]
                                    flex items-center justify-center">
                            <div class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-outline)]"></div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-mono font-bold px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">β.2</span>
                                <span class="text-[10px] uppercase tracking-widest font-bold px-2 py-0.5 rounded-lg bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">ارتباطات و دانش</span>
                            </div>
                            <span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] font-mono tracking-wider" dir="ltr">Feb 20, 2026</span>
                        </div>

                        <div class="rounded-xl p-4 border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-variant)]/40 hover:bg-[var(--md-sys-color-surface-variant)]/70 transition-colors">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                                    <span class="material-symbols-rounded text-sm" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">forum</span>
                                </div>
                                <h5 class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)]">رسانه داخلی، اطلاع‌رسانی و خودخدمتی</h5>
                            </div>
                            <ul class="space-y-2">
                                @foreach([
                                    ['پست و اعلانات', 'اطلاع‌رسانی رسمی منابع انسانی، پست‌های پین‌شده و آرشیو پیام‌ها با اعلان ایمیلی.'],
                                    ['اخبار و فیدها', 'تجمیع اخبار صنعت/سازمان برای دسترسی یکپارچه به جریان اطلاعات به‌روز.'],
                                    ['گالری تصاویر', 'پوشش تصویری و ویدیویی رویدادها، دستاوردها و خاطرات سازمانی.'],
                                    ['پیام‌رسان داخلی', 'ارتباط سریع میان کارکنان برای هماهنگی روزانه و کاهش وابستگی به کانال‌های خارج از سامانه.'],
                                    ['پرسش‌های متداول (FAQ)', 'دانشنامه پاسخ‌های تکرارشونده جهت تسریع خودخدمتی کارکنان به‌ویژه در ماه‌های ابتدایی همکاری.'],
                                    ['رادیو زنده', 'پخش محتوای صوتی سازمانی برای اطلاع‌رسانی و تعامل درون‌سازمانی.'],
                                ] as [$title, $desc])
                                    <li class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden bg-[var(--md-sys-color-surface)]/70">
                                        <div class="w-full flex items-start gap-2 p-3 text-right">
                                            <span class="material-symbols-rounded text-sm shrink-0 text-[var(--md-sys-color-secondary)]"
                                                  style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                            <strong class="text-xs flex-1 text-[var(--md-sys-color-on-surface)] text-right">{{ $title }}</strong>
                                        </div>
                                        <div class="px-3 pb-3 pt-1 text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)] border-t border-[var(--md-sys-color-outline-variant)]/30 pr-8">
                                            {{ $desc }}
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    {{-- ─── β.1 ─── --}}
                    <div class="relative border-r-2 border-[var(--md-sys-color-outline-variant)]/40 pr-6 pb-8 last:border-0 last:pb-0">
                        <div class="absolute -right-[9px] top-0 w-4 h-4 rounded-full z-10
                                    bg-[var(--md-sys-color-surface-variant)]
                                    border-2 border-[var(--md-sys-color-outline-variant)]
                                    flex items-center justify-center">
                            <div class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-outline)]"></div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-mono font-bold px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">β.1</span>
                                <span class="text-[10px] uppercase tracking-widest font-bold px-2 py-0.5 rounded-lg bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]">پایه</span>
                            </div>
                            <span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] font-mono tracking-wider" dir="ltr">Feb 10, 2026</span>
                        </div>

                        <div class="rounded-xl p-4 border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-variant)]/40 hover:bg-[var(--md-sys-color-surface-variant)]/70 transition-colors">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]">
                                    <span class="material-symbols-rounded text-sm" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">architecture</span>
                                </div>
                                <h5 class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)]">زیرساخت، امنیت و تجربه کاربری</h5>
                            </div>
                            <ul class="space-y-2">
                                @foreach([
                                    ['احراز هویت و موتور تم', 'بازطراحی کامل با پشتیبانی Material You و حالت Dark/Light برای تجربه یکنواخت در تمام ماژول‌ها.'],
                                    ['زیرساخت عملیاتی', 'پشتیبانی از تقویم سازمانی، لینک‌ها و ابزارها، گزارشات و سایر قابلیت‌های پایه برای آماده‌سازی بستر منابع انسانی.'],
                                    ['اختیارات (Authority/Auth)', 'تعریف سطح دسترسی و سیاست‌های نقش‌محور برای ماژول‌ها و عملیات مدیریتی.'],
                                    ['پرسش‌نامه انرژی', 'سنجش وضعیت انرژی کارکنان برای پایش سلامت سازمانی و تصمیم‌سازی HR.'],
                                    ['سایر ابزارها', 'ماشین‌حساب، کرنومتر، تایمر، نمایش زمان، وضعیت آب‌وهوا و ابزارهای کمکی روزانه (ماژول Others).'],
                                ] as [$title, $desc])
                                    <li class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden bg-[var(--md-sys-color-surface)]/70">
                                        <div class="w-full flex items-start gap-2 p-3 text-right">
                                            <span class="material-symbols-rounded text-sm shrink-0 text-[var(--md-sys-color-error)]"
                                                  style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">check_circle</span>
                                            <strong class="text-xs flex-1 text-[var(--md-sys-color-on-surface)] text-right">{{ $title }}</strong>
                                        </div>
                                        <div class="px-3 pb-3 pt-1 text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)] border-t border-[var(--md-sys-color-outline-variant)]/30 pr-8">
                                            {{ $desc }}
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    {{-- Module Summaries --}}
                    <div x-data="{ open: false }"
                         class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-variant)]/30 overflow-hidden">
                        <button @click="open = !open"
                                class="w-full flex items-center justify-between flex-wrap gap-3 p-4 text-right hover:bg-[var(--md-sys-color-surface-variant)]/50 transition-colors">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                                    <span class="material-symbols-rounded text-sm"
                                          style="font-variation-settings:'FILL' 1,'wght' 500,'GRAD' 0,'opsz' 24">inventory_2</span>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">خلاصه کامل همه ماژول‌ها</h4>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] px-2 py-1 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                                    {{ $allModules->count() }} ماژول
                                </span>
                                <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-on-surface-variant)] transition-transform duration-200"
                                      :class="open ? 'rotate-180' : ''">expand_more</span>
                            </div>
                        </button>
                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 max-h-0"
                             x-transition:enter-end="opacity-100 max-h-[1000px]"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 max-h-[1000px]"
                             x-transition:leave-end="opacity-0 max-h-0"
                             class="px-4 pb-4 overflow-hidden">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($allModules as $module)
                                    <div class="rounded-xl p-3 border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)]/70">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]"
                                                  style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">{{ $module['icon'] ?? 'widgets' }}</span>
                                            <h5 class="text-xs font-semibold text-[var(--md-sys-color-on-surface)]">{{ trim($module['title'] ?? 'ماژول') }}</h5>
                                        </div>
                                        <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">
                                            {{ $module['summary'] ?? ($module['content'] ?? 'توضیحی ثبت نشده است.') }}
                                        </p>
                                    </div>
                                @endforeach
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
