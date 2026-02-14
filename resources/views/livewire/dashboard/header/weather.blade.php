<div class="flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/5 text-[12px] font-medium"
     title="{{ $weatherData['description'] ?? '' }}">

    @php
        $weather = $weatherData['weather'] ?? 'Clear';
        $temp = $weatherData['temperature'] ?? '--';
        $icon = 'wb_sunny';
        $color = 'text-amber-300';

        switch($weather) {
            case 'Drizzle':
            case 'Rain':
                $icon = 'rainy';
                $color = 'text-blue-300';
                break;
            case 'Thunderstorm':
                $icon = 'thunderstorm';
                $color = 'text-purple-300';
                break;
            case 'Clouds':
                $icon = 'cloud';
                $color = 'text-gray-300';
                break;
            case 'Snow':
                $icon = 'ac_unit';
                $color = 'text-cyan-200';
                break;
            case 'Clear':
            default:
                $icon = 'wb_sunny';
                $color = 'text-amber-300';
                break;
        }
    @endphp

    <span class="material-symbols-rounded text-[18px] {{ $color }} animate-pulse">{{ $icon }}</span>
    <span>{{ $temp }} °C</span>
</div>
