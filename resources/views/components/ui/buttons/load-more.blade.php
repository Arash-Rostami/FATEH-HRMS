@props([
    'action',
    'text',
    'loadingText' => null,
    'icon' => null,
    'iconSize' => 'text-lg',
    'iconHoverClass' => 'group-hover:translate-y-0.5',
    'island' => null,
])

@php
    $clickExpr = $island ? "\$wire.\$island('{$island}').{$action}()" : "\$wire.{$action}()";
@endphp

<x-ui.buttons.form
    variant="none"
    x-on:click="{{ $clickExpr }}"
    wire:loading.attr="disabled"
    wire:target="{{ $action }}"
    {{ $attributes->merge(['class' => 'group flex items-center justify-center gap-2 transition-all outline-none']) }}
>
    <span wire:loading.remove wire:target="{{ $action }}">{{ $text }}</span>

    @if($loadingText)
        <span wire:loading wire:target="{{ $action }}">{{ $loadingText }}</span>
    @endif

    @if($icon)
        <span class="material-symbols-rounded {{ $iconSize }} {{ $iconHoverClass }} transition-transform"
              wire:loading.remove
              wire:target="{{ $action }}">
            {{ $icon }}
        </span>
    @endif

    <span class="material-symbols-rounded {{ $iconSize }} animate-spin"
          wire:loading
          wire:target="{{ $action }}">
        progress_activity
    </span>
</x-ui.buttons.form>
