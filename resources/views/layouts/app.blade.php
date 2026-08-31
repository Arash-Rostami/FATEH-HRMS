<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      class="h-full antialiased">
<head>
    <script src="{{ asset('js/mode-manager.js') }}"></script>
    <script src="{{ asset('js/prefs-manager.js') }}"></script>
    <x-dashboard.meta-tags/>
    <x-dashboard.module-chunks/>

    @php
        $moduleChunk = match(request()->route()?->getName()) {
            'dms' => 'dms',
            'ths' => 'ths',
            'tasks' => 'taskboard',
            'projects' => 'project',
            'channels' => 'channel',
            'contact' => 'contact',
            'reservation' => 'reservation',
            'profile' => 'profile',
            'analytics' => 'analytics',
            'energy' => 'energy',
            'tasksheet' => 'tasksheet',
            'tasksheet.shared' => 'tasksheet',
            default => null,
        };
        $viteEntries = ['resources/css/app.css', 'resources/js/app.js'];
        if ($moduleChunk) {
            $viteEntries[] = "resources/js/components/alpine/modules/{$moduleChunk}.js";
        }
    @endphp
    @vite($viteEntries)
    @filamentStyles
    @livewireStyles
</head>
<body
    class="antialiased container-scrollbar custom-scrollbar min-h-screen flex flex-col bg-[var(--md-sys-color-background)] text-[var(--md-sys-color-on-background)] transition-colors duration-500">
<div class="loading-line"></div>

@unless(View::hasSection('minimal_layout'))
    <x-dashboard.header/>
    @livewire(\App\Livewire\Dashboard\Countdown::class)
    @livewire(\App\Livewire\Dashboard\EventReminder::class)
    @livewire(\App\Livewire\Dashboard\Edge::class)
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
