<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full antialiased"
      x-data="{
          theme: localStorage.getItem('user-theme') || 'default',
          isDark: document.documentElement.classList.contains('dark')
      }">
<head>
    <x-dashboard.meta-tags/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="antialiased h-screen overflow-hidden bg-[var(--md-sys-color-background)] text-[var(--md-sys-color-on-background)] transition-colors duration-500 selection:bg-[var(--md-sys-color-primary)]/30 selection:text-[var(--md-sys-color-on-surface)] font-yekan relative">

    <!-- 3D Particle Network Container (Full Screen Background) -->
    <div id="auth-canvas-container" class="fixed inset-0 z-0 pointer-events-auto"></div>

    <!-- Gradient Overlay for Depth -->
    <div class="fixed inset-0 z-0 bg-gradient-to-br from-[var(--md-sys-color-surface-container-lowest)]/80 via-transparent to-[var(--md-sys-color-primary-container)]/10 pointer-events-none"></div>

    <div class="relative z-10 flex h-full w-full pointer-events-none">

        <!-- RIGHT SIDE: Form Section (40%) -->
        <div class="w-full lg:w-[40%] relative flex flex-col items-center justify-center p-6 lg:p-12 pointer-events-auto bg-[var(--md-sys-color-surface)]/40 backdrop-blur-md border-l border-[var(--md-sys-color-outline-variant)]/20 shadow-2xl overflow-y-auto custom-scrollbar">

            <!-- Theme Toggle Pill (Absolute Top-Right of Form Area) -->
            <div class="absolute top-6 right-6 z-50">
                 <div class="flex items-center gap-1 bg-[var(--md-sys-color-surface-container)]/80 backdrop-blur-md border border-[var(--md-sys-color-outline-variant)]/40 rounded-full p-1 shadow-lg transition-transform hover:scale-105"
                      x-data="{ open: false }">

                    <!-- Dark Mode Toggle -->
                    <button @click="window.ThemeManager.toggleMode(); isDark = !isDark"
                            class="w-9 h-9 rounded-full flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-secondary-container)] hover:text-[var(--md-sys-color-on-secondary-container)] transition-colors"
                            title="Toggle Dark Mode">
                        <span class="material-symbols-rounded text-[20px] transition-transform duration-500"
                              :class="isDark ? 'rotate-180' : 'rotate-0'"
                              x-text="isDark ? 'dark_mode' : 'light_mode'"></span>
                    </button>

                    <div class="w-[1px] h-5 bg-[var(--md-sys-color-outline-variant)]/50"></div>

                    <!-- Palette Toggle -->
                    <div class="relative">
                        <button @click="open = !open"
                                class="w-9 h-9 rounded-full flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-secondary-container)] hover:text-[var(--md-sys-color-on-secondary-container)] transition-colors"
                                :class="open ? 'text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-secondary-container)]' : ''"
                                title="Change Theme Color">
                            <span class="material-symbols-rounded text-[20px]">palette</span>
                        </button>

                        <!-- Color Palette Dropdown -->
                        <div x-show="open" x-cloak
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute top-full right-0 mt-3 p-3 grid grid-cols-3 gap-2 bg-[var(--md-sys-color-surface-container-high)]/90 backdrop-blur-xl border border-[var(--md-sys-color-outline-variant)]/40 rounded-2xl shadow-xl w-[140px] z-50">
                            <template x-for="color in $store.theme.colors" :key="color.name">
                                <button :title="color.title"
                                        @click="$store.theme.set(color.name)"
                                        class="w-8 h-8 rounded-full border border-white/10 hover:scale-110 transition-transform relative"
                                        :style="`background: ${color.color}`">
                                    <div x-show="$store.theme.current === color.name"
                                         class="absolute inset-0 flex items-center justify-center">
                                        <span class="w-2 h-2 rounded-full bg-white shadow-sm"></span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                 </div>
            </div>

            <!-- Glass Card Container for Form -->
            <div class="w-full max-w-[420px] relative animate-fade">
                <!-- Floating Effect Background Blob -->
                <div class="absolute -top-20 -left-20 w-64 h-64 bg-[var(--md-sys-color-primary)]/20 rounded-full blur-[80px] animate-pulse-glow pointer-events-none"></div>
                <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-[var(--md-sys-color-tertiary)]/20 rounded-full blur-[80px] animate-pulse-glow pointer-events-none" style="animation-delay: 1s;"></div>

                <!-- Actual Form Card -->
                <div class="relative glass-panel bg-[var(--md-sys-color-surface)]/60 backdrop-blur-[24px] rounded-[2rem] border border-[var(--md-sys-color-outline-variant)]/30 shadow-[0_8px_32px_rgba(0,0,0,0.08)] p-8 lg:p-10 overflow-hidden group hover:shadow-[0_16px_48px_rgba(var(--md-sys-color-primary-rgb),0.15)] transition-all duration-500 animate-float">

                    <!-- Decorative Top Line -->
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-[var(--md-sys-color-primary)] to-transparent opacity-50"></div>

                    {{ $slot }}
                </div>

                <!-- Footer/Copyright -->
                <div class="mt-8 text-center text-[var(--md-sys-color-outline)] text-xs font-medium opacity-60">
                    &copy; {{ date('Y') }} HRMS Enterprise. All rights reserved.
                </div>
            </div>
        </div>

        <!-- LEFT SIDE: Visuals Section (60%) -->
        <div class="hidden lg:flex lg:w-[60%] relative h-full items-end justify-start p-16 pointer-events-none">
            <!-- Content Overlay -->
            <div class="max-w-2xl relative z-10">
                <!-- Text Decoration Line -->
                <div class="w-20 h-1 bg-[var(--md-sys-color-primary)] mb-8 rounded-full animate-slide-right"></div>

                <h1 class="text-5xl lg:text-7xl font-bold tracking-tight text-[var(--md-sys-color-on-surface)] mb-6 leading-tight animate-slide-up drop-shadow-lg" style="font-family: 'Yekan', sans-serif;">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--md-sys-color-primary)] to-[var(--md-sys-color-tertiary)]">Future</span> of<br/>
                    Enterprise Management
                </h1>

                <p class="text-xl text-[var(--md-sys-color-on-surface-variant)]/90 max-w-lg leading-relaxed animate-fade drop-shadow-md" style="animation-delay: 0.3s;">
                    Experience the next generation of HRMS. seamless, intelligent, and designed for tomorrow.
                </p>
            </div>
        </div>

    </div>

    @livewireScripts
</body>
</html>
