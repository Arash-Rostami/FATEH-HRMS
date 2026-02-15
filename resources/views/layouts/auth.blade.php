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

<body
    class="antialiased min-h-screen bg-[var(--md-sys-color-background)] text-gray-900 dark:text-white selection:bg-blue-500/30 selection:text-blue-600 dark:selection:text-blue-200 overflow-hidden transition-colors duration-500 ">

<div class="flex min-h-screen w-full flex-col lg:flex-row relative">

    <!-- RIGHT SIDE: Visuals (40%) - RTL Order 1 -->
    <div class="relative hidden lg:block lg:w-[40%] h-full min-h-screen overflow-hidden order-1 lg:order-1 group">

        <!-- Background Image (Full Bleed) -->
        <div class="absolute inset-0 z-0 bg-[var(--md-sys-color-surface-container)]">
            <div
                class="absolute inset-0 w-full h-full block dark:hidden transition-transform duration-[40s] hover:scale-110 ease-linear brightness-100 mix-blend-multiply opacity-90">
                <img src="{{ Vite::asset('resources/assets/img/mining.jpg') }}"
                     alt="Mining Operations Light"
                     class="animate-fade w-full h-auto object-contain rounded-[2.5rem] transition-transform duration-700 hover:scale-[1.02]"/>
            </div>

            <div
                class="absolute inset-0 w-full h-full hidden dark:block transition-transform duration-[40s] hover:scale-110 ease-linear brightness-100 mix-blend-luminosity opacity-60">
                <img src="{{ Vite::asset('resources/assets/img/mining.svg') }}"
                     alt="Mining Operations Dark"
                     class="w-full h-auto object-contain rounded-2xl drop-shadow-2xl transition-transform duration-700 hover:scale-[1.02]"/>
            </div>

            <!-- Dynamic Theme Overlay (Added per request) -->
            <div class="absolute inset-0 bg-[var(--md-sys-color-primary)]/10 mix-blend-overlay transition-colors duration-500"></div>

            <div
                class="absolute inset-0 hidden dark:block bg-gradient-to-t from-[var(--md-sys-color-background)] via-transparent to-black/40 mix-blend-multiply transition-opacity duration-500"></div>

            <div
                class="absolute inset-0 hidden dark:block bg-blue-900/10 mix-blend-overlay transition-opacity duration-500"></div>
        </div>

        <!-- Content Overlay -->
        <div
            class="absolute bottom-0 right-0 p-16 z-20 w-full bg-gradient-to-t from-[var(--md-sys-color-background)]/90 to-transparent">
            <div
                class="max-w-2xl transform translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-700 delay-100">
                <div class="flex items-center gap-4 mb-6">
                    <div class="h-[2px] w-12 bg-blue-500"></div>
                    <span class="text-blue-300 dark:text-blue-400 tracking-[0.2em] uppercase text-sm font-bold">Enterprise System</span>
                </div>
                <h1 class="text-5xl font-black leading-tight text-white mb-4 drop-shadow-2xl">
                    Resource Planning <br/>
                    <span
                        class="text-transparent bg-clip-text bg-[var(--md-sys-color-primary-container)] ">Evolution 2026</span>
                </h1>
                <p class="text-gray-200 dark:text-gray-400 text-lg max-w-lg leading-relaxed shadow-black drop-shadow-md">
                    Advanced analytics and mining operation management for the next generation of industry leaders.
                </p>
            </div>
        </div>

        <!-- Animated Particles/Overlay Effect (CSS Only) -->
        <div
            class="absolute inset-0 z-10 opacity-10 dark:opacity-20 pointer-events-none bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48Y2lyY2xlIGN4PSIxMDAiIGN5PSIxMDAiIHI9IjEiIGZpbGw9IiNmZmYiLz48L3N2Zz4=')] [mask-image:linear-gradient(to_bottom,transparent,black)]"></div>
    </div>

    <!-- LEFT SIDE: Form (60%) - RTL Order 2 -->
    <div
        class=" w-full lg:w-[60%] flex flex-col justify-center items-center p-8 lg:p-16 xl:p-24 bg-[var(--md-sys-color-surface)] dark:bg-[#0F1218] z-20 order-2 lg:order-2 transition-colors duration-500">

        <!-- Ambient Glows -->
        <div
            class="absolute top-[-20%] right-[-10%] w-[400px] h-[400px] bg-blue-100 dark:bg-blue-600/10 rounded-full blur-[120px] pointer-events-none opacity-50 dark:opacity-100 transition-opacity duration-500"></div>
        <div
            class="absolute bottom-[-10%] left-[-20%] w-[500px] h-[500px] bg-indigo-100 dark:bg-indigo-900/20 rounded-full blur-[120px] pointer-events-none opacity-50 dark:opacity-100 transition-opacity duration-500"></div>

        <!-- Login Container -->
        <div class="w-full max-w-[650px] relative z-30">

            <!-- Glass Form Wrapper -->
            <div class=" group">
                {{ $slot }}
            </div>


        </div>

        <!-- Theme Toggle Widget (Bottom Left) -->
        <div class="fixed bottom-8 left-8 z-50 flex items-center gap-2" x-cloak>
            <div
                class="glass-panel p-1.5 rounded-full flex gap-1 bg-white/80 dark:bg-white/5 backdrop-blur-md border border-gray-200 dark:border-white/10 shadow-xl transition-all hover:bg-white hover:dark:bg-white/10">
                <button @click="window.ThemeManager.toggleMode(); isDark = !isDark"
                        class="w-9 h-9 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-blue-600 hover:dark:text-white hover:bg-gray-100 hover:dark:bg-white/10 transition-all"
                        :title="isDark ? 'Switch to Light' : 'Switch to Dark'">
                    <span class="material-symbols-rounded text-[20px]"
                          x-text="isDark ? 'dark_mode' : 'light_mode'"></span>
                </button>
                <div class="w-[1px] h-4 bg-gray-300 dark:bg-white/10 my-auto"></div>
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open"
                            class="w-9 h-9 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-blue-600 hover:dark:text-white hover:bg-gray-100 hover:dark:bg-white/10 transition-all">
                        <span class="material-symbols-rounded text-[20px]">palette</span>
                    </button>
                    <!-- Color Palette Popover -->
                    <div x-show="open" @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute bottom-12 left-0 p-2 rounded-xl bg-white dark:bg-[#1A1F2B] border border-gray-200 dark:border-white/10 shadow-2xl flex flex-col gap-2 min-w-[40px]">
                        <template x-for="color in $store.theme.colors" :key="color.name">
                            <button @click="$store.theme.set(color.name); open = false"
                                    class="w-6 h-6 rounded-full border border-gray-200 dark:border-white/10 hover:scale-110 transition-transform"
                                    :style="'background: ' + color.color"></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

@livewireScripts
</body>
</html>
