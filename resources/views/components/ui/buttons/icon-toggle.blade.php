@props([
    'state',
    'icon',
    'title' => '',
])

<button
    type="button"
    @click="{{ $state }} = !{{ $state }}"
    title="{{ $title }}"
    {{ $attributes->merge(['class' => 'hidden md:flex items-center justify-center w-8 h-8 rounded-md transition-all duration-200']) }}
    :class="{{ $state }} ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/40'">
    <span class="material-symbols-rounded text-[18px]">{{ $icon }}</span>
</button>