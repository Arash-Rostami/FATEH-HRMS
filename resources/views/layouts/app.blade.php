<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      class="h-full antialiased">
<head>
    <x-dashboard.meta-tags/>

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/core/filament.css'])
    @livewireStyles
</head>
<body
    class="antialiased container-scrollbar custom-scrollbar min-h-screen bg-[var(--md-sys-color-background)] text-[var(--md-sys-color-on-background)] transition-colors duration-500">
<div class="loading-line"></div>

@unless(View::hasSection('minimal_layout'))
    <x-dashboard.header/>
@endunless


@isset($slot)
    {{ $slot }}
@else
    @yield('content')
@endisset



@unless(View::hasSection('minimal_layout'))
    <x-dashboard.global/>
    <x-dashboard.footer/>
@endunless

@livewireScripts
@livewire('notifications')
</body>
</html>
