@props([
    'image' => null,
    'existingImage' => null,
    'icon' => 'person',
    'iconSize' => 'text-5xl',
    'alt' => 'profile-image',
    'inactive' => false,
])

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
