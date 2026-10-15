@props(['options', 'sort', 'sortDir', 'isDefault'])

<div x-data="{ open: false }" @click.away="open = false" @keydown.escape.window="open = false" class="relative">
    <button type="button" @click="open = !open" title="مرتب‌سازی" aria-label="مرتب‌سازی" :aria-expanded="open"
            :class="{ 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]': open, 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]': !open }"
            class="inline-flex items-center justify-center p-1 rounded-lg transition-colors normal-case">
        <span class="material-symbols-rounded text-[18px] leading-none">swap_vert</span>
        @if(!$isDefault)
            <span wire:key="table-sort-dot" class="absolute -left-1 -top-1 h-2 w-2 rounded-full bg-[var(--md-sys-color-error)]"></span>
        @endif
    </button>
    <div x-show="open" x-transition.origin style="display: none;"
         class="absolute left-0 top-11 z-30 w-52 rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-2 shadow-2xl">
        <div class="mb-1.5 flex items-center justify-between px-1.5">
            <span class="text-[11px] font-bold text-[var(--md-sys-color-on-surface)]">مرتب‌سازی</span>
            <button type="button" wire:click="resetSort" @click="open = false" :disabled="@js($isDefault)"
                    class="text-[10px] font-semibold text-[var(--md-sys-color-primary)] transition-opacity hover:opacity-70 disabled:opacity-40">بازنشانی ترتیب</button>
        </div>
        <div class="flex flex-col gap-0.5">
            @foreach($options as $key => $label)
                <button type="button" wire:click="sortBy('{{ $key }}')"
                        class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-right transition-colors hover:bg-[var(--md-sys-color-surface-container-highest)] {{ $sort === $key ? 'text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)]' }}">
                    <span class="material-symbols-rounded text-[18px] leading-none">{{ $sort === $key ? ($sortDir === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}</span>
                    <span class="flex-1 text-[11px] font-medium">{{ $label }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>
