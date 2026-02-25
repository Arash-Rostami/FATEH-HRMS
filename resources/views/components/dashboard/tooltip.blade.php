@props(['text' => '', 'position' => 'bottom'])

@php
    $positionClasses = match($position) {
        'left' => 'right-[calc(100%+12px)] top-1/2 -translate-y-1/2 translate-x-2 group-hover:translate-x-0 origin-right',
        'right' => 'left-[calc(100%+12px)] top-1/2 -translate-y-1/2 -translate-x-2 group-hover:translate-x-0 origin-left',
        'top' => 'bottom-[calc(100%+12px)] left-1/2 -translate-x-1/2 translate-y-2 group-hover:translate-y-0 origin-bottom',
        'bottom' => 'top-[calc(100%+12px)] left-1/2 -translate-x-1/2 -translate-y-2 group-hover:translate-y-0 origin-top',
        default => 'top-[calc(100%+12px)] left-1/2 -translate-x-1/2 -translate-y-2 group-hover:translate-y-0 origin-top',
    };
@endphp

<span
    {{ $attributes->merge(['class' => "absolute $positionClasses bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] text-[12px] font-medium tracking-wide px-3 py-1.5 rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-[opacity,visibility,transform] duration-300 ease-out whitespace-nowrap z-[9999] shadow-[0_4px_6px_-1px_rgba(0,0,0,0.1),0_2px_4px_-1px_rgba(0,0,0,0.06)] border border-[var(--md-sys-color-outline)]/10 pointer-events-none"]) }}
    role="tooltip"
>
    {{ $text }}
</span>
