@props(['scope', 'columns'])

@php
    $hiddenGetter = match ($scope) {
        'ths' => 'thsHidden',
        'reservation' => 'reservationHidden',
        default => 'hidden',
    };
@endphp

<div x-data="{ open: false }" @click.away="open = false" class="relative">
    <button type="button"
            @click="open = !open"
            title="نمایش و مخفی کردن ستون‌ها"
            :class="{ 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]': open, 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]': !open }"
            class="inline-flex items-center justify-center p-1 rounded-lg transition-colors normal-case">
        <span class="material-symbols-rounded text-[18px]">view_column</span>
        <span x-show="$store.colVisibility.{{ $hiddenGetter }}.length > 0" class="absolute -left-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-[var(--md-sys-color-error)] px-1 text-[9px] font-bold text-white" x-text="$store.colVisibility.{{ $hiddenGetter }}.length" style="display: none;"></span>
    </button>

    <div x-show="open"
         x-transition.origin
         style="display: none;"
         class="absolute left-0 top-11 z-30 w-60 rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-2 shadow-2xl">
        <div class="mb-1.5 flex items-center justify-between px-1.5">
            <span class="text-[11px] font-bold text-[var(--md-sys-color-on-surface)]">ستون‌ها</span>
            <button type="button"
                    @click="$store.colVisibility.reset(@js($scope))"
                    :disabled="$store.colVisibility.{{ $hiddenGetter }}.length === 0"
                    class="text-[10px] font-semibold text-[var(--md-sys-color-primary)] transition-opacity hover:opacity-70 disabled:opacity-40 disabled:no-underline">
                نمایش همه
            </button>
        </div>
        <div class="flex flex-col gap-0.5">
            @foreach($columns as $colKey => $colLabel)
                <button type="button" @click="$store.colVisibility.toggle('{{ $colKey }}', @js($scope))" class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-right transition-colors hover:bg-[var(--md-sys-color-surface-container-highest)]">
                    <span class="material-symbols-rounded text-[18px]" :class="$store.colVisibility.isHidden('{{ $colKey }}', @js($scope)) ? 'text-[var(--md-sys-color-outline)]' : 'text-[var(--md-sys-color-primary)]'" x-text="$store.colVisibility.isHidden('{{ $colKey }}', @js($scope)) ? 'check_box_outline_blank' : 'check_box'"></span>
                    <span class="flex-1 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $colLabel }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>
