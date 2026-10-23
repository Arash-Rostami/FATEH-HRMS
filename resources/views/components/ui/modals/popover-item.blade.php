@props(['icon', 'label', 'href' => null, 'last' => false])

@php
    $classes = 'flex w-full items-center gap-3 px-4 py-3 text-right text-sm text-[var(--md-sys-color-on-surface)] transition-colors hover:bg-[var(--md-sys-color-primary)]/10'
        . ($last ? '' : ' border-b border-[var(--md-sys-color-outline-variant)]/20');
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">{{ $icon }}</span>
        <span>{{ $label }}</span>
    </a>
@else
    <button type="button" {{ $attributes->merge(['class' => $classes]) }}>
        <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">{{ $icon }}</span>
        <span>{{ $label }}</span>
    </button>
@endif
