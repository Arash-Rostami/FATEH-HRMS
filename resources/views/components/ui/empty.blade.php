@props([
    'icon',
    'title',
    'description' => null,
    'variant' => 'list',
    'fill' => false,
    'watermark' => null,
    'animate' => false,
])

@php
    [$iconBox, $iconSize, $heading] = match ($variant) {
        'welcome' => ['p-6 rounded-3xl bg-[var(--md-sys-color-surface-container-high)]', 'text-[56px] leading-none', 'text-lg font-bold'],
        default => ['p-4 rounded-full bg-[var(--md-sys-color-surface-container-high)]', 'text-[40px] leading-none', 'text-base font-medium'],
    };
    $heightClass = $fill ? 'h-full w-full' : 'h-64';
    $rootClass = 'flex flex-col items-center justify-center text-center text-[var(--md-sys-color-on-surface-variant)] opacity-60 ' . $heightClass;
    if ($watermark !== null) {
        $rootClass .= ' relative overflow-hidden';
    }
    $contentZ = $watermark !== null ? ' relative z-10' : '';
    $iconSpanClass = 'material-symbols-rounded ' . $iconSize . ($animate ? ' animate-pulse' : '');
@endphp

<div class="{{ $rootClass }}">
    @if($watermark !== null)
        <span class="material-symbols-rounded absolute inset-0 flex items-center justify-center text-[220px] leading-none text-[var(--md-sys-color-on-surface)] opacity-[0.04] pointer-events-none select-none">{{ $watermark }}</span>
    @endif

    <div class="{{ $iconBox . $contentZ }} mb-4">
        <span class="{{ $iconSpanClass }}">{{ $icon }}</span>
    </div>

    <p class="{{ $heading . $contentZ }}">{{ $title }}</p>

    @if($description)
        <p class="text-xs mt-1 opacity-70{{ $contentZ }}">{{ $description }}</p>
    @endif

    @if(isset($slot) && trim($slot) !== '')
        <div class="mt-4 w-full{{ $contentZ }}">{{ $slot }}</div>
    @endif
</div>