@php
    $temp = $weatherData['temperature'] ?? null;
    $available = is_numeric($temp);
    [$icon, $color, $label] = match ($available ? ($weatherData['weather'] ?? '') : null) {
        'Clear' => ['wb_sunny', 'text-amber-300', 'صاف'],
        'Drizzle' => ['rainy', 'text-blue-300', 'باران نم‌نم'],
        'Rain' => ['rainy', 'text-blue-300', 'بارانی'],
        'Thunderstorm' => ['thunderstorm', 'text-purple-300', 'رعدوبرق'],
        'Clouds' => ['cloud', 'text-gray-300', 'ابری'],
        'Snow' => ['ac_unit', 'text-cyan-200', 'برفی'],
        null => ['cloud', 'text-gray-300', 'وضعیت آب‌وهوا در دسترس نیست'],
        default => ['cloud', 'text-gray-300', 'دمای هوا'],
    };
    $description = $available ? $label . '، ' . convertToPersian($temp) . ' درجهٔ سانتی‌گراد' : $label;
@endphp

<div class="flex items-center gap-2 text-[12px] font-medium" title="{{ $description }}">
    <span aria-hidden="true" class="material-symbols-rounded text-[18px] {{ $color }} animate-pulse">{{ $icon }}</span>
    <span class="sr-only">{{ $description }}</span>
    <span aria-hidden="true" dir="ltr">{{ $available ? convertToPersian($temp) . ' °C' : '—' }}</span>
</div>
