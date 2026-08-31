@props(['width' => 'w-full', 'height' => 'h-4'])

<div {{ $attributes->merge(['class' => "$width $height rounded-lg bg-[var(--md-sys-color-surface-variant)] animate-pulse"]) }}></div>
