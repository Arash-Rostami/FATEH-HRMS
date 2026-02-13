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
<body class="min-h-screen bg-[var(--md-sys-color-background)] text-[var(--md-sys-color-on-background)] transition-colors duration-500 overflow-hidden flex selection:bg-[var(--md-sys-color-primary)]/30 selection:text-[var(--md-sys-color-on-surface)]">

    <!-- Background Blobs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute top-[-10%] right-[-10%] w-[50vw] h-[50vw] rounded-full bg-[var(--md-sys-color-primary-container)] opacity-30 blur-[120px] mix-blend-multiply dark:mix-blend-screen transition-colors duration-700"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[60vw] h-[60vw] rounded-full bg-[var(--md-sys-color-tertiary-container)] opacity-30 blur-[100px] mix-blend-multiply dark:mix-blend-screen transition-colors duration-700"></div>
        <div class="absolute inset-0 opacity-[0.03] dark:opacity-[0.05] bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIj48ZmlsdGVyIGlkPSJuIj48ZmVUdXJidWxlbmNlIHR5cGU9ImZyYWN0YWxOb2lzZSIgYmFzZUZyZXF1ZW5jeT0iMC42NSIgbnVtT2N0YXZlcz0iMyIgc3RpdGNoVGlsZXM9InN0aXRjaCIvPjwvZmlsdGVyPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbHRlcj0idXJsKCNuKSIgb3BhY2l0eT0iMC40Ii8+PC9zdmc+')]"></div>
    </div>

    <!-- 3-Column Layout Wrapper -->
    <div class="relative z-10 flex w-full h-screen overflow-hidden" x-data="{ sidebarOpen: false }">

        <!-- Mobile Backdrop -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition.opacity class="fixed inset-0 bg-black/50 z-40 lg:hidden glass-panel backdrop-blur-sm"></div>

        <!-- RIGHT SIDEBAR (Main Nav) -->
        <aside class="fixed inset-y-0 right-0 z-50 w-72 bg-[var(--md-sys-color-surface)] lg:bg-transparent lg:static lg:block lg:flex-shrink-0 flex flex-col glass-panel m-0 lg:m-4 lg:ml-0 lg:rounded-2xl border-l lg:border border-[var(--md-sys-color-outline-variant)]/20 shadow-2xl lg:shadow-lg transition-transform duration-300 ease-in-out"
               :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">
             <!-- Logo Area -->
             <div class="h-20 flex items-center justify-between px-6 gap-3 border-b border-[var(--md-sys-color-outline-variant)]/10">
                 <div class="flex items-center gap-3">
                     <div class="w-10 h-10 rounded-full bg-[var(--md-sys-color-primary)] flex items-center justify-center text-[var(--md-sys-color-on-primary)] shadow-md">
                         <span class="material-symbols-rounded text-2xl">security</span>
                     </div>
                     <span class="font-bold text-lg tracking-tight">ERP Gate</span>
                 </div>
                 <button @click="sidebarOpen = false" class="lg:hidden text-[var(--md-sys-color-on-surface-variant)]">
                     <span class="material-symbols-rounded text-2xl">close</span>
                 </button>
             </div>

             <!-- Nav Links -->
             <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1 custom-scrollbar">
                 <a href="{{ route('dashboard') }}" class="flex items-center gap-4 px-4 py-3 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-bold transition-all hover:shadow-md">
                     <span class="material-symbols-rounded">dashboard</span>
                     <span>{{ __('داشبورد اصلی') }}</span>
                 </a>
                 <a href="#" class="flex items-center gap-4 px-4 py-3 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/50 transition-colors font-medium">
                     <span class="material-symbols-rounded">group</span>
                     <span>{{ __('کاربران') }}</span>
                 </a>
                 <a href="#" class="flex items-center gap-4 px-4 py-3 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/50 transition-colors font-medium">
                     <span class="material-symbols-rounded">settings</span>
                     <span>{{ __('تنظیمات سیستم') }}</span>
                 </a>
             </nav>

             <!-- User Mini Profile -->
             <div class="p-4 border-t border-[var(--md-sys-color-outline-variant)]/10">
                 <div class="bg-[var(--md-sys-color-surface-variant)]/30 rounded-xl p-3 flex items-center gap-3">
                     <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[var(--md-sys-color-primary)] to-[var(--md-sys-color-tertiary)]"></div>
                     <div class="flex-1 min-w-0">
                         <p class="text-sm font-bold truncate">{{ Auth::user()->name ?? 'کاربر مهمان' }}</p>
                         <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] truncate">مدیر سیستم</p>
                     </div>
                 </div>
             </div>
        </aside>

        <!-- CENTER CONTENT (Main Area) -->
        <main class="flex-1 flex flex-col min-w-0 overflow-hidden relative w-full">
            <!-- Top Bar -->
            <header class="h-20 flex items-center justify-between px-4 lg:px-6 py-4 flex-shrink-0 gap-4">
                 <!-- Mobile Toggle -->
                 <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-xl hover:bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface)] transition-colors">
                     <span class="material-symbols-rounded text-2xl">menu</span>
                 </button>

                 <!-- Search -->
                 <div class="glass-panel rounded-full flex items-center px-4 h-12 w-full max-w-md gap-2 focus-within:ring-2 ring-[var(--md-sys-color-primary)] transition-shadow">
                     <span class="material-symbols-rounded text-[var(--md-sys-color-on-surface-variant)]">search</span>
                     <input type="text" placeholder="{{ __('جستجو در بخش‌ها...') }}" class="bg-transparent border-none outline-none w-full text-sm placeholder-[var(--md-sys-color-on-surface-variant)]/50 focus:ring-0">
                 </div>

                 <!-- Actions -->
                 <div class="flex items-center gap-2 lg:gap-3" x-data="{ openTheme: false }">
                     <button @click="window.ThemeManager.toggleMode(); isDark = !isDark" class="w-10 h-10 rounded-full glass-panel flex items-center justify-center hover:bg-[var(--md-sys-color-surface-variant)] transition-colors text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="material-symbols-rounded transition-transform duration-500" :class="isDark ? 'rotate-180' : 'rotate-0'" x-text="isDark ? 'dark_mode' : 'light_mode'"></span>
                     </button>

                     <div class="relative hidden sm:block">
                        <button @click="openTheme = !openTheme" class="w-10 h-10 rounded-full glass-panel flex items-center justify-center hover:bg-[var(--md-sys-color-surface-variant)] transition-colors text-[var(--md-sys-color-on-surface-variant)]">
                            <span class="material-symbols-rounded">palette</span>
                        </button>
                        <!-- Theme Dropdown -->
                        <div x-show="openTheme" @click.away="openTheme = false" x-transition class="absolute top-12 left-0 glass-panel p-3 rounded-2xl flex gap-2 z-50 min-w-[200px] justify-center shadow-xl">
                            <button @click="window.ThemeManager.setTheme('default'); theme = 'default'" class="w-8 h-8 rounded-full border-2 border-white/20 hover:scale-110 transition-transform" style="background: #4e5f66" title="پیش‌فرض"></button>
                            <button @click="window.ThemeManager.setTheme('blue'); theme = 'blue'" class="w-8 h-8 rounded-full border-2 border-white/20 hover:scale-110 transition-transform" style="background: #0061a4" title="آبی"></button>
                            <button @click="window.ThemeManager.setTheme('ocean'); theme = 'ocean'" class="w-8 h-8 rounded-full border-2 border-white/20 hover:scale-110 transition-transform" style="background: #006782" title="اقیانوسی"></button>
                            <button @click="window.ThemeManager.setTheme('forest'); theme = 'forest'" class="w-8 h-8 rounded-full border-2 border-white/20 hover:scale-110 transition-transform" style="background: #006e1c" title="جنگلی"></button>
                            <button @click="window.ThemeManager.setTheme('sunset'); theme = 'sunset'" class="w-8 h-8 rounded-full border-2 border-white/20 hover:scale-110 transition-transform" style="background: #c00016" title="غروب"></button>
                        </div>
                     </div>

                     <div class="hidden sm:block w-px h-8 bg-[var(--md-sys-color-outline-variant)]/30 mx-1"></div>

                     <div class="hidden sm:block">
                         <livewire:auth.logout-button />
                     </div>

                     <!-- Mobile Logout Icon -->
                     <div class="sm:hidden">
                         <livewire:auth.logout-button :icon-only="true" />
                     </div>
                 </div>
            </header>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto p-4 lg:p-6 pt-0 custom-scrollbar">
                {{ $slot }}
            </div>
        </main>

        <!-- LEFT SIDEBAR (Tools) -->
        <aside class="w-80 flex-shrink-0 flex flex-col m-4 mr-0 hidden xl:flex gap-4">
            <!-- Calendar Widget -->
            <div class="glass-panel p-6 rounded-2xl flex-1 flex flex-col gap-4">
                <h3 class="font-bold text-[var(--md-sys-color-on-surface)] flex items-center gap-2">
                    <span class="material-symbols-rounded text-[var(--md-sys-color-primary)]">calendar_month</span>
                    {{ __('تقویم کاری') }}
                </h3>
                <div class="flex-1 bg-[var(--md-sys-color-surface-variant)]/10 rounded-xl flex items-center justify-center border border-[var(--md-sys-color-outline-variant)]/10">
                    <span class="text-sm text-[var(--md-sys-color-outline)]">ویجت تقویم</span>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="glass-panel p-6 rounded-2xl h-1/3 flex flex-col gap-4">
                 <h3 class="font-bold text-[var(--md-sys-color-on-surface)] flex items-center gap-2">
                    <span class="material-symbols-rounded text-[var(--md-sys-color-tertiary)]">bolt</span>
                    {{ __('دسترسی سریع') }}
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <button class="flex flex-col items-center justify-center p-3 rounded-xl bg-[var(--md-sys-color-surface-variant)]/30 hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors gap-2 group">
                        <span class="material-symbols-rounded group-hover:scale-110 transition-transform">add_task</span>
                        <span class="text-xs font-medium">{{ __('تسک جدید') }}</span>
                    </button>
                    <button class="flex flex-col items-center justify-center p-3 rounded-xl bg-[var(--md-sys-color-surface-variant)]/30 hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors gap-2 group">
                        <span class="material-symbols-rounded group-hover:scale-110 transition-transform">mail</span>
                        <span class="text-xs font-medium">{{ __('ارسال پیام') }}</span>
                    </button>
                </div>
            </div>
        </aside>

    </div>

    @livewireScripts
</body>
</html>
