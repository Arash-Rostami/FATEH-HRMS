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
    class="min-h-screen bg-[var(--md-sys-color-background)] text-[var(--md-sys-color-on-background)] transition-colors duration-500 overflow-x-hidden selection:bg-[var(--md-sys-color-primary)]/30 selection:text-[var(--md-sys-color-on-surface)]">

<div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
    <div
        class="absolute top-[-10%] right-[-10%] w-[50vw] h-[50vw] rounded-full bg-[var(--md-sys-color-primary-container)] opacity-30 blur-[120px] transition-colors duration-700"></div>
    <div
        class="absolute bottom-[-10%] left-[-10%] w-[60vw] h-[60vw] rounded-full bg-[var(--md-sys-color-tertiary-container)] opacity-30 blur-[100px] transition-colors duration-700"></div>
    <div
        class="absolute inset-0 opacity-[0.03] dark:opacity-[0.05] bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIj48ZmlsdGVyIGlkPSJuIj48ZmVUdXJidWxlbmNlIHR5cGU9ImZyYWN0YWxOb2lzZSIgYmFzZUZyZXF1ZW5jeT0iMC42NSIgbnVtT2N0YXZlcz0iMyIgc3RpdGNoVGlsZXM9InN0aXRjaCIvPjwvZmlsdGVyPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbHRlcj0idXJsKCNuKSIgb3BhY2l0eT0iMC40Ii8+PC9zdmc+')]"></div>
</div>

<div class="relative min-h-screen flex flex-col lg:flex-row z-10">

    <div class="w-full lg:w-1/2 flex flex-col items-center justify-center p-6 lg:p-12 xl:p-24 relative">
        <div class="w-full max-w-[600px]">
            {{ $slot }}
        </div>
    </div>

    <div class="hidden lg:flex lg:w-1/2 h-screen sticky top-0 bg-[var(--md-sys-color-surface-container-high)] border-r border-[var(--md-sys-color-outline-variant)]/40 items-center justify-center overflow-hidden">

        <div class="absolute w-[80%] h-[80%] rounded-full bg-[var(--md-sys-color-surface-container-highest)]/50 blur-3xl"></div>

        <div class="relative w-full max-w-[85%] p-8 z-10 dark:hidden">
            <img src="{{ Vite::asset('resources/assets/img/mining.jpg') }}"
                 alt="Mining Operations Light"
                 class="w-full h-auto object-contain rounded-2xl drop-shadow-2xl transition-transform duration-700 hover:scale-[1.02]"/>
        </div>

        <div class="relative w-full max-w-[85%] p-8 z-10 hidden dark:block">
            <img src="{{ Vite::asset('resources/assets/img/mining.svg') }}"
                 alt="Mining Operations Dark"
                 class="w-full h-auto object-contain rounded-2xl drop-shadow-2xl transition-transform duration-700 hover:scale-[1.02]"/>
        </div>

    </div>

</div>

<div class="fixed top-6 left-6 z-50 flex flex-col gap-3" x-data="{ open: false }">
    <div
        class="glass-panel rounded-2xl p-1.5 flex flex-col gap-2 transition-all duration-300 shadow-xl bg-[var(--md-sys-color-surface)]/80 backdrop-blur-xl border border-[var(--md-sys-color-outline-variant)]">

        <button @click="window.ThemeManager.toggleMode(); isDark = !isDark"
                class="w-10 h-10 rounded-xl flex items-center justify-center transition-all cursor-pointer hover:bg-[var(--md-sys-color-secondary-container)] hover:text-[var(--md-sys-color-on-secondary-container)] active:scale-90 text-[var(--md-sys-color-on-surface-variant)]">
            <span
                class="material-symbols-rounded text-[24px] transition-transform duration-500 rotate-0 dark:rotate-180"
                x-text="isDark ? 'dark_mode' : 'light_mode'"></span>
        </button>

        <div class="w-full h-[1px] bg-[var(--md-sys-color-outline-variant)]/50"></div>

        <button @click="open = !open"
                class="w-10 h-10 rounded-xl flex items-center justify-center cursor-pointer transition-all hover:bg-[var(--md-sys-color-secondary-container)] hover:text-[var(--md-sys-color-on-secondary-container)] active:scale-90"
                :class="{ 'text-[var(--md-sys-color-primary)]': open, 'text-[var(--md-sys-color-on-surface-variant)]': !open }">
            <span class="material-symbols-rounded text-[24px]">palette</span>
        </button>
    </div>

    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="absolute left-14 top-0 p-2.5 rounded-2xl flex flex-col gap-2 bg-[var(--md-sys-color-surface)]/90 backdrop-blur-xl border border-[var(--md-sys-color-outline-variant)] shadow-xl">
        <template x-for="color in $store.theme.colors" :key="color.name">
            <button :title="color.title"
                    @click="$store.theme.set(color.name)"
                    class="w-8 h-8 rounded-full border border-[var(--md-sys-color-outline)]/20 cursor-pointer hover:scale-110 transition-transform shadow-sm relative group"
                    :style="`background: ${color.color}`">
                <div x-show="$store.theme.current === color.name"
                     class="absolute inset-0 flex items-center justify-center">
                    <span class="w-1.5 h-1.5 rounded-full bg-white shadow-sm"></span>
                </div>
            </button>
        </template>
    </div>
</div>

@livewireScripts
</body>
</html>
