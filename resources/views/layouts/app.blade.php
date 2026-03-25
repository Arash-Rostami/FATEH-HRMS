<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      class="h-full antialiased">
<head>
    <x-dashboard.meta-tags/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>
<body
    class="antialiased container-scrollbar custom-scrollbar min-h-screen bg-[var(--md-sys-color-background)] text-[var(--md-sys-color-on-background)] transition-colors duration-500">
@unless(View::hasSection('minimal_layout'))
    <x-dashboard.header.scrollable/>
@endunless


@isset($slot)
    {{ $slot }}
@else
    @yield('content')
@endisset

@include('components.dashboard.tools.calculator')
@include('components.dashboard.tools.stopwatch')

@unless(View::hasSection('minimal_layout'))
    <x-dashboard.modal.toast/>
    <x-dashboard.footer.occasion/>

    <x-dashboard.footer.main/>
@endunless

@livewireScripts
<x-service-worker/>
</body>
</html>
