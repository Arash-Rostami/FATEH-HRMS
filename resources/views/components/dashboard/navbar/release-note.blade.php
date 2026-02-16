<div x-data="{ open: false }" class="relative" @keydown.window.escape="open = false">
    <button @click="open = true"
            class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)]/10 active:bg-[var(--md-sys-color-surface-container-high)]/15 active:scale-95 transition-all duration-200 flex items-center justify-center relative group touch-manipulation"
            title="تغییرات و به‌روزرسانی‌ها">
        <span class="material-symbols-rounded text-[20px] sm:text-[22px] text-emerald-400 group-hover:animate-pulse">new_releases</span>
        <span class="absolute top-1.5 right-1.5 sm:top-2 sm:right-2 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-[var(--md-sys-color-surface)] animate-ping"></span>
        <span class="absolute top-1.5 right-1.5 sm:top-2 sm:right-2 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-[var(--md-sys-color-surface)]"></span>
    </button>

    <!-- Modal Overlay -->
    <div x-show="open"
         class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center px-0 sm:px-4 py-0 sm:py-6"
         style="display: none;"
         x-cloak>

        <!-- Backdrop -->
        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
             @click="open = false"></div>

        <!-- Modal Content -->
        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95"
             class="relative bg-[var(--md-sys-color-surface)] border-0 sm:border border-[var(--md-sys-color-outline-variant)]/10 rounded-t-2xl sm:rounded-2xl shadow-2xl transform transition-all w-full sm:max-w-2xl max-h-[90vh] sm:max-h-[85vh] flex flex-col overflow-hidden text-[var(--md-sys-color-on-surface)]"
             dir="rtl">

            <!-- Header -->
            <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-[var(--md-sys-color-outline-variant)]/10 bg-[var(--md-sys-color-surface-container)]/30 shrink-0">
                <div class="flex items-center gap-2 sm:gap-3">
                    <span class="material-symbols-rounded text-emerald-400 text-xl sm:text-2xl">rocket_launch</span>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold">آخرین تغییرات</h3>
                        <p class="text-[10px] sm:text-xs opacity-50 hidden sm:block">لیست به‌روزرسانی‌های سامانه</p>
                    </div>
                </div>
                <button @click="open = false" class="opacity-40 hover:opacity-100 transition-opacity p-2 -ml-2 touch-manipulation">
                    <span class="material-symbols-rounded text-xl sm:text-2xl">close</span>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div class="p-4 sm:p-6 overflow-y-auto custom-scrollbar space-y-6 sm:space-y-8 text-right">

                <!-- Version 3.11 -->
                <div class="relative pr-4 sm:pr-6 border-r-2 border-[var(--md-sys-color-outline-variant)]/10 last:border-0 pb-6 sm:pb-8 last:pb-0">
                    <div class="absolute -right-[7px] sm:-right-[9px] top-0 w-3 h-3 sm:w-4 sm:h-4 rounded-full bg-[var(--md-sys-color-surface)] border-2 border-emerald-500 flex items-center justify-center z-10">
                        <div class="w-1 h-1 sm:w-1.5 sm:h-1.5 rounded-full bg-emerald-500"></div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-3 sm:mb-4 gap-1 sm:gap-2">
                        <h4 class="text-base sm:text-lg font-bold flex items-center gap-2">
                            <span class="bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded text-xs font-mono">v3.11</span>
                        </h4>
                        <span class="text-[10px] sm:text-xs opacity-40 font-mono tracking-wider">October 2, 2025</span>
                    </div>

                    <div class="space-y-3 sm:space-y-4 text-xs sm:text-sm opacity-80 leading-relaxed">
                        <!-- User Panel -->
                        <div class="bg-[var(--md-sys-color-surface-container)]/30 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-[var(--md-sys-color-outline-variant)]/5 hover:border-[var(--md-sys-color-outline-variant)]/10 transition-colors group">
                            <h5 class="font-semibold text-emerald-500 mb-2 sm:mb-3 text-[10px] sm:text-xs uppercase tracking-wider flex items-center gap-2">
                                <span class="w-0.5 sm:w-1 h-3 sm:h-4 bg-emerald-500 rounded-full"></span>
                                پنل کاربری
                            </h5>
                            <ul class="space-y-2 sm:space-y-3">
                                <li class="flex items-start gap-2 group-hover:opacity-100 transition-opacity">
                                    <span class="material-symbols-rounded text-sm sm:text-[16px] mt-0.5 text-emerald-500/70 flex-shrink-0">check_circle</span>
                                    <span>اضافه شدن <strong>نوار ابزار و منوی هوشمند ریسپانسیو</strong>.</span>
                                </li>
                                <li class="flex items-start gap-2 group-hover:opacity-100 transition-opacity">
                                    <span class="material-symbols-rounded text-sm sm:text-[16px] mt-0.5 text-emerald-500/70 flex-shrink-0">check_circle</span>
                                    <span>اضافه شدن <strong>شمارنده هوشمند</strong> برای اعلانات.</span>
                                </li>
                                <li class="flex flex-col gap-2 opacity-70 text-[10px] sm:text-xs bg-[var(--md-sys-color-scrim)]/5 rounded-lg p-2 sm:p-3">
                                    <div class="flex items-center gap-2 text-emerald-500/80 mb-1">
                                        <span class="material-symbols-rounded text-sm sm:text-[16px]">subdirectory_arrow_left</span>
                                        <span class="font-semibold">جزئیات بیشتر</span>
                                    </div>
                                    <ul class="space-y-1.5 sm:space-y-2 pr-3 sm:pr-4 border-r border-[var(--md-sys-color-outline-variant)]/10">
                                        <li class="flex items-start gap-2">
                                            <span class="w-1 h-1 rounded-full bg-current opacity-30 mt-1 flex-shrink-0"></span>
                                            <span>بهبود رنگ‌بندی آنالیتیکس ماژول تست انرژی.</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span class="w-1 h-1 rounded-full bg-current opacity-30 mt-1 flex-shrink-0"></span>
                                            <span>اتصال درخت سلسله‌مراتب سازمانی به تست انرژی برای دید مدیریتی.</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span class="w-1 h-1 rounded-full bg-current opacity-30 mt-1 flex-shrink-0"></span>
                                            <span>تغییر پیوست اسناد در ماژول آنبوردینگ برای دانلود و پیش‌نمایش.</span>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>

                        <!-- Admin Panel -->
                        <div class="bg-[var(--md-sys-color-surface-container)]/30 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-[var(--md-sys-color-outline-variant)]/5 hover:border-[var(--md-sys-color-outline-variant)]/10 transition-colors group">
                            <h5 class="font-semibold text-emerald-500 mb-2 sm:mb-3 text-[10px] sm:text-xs uppercase tracking-wider flex items-center gap-2">
                                <span class="w-0.5 sm:w-1 h-3 sm:h-4 bg-emerald-500 rounded-full"></span>
                                پنل مدیریت
                            </h5>
                            <ul class="space-y-2 sm:space-y-3">
                                <li class="flex items-start gap-2 group-hover:opacity-100 transition-opacity">
                                    <span class="material-symbols-rounded text-sm sm:text-[16px] mt-0.5 text-emerald-500/70 flex-shrink-0">check_circle</span>
                                    <span>بازطراحی کامل <strong>ماژول دسترسی‌ها (Authority)</strong>.</span>
                                </li>
                                <li class="flex items-start gap-2 group-hover:opacity-100 transition-opacity">
                                    <span class="material-symbols-rounded text-sm sm:text-[16px] mt-0.5 text-emerald-500/70 flex-shrink-0">check_circle</span>
                                    <span>امکان غیرفعال‌سازی و مرتب‌سازی برای پیام‌های فوری، نظرسنجی و پرسشنامه‌ها.</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Version 3.10 -->
                <div class="relative pr-4 sm:pr-6 border-r-2 border-[var(--md-sys-color-outline-variant)]/10 last:border-0 pb-6 sm:pb-8 last:pb-0">
                    <div class="absolute -right-[7px] sm:-right-[9px] top-0 w-3 h-3 sm:w-4 sm:h-4 rounded-full bg-[var(--md-sys-color-surface)] border-2 border-emerald-500 flex items-center justify-center z-10">
                        <div class="w-1 h-1 sm:w-1.5 sm:h-1.5 rounded-full bg-emerald-500"></div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-3 sm:mb-4 gap-1 sm:gap-2">
                        <h4 class="text-base sm:text-lg font-bold flex items-center gap-2">
                            <span class="bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded text-xs font-mono">v3.10</span>
                        </h4>
                        <span class="text-[10px] sm:text-xs opacity-40 font-mono tracking-wider">September 15, 2025</span>
                    </div>

                    <div class="space-y-3 sm:space-y-4 text-xs sm:text-sm opacity-80 leading-relaxed">
                        <div class="bg-[var(--md-sys-color-surface-container)]/30 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-[var(--md-sys-color-outline-variant)]/5 hover:border-[var(--md-sys-color-outline-variant)]/10 transition-colors group">
                            <h5 class="font-semibold text-emerald-500 mb-2 sm:mb-3 text-[10px] sm:text-xs uppercase tracking-wider flex items-center gap-2">
                                <span class="w-0.5 sm:w-1 h-3 sm:h-4 bg-emerald-500 rounded-full"></span>
                                پنل کاربری
                            </h5>
                            <ul class="space-y-2 sm:space-y-3">
                                <li class="flex items-start gap-2 group-hover:opacity-100 transition-opacity">
                                    <span class="material-symbols-rounded text-sm sm:text-[16px] mt-0.5 text-emerald-500/70 flex-shrink-0">check_circle</span>
                                    <span>ماژول جدید <strong>فید (Feed)</strong> با امکان کامنت و واکنش (متن، تصویر، ویدیو).</span>
                                </li>
                                <li class="flex items-start gap-2 group-hover:opacity-100 transition-opacity">
                                    <span class="material-symbols-rounded text-sm sm:text-[16px] mt-0.5 text-emerald-500/70 flex-shrink-0">check_circle</span>
                                    <span>ماژول جدید <strong>گالری</strong> با نمای کلاژ و حالت عمومی/خصوصی.</span>
                                </li>
                                <li class="flex items-start gap-2 group-hover:opacity-100 transition-opacity">
                                    <span class="material-symbols-rounded text-sm sm:text-[16px] mt-0.5 text-emerald-500/70 flex-shrink-0">check_circle</span>
                                    <span>ماژول جدید <strong>رادیو آنلاین</strong>.</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="px-4 sm:px-6 py-3 sm:py-3 bg-[var(--md-sys-color-surface-container)]/30 border-t border-[var(--md-sys-color-outline-variant)]/10 text-center shrink-0">
                <p class="text-[10px] opacity-30">تمام حقوق محفوظ است &copy; {{ date('Y') }}</p>
            </div>
        </div>
    </div>
</div>

<style>
    .touch-manipulation {
        touch-action: manipulation;
    }

    /* Smooth scrollbar for webkit browsers */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: var(--md-sys-color-outline-variant);
        border-radius: 20px;
        opacity: 0.3;
    }

    /* Hide x-cloak elements before Alpine loads */
    [x-cloak] {
        display: none !important;
    }
</style>
