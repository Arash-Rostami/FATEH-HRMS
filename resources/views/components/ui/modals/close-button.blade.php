@props([
    'close' => 'show = false',
    'label' => 'بستن',
    'tone' => 'neutral',
    'size' => 'md',
])

<button type="button" x-on:click="{!! $close !!}" title="{{ $label }}" aria-label="{{ $label }}"
    {{ $attributes->merge(['class' =>
        'group/close flex items-center justify-center shrink-0 active:scale-[0.92] transition-all duration-200 ease-out focus:outline-none focus:ring-2 '
        . ($size === 'sm' ? 'w-7 h-7 rounded-lg' : ($size === 'lg' ? 'w-10 h-10 rounded-xl' : 'w-8 h-8 sm:w-9 sm:h-9 rounded-xl'))
        . ' '
        . match ($tone) {
            'on-primary' => 'bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_15%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_25%,transparent)] text-[var(--md-sys-color-on-primary)] border border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_20%,transparent)] focus:ring-[color-mix(in_srgb,var(--md-sys-color-on-primary)_40%,transparent)]',
            'on-dark' => 'bg-black/55 hover:bg-black/75 text-white focus:ring-white/40',
            default => 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_12%,transparent)] hover:text-[var(--md-sys-color-error)] focus:ring-[color-mix(in_srgb,var(--md-sys-color-error)_30%,transparent)]',
        }
    ]) }}>
    <span class="sr-only">{{ $label }}</span>
    <span class="material-symbols-rounded group-hover/close:rotate-90 transition-transform duration-300 ease-out {{ $size === 'sm' ? 'text-[16px]' : ($size === 'lg' ? 'text-2xl' : 'text-[18px] sm:text-xl') }}">close</span>
</button>
