<div class="flex items-center gap-3">
    <!-- Menu Toggle (Hamburger) -->
    <button @click="toggleMenu" :aria-expanded="menuOpen.toString()" aria-label="Toggle menu"
            class="w-10 h-10 rounded-xl hover:bg-white/10 active:bg-white/15 active:scale-95 transition-all duration-200 flex items-center justify-center"
            :class="menuOpen && 'bg-white/12'">
        <span class="material-symbols-rounded text-[24px]" x-text="menuOpen ? 'close' : 'menu'"></span>
    </button>
</div>

<!-- Command Palette -->
<livewire:dashboard.navbar.command-palette />

<!-- CENTER SECTION: Creative Timer -->
<div class="hidden lg:block absolute left-1/2 -translate-x-1/2" x-data="timer" x-cloak>
    <button @click="toggleMode()"
            class="group flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/5 text-[12px] font-medium text-white backdrop-blur-sm transition-colors duration-300 hover:bg-white/20 cursor-pointer overflow-hidden relative">

        <!-- Animated Glow -->
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>

        <span class="material-symbols-rounded text-[18px] text-teal-300 transition-transform duration-300 group-hover:rotate-180 relative z-10"
              x-text="mode === 'fa' ? 'schedule' : 'public'">
        </span>

        <div class="flex items-baseline gap-2 relative z-10" :dir="mode === 'fa' ? 'rtl' : 'ltr'">
            <span class="tracking-wide font-mono" x-text="time"></span>
            <span class="text-white/20">|</span>
            <span class="text-white/80" x-text="date"></span>
        </div>
    </button>
</div>

