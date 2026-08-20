@php
    $hourHeight = $hourHeight ?? 60;
    $startHour = 6;
    $endHour = 24;
@endphp
<div class="flex">
    <div class="w-12 shrink-0 relative" style="height: {{ ($endHour - $startHour) * $hourHeight }}px">
        @for($h = $startHour; $h <= $endHour; $h++)
            <div class="absolute left-0 right-0 -translate-y-1/2 text-[10px] font-bold text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_70%,transparent)] tabular-nums pr-1 text-right" style="top: {{ ($h - $startHour) * $hourHeight }}px">
                {{ convertToPersian(str_pad((string) $h, 2, '0', STR_PAD_LEFT)) }}
            </div>
        @endfor
    </div>
</div>