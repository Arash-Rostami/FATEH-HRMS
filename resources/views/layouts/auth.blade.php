<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full antialiased"
      x-data="{
          theme: localStorage.getItem('user-theme') || 'default',
          isDark: document.documentElement.classList.contains('dark')
      }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col items-center justify-center bg-[var(--md-sys-color-background)] text-[var(--md-sys-color-on-background)] transition-colors duration-500 overflow-x-hidden py-6 selection:bg-[var(--md-sys-color-primary)]/30 selection:text-[var(--md-sys-color-on-surface)]">

<div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
    <div class="absolute top-[-10%] right-[-10%] w-[50vw] h-[50vw] rounded-full bg-[var(--md-sys-color-primary-container)] opacity-40 blur-[120px] mix-blend-multiply dark:mix-blend-screen transition-colors duration-700"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-[60vw] h-[60vw] rounded-full bg-[var(--md-sys-color-tertiary-container)] opacity-40 blur-[100px] mix-blend-multiply dark:mix-blend-screen transition-colors duration-700"></div>
    <div class="absolute inset-0 opacity-[0.03] dark:opacity-[0.05] bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIj48ZmlsdGVyIGlkPSJuIj48ZmVUdXJidWxlbmNlIHR5cGU9ImZyYWN0YWxOb2lzZSIgYmFzZUZyZXF1ZW5jeT0iMC42NSIgbnVtT2N0YXZlcz0iMyIgc3RpdGNoVGlsZXM9InN0aXRjaCIvPjwvZmlsdGVyPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbHRlcj0idXJsKCNuKSIgb3BhY2l0eT0iMC40Ii8+PC9zdmc+')]"></div>
</div>

<div class="fixed top-6 left-6 z-50 flex flex-col gap-3" x-data="{ open: false }">
    <div class="glass-panel rounded-2xl p-1.5 flex flex-col gap-2 transition-all duration-300 shadow-lg">
        <button @click="window.ThemeManager.toggleMode(); isDark = !isDark" class="w-10 h-10 rounded-xl flex items-center justify-center transition-all hover:bg-[var(--md-sys-color-on-surface)]/10 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]">
            <span class="material-symbols-rounded text-[24px] transition-transform duration-500 rotate-0 dark:rotate-180" x-text="isDark ? 'dark_mode' : 'light_mode'"></span>
        </button>
        <div class="w-full h-[1px] bg-[var(--md-sys-color-outline-variant)]/30"></div>
        <button @click="open = !open" class="w-10 h-10 rounded-xl flex items-center justify-center transition-all hover:bg-[var(--md-sys-color-on-surface)]/10 active:scale-90" :class="{ 'text-[var(--md-sys-color-primary)]': open, 'text-[var(--md-sys-color-on-surface-variant)]': !open }">
            <span class="material-symbols-rounded text-[24px]">palette</span>
        </button>
    </div>

    <div x-show="open" x-cloak x-transition class="glass-panel absolute left-14 top-[58px] p-2 rounded-2xl flex gap-2">
        <button @click="window.ThemeManager.setTheme('default'); theme = 'default'" class="w-8 h-8 rounded-full border-2 border-white/20" style="background: #4e5f66"></button>
        <button @click="window.ThemeManager.setTheme('blue'); theme = 'blue'" class="w-8 h-8 rounded-full border-2 border-white/20" style="background: #0061a4"></button>
        <button @click="window.ThemeManager.setTheme('ocean'); theme = 'ocean'" class="w-8 h-8 rounded-full border-2 border-white/20" style="background: #006782"></button>
        <button @click="window.ThemeManager.setTheme('forest'); theme = 'forest'" class="w-8 h-8 rounded-full border-2 border-white/20" style="background: #006e1c"></button>
        <button @click="window.ThemeManager.setTheme('sunset'); theme = 'sunset'" class="w-8 h-8 rounded-full border-2 border-white/20" style="background: #c00016"></button>
    </div>
</div>

{{ $slot }}

@livewireScripts
</body>
</html>
