@props(['title' => 'یادداشت‌های انتشار'])

<div x-data="{ open: false, active: false }"
     x-init="$watch('open', value => { if(value) { requestAnimationFrame(() => requestAnimationFrame(() => active = true)) } else { active = false } })"
     class="relative">

    <button @click="open = true"
            class="w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)]/50 active:bg-[var(--md-sys-color-surface-container-high)] active:scale-95 transition-all duration-200 flex items-center justify-center relative group"
        {{ $attributes->merge(['class' => '']) }}>
        <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100 transition-opacity">new_releases</span>
        <span class="absolute top-2 right-2 w-2 h-2 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.6)]"></span>
        <x-dashboard.tooltip :text="$title" position="bottom" />
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

            <div class="custom-modal-content text-right" style="max-width: 650px;">
                <h3 class="modal-title mb-10 text-center">یادداشت‌های انتشار</h3>

                <div class="text-right space-y-8 pr-4">
                    <!-- Version 3.11 -->
                    <div class="relative border-r-2 border-white/20 pr-6 pb-8 last:border-0 last:pb-0">
                        <div class="absolute -right-[9px] top-0 w-4 h-4 rounded-full bg-[var(--md-sys-color-primary)] border-2 border-emerald-400 flex items-center justify-center z-10 shadow-lg shadow-emerald-500/20">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                            <h4 class="text-xl font-bold flex items-center gap-3 text-white">
                                <span class="bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded text-xs font-mono border border-emerald-500/30">v3.11</span>
                            </h4>
                            <span class="text-xs text-white/50 font-mono tracking-wider">October 2, 2025</span>
                        </div>

                        <div class="space-y-4 text-sm text-white/90 leading-relaxed">
                            <!-- User Panel -->
                            <div class="bg-white/5 rounded-xl p-4 border border-white/10 hover:bg-white/10 transition-colors group">
                                <h5 class="font-bold text-emerald-400 mb-3 text-xs uppercase tracking-wider flex items-center gap-2">
                                    <span class="w-1 h-3 bg-emerald-400 rounded-full"></span>
                                    پنل کاربری
                                </h5>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 group-hover:opacity-100 transition-opacity">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-emerald-400/80 shrink-0">check_circle</span>
                                        <span>اضافه شدن <strong>نوار ابزار و منوی هوشمند ریسپانسیو</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 group-hover:opacity-100 transition-opacity">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-emerald-400/80 shrink-0">check_circle</span>
                                        <span>اضافه شدن <strong>شمارنده هوشمند</strong> برای اعلانات.</span>
                                    </li>
                                    <li class="flex flex-col gap-2 opacity-80 text-xs bg-black/20 rounded-lg p-3 mt-2">
                                        <div class="flex items-center gap-2 text-emerald-300 mb-1">
                                            <span class="material-symbols-rounded text-sm">subdirectory_arrow_left</span>
                                            <span class="font-semibold">جزئیات بیشتر</span>
                                        </div>
                                        <ul class="space-y-2 pr-3 border-r border-white/10">
                                            <li class="flex items-start gap-2">
                                                <span class="w-1 h-1 rounded-full bg-white/40 mt-1.5 shrink-0"></span>
                                                <span>بهبود رنگ‌بندی آنالیتیکس ماژول تست انرژی.</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <span class="w-1 h-1 rounded-full bg-white/40 mt-1.5 shrink-0"></span>
                                                <span>اتصال درخت سلسله‌مراتب سازمانی به تست انرژی.</span>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>

                            <!-- Admin Panel -->
                            <div class="bg-white/5 rounded-xl p-4 border border-white/10 hover:bg-white/10 transition-colors group">
                                <h5 class="font-bold text-emerald-400 mb-3 text-xs uppercase tracking-wider flex items-center gap-2">
                                    <span class="w-1 h-3 bg-emerald-400 rounded-full"></span>
                                    پنل مدیریت
                                </h5>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 group-hover:opacity-100 transition-opacity">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-emerald-400/80 shrink-0">check_circle</span>
                                        <span>بازطراحی کامل <strong>ماژول دسترسی‌ها (Authority)</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 group-hover:opacity-100 transition-opacity">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-emerald-400/80 shrink-0">check_circle</span>
                                        <span>امکان غیرفعال‌سازی پیام‌های فوری و نظرسنجی‌ها.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Version 3.10 -->
                    <div class="relative border-r-2 border-white/20 pr-6 pb-8 last:border-0 last:pb-0">
                        <div class="absolute -right-[9px] top-0 w-4 h-4 rounded-full bg-[var(--md-sys-color-primary)] border-2 border-emerald-400 flex items-center justify-center z-10 shadow-lg shadow-emerald-500/20">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                            <h4 class="text-xl font-bold flex items-center gap-3 text-white">
                                <span class="bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded text-xs font-mono border border-emerald-500/30">v3.10</span>
                            </h4>
                            <span class="text-xs text-white/50 font-mono tracking-wider">September 15, 2025</span>
                        </div>

                        <div class="space-y-4 text-sm text-white/90 leading-relaxed">
                            <div class="bg-white/5 rounded-xl p-4 border border-white/10 hover:bg-white/10 transition-colors group">
                                <h5 class="font-bold text-emerald-400 mb-3 text-xs uppercase tracking-wider flex items-center gap-2">
                                    <span class="w-1 h-3 bg-emerald-400 rounded-full"></span>
                                    پنل کاربری
                                </h5>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 group-hover:opacity-100 transition-opacity">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-emerald-400/80 shrink-0">check_circle</span>
                                        <span>ماژول جدید <strong>فید (Feed)</strong> با امکان کامنت و واکنش.</span>
                                    </li>
                                    <li class="flex items-start gap-2 group-hover:opacity-100 transition-opacity">
                                        <span class="material-symbols-rounded text-sm mt-0.5 text-emerald-400/80 shrink-0">check_circle</span>
                                        <span>ماژول جدید <strong>گالری</strong> و <strong>رادیو آنلاین</strong>.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-white/10 text-center text-xs opacity-40">
                    تمام حقوق محفوظ است &copy; {{ date('Y') }}
                </div>
            </div>
        </div>
    </template>
</div>
