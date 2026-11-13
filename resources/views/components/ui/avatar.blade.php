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
    'uid' => null,
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
        'xs' => 'w-[11px] h-[11px]',
        'sm' => 'w-[13px] h-[13px]',
        'lg' => 'w-[19px] h-[19px]',
        'xl' => 'w-6 h-6',
        default => 'w-[15px] h-[15px]',
    };
    $badgeIcon = match ($size) {
        'xs' => '!text-[7px]',
        'sm' => '!text-[8px]',
        'lg' => '!text-[11px]',
        'xl' => '!text-[14px]',
        default => '!text-[9px]',
    };
@endphp

@if ($wrap)
<span @if($uid) data-u="{{ $uid }}" @endif class="relative inline-block shrink-0 {{ $box }}">
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
<span title="{{ $enum->tip() }}{{ filled($focus) ? ' · ' . $focus : '' }}" data-presence="{{ $enum->value }}" data-focus="{{ $focus }}" style="--pb: {{ $enum->cssVar() }}" class="presence-badge absolute -bottom-0.5 -right-0.5 flex items-center justify-center rounded-[30%] data-[presence=angry]:animate-bounce {{ $badge }}">
<span class="material-symbols-rounded leading-none font-fill {{ $badgeIcon }}">{{ filled($focus) ? 'self_improvement' : $enum->icon() }}</span>
</span>
@endif
</span>
@endif