<!-- LEFT SECTION (RTL Order) -->
<div class="flex items-center gap-3">

    <!-- Weather Widget -->
    <div class="hidden xl:flex items-center gap-2 text-sm font-medium">
        <livewire:dashboard.header.weather/>
    </div>

    <div class="hidden xl:block w-px h-6 bg-white/15 mx-1"></div>

    @auth
        <div class="hidden xl:flex items-center gap-1">
            <!-- Birthday -->
            <div class="relative w-10 h-10 rounded-xl hover:bg-white/10 active:bg-white/15 transition-all duration-200 flex items-center justify-center cursor-help group" title="تعداد روزهای باقی مانده به تولد">
                <span class="material-symbols-rounded text-[20px] text-pink-300 group-hover:animate-bounce">cake</span>
                <span class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-pink-500 text-[9px] font-bold flex items-center justify-center shadow-sm">
            {{ auth()->user()->profile?->daysUntil(auth()->user()->profile->birthdate) ?? '-' }}
        </span>
            </div>

            <!-- Work Anniversary -->
            <div class="relative w-10 h-10 rounded-xl hover:bg-white/10 active:bg-white/15 transition-all duration-200 flex items-center justify-center cursor-help group" title="تعداد روزهای سپری شده از آغاز کار در شرکت">
                <span class="material-symbols-rounded text-[20px] text-emerald-300">work_history</span>
                <span class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-emerald-500 text-[9px] font-bold flex items-center justify-center shadow-sm">
            {{ auth()->user()->profile?->daysPassed(auth()->user()->profile->start_date) ?? '-' }}
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
            class="hidden sm:flex w-10 h-10 rounded-xl hover:bg-white/10 active:bg-white/15 active:scale-95 transition-all duration-200 items-center justify-center"
            title="تمام صفحه">
        <span class="material-symbols-rounded text-[22px]" x-text="isFullscreen ? 'close_fullscreen' : 'fullscreen'"></span>
    </button>

    <!-- Theme Palette & Toggle -->
    <div class="relative" x-data="{ open: false }">
        <button @click="open = !open"
                class="w-10 h-10 rounded-xl hover:bg-white/10 active:bg-white/15 active:scale-95 transition-all duration-200 flex items-center justify-center"
                title="تغییر تم">
            <span class="material-symbols-rounded text-[22px]">palette</span>
        </button>

        <div x-show="open" @click.outside="open = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="absolute left-0 mt-2 p-2 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-xl z-50 flex flex-col gap-2 min-w-[40px]"
             style="display: none;">

            <!-- Mode Toggle -->
            <button @click="$store.theme.toggleMode()"
                    class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] transition-all">
                <span class="material-symbols-rounded text-[20px]" x-text="$store.theme.isDark ? 'dark_mode' : 'light_mode'"></span>
            </button>

            <div class="h-px bg-[var(--md-sys-color-outline-variant)]/20"></div>

            <!-- Colors -->
            <template x-for="color in $store.theme.colors" :key="color.name">
                <button @click="$store.theme.set(color.name); open = false"
                        class="w-8 h-8 rounded-full border border-[var(--md-sys-color-outline-variant)] hover:scale-110 transition-transform relative"
                        :style="'background: ' + color.color">
                </button>
            </template>
        </div>
    </div>

    <!-- Quick Settings (New) -->
    <livewire:dashboard.navbar.quick-settings />

    <!-- Changelog (Static) -->
    <x-dashboard.navbar.changelog />

    <!-- Notifications -->
    <button class="relative w-10 h-10 rounded-xl hover:bg-white/10 active:bg-white/15 active:scale-95 transition-all duration-200 flex items-center justify-center">
        <span class="material-symbols-rounded text-[22px]">notifications</span>
        <span class="absolute top-2 right-2 w-2 h-2 bg-[var(--md-sys-color-error)] rounded-full ring-2 ring-[var(--md-sys-color-primary)]"></span>
    </button>

    <div class="w-px h-6 bg-white/15 mx-1"></div>

    <!-- Status Switcher (New) -->
    <livewire:dashboard.navbar.status-switcher />

    <!-- User Profile Dropdown -->
    <div class="relative" x-data="{ open: false }">
        <button @click="open = !open"
                class="flex items-center gap-2 pl-2 pr-1 py-1 rounded-xl hover:bg-white/10 active:bg-white/15 transition-all duration-200 group">

            <!-- Avatar -->
            <div class="w-8 h-8 rounded-full bg-white/10 border border-white/20 flex items-center justify-center overflow-hidden">
                @if(auth()->check() && auth()->user()->avatar)
                    <img src="{{ auth()->user()->avatar }}" alt="Avatar" class="w-full h-full object-cover">
                @else
                    <span class="material-symbols-rounded text-white text-[20px]">person</span>
                @endif
            </div>

            <!-- Name & Personnel ID -->
            <div class="hidden md:flex flex-col items-start text-right leading-none gap-0.5">
                <div class="text-[12px] font-bold truncate max-w-[100px]">{{ auth()->user()->name ?? 'مهمان' }}</div>
                @auth
                    <div class="text-[10px] text-white/70 font-mono tracking-wider mt-2">
                        {{ auth()->user()->profile?->personnel_id ?? '' }}
                    </div>
                @endauth
            </div>

            <span class="material-symbols-rounded text-[18px] opacity-70 group-hover:opacity-100 transition-opacity">expand_more</span>
        </button>

        <!-- Dropdown Menu -->
        <div x-show="open" @click.outside="open = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="absolute left-0 mt-2 w-40 bg-[var(--md-sys-color-surface)] rounded-xl shadow-xl border border-[var(--md-sys-color-outline-variant)]/10 overflow-hidden z-50 text-[var(--md-sys-color-on-surface)]"
             style="display: none;">

            <div class="p-1.5 space-y-0.5">
                <a href="{{ url('/profile') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-primary)] transition-colors">
                    <span class="material-symbols-rounded text-[20px]">person</span>
                    پروفایل
                </a>
                <div class="h-px bg-[var(--md-sys-color-outline-variant)]/10 my-1 mx-2"></div>

                <!-- Logout Livewire Component -->
                <div class="px-3 py-2">
                    <livewire:auth.logout-button />
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Extracted Menu Modal -->
<x-dashboard.navbar.menu-modal />
