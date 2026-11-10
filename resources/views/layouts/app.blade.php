@php
    use App\Livewire\Dashboard\Countdown;
    use App\Livewire\Dashboard\EventReminder;
    use App\Livewire\Dashboard\Edge;
    use App\Livewire\Dashboard\Pulse;
    use App\Livewire\Dashboard\UnreadNotifications;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      class="h-full antialiased"
      data-uid="{{ auth()->id() }}"
      data-max-scope="{{ $maxScope }}">
<head>
    <script>window.__defaultTheme = @json(config('app.theme_palette'));</script>
    <script src="{{ asset('js/mode-manager.js') }}"></script>
    <script src="{{ asset('js/prefs-manager.js') }}"></script>
    <x-dashboard.meta-tags/>
    <x-dashboard.module-chunks/>
    <script>window.__tenantBackdrop = @json($backdrop);</script>
    @if(config('app.off'))<style>{!! tenantOffCss() !!}</style>@endif
    @if($themeCss = tenantThemeCss())<style>{!! $themeCss !!}</style>@endif
    @vite($viteEntries)
    @filamentStyles
    @livewireStyles
</head>
<body
    class="antialiased container-scrollbar custom-scrollbar min-h-screen flex flex-col bg-[var(--md-sys-color-background)] text-[var(--md-sys-color-on-background)] transition-colors duration-500">
@unless($embed)
    <div class="loading-line"></div>
@endunless

@if($chrome)
    <x-dashboard.header/>
    @unless(tenantOff('calendar'))
        @livewire(Countdown::class)
        @livewire(EventReminder::class)
    @endunless
    @livewire(Edge::class)
@endif

<div id="content-shell">
    @isset($slot)
        {{ $slot }}
    @else
        @yield('content')
    @endisset
</div>

@if($chrome)
    <x-dashboard.global/>
    <x-dashboard.footer/>
@endif

@filamentScripts
@livewireScripts
@unless($embed)
    @livewire(Pulse::class)
    @livewire(UnreadNotifications::class)
@endunless
</body>
</html>
