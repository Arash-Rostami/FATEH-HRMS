<nav class="sticky top-0 w-full h-16 bg-[var(--md-sys-color-surface-container)]/80 backdrop-blur-md border-b border-[var(--md-sys-color-outline-variant)]/20 px-4 md:px-6 flex items-center justify-between z-30 transition-all duration-300 shadow-sm"
     x-data="menu">

    <!-- LEFT SECTION (RTL: Right) -->
    <div class="flex items-center gap-2 md:gap-4">
        <!-- Sidebar Toggle -->
        <button @click="$store.app.toggleSidebar()"
                class="hidden lg:flex w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)] active:scale-95 transition-all duration-200 items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)]">
            <span class="material-symbols-rounded text-[24px]">menu_open</span>
        </button>

        <!-- Brand/Logo -->
        <a href="{{ url('/dashboard') }}" class="flex items-center gap-2 group">
            <div class="relative w-8 h-8 md:w-9 md:h-9 overflow-hidden rounded-xl bg-gradient-to-tr from-[var(--md-sys-color-primary)] to-[var(--md-sys-color-tertiary)] flex items-center justify-center shadow-lg shadow-[var(--md-sys-color-primary)]/20 transition-transform duration-300 group-hover:scale-105 group-hover:rotate-3">
                <span class="material-symbols-rounded text-white text-[20px]">diamond</span>
                <div class="absolute inset-0 bg-white/20 blur-md opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            </div>
            <span class="text-base md:text-lg font-bold bg-clip-text text-transparent bg-gradient-to-r from-[var(--md-sys-color-on-surface)] to-[var(--md-sys-color-on-surface-variant)] hidden sm:block">
                اینتــرا
            </span>
        </a>
    </div>

    <!-- CENTER SECTION: Creative Timer -->
    <div class="hidden xl:block absolute left-1/2 -translate-x-1/2" x-data="timer" x-cloak>
        <button @click="toggleMode()"
                class="group relative flex items-center gap-2 px-3 py-1.5 rounded-xl bg-[var(--md-sys-color-surface-container-high)]/50 border border-[var(--md-sys-color-outline-variant)]/20 backdrop-blur-md shadow-sm transition-all duration-300 hover:bg-[var(--md-sys-color-surface-container-high)] hover:shadow-[var(--md-sys-color-primary)]/10 hover:scale-[1.02] active:scale-95 cursor-pointer overflow-hidden">

            <!-- Animated Background Glow -->
            <div class="absolute inset-0 bg-gradient-to-r from-[var(--md-sys-color-primary)]/0 via-[var(--md-sys-color-primary)]/5 to-[var(--md-sys-color-primary)]/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>

            <div class="flex items-center gap-2 relative z-10">
                <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)] transition-transform duration-300 group-hover:rotate-12" x-text="mode === 'fa' ? 'schedule' : 'public'"></span>

                <div class="flex items-baseline gap-1.5" :dir="mode === 'fa' ? 'rtl' : 'ltr'">
                     <span class="text-[13px] font-bold tracking-wide text-[var(--md-sys-color-on-surface)] font-mono tabular-nums" x-text="time"></span>
                     <span class="w-px h-3 bg-[var(--md-sys-color-outline-variant)]"></span>
                     <span class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wide opacity-90" x-text="date"></span>
                </div>
            </div>
        </button>
    </div>

    <!-- RIGHT SECTION (RTL: Left) -->
    <div class="flex items-center gap-2 md:gap-3">

        @auth
        <!-- Stats Pill (Birthday & Work Anniversary) -->
        <div class="hidden md:flex items-center bg-[var(--md-sys-color-surface-container-low)] rounded-xl border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden shadow-sm h-9">

            <!-- Birthday -->
            <div class="flex items-center gap-1.5 px-3 py-1 h-full hover:bg-[var(--md-sys-color-surface-container-high)] transition-colors duration-200 cursor-help group border-l border-[var(--md-sys-color-outline-variant)]/10" title="روز تا تولد">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-tertiary)] group-hover:animate-bounce">cake</span>
                <span class="text-[12px] font-bold text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-colors">
                    {{ auth()->user()->profile?->countNumberOfDaysTo(auth()->user()->profile->birthdate) ?? '-' }}
                </span>
            </div>

            <!-- Work Anniversary -->
            <div class="flex items-center gap-1.5 px-3 py-1 h-full hover:bg-[var(--md-sys-color-surface-container-high)] transition-colors duration-200 cursor-help group" title="روز کارکرد">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-secondary)]">work_history</span>
                <span class="text-[12px] font-bold text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-colors">
                    {{ auth()->user()->profile?->countDaysPassedSince(auth()->user()->profile->start_date) ?? '-' }}
                </span>
            </div>
        </div>
        @endauth

        <!-- Fullscreen Toggle -->
        <button x-data="{ isFullscreen: false, toggle() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen(); this.isFullscreen = true;
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen(); this.isFullscreen = false;
                        }
                    }
                }}"
                @click="toggle()"
                class="hidden sm:flex w-9 h-9 rounded-xl items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-surface-container-high)] transition-all active:scale-95"
                title="تمام صفحه">
            <span class="material-symbols-rounded text-[20px]" x-text="isFullscreen ? 'close_fullscreen' : 'fullscreen'"></span>
        </button>

        <!-- Theme Toggle -->
        <button @click="$store.theme.toggleMode()"
                class="w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-300 hover:bg-[var(--md-sys-color-surface-container-high)] active:scale-90"
                :class="$store.theme.isDark ? 'text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)]'"
                title="تغییر تم">
            <span class="material-symbols-rounded text-[20px]" x-text="$store.theme.isDark ? 'dark_mode' : 'light_mode'"></span>
        </button>

        <div class="hidden lg:block w-px h-6 bg-[var(--md-sys-color-outline-variant)]/20 mx-1"></div>

        <!-- User Profile & Menu -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
                    class="flex items-center gap-2 p-1 pl-3 rounded-full hover:bg-[var(--md-sys-color-surface-container-high)] transition-all active:scale-95 group border border-transparent hover:border-[var(--md-sys-color-outline-variant)]/20">

                <!-- Avatar -->
                <div class="relative w-8 h-8 rounded-full overflow-hidden border border-[var(--md-sys-color-outline-variant)]/20 group-hover:border-[var(--md-sys-color-primary)]/50 transition-colors">
                    @if(auth()->check() && auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}" alt="Avatar" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-[var(--md-sys-color-primary-container)] to-[var(--md-sys-color-tertiary-container)] flex items-center justify-center text-[var(--md-sys-color-on-primary-container)] font-bold text-xs">
                            {{ substr(auth()->user()->name ?? 'Guest', 0, 1) }}
                        </div>
                    @endif
                </div>

                <!-- Name (Desktop) -->
                <div class="hidden md:flex flex-col items-start leading-none gap-0.5">
                    <span class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] group-hover:text-[var(--md-sys-color-primary)] transition-colors">
                        {{ auth()->user()->name ?? 'مهمان' }}
                    </span>
                    <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] truncate max-w-[80px]">
                        {{ auth()->user()->role ?? 'کاربر' }}
                    </span>
                </div>

                <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-on-surface-variant)] group-hover:rotate-180 transition-transform duration-300">expand_more</span>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="open" @click.outside="open = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="absolute left-0 mt-2 w-48 bg-[var(--md-sys-color-surface-container)] rounded-2xl shadow-xl border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden z-50 origin-top-left"
                 style="display: none;">

                <div class="p-2 space-y-1">
                    <a href="{{ url('/profile') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-primary)] transition-colors">
                        <span class="material-symbols-rounded text-[20px]">person</span>
                        پروفایل کاربری
                    </a>
                    <a href="{{ url('/settings') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-primary)] transition-colors">
                        <span class="material-symbols-rounded text-[20px]">settings</span>
                        تنظیمات
                    </a>
                    <div class="h-px bg-[var(--md-sys-color-outline-variant)]/20 my-1"></div>

                    <!-- Logout -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-on-error-container)] transition-colors text-right">
                            <span class="material-symbols-rounded text-[20px]">logout</span>
                            خروج از حساب
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Toggle (Legacy) -->
        <button @click="toggleMenu" :aria-expanded="menuOpen.toString()" aria-label="Toggle menu"
                class="lg:hidden w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)] active:scale-95 transition-all duration-200 flex items-center justify-center text-[var(--md-sys-color-on-surface)]"
                :class="menuOpen && 'bg-[var(--md-sys-color-surface-container-high)]'">
            <span class="material-symbols-rounded text-[24px]" x-text="menuOpen ? 'close' : 'menu'"></span>
        </button>
    </div>

    <!-- Mobile Menu Overlay (Existing Logic) -->
    <div x-show="menuOpen" @click="closeMenu" x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40"
         style="display: none;"></div>

    <template x-if="menuOpen">
        <div @keydown.window.escape="closeMenu" x-transition:enter="transition-all duration-300 ease-out"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition-all duration-200 ease-in" x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 flex items-start justify-center pt-2 px-2 pb-0 sm:items-center sm:p-6"
             role="dialog" aria-modal="true">
            <div
                class="w-full h-full sm:h-auto sm:w-[920px] sm:max-w-[95%] bg-[var(--md-sys-color-surface)] rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col">
                <!-- Legacy Menu Content (Unchanged) -->
                <div dir="rtl" class="flex flex-col h-full">
                    <div class="relative px-5 py-5 bg-[var(--md-sys-color-primary)] border-b border-white/5 shrink-0">
                        <button @click="closeMenu"
                                class="absolute left-4 top-4 w-10 h-10 flex items-center justify-center rounded-xl bg-white/95 hover:bg-white active:scale-95 shadow-lg hover:shadow-xl transition-all duration-200"
                                aria-label="بستن منو">
                            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-surface)]">close</span>
                        </button>
                        <div class="flex items-center gap-4">
                            <div
                                class="w-14 h-14 rounded-2xl bg-[var(--md-sys-color-primary-container)] flex items-center justify-center shadow-md">
                                <span
                                    class="material-symbols-rounded text-[26px] text-[var(--md-sys-color-on-primary-container)]">account_circle</span>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-white mb-0.5">کاربر سیستم</div>
                                <div class="text-sm text-white/80">user@example.com</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar">
                        <div class="p-4 sm:p-6 pb-24 sm:pb-6 flex flex-col justify-center min-h-full">
                            <div class="w-full max-w-4xl mx-auto">
                                <div class="overflow-hidden rounded-xl">
                                    <div class="flex transition-transform duration-300 ease-out w-full"
                                         :style="`transform: translateX(-${current * 100}%)`">
                                        <template x-for="p in pages" :key="p">
                                            <div class="w-full flex-shrink-0 px-0.5">
                                                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2 sm:gap-3">
                                                    <template x-for="item in pageItems(p-1)" :key="item.href">
                                                        <a :href="item.href" @click="closeMenu"
                                                           class="group flex flex-col items-center gap-2 p-3 rounded-2xl bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] active:scale-[0.96] transition-all duration-200 h-[110px] sm:h-[130px] justify-center border border-transparent hover:border-[var(--md-sys-color-outline-variant)]/20">
                                                            <div
                                                                class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center bg-gradient-to-tr from-[var(--md-sys-color-primary)]/10 to-[var(--md-sys-color-primary)]/5 border border-[var(--md-sys-color-primary)]/10 shadow-sm group-hover:shadow-md group-hover:scale-110 transition-all duration-300">
                                                                <span
                                                                    class="material-symbols-rounded text-[22px] sm:text-[24px] text-[var(--md-sys-color-primary)]"
                                                                    x-text="item.icon"></span>
                                                            </div>
                                                            <div class="text-center w-full">
                                                                <div
                                                                    class="text-[11px] sm:text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-0.5 truncate px-1"
                                                                    x-text="item.title"></div>
                                                                <div
                                                                    class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] hidden sm:block leading-tight truncate px-1 opacity-80"
                                                                    x-text="item.sub"></div>
                                                            </div>
                                                        </a>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="flex items-center justify-center gap-3 sm:gap-4 mt-5 sm:mt-6"
                                     x-show="pages > 1">
                                    <button @click="next" :disabled="current >= pages - 1"
                                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center bg-[var(--md-sys-color-surface-container)] hover:bg-[var(--md-sys-color-surface-container-high)] active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed transition-all duration-200 shadow-sm hover:shadow-md border border-[var(--md-sys-color-outline-variant)]/20">
                                        <span
                                            class="material-symbols-rounded text-xl sm:text-2xl text-[var(--md-sys-color-on-surface)] rtl:-scale-x-100">chevron_left</span>
                                    </button>

                                    <div class="flex gap-2 sm:gap-2.5">
                                        <template x-for="p in pages" :key="p">
                                            <button @click="current = p-1"
                                                    class="rounded-full transition-all duration-300"
                                                    :class="current === p-1 ? 'w-7 sm:w-8 h-2 sm:h-2.5 bg-[var(--md-sys-color-primary)] shadow-md' : 'w-2 sm:w-2.5 h-2 sm:h-2.5 bg-[var(--md-sys-color-outline-variant)]/40 hover:bg-[var(--md-sys-color-outline-variant)]/70 hover:w-4 sm:hover:w-5'"></button>
                                        </template>
                                    </div>

                                    <button @click="prev" :disabled="current === 0"
                                            class="w-8 h-8 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center bg-[var(--md-sys-color-surface-container)] hover:bg-[var(--md-sys-color-surface-container-high)] active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed transition-all duration-200 shadow-sm hover:shadow-md border border-[var(--md-sys-color-outline-variant)]/20">
                                        <span
                                            class="material-symbols-rounded text-xl sm:text-2xl text-[var(--md-sys-color-on-surface)] rtl:-scale-x-100">chevron_right</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="px-6 py-3 border-t border-[var(--md-sys-color-outline-variant)]/10 bg-[var(--md-sys-color-surface-container-lowest)] shrink-0 hidden sm:block">
                        <div
                            class="flex items-center justify-between text-xs text-[var(--md-sys-color-on-surface-variant)]">
                            <div class="font-medium">نسخه سیستم: 1.0.0</div>
                            <div
                                class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-[var(--md-sys-color-surface-container)] text-[12px] font-medium">
                                <livewire:dashboard.header.weather />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</nav>
