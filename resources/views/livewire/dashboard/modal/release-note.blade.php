<x-dashboard.modal.base
    wire:model="isOpen"
    title="یادداشت‌های انتشار"
>
    <!-- Professional Timeline Content -->
    <div class="relative pl-6 space-y-12 text-right">

        <!-- Vertical Line -->
        <div class="absolute right-3 top-2 bottom-0 w-0.5 bg-[var(--md-sys-color-outline-variant)]/20"></div>

        <!-- Version 3.11 -->
        <div class="relative pr-10">
            <!-- Timeline Dot -->
            <div class="absolute right-0 top-1.5 w-6 h-6 rounded-full bg-[var(--md-sys-color-primary)] border-4 border-[var(--md-sys-color-surface)] shadow-md z-10 flex items-center justify-center"></div>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-2">
                <div class="flex items-center gap-3">
                    <h4 class="text-xl font-black text-[var(--md-sys-color-on-surface)] tracking-tight">نسخه 3.11</h4>
                    <span class="bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border border-[var(--md-sys-color-primary)]/10">Stable</span>
                </div>
                <span class="text-xs text-[var(--md-sys-color-on-surface-variant)] font-medium tracking-wide opacity-70">2 اکتبر 2025</span>
            </div>

            <!-- Content Cards -->
            <div class="space-y-4">
                <!-- User Panel Section -->
                <div class="group bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-200 rounded-2xl p-5 border border-[var(--md-sys-color-outline-variant)]/20">
                    <h5 class="flex items-center gap-2 text-sm font-bold text-[var(--md-sys-color-primary)] mb-4 uppercase tracking-wider">
                        <span class="material-symbols-rounded text-lg">person</span>
                        پنل کاربری
                    </h5>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 group-hover:translate-x-1 transition-transform duration-200">
                            <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)] mt-0.5 opacity-80 shrink-0">check_small</span>
                            <span class="text-sm text-[var(--md-sys-color-on-surface)] leading-relaxed">
                                اضافه شدن <strong class="text-[var(--md-sys-color-on-surface-variant)]">نوار ابزار و منوی هوشمند ریسپانسیو</strong> با قابلیت شخصی‌سازی.
                            </span>
                        </li>
                        <li class="flex items-start gap-3 group-hover:translate-x-1 transition-transform duration-200 delay-75">
                            <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)] mt-0.5 opacity-80 shrink-0">check_small</span>
                            <span class="text-sm text-[var(--md-sys-color-on-surface)] leading-relaxed">
                                سیستم جدید <strong class="text-[var(--md-sys-color-on-surface-variant)]">شمارنده هوشمند</strong> برای مدیریت بهتر اعلانات سیستمی.
                            </span>
                        </li>

                        <!-- Sub-features -->
                        <li class="mt-4 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/10">
                             <div class="flex items-center gap-2 text-xs font-bold text-[var(--md-sys-color-secondary)] mb-2 opacity-80">
                                <span class="material-symbols-rounded text-base">subdirectory_arrow_left</span>
                                <span>بهبودهای جزئی</span>
                            </div>
                            <ul class="space-y-2 pr-6 border-r border-[var(--md-sys-color-outline-variant)]/10">
                                <li class="text-xs text-[var(--md-sys-color-on-surface-variant)] leading-relaxed flex items-center gap-2">
                                    <span class="w-1 h-1 rounded-full bg-[var(--md-sys-color-on-surface-variant)] opacity-50"></span>
                                    بهبود کنتراست رنگ‌ها در ماژول تست انرژی.
                                </li>
                                <li class="text-xs text-[var(--md-sys-color-on-surface-variant)] leading-relaxed flex items-center gap-2">
                                    <span class="w-1 h-1 rounded-full bg-[var(--md-sys-color-on-surface-variant)] opacity-50"></span>
                                    اتصال چارت سازمانی به داشبورد مدیریتی.
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>

                <!-- Admin Panel Section -->
                <div class="group bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-200 rounded-2xl p-5 border border-[var(--md-sys-color-outline-variant)]/20">
                    <h5 class="flex items-center gap-2 text-sm font-bold text-[var(--md-sys-color-tertiary)] mb-4 uppercase tracking-wider">
                        <span class="material-symbols-rounded text-lg">admin_panel_settings</span>
                        پنل مدیریت
                    </h5>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 group-hover:translate-x-1 transition-transform duration-200">
                            <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-tertiary)] mt-0.5 opacity-80 shrink-0">check_small</span>
                            <span class="text-sm text-[var(--md-sys-color-on-surface)] leading-relaxed">
                                بازطراحی کامل <strong class="text-[var(--md-sys-color-on-surface-variant)]">ماژول سطوح دسترسی (Authority)</strong> با رابط کاربری جدید.
                            </span>
                        </li>
                        <li class="flex items-start gap-3 group-hover:translate-x-1 transition-transform duration-200 delay-75">
                            <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-tertiary)] mt-0.5 opacity-80 shrink-0">check_small</span>
                            <span class="text-sm text-[var(--md-sys-color-on-surface)] leading-relaxed">
                                امکان غیرفعال‌سازی سراسری پیام‌های فوری و ماژول نظرسنجی.
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Version 3.10 -->
        <div class="relative pr-10 opacity-60 hover:opacity-100 transition-opacity duration-300">
            <!-- Timeline Dot -->
             <div class="absolute right-1 top-2 w-4 h-4 rounded-full bg-[var(--md-sys-color-surface-container-highest)] border-2 border-[var(--md-sys-color-outline-variant)] z-10"></div>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                <h4 class="text-lg font-bold text-[var(--md-sys-color-on-surface-variant)] tracking-tight">نسخه 3.10</h4>
                <span class="text-xs text-[var(--md-sys-color-on-surface-variant)] font-medium tracking-wide opacity-70">15 سپتامبر 2025</span>
            </div>

            <div class="bg-[var(--md-sys-color-surface-container-lowest)] rounded-xl p-4 border border-[var(--md-sys-color-outline-variant)]/10">
                <ul class="space-y-2">
                    <li class="flex items-center gap-2 text-sm text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-primary)] opacity-50"></span>
                        انتشار ماژول فید (Feed) با قابلیت تعامل کامل.
                    </li>
                    <li class="flex items-center gap-2 text-sm text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-primary)] opacity-50"></span>
                        افزوده شدن گالری تصاویر و رادیو آنلاین.
                    </li>
                </ul>
            </div>
        </div>

    </div>

    <div class="mt-12 pt-6 border-t border-[var(--md-sys-color-outline-variant)]/10 text-center">
        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-50 tracking-widest uppercase font-mono">
            &copy; {{ date('Y') }} All Rights Reserved
        </p>
    </div>
</x-dashboard.modal.base>
