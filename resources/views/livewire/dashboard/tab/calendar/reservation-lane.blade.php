@php
    $reservations = $reservations ?? [];
    $span = $span ?? 1;
@endphp
@if(!empty($reservations))
    <div
        @class([
            'col-span-1 row-start-1 flex items-center gap-2 px-3 py-1.5 rounded-lg bg-[color-mix(in_srgb,var(--tool-sage-bg,var(--md-sys-color-tertiary-container))_70%,transparent)] border border-[color-mix(in_srgb,var(--tool-sage-color,var(--md-sys-color-tertiary))_40%,transparent)] text-[var(--tool-sage-color,var(--md-sys-color-on-tertiary-container))]',
        ])
        style="grid-column: span {{ $span }} / span {{ $span }}"
    >
        <span class="material-symbols-rounded text-[16px] shrink-0" style="font-variation-settings: 'FILL' 1;">event_seat</span>
        <div class="flex flex-col min-w-0">
            @foreach($reservations as $res)
                <span wire:key="res-{{ $res['id'] }}" class="truncate text-[11px] font-bold">{{ $res['title'] }}</span>
            @endforeach
        </div>
    </div>
@endif