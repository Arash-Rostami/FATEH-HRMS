@props(['column', 'sort', 'sortDir', 'enabled' => true])

@if($enabled)
    <button type="button" wire:key="table-sort-label-{{ $column }}" wire:click="sortBy('{{ $column }}')"
            class="inline-flex items-center gap-1 transition-colors hover:text-[var(--md-sys-color-primary)] {{ $sort === $column ? 'text-[var(--md-sys-color-primary)]' : '' }}">
        <span>{{ $slot }}</span>
        <span class="material-symbols-rounded text-[14px] {{ $sort === $column ? '' : 'opacity-40' }}">{{ $sort === $column ? ($sortDir === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}</span>
    </button>
@else
    <span wire:key="table-sort-plain-{{ $column }}">{{ $slot }}</span>
@endif
