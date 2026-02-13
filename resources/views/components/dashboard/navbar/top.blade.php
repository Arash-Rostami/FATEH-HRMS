<nav x-data="{ menuOpen: false }"
     class="bg-[var(--md-sys-color-primary)] text-white/95 px-4 lg:px-8 flex justify-between items-center h-[48px] shadow-sm border-b border-white/5 shrink-0 relative z-50">

    {{-- Left Section --}}
    <div class="flex items-center gap-4">
        <button class="relative p-2 rounded-full hover:bg-white/10 transition-all duration-200 active:scale-95">
            <span class="material-symbols-rounded text-[20px]">notifications</span>
            <span class="absolute top-2 right-2.5 w-2 h-2 bg-[#FF7F6E] rounded-full border-2 border-[var(--md-sys-color-primary)]"></span>
        </button>

        <div class="hidden lg:flex items-center gap-3 text-[12px] font-medium">
            <span class="bg-white/10 px-3 py-1 rounded-full border border-white/5">Wednesday</span>
            <div class="flex items-center gap-2 opacity-80">
                <span>1404.11.19</span>
                <span class="w-1 h-1 bg-white/30 rounded-full"></span>
                <span>14.02.2026</span>
            </div>
        </div>
    </div>

    {{-- Center Time --}}
    <div class="hidden lg:block absolute left-1/2 -translate-x-1/2">
        <div class="flex items-center gap-2 bg-black/10 px-4 py-1.5 rounded-xl border border-white/5 tracking-wider font-semibold text-[13px]">
            <span class="material-symbols-rounded text-[16px] opacity-70">schedule</span>
            01:26 PM
        </div>
    </div>

    {{-- Right Section --}}
    <div class="flex items-center gap-4">
        <div class="hidden lg:flex items-center gap-1.5">
            <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/5 text-[12px] font-medium">
                <span class="material-symbols-rounded text-[18px] text-amber-300">wb_sunny</span>
                8 °C
            </div>

            <div class="flex items-center gap-3 px-3 py-1 rounded-full bg-white/10 border border-white/5 text-[12px] font-medium">
                <span class="flex items-center gap-1">
                    <span class="material-symbols-rounded text-[16px] opacity-70">home</span>
                    344
                </span>
                <span class="w-[1px] h-3 bg-white/20"></span>
                <span class="flex items-center gap-1">
                    <span class="material-symbols-rounded text-[16px] opacity-70">play_circle</span>
                    2987
                </span>
            </div>
        </div>

        <div class="hidden lg:block w-[1px] h-6 bg-white/10 mx-1"></div>

        <div class="relative">
            <button
                @click="menuOpen = !menuOpen"
                class="p-1.5 rounded-lg hover:bg-white/15 transition-all duration-200 active:scale-95"
                :class="menuOpen && 'bg-white/10'">
                <span class="material-symbols-rounded text-[26px] transition-transform duration-300"
                      :class="menuOpen && 'rotate-180'"
                      x-text="menuOpen ? 'close' : 'menu'">
                </span>
            </button>

            {{-- Dropdown Menu --}}
            <div
                x-show="menuOpen"
                @click.away="menuOpen = false"
                {{--
                    PERFORMANCE NOTE:
                    'will-change-[opacity,transform]' is added here.
                    It prepares the GPU before the animation starts.
                --}}
                x-transition:enter="transition ease-[cubic-bezier(0.16,1,0.3,1)] duration-200 origin-top-right will-change-[opacity,transform]"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150 origin-top-right will-change-[opacity,transform]"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                {{--
                    CREATIVE TRICKS ADDED:
                    1. backdrop-saturate-150: Makes colors behind the blur more vivid (Premium Glass).
                    2. ring-1 ring-inset ring-white/10: Adds an inner light reflection edge (Chamfered Edge).
                --}}
                class="absolute right-0 mt-3 w-72 bg-[var(--md-sys-color-surface-container-highest)]/90 backdrop-blur-xl backdrop-saturate-150 ring-1 ring-inset ring-white/10 rounded-3xl shadow-[0_8px_32px_rgba(0,0,0,0.12)] border border-[var(--md-sys-color-outline-variant)]/10 overflow-hidden"
                style="display: none;">

                {{-- Header --}}
                <div class="px-4 py-3 bg-gradient-to-b from-[var(--md-sys-color-surface-container-high)] to-transparent border-b border-[var(--md-sys-color-outline-variant)]/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[var(--md-sys-color-primary)] flex items-center justify-center">
                            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary)]">account_circle</span>
                        </div>
                        <div class="flex-1 text-right">
                            <div class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">کاربر سیستم</div>
                            <div class="text-xs text-[var(--md-sys-color-on-surface-variant)]">user@example.com</div>
                        </div>
                    </div>
                </div>

                {{-- Settings Section --}}
                <div class="p-2">
                    <div class="px-3 py-2 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-[0.1em] text-right">
                        تنظیمات
                    </div>

                    <a href="{{ url('/settings/profile') }}"
                       class="group flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 active:scale-[0.97] relative overflow-hidden hover:bg-[var(--md-sys-color-primary-container)]">
                        <div class="absolute inset-0 bg-[var(--md-sys-color-primary)]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
                        <div class="flex-1 text-right relative z-10">
                            <span class="text-sm font-medium text-[var(--md-sys-color-on-surface)] group-hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">پروفایل کاربری</span>
                        </div>
                        <div class="w-9 h-9 rounded-lg bg-[var(--md-sys-color-primary)]/10 flex items-center justify-center group-hover:bg-[var(--md-sys-color-primary)] transition-all duration-200 relative z-10">
                            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)] group-hover:text-[var(--md-sys-color-on-primary)] transition-colors">person</span>
                        </div>
                    </a>

                    <a href="{{ url('/settings/preferences') }}"
                       class="group flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 active:scale-[0.97] relative overflow-hidden hover:bg-[var(--md-sys-color-primary-container)]">
                        <div class="absolute inset-0 bg-[var(--md-sys-color-primary)]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
                        <div class="flex-1 text-right relative z-10">
                            <span class="text-sm font-medium text-[var(--md-sys-color-on-surface)] group-hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">تنظیمات سیستم</span>
                        </div>
                        <div class="w-9 h-9 rounded-lg bg-[var(--md-sys-color-primary)]/10 flex items-center justify-center group-hover:bg-[var(--md-sys-color-primary)] transition-all duration-200 relative z-10">
                            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)] group-hover:text-[var(--md-sys-color-on-primary)] transition-colors">tune</span>
                        </div>
                    </a>
                </div>

                {{-- Divider --}}
                <div class="px-4 py-1">
                    <div class="h-[1px] bg-gradient-to-r from-transparent via-[var(--md-sys-color-outline-variant)]/30 to-transparent"></div>
                </div>

                {{-- Tools Section --}}
                <div class="p-2">
                    <div class="px-3 py-2 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-[0.1em] text-right">
                        ابزارها
                    </div>

                    <a href="{{ url('/tools/export') }}"
                       class="group flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 active:scale-[0.97] relative overflow-hidden hover:bg-[var(--md-sys-color-primary-container)]">
                        <div class="absolute inset-0 bg-[var(--md-sys-color-primary)]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
                        <div class="flex-1 text-right relative z-10">
                            <span class="text-sm font-medium text-[var(--md-sys-color-on-surface)] group-hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">دریافت خروجی</span>
                        </div>
                        <div class="w-9 h-9 rounded-lg bg-[var(--md-sys-color-primary)]/10 flex items-center justify-center group-hover:bg-[var(--md-sys-color-primary)] transition-all duration-200 relative z-10">
                            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)] group-hover:text-[var(--md-sys-color-on-primary)] transition-colors">download</span>
                        </div>
                    </a>

                    <a href="{{ url('/tools/import') }}"
                       class="group flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 active:scale-[0.97] relative overflow-hidden hover:bg-[var(--md-sys-color-primary-container)]">
                        <div class="absolute inset-0 bg-[var(--md-sys-color-primary)]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
                        <div class="flex-1 text-right relative z-10">
                            <span class="text-sm font-medium text-[var(--md-sys-color-on-surface)] group-hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">بارگذاری فایل</span>
                        </div>
                        <div class="w-9 h-9 rounded-lg bg-[var(--md-sys-color-primary)]/10 flex items-center justify-center group-hover:bg-[var(--md-sys-color-primary)] transition-all duration-200 relative z-10">
                            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)] group-hover:text-[var(--md-sys-color-on-primary)] transition-colors">upload</span>
                        </div>
                    </a>
                </div>

                {{-- Divider --}}
                <div class="px-4 py-1">
                    <div class="h-[1px] bg-gradient-to-r from-transparent via-[var(--md-sys-color-outline-variant)]/30 to-transparent"></div>
                </div>

                {{-- Account Section --}}
                <div class="p-2 pb-3">
                    <a href="{{ url('/logout') }}"
                       class="group flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 active:scale-[0.97] relative overflow-hidden hover:bg-[var(--md-sys-color-error-container)]">
                        <div class="absolute inset-0 bg-[var(--md-sys-color-error)]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
                        <div class="flex-1 text-right relative z-10">
                            <span class="text-sm font-medium text-[var(--md-sys-color-error)] group-hover:text-[var(--md-sys-color-on-error-container)] transition-colors">خروج از حساب</span>
                        </div>
                        <div class="w-9 h-9 rounded-lg bg-[var(--md-sys-color-error)]/10 flex items-center justify-center group-hover:bg-[var(--md-sys-color-error)] transition-all duration-200 relative z-10">
                            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-error)] group-hover:text-[var(--md-sys-color-on-error)] transition-colors">logout</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Backdrop --}}
    <div
        x-show="menuOpen"
        @click="menuOpen = false"
        x-transition:enter="transition-opacity duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/20 backdrop-blur-sm -z-10"
        style="display: none;"></div>
</nav>
