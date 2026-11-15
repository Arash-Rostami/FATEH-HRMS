@props([
    'action' => null,
    'click' => null,
    'text',
    'loadingText' => null,
    'icon' => null,
    'iconSize' => 'text-lg',
    'iconHoverClass' => 'group-hover:translate-y-0.5',
    'island' => null,
    'keepPlace' => false,
    'wrap' => null,
])

@php
    $clickExpr = $island ? "\$wire.\$island('{$island}').{$action}()" : "\$wire.{$action}()";
    $clickExpr = $keepPlace ? "loadOlderKeepingPlace(() => {$clickExpr})" : ($wrap ? "{$wrap}(() => {$clickExpr})" : $clickExpr);
    $clickExpr = $click ?? $clickExpr;
    $wireAttributes = $action ? ['wire:loading.attr' => 'disabled', 'wire:target' => $action] : [];
@endphp

<x-ui.buttons.form
    variant="none"
    x-on:click="{{ $clickExpr }}"
    {{ $attributes->merge(['class' => 'group flex items-center justify-center gap-2 transition-all outline-none'] + $wireAttributes) }}
>
    <span @if($action) wire:loading.remove wire:target="{{ $action }}" @endif>{{ $text }}</span>

    @if($loadingText && $action)
        <span wire:loading wire:target="{{ $action }}">{{ $loadingText }}</span>
    @endif

    @if($icon)
        <span class="material-symbols-rounded {{ $iconSize }} {{ $iconHoverClass }} transition-transform"
              @if($action) wire:loading.remove wire:target="{{ $action }}" @endif>
            {{ $icon }}
        </span>
    @endif

    @if($action)
        <span class="material-symbols-rounded {{ $iconSize }} animate-spin"
              wire:loading
              wire:target="{{ $action }}">
            progress_activity
        </span>
    @endif
</x-ui.buttons.form>
