@props([
    'image' => null,
    'existingImage' => null,
    'icon' => 'person',
    'alt' => 'profile-image',
])

@if ($image)
    <img
        loading="lazy"
        decoding="async"
        alt="{{ $alt }}"
        src="{{ $image->temporaryUrl() }}"
        {{ $attributes->merge(['class' => 'w-full h-full object-cover fade-in']) }}
    >
@elseif($existingImage)
    <img
        loading="lazy"
        decoding="async"
        alt="{{ $alt }}"
         src="{{ Storage::url($existingImage) }}"
        {{ $attributes->merge(['class' => 'w-full h-full object-cover fade-in']) }}
    >
@else
    <div {{ $attributes->merge(['class' => 'w-full h-full bg-[var(--md-sys-color-surface-variant)] flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)]']) }}>
        <span class="material-symbols-rounded text-5xl fade-in">{{ $icon }}</span>
    </div>
@endif
