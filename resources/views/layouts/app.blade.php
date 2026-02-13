<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-dashboard.meta-tags/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>
<body class="antialiased container-scrollbar custom-scrollbar min-h-screen bg-gray-50 text-gray-800">
@yield('content')
@livewireScripts
</body>
</html>
