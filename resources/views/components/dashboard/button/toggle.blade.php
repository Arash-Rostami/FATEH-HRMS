@props([
    'checked'      => false,
    'alpine'       => false,
    'alpineState'  => null,
    'icon'         => null,
    'title'        => '',
    'description'  => null,
    'xText'        => null,
    'name'         => null,
    'bordered'     => false,
])

@php
    $isDetailed = $icon !== null;

    $defaultClasses = $isDetailed
        ? 'w-full flex items-center gap-4 p-5 text-right transition-all hover:brightness-95'
        : 'flex items-center gap-3 px-4 py-2 transition-all hover:brightness-95 active:scale-[0.98]';

    if (!$isDetailed && $bordered) {
        $defaultClasses .= ' rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)]';
    }
@endphp

<button
    type="button"
    {{ $attributes->class([$defaultClasses]) }}
    @if(!$alpine && $name)
        wire:key="toggle-{{ $name }}"
    @endif
>
    {{-- Left content --}}
    @if ($isDetailed)
        <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
            <span class="material-symbols-rounded text-base">{{ $icon }}</span>
        </div>

        <div class="flex-1 text-right">
            <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">
                {{ $title }}
            </p>
            @if ($description)
                <p class="text-[11px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)]">
                    {{ $description }}
                </p>
            @endif
        </div>
    @else
        <span
            class="text-sm font-bold text-[var(--md-sys-color-on-surface)]"
            @if ($xText)
                x-text="{{ $xText }}"
            @endif
        >
            @if (!$xText)
                {{ $title }}
            @endif
        </span>
    @endif

    <div
        @if($alpine && $alpineState)
            class="relative w-12 h-6 rounded-full shrink-0 transition-all duration-200"
        :class="{
                'bg-[var(--md-sys-color-primary)]': {{ $alpineState }},
                'bg-[var(--md-sys-color-surface-variant)]': !{{ $alpineState }}
            }"
    @else
        @class([
            'relative w-12 h-6 rounded-full shrink-0 transition-all duration-200',
            'bg-[var(--md-sys-color-primary)]' => $checked,
            'bg-[var(--md-sys-color-surface-variant)]' => !$checked,
        ])
        @endif
    >
        <div
            @if($alpine && $alpineState)
                class="absolute top-1 w-4 h-4 rounded-full shadow-sm transition-all duration-200"
            :class="{
                    'right-1 bg-[var(--md-sys-color-on-primary)]': {{ $alpineState }},
                    'left-1 bg-[var(--md-sys-color-on-surface-variant)]': !{{ $alpineState }}
                }"
        @else
            @class([
                'absolute top-1 w-4 h-4 rounded-full shadow-sm transition-all duration-200',
                'right-1 bg-[var(--md-sys-color-on-primary)]' => $checked,
                'left-1 bg-[var(--md-sys-color-on-surface-variant)]' => !$checked,
            ])
            @endif
        ></div>
    </div>
</button>
