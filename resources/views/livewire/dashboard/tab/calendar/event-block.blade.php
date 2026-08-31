@php
    $b = $presenter->eventBlockData($event);
    $locked = $b['locked'];
    $isReservation = $b['isReservation'];
    $draggable = $b['draggable'];
    $topPx = $b['topPx'];
    $heightPx = $b['heightPx'];
    $leftPct = $b['leftPct'];
    $widthPct = $b['widthPct'];
    $rangeLabel = $b['rangeLabel'];
@endphp
<div
    wire:key="pill-{{ $event['id'] }}"
    data-event-id="{{ $event['id'] }}"
    data-mtime="{{ $event['mtime'] ?? '' }}"
    x-data="{ ...calendarDrag({ eventId: @js($event['id']), locked: @js($locked || $isReservation), isOwner: @js($event['is_owner']) }) }"
    @click="!justDragged && (@js($isReservation) ? $wire.reservationHint() : $wire.editEvent({{ $event['id'] }}))"
    @revert-event-{{ $event['id'] }}.window="$el.style.top = ({{ $topPx }}) + 'px'"
    @revert-resize-{{ $event['id'] }}.window="$el.style.height = ({{ $heightPx }}) + 'px'"
    style="top: {{ $topPx }}px; height: {{ $heightPx }}px; left: calc({{ $leftPct }}% + 2px); width: calc({{ $widthPct }}% - 4px); {{ $draggable ? 'touch-action: none; cursor: grab;' : 'touch-action: auto;' }}"
    title="{{ $event['title'] }} — {{ $rangeLabel }}"
    @class([
        'group absolute rounded-lg flex flex-col justify-start gap-1.5 px-2 py-1.5 text-[11px] font-bold shadow-sm z-10 overflow-hidden select-none cursor-pointer',
        'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)] text-[var(--md-sys-color-on-primary-container)] border border-[color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' => $event['is_owner'] && !$isReservation && empty($event['private']),
        'bg-[color-mix(in_srgb,var(--tool-amethyst-bg,var(--md-sys-color-tertiary-container))_70%,transparent)] text-[var(--tool-amethyst-color,var(--md-sys-color-on-tertiary-container))] border border-[color-mix(in_srgb,var(--tool-amethyst-color,var(--md-sys-color-tertiary))_40%,transparent)]' => $event['is_owner'] && !empty($event['private']),
        'bg-[color-mix(in_srgb,var(--md-sys-color-secondary-container)_70%,transparent)] text-[var(--md-sys-color-on-secondary-container)] border border-[color-mix(in_srgb,var(--md-sys-color-secondary)_40%,transparent)]' => !$event['is_owner'] && !empty($event['is_shared']),
        'bg-[color-mix(in_srgb,var(--tool-sage-bg,var(--md-sys-color-tertiary-container))_70%,transparent)] text-[var(--tool-sage-color,var(--md-sys-color-on-tertiary-container))] border border-[color-mix(in_srgb,var(--tool-sage-color,var(--md-sys-color-tertiary))_40%,transparent)]' => $isReservation,
    ])
>
    <div class="flex items-center gap-1.5">
        <span class="material-symbols-rounded text-[14px] shrink-0" style="font-variation-settings: 'FILL' 1;">
            @if($isReservation)event_seat
            @elseif(!empty($event['private']))lock
            @elseif(!$event['is_owner'] && !empty($event['is_shared']))group
            @else event
            @endif
        </span>
        <span class="truncate flex-1">{{ $event['title'] }}</span>
        <span class="shrink-0 tabular-nums opacity-80">{{ $rangeLabel }}</span>
        @if($draggable)
            <span
                class="material-symbols-rounded text-[12px] shrink-0 opacity-0 group-hover:opacity-70 transition-opacity duration-150"
                title="ویرایش"
            >edit</span>
        @endif
    </div>

    @if($draggable && $heightPx >= 30)
        <div
            x-data="{ ...calendarResize({ eventId: @js($event['id']), startMinutes: @js($event['start_minutes']), durationMinutes: @js($event['duration_minutes']), isOwner: @js($event['is_owner']), locked: @js($locked || $isReservation) }) }"
            @click.stop=""
            class="absolute bottom-0 left-1 right-1 h-1.5 cursor-ns-resize bg-[color-mix(in_srgb,currentColor_30%,transparent)] hover:bg-[color-mix(in_srgb,currentColor_60%,transparent)] rounded-t-full"
            title="برای تغییر مدت بکشید"
        ></div>
    @endif
</div>