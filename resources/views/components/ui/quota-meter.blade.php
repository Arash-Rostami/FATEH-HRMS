@props([
    'label',
    'used',
    'cap',
    'pct',
    'tone',
    'icon' => 'stacks',
    'warn' => null,
    'warnKey' => null,
    'barKey' => null,
    'aria' => null,
])

<div {{ $attributes }}>
    <div class="flex items-center gap-2 mb-1.5">
        @if($icon)
            <span class="material-symbols-rounded text-[16px] {{ $tone['text'] }}">{{ $icon }}</span>
        @endif
        <span class="text-[11px] font-semibold {{ $tone['text'] }}">
            {{ $label }}: {{ convertToPersian((string) $used) }} / {{ convertToPersian((string) $cap) }}
        </span>
    </div>
    <div @if($barKey) wire:key="{{ $barKey }}" @endif @if($aria) role="progressbar" aria-label="{{ $aria }}"
         aria-valuenow="{{ $used }}" aria-valuemin="0" aria-valuemax="{{ $cap }}" @endif
         class="h-1.5 w-full rounded-full bg-[var(--md-sys-color-surface-variant)] overflow-hidden">
        <div class="h-full rounded-full transition-all duration-500 {{ $tone['fill'] }}" style="width: {{ $pct }}%"></div>
    </div>
    @if($warn)
        <p @if($warnKey) wire:key="{{ $warnKey }}" @endif class="mt-1.5 text-[11px] font-semibold {{ $tone['text'] }}">{{ $warn }}</p>
    @endif
    {{ $slot }}
</div>
