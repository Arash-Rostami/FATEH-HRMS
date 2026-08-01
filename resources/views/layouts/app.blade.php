<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      class="h-full antialiased">
<head>
    <script src="{{ asset('js/mode-manager.js') }}"></script>
    <x-dashboard.meta-tags/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @filamentStyles
    @livewireStyles
</head>
<body
    class="antialiased container-scrollbar custom-scrollbar min-h-screen flex flex-col bg-[var(--md-sys-color-background)] text-[var(--md-sys-color-on-background)] transition-colors duration-500">
<div class="loading-line"></div>

@unless(View::hasSection('minimal_layout'))
    <x-dashboard.header/>
    @livewire(\App\Livewire\Dashboard\Countdown::class)
@endunless

<div id="content-shell">
    @isset($slot)
        {{ $slot }}
    @else
        @yield('content')
    @endisset
</div>



@unless(View::hasSection('minimal_layout'))
    <x-dashboard.global/>
    <x-dashboard.footer/>
@endunless


@filamentScripts
@livewireScripts
@livewire(\App\Livewire\Dashboard\UnreadNotifications::class)
</body>
</html>
