<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full antialiased" data-theme="default">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full flex flex-col items-center justify-center bg-[var(--md-sys-color-background)] text-[var(--md-sys-color-on-background)] transition-colors duration-500 overflow-hidden relative selection:bg-[var(--md-sys-color-primary)] selection:text-[var(--md-sys-color-on-primary)]">
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute top-[-20%] right-[-10%] w-[60vw] h-[60vw] rounded-full bg-[var(--md-sys-color-primary-container)] opacity-20 blur-[100px] animate-pulse-slow"></div>
        <div class="absolute bottom-[-20%] left-[-10%] w-[50vw] h-[50vw] rounded-full bg-[var(--md-sys-color-secondary-container)] opacity-20 blur-[100px] animate-pulse-slow delay-1000"></div>
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:24px_24px]"></div>
    </div>
    <div class="fixed top-6 left-6 z-50 flex gap-2">
        <button onclick="window.setTheme('default')" class="w-8 h-8 rounded-full bg-[#00639b] border-2 border-white shadow-lg transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#00639b]" title="Default Blue"></button>
        <button onclick="window.setTheme('ocean')" class="w-8 h-8 rounded-full bg-[#00668b] border-2 border-white shadow-lg transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#00668b]" title="Ocean Deep"></button>
        <button onclick="window.setTheme('forest')" class="w-8 h-8 rounded-full bg-[#2d6b2f] border-2 border-white shadow-lg transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#2d6b2f]" title="Forest Green"></button>
        <button onclick="window.setTheme('sunset')" class="w-8 h-8 rounded-full bg-[#9c4146] border-2 border-white shadow-lg transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#9c4146]" title="Sunset Red"></button>
    </div>
    <main class="relative z-10 w-full max-w-md px-4">
        <div class="mb-8 flex justify-center">
            <div class="w-16 h-16 bg-[var(--md-sys-color-primary)] rounded-2xl flex items-center justify-center text-[var(--md-sys-color-on-primary)] shadow-xl rotate-3">
                 <span class="material-symbols-rounded text-4xl">verified_user</span>
            </div>
        </div>
        <div class="backdrop-blur-xl bg-[var(--md-sys-color-surface)]/80 border border-[var(--md-sys-color-outline-variant)] shadow-[var(--md-sys-elevation-3)] rounded-[var(--md-sys-shape-corner-large)] p-8 sm:p-10 relative overflow-hidden group transition-all hover:shadow-[var(--md-sys-elevation-4)]">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-[var(--md-sys-color-primary)] to-transparent opacity-50 group-hover:opacity-100 transition-opacity"></div>
            {{ $slot }}
        </div>
        <div class="mt-8 text-center text-[var(--md-sys-color-on-surface-variant)] text-xs font-light">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </main>
    @livewireScripts
</body>
</html>
