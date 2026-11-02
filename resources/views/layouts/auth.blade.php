@php
    $videos = collect(config('app.videos', []))
        ->map(fn ($path) => asset($path))
        ->values()
        ->all();
@endphp

<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full antialiased" x-data="authBackground(@js($videos))">
<head>
    <script>window.__defaultTheme = @json(config('app.theme_palette'));</script>
    <x-dashboard.meta-tags />
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/auth.js'])
    @livewireStyles
</head>

<body class="antialiased min-h-screen overflow-hidden text-gray-900 transition-colors duration-500 dark:text-white selection:bg-[var(--md-sys-color-primary)]/30 selection:text-[var(--md-sys-color-primary)]">
<x-auth.background-layer />

<div class="relative z-10 flex min-h-screen w-full flex-col lg:flex-row">
    <x-auth.brand-panel />

    <div class="relative z-20 order-2 flex min-h-screen w-full flex-col justify-center p-4 transition-all duration-500 sm:p-8 lg:flex-1 lg:p-16 xl:p-24">
        <x-auth.toolbar />

        <div class="relative z-30 my-auto flex w-full max-w-[650px] flex-col items-center justify-center opacity-90">
            <div class="group w-full">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>

<x-auth.logo />

@livewireScripts
<x-service-worker />
</body>
</html>
