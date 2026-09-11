@php($active = ($reportSort['field'] ?? null) === $field)
@php($icon = $active ? (($reportSort['dir'] ?? 'asc') === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more')
<th class="px-6 py-4">
    <button type="button" wire:click="setReportSort('{{ $field }}')" class="inline-flex items-center gap-1 hover:text-[var(--md-sys-color-on-surface)] transition-colors">
        <span>{{ $label }}</span>
        <span class="material-symbols-rounded text-[14px] @if($active) text-[var(--md-sys-color-primary)] @else text-[var(--md-sys-color-on-surface-variant)]/50 @endif">{{ $icon }}</span>
    </button>
</th>