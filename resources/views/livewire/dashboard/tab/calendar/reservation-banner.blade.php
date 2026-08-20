@if(!empty($reservations))
    <div class="grid gap-1" style="{{ $columnsStyle }}">
        <div></div>
        <div @if($gridSpan) style="{{ $gridSpan }}" @endif class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-[color-mix(in_srgb,var(--tool-sage-bg,var(--md-sys-color-tertiary-container))_70%,transparent)] border border-[color-mix(in_srgb,var(--tool-sage-color,var(--md-sys-color-tertiary))_40%,transparent)] text-[var(--tool-sage-color,var(--md-sys-color-on-tertiary-container))]">
            <span class="material-symbols-rounded text-[16px] shrink-0" style="font-variation-settings: 'FILL' 1;">event_seat</span>
            <div class="flex flex-col min-w-0">
                @foreach($reservations as $res)
                    <span wire:key="{{ $keyPrefix }}-{{ $res['id'] }}" class="truncate text-[11px] font-bold">{{ $res['title'] }}</span>
                @endforeach
            </div>
        </div>
    </div>
@endif