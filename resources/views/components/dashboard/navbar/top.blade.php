<div class="relative w-full h-[72px] flex items-center justify-between px-6 z-50 transition-all duration-300">

    <!-- LEFT SECTION: Tools (Hamburger, Search, Weather) -->
    <div class="flex items-center gap-4 relative z-20">
        <!-- Menu Toggle -->
        <button @click="toggleMenu"
                :aria-expanded="menuOpen.toString()"
                aria-label="Toggle menu"
                class="w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)]/50 active:scale-95 transition-all duration-200 flex items-center justify-center text-[var(--md-sys-color-on-surface)]"
                :class="menuOpen && 'bg-[var(--md-sys-color-surface-container-high)]'">
            <span class="material-symbols-rounded text-[24px]" x-text="menuOpen ? 'close' : 'menu'"></span>
        </button>

        <!-- Command Palette Trigger -->
        <livewire:dashboard.navbar.command-palette />

        <div class="w-px h-6 bg-[var(--md-sys-color-outline-variant)]/20 mx-1 hidden xl:block"></div>

        <!-- Weather Widget -->
        <div class="hidden xl:flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-on-surface-variant)]">
            <livewire:dashboard.header.weather/>
        </div>
    </div>

    <!-- CENTER SECTION: Creative Timer (Absolute Center) -->
    <div class="hidden lg:flex absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 z-10" x-data="timer" x-cloak>
        <button @click="toggleMode()"
                class="group flex items-center gap-3 px-5 py-2 rounded-full bg-[var(--md-sys-color-surface-container)]/30 border border-[var(--md-sys-color-outline-variant)]/10 backdrop-blur-md shadow-lg transition-all duration-300 hover:bg-[var(--md-sys-color-surface-container)]/50 hover:shadow-[var(--md-sys-color-primary)]/5 hover:border-[var(--md-sys-color-primary)]/20 relative overflow-hidden">

            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)] transition-transform duration-500 group-hover:rotate-180"
                  x-text="mode === 'fa' ? 'schedule' : 'public'">
            </span>

            <div class="flex items-baseline gap-2.5 text-[var(--md-sys-color-on-surface)]" :dir="mode === 'fa' ? 'rtl' : 'ltr'">
                <span class="tracking-widest font-mono text-base font-bold tabular-nums" x-text="time"></span>
                <span class="opacity-20 text-sm">|</span>
                <span class="opacity-80 text-sm font-medium tracking-wide" x-text="date"></span>
            </div>

            <!-- Glow Effect -->
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[var(--md-sys-color-primary)]/5 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
        </button>
    </div>

    <!-- RIGHT SECTION: Status, Actions, Profile (RTL Order) -->
    <div class="flex items-center gap-3 relative z-20">

        <!-- Status Switcher -->
        <livewire:dashboard.navbar.status-switcher />

        <div class="w-px h-6 bg-[var(--md-sys-color-outline-variant)]/20 mx-1 hidden sm:block"></div>

        <!-- Quick Actions Group -->
        <div class="flex items-center gap-2">
            <!-- Changelog -->
            <x-dashboard.navbar.changelog />

            <!-- Quick Settings -->
            <livewire:dashboard.navbar.quick-settings />

            <!-- Theme Toggle -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                        class="w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)]/50 active:scale-95 transition-all duration-200 flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)]"
                        title="تغییر تم">
                    <span class="material-symbols-rounded text-[22px]">palette</span>
                </button>

                <div x-show="open" @click.outside="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     class="absolute left-0 mt-2 p-2 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-xl z-50 flex flex-col gap-2 min-w-[40px]"
                     style="display: none;">

                    <button @click="$store.theme.toggleMode()"
                            class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] transition-all">
                        <span class="material-symbols-rounded text-[20px]" x-text="$store.theme.isDark ? 'dark_mode' : 'light_mode'"></span>
                    </button>

                    <div class="h-px bg-[var(--md-sys-color-outline-variant)]/20"></div>

                    <template x-for="color in $store.theme.colors" :key="color.name">
                        <button @click="$store.theme.set(color.name); open = false"
                                class="w-8 h-8 rounded-full border border-[var(--md-sys-color-outline-variant)] hover:scale-110 transition-transform relative"
                                :style="'background: ' + color.color">
                        </button>
                    </template>
                </div>
            </div>

            <!-- Notifications -->
            <button class="relative w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)]/50 active:scale-95 transition-all duration-200 flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)]">
                <span class="material-symbols-rounded text-[22px]">notifications</span>
                <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-[var(--md-sys-color-error)] rounded-full ring-2 ring-[var(--md-sys-color-surface)] animate-pulse"></span>
            </button>
        </div>

        <div class="w-px h-6 bg-[var(--md-sys-color-outline-variant)]/20 mx-1 hidden sm:block"></div>

        <!-- User Profile -->
        <div class="relative pl-1" x-data="{ open: false }">
            <button @click="open = !open"
                    class="flex items-center gap-3 pl-2 pr-1 py-1 rounded-full hover:bg-[var(--md-sys-color-surface-container-high)]/30 active:scale-95 transition-all duration-200 group border border-transparent hover:border-[var(--md-sys-color-outline-variant)]/10">

                <!-- Avatar with Status Ring -->
                <div class="relative">
                    <div class="w-9 h-9 rounded-full bg-[var(--md-sys-color-surface-container)] flex items-center justify-center overflow-hidden ring-2 ring-[var(--md-sys-color-surface)] shadow-sm group-hover:ring-[var(--md-sys-color-primary)]/30 transition-all">
                        @if(auth()->check() && auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="Avatar" class="w-full h-full object-cover">
                        @else
                            <span class="material-symbols-rounded text-[var(--md-sys-color-on-surface-variant)] text-[22px]">person</span>
                        @endif
                    </div>
                    <!-- Online/Status Indicator (connected to StatusSwitcher) -->
                    <div class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-[var(--md-sys-color-surface)] shadow-sm"
                         :class="{
                             'bg-emerald-500': $store.statusSwitcher && $store.statusSwitcher.status === 'onsite',
                             'bg-sky-500': $store.statusSwitcher && $store.statusSwitcher.status === 'remote',
                             'bg-rose-500': $store.statusSwitcher && $store.statusSwitcher.status === 'busy',
                             'bg-amber-500': $store.statusSwitcher && $store.statusSwitcher.status === 'mission'
                         }"></div>
                </div>

                <!-- Name & ID -->
                <div class="hidden md:flex flex-col items-end text-right leading-none gap-1">
                    <div class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)] truncate max-w-[120px]">{{ auth()->user()->name ?? 'مهمان' }}</div>
                    @auth
                        <div class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-mono tracking-wider opacity-70">
                            {{ auth()->user()->profile?->personnel_id ?? '' }}
                        </div>
                    @endauth
                </div>

                <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-surface-variant)] opacity-50 group-hover:opacity-100 transition-all group-hover:translate-y-0.5">expand_more</span>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="open" @click.outside="open = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 class="absolute left-0 mt-3 w-48 bg-[var(--md-sys-color-surface)] rounded-2xl shadow-2xl border border-[var(--md-sys-color-outline-variant)]/10 overflow-hidden z-50 text-[var(--md-sys-color-on-surface)] origin-top-left"
                 style="display: none;">

                <div class="p-2 space-y-1">
                    <a href="{{ url('/profile') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-primary)] transition-colors group">
                        <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)]">account_circle</span>
                        پروفایل کاربری
                    </a>

                    <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-primary)] transition-colors group">
                        <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)]">settings</span>
                        تنظیمات
                    </a>

                    <div class="h-px bg-[var(--md-sys-color-outline-variant)]/10 my-1 mx-2"></div>

                    <!-- Logout Livewire Component -->
                    <div class="px-1">
                        <livewire:auth.logout-button />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Extracted Menu Modal -->
    <x-dashboard.navbar.menu-modal />

</div>
