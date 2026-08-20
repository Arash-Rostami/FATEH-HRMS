@php $iconByType = $iconByType ?? []; @endphp
<div wire:key="{{ $keyPrefix }}-{{ $entry['id'] }}" @class([
    'flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-bold truncate',
    'bg-[color-mix(in_srgb,var(--md-sys-color-error-container)_50%,transparent)] text-[var(--md-sys-color-error)]' => $entry['type'] === 'holiday',
    'bg-[color-mix(in_srgb,var(--tool-amethyst-bg,var(--md-sys-color-tertiary-container))_70%,transparent)] text-[var(--tool-amethyst-color,var(--md-sys-color-on-tertiary-container))]' => $entry['type'] === 'birthday',
    'bg-[color-mix(in_srgb,var(--tool-gold-bg,var(--md-sys-color-secondary-container))_70%,transparent)] text-[var(--tool-gold-color,var(--md-sys-color-on-secondary-container))]' => $entry['type'] === 'anniversary',
])>
    <span class="material-symbols-rounded text-[12px] shrink-0" style="font-variation-settings: 'FILL' 1;">{{ $iconByType[$entry['type']] ?? '' }}</span>
    <span class="truncate">{{ $entry['title'] }}</span>
</div>