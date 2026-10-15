@props([
    'image' => null,
    'existingImage' => null,
    'icon' => 'person',
    'iconSize' => 'text-5xl',
    'alt' => 'profile-image',
    'inactive' => false,
    'size' => null,
    'online' => null,
    'status' => null,
    'focus' => null,
    'onlineLabel' => null,
])

@php
    $wrap = $size !== null || $online !== null || $status !== null;
    $enum = $status === null ? null : presence($status);
    $box = match ($size) {
        'xs' => 'w-6 h-6',
        'sm' => 'w-8 h-8',
        'md' => 'w-10 h-10',
        'lg' => 'w-14 h-14',
        'xl' => 'w-20 h-20',
        default => 'w-full h-full',
    };
    $dot = match ($size) {
        'xs', 'sm' => 'w-2.5 h-2.5',
        'lg' => 'w-3.5 h-3.5',
        'xl' => 'w-4 h-4',
        default => 'w-3 h-3',
    };
    $badge = match ($size) {
        'xs' => 'w-3 h-3 text-[8px]',
        'lg' => 'w-5 h-5 text-[12px]',
        'xl' => 'w-6 h-6 text-[14px]',
        default => 'w-4 h-4 text-[10px]',
    };
@endphp

@if ($wrap)
<span class="relative inline-block shrink-0 {{ $box }}">
<span class="block w-full h-full overflow-hidden rounded-[28%]">
@endif
@if ($image)
    <img
        loading="lazy"
        decoding="async"
        alt="{{ $alt }}"
        src="{{ $image->temporaryUrl() }}"
        {{ $attributes->merge(['class' => 'w-full h-full object-cover fade-in rounded-md' . ($inactive ? ' grayscale opacity-60' : '')]) }}
    >
@elseif($existingImage)
    <img
        loading="lazy"
        decoding="async"
        alt="{{ $alt }}"
        src="{{ $existingImage }}"
        {{ $attributes->merge(['class' => 'w-full h-full object-cover fade-in rounded-md' . ($inactive ? ' grayscale opacity-60' : '')]) }}
    >
@else
    <div {{ $attributes->merge(['class' => 'w-full h-full rounded-md bg-[var(--md-sys-color-surface-variant)] flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)]' . ($inactive ? ' grayscale opacity-60' : '')]) }}>
        <span class="material-symbols-rounded {{ $iconSize }} fade-in">{{ $icon }}</span>
    </div>
@endif
@if ($wrap)
</span>
@if ($online !== null && !$inactive)
<x-ui.presence-dot :online="$online" :size="$dot" :label="$onlineLabel"/>
@endif
@if ($enum && !$inactive)
<span title="{{ $enum->tip() }}{{ filled($focus) ? ' · ' . $focus : '' }}" class="absolute -bottom-1 -right-1 flex items-center justify-center rounded-md border-2 border-[var(--md-sys-color-surface)] {{ $badge }} {{ $enum->badgeBgClass() }} {{ $enum->badgeClasses() }}">
<span class="material-symbols-rounded leading-none">{{ filled($focus) ? 'self_improvement' : $enum->icon() }}</span>
</span>
@endif
</span>
@endif
