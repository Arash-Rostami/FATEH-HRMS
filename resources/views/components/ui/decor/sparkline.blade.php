@props([
    'values' => [],
    'width' => 160,
    'height' => 32,
    'color' => 'var(--md-sys-color-primary)',
    'gap' => 2,
])

@php
    $count = count($values);
    $max = $count ? max(1, max($values)) : 1;
    $barWidth = $count ? max(1, ($width - $gap * ($count - 1)) / $count) : $width;
@endphp

<svg {{ $attributes->merge(['class' => 'shrink-0']) }} width="{{ $width }}" height="{{ $height }}" viewBox="0 0 {{ $width }} {{ $height }}">
    @foreach($values as $i => $value)
        @php
            $barHeight = max(1, ($value / $max) * $height);
            $x = $i * ($barWidth + $gap);
            $y = $height - $barHeight;
        @endphp
        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $barHeight }}" rx="1" fill="{{ $color }}">
            <title>{{ $value }}</title>
        </rect>
    @endforeach
</svg>
