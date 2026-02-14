<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      class="h-full antialiased">
<head>
    <x-dashboard.meta-tags/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>
<body class="antialiased container-scrollbar custom-scrollbar min-h-screen bg-[var(--md-sys-color-background)] text-[var(--md-sys-color-on-background)] transition-colors duration-500">
@yield('content')
@livewireScripts
</body>
</html>
