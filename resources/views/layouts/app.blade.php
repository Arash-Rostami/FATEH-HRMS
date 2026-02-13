<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-dashboard.meta-tags/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>
<body class="antialiased min-h-screen bg-gray-50 text-gray-800">


{{ $slot }}


@livewireScripts
</body>
</html>
