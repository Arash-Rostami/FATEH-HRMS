@php $iconByType = $iconByType ?? []; @endphp
<div wire:key="{{ $keyPrefix }}-{{ $entry['id'] }}" @class([
    'flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-bold truncate',
    'bg-[color-mix(in_srgb,var(--md-sys-color-error-container)_50%,transparent)] text-[var(--md-sys-color-error)]' => $entry['type'] === 'holiday',
    'bg-[color-mix(in_srgb,var(--tool-amethyst-bg,var(--md-sys-color-tertiary-container))_70%,transparent)] text-[var(--tool-amethyst-color,var(--md-sys-color-on-tertiary-container))]' => $entry['type'] === 'birthday',
    'bg-[color-mix(in_srgb,var(--tool-gold-bg,var(--md-sys-color-secondary-container))_70%,transparent)] text-[var(--tool-gold-color,var(--md-sys-color-on-secondary-container))]' => $entry['type'] === 'anniversary',
    'bg-[color-mix(in_srgb,var(--tool-sapphire-bg,var(--md-sys-color-primary-container))_70%,transparent)] text-[var(--tool-sapphire-color,var(--md-sys-color-on-primary-container))]' => $entry['type'] === 'reminder',
])>
    <span class="material-symbols-rounded text-[12px] shrink-0" style="font-variation-settings: 'FILL' 1;">{{ $iconByType[$entry['type']] ?? '' }}</span>
    @if($entry['type'] === 'reminder' && !empty($entry['time']))
        <span class="shrink-0 opacity-80">{{ convertToPersian($entry['time']) }}</span>
    @endif
    <span class="truncate">{{ $entry['title'] }}</span>
    @if($entry['can_sms'] ?? false)
        <x-ui.buttons.form wire:key="{{ $keyPrefix }}-sms-{{ $entry['id'] }}" variant="none" size="icon" icon="sms"
            title="ارسال پیامک تبریک" aria-label="ارسال پیامک تبریک" class="shrink-0 !w-4 !h-4 -my-0.5 !rounded-md border border-current/40 hover:bg-current/10 [&>span]:!text-[10px]"
            :data-sms="json_encode(['scope' => 'calendar', 'id' => $entry['user_id'], 'name' => $entry['sms_name'], 'type' => $entry['type'], 'key' => $entry['sms_key']])"
            x-cloak x-show="!smsAlreadySent(JSON.parse($el.dataset.sms).key)"
            x-on:click.stop="$dispatch('open-sms', JSON.parse($el.dataset.sms))" />
    @endif
    @if($entry['is_snoozed'] ?? false)
        <span class="shrink-0 opacity-70 text-[9px]" title="اعلان موقتاً خاموش است">({{ convertToPersian($entry['snoozed_hours']) }}h)</span>
    @endif
</div>
