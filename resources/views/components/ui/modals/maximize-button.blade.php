@props([
    'toggle' => 'toggleMaximize()',
    'state' => 'max',
    'label' => 'بزرگ کردن',
    'labelOn' => 'کوچک کردن',
    'tone' => 'neutral',
    'size' => 'md',
    'alpineClass' => null,
])

@php
    $box = match ($size) {
        'xs' => 'p-1 rounded-lg',
        'sm' => 'w-7 h-7 rounded-lg',
        'widget' => 'size-8 rounded-lg',
        'tile' => 'h-9 w-9 rounded-xl',
        'lg' => 'w-10 h-10 rounded-xl',
        'responsive' => 'w-10 h-10 md:w-12 md:h-12 rounded-xl md:rounded-2xl',
        default => 'h-8 w-8 rounded-xl',
    };
    $icon = match ($size) {
        'xs', 'tile' => 'text-[18px] leading-none',
        'sm' => 'text-[16px] leading-none',
        'widget', 'lg' => 'text-[20px] leading-none',
        'responsive' => 'text-[20px] md:text-[24px] leading-none',
        default => 'text-base leading-none',
    };
    [$on, $off] = match ($tone) {
        'on-primary' => [
            'bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_35%,transparent)] text-[var(--md-sys-color-on-primary)]',
            'bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_15%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_25%,transparent)] text-[var(--md-sys-color-on-primary)]',
        ],
        'on-dark' => ['bg-white/90 text-black', 'bg-black/55 hover:bg-black/75 text-white'],
        default => [
            'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]',
            'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]',
        ],
    };
    $titles = "{$state} ? '{$labelOn}' : '{$label}'";
@endphp

<button type="button" x-on:click="{!! $toggle !!}" x-bind:title="{!! $titles !!}" x-bind:aria-label="{!! $titles !!}"
    x-bind:class="[{!! $alpineClass ? $alpineClass . ', ' : '' !!}{!! $state !!} ? '{{ $on }}' : '{{ $off }}']"
    {{ $attributes->merge(['class' => "group/max flex items-center justify-center shrink-0 normal-case active:scale-[0.92] transition-all duration-200 ease-out focus:outline-none {$box}"]) }}>
    <span class="sr-only" x-text="{!! $titles !!}"></span>
    <span class="material-symbols-rounded group-hover/max:scale-110 transition-transform duration-300 ease-out {{ $icon }}" aria-hidden="true" x-text="{!! $state !!} ? 'close_fullscreen' : 'open_in_full'"></span>
</button>
