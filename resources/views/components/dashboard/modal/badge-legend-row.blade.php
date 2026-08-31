@props(['item'])

@php
    $chipClasses = match ($item['tone']) {
        'sapphire' => 'bg-[var(--tool-sapphire-bg)] text-[var(--tool-sapphire-color)]',
        'gold' => 'bg-[var(--tool-gold-bg)] text-[var(--tool-gold-color)]',
        'amethyst' => 'bg-[var(--tool-amethyst-bg)] text-[var(--tool-amethyst-color)]',
        'sage' => 'bg-[var(--tool-sage-bg)] text-[var(--tool-sage-color)]',
    };
@endphp

<div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
        <span class="material-symbols-rounded text-[16px]">{{ $item['icon'] }}</span>
    </div>
    <div class="min-w-0">
        <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $item['label'] }}</p>
        <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">
            <span class="font-bold">روشن می‌شود:</span> {{ $item['lights'] }}
            <span class="mx-1 opacity-40">·</span>
            <span class="font-bold">خاموش می‌شود:</span> {{ $item['clears'] }}
            @isset($item['surface'])
                <span class="mx-1 opacity-40">·</span>
                <span class="font-bold">محل نمایش:</span> {{ $item['surface'] }}
            @endisset
        </p>
    </div>
</div>
