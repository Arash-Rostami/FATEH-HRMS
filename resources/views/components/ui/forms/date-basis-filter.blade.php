@props([
    'apply',
    'clear' => null,
    'startYear' => 1300,
    'endYear' => 1410,
    'basisOptions' => null,
    'currentBasis' => null,
    'setBasis' => null,
])

<x-ui.forms.date-span :apply="$apply" :clear="$clear" :startYear="$startYear" :endYear="$endYear">
    @if($basisOptions)
        @foreach($basisOptions as $basisKey => $basisLabel)
            <button type="button"
                    x-on:click="$wire.{{ $setBasis }}('{{ $basisKey }}')"
                    @class([
                        'flex-1 rounded-lg border py-1.5 text-[11px] font-semibold transition-colors',
                        'border-transparent bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' => $currentBasis === $basisKey,
                        'border-[var(--md-sys-color-outline-variant)]/60 bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-highest)]' => $currentBasis !== $basisKey,
                    ])>
                {{ $basisLabel }}
            </button>
        @endforeach
    @endif
</x-ui.forms.date-span>
