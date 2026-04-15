@props([
    'target',
    'text',
    'loadingText',
    'icon' => null,
    'iconSize' => 'text-[18px]'
])

<button type="submit"
        wire:loading.attr="disabled"
    {{ $attributes->merge(['class' => 'flex items-center justify-center gap-2 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] text-sm transition-all active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed group hover:opacity-90']) }}>

    <span wire:loading.remove wire:target="{{ $target }}">{{ $text }}</span>

    <span wire:loading wire:target="{{ $target }}">{{ $loadingText }}</span>

    @if($icon)
        <span class="material-symbols-rounded {{ $iconSize }} transform rtl:-scale-x-100 group-hover:translate-x-1 transition-transform"
              wire:loading.remove
              wire:target="{{ $target }}">
            {{ $icon }}
        </span>
    @endif

    <span class="material-symbols-rounded {{ $iconSize }} animate-spin"
          wire:loading
          wire:target="{{ $target }}">
        progress_activity
    </span>
</button>
