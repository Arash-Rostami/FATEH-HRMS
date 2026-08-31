@props([
    'type'    => 'button',
    'variant' => 'primary',
    'icon'    => null,
    'loading' => null,
    'size'    => null,
])

@php
    $baseClasses = match($variant) {
        'primary'   => 'md3-btn',
        'secondary' => 'md3-btn-outlined',
        'danger'    => 'bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)] md3-btn hover:brightness-110 shadow-red-500/30',
        'ghost'     => 'bg-transparent hover:bg-[var(--md-sys-color-on-surface)]/10 text-[var(--md-sys-color-on-surface)] rounded-xl px-4 h-10',
        'tonal'     => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] rounded-xl h-10 px-4 text-sm font-bold',
        default     => 'md3-btn',
    };

    $isIcon = $size === 'icon';
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => "relative inline-flex items-center justify-center transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed transform active:scale-95 $baseClasses" . ($isIcon ? ' w-10 h-10 p-0 rounded-xl gap-0' : ' gap-2')
    ]) }}
>
    @if($loading)
        <svg wire:loading wire:target="{{ $loading }}" class="animate-spin h-4 w-4 text-current shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @endif

    @if($icon)
        <span
            @if($loading) wire:loading.remove wire:target="{{ $loading }}" @endif
        class="material-symbols-rounded text-[20px] leading-none shrink-0"
        >
            {{ $icon }}
        </span>
    @endif

    {{ $slot }}
</button>
