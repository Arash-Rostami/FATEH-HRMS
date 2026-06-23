@props([
    'image' => null,
    'existingImage' => null,
    'icon' => 'person',
    'iconSize' => 'text-5xl',
    'alt' => 'profile-image',
])

@if ($image)
    <img
        loading="lazy"
        decoding="async"
        alt="{{ $alt }}"
        src="{{ $image->temporaryUrl() }}"
        {{ $attributes->merge(['class' => 'w-full h-full object-cover fade-in rounded-md']) }}
    >
@elseif($existingImage)
    <img
        loading="lazy"
        decoding="async"
        alt="{{ $alt }}"
        src="{{ $existingImage }}"
        {{ $attributes->merge(['class' => 'w-full h-full object-cover fade-in rounded-md']) }}
    >
@else
    <div {{ $attributes->merge(['class' => 'w-full h-full bg-[var(--md-sys-color-surface-variant)] flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)]']) }}>
        <span class="material-symbols-rounded {{ $iconSize }} fade-in">{{ $icon }}</span>
    </div>
@endif
