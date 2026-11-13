@php
    $scope = $scope ?? 'day';
    $hideEmpty = $hideEmpty ?? false;
    $agenda = $this->agendaItems($scope);
    $allEmpty = collect($agenda)->every(fn($day) => empty($day['items']));
@endphp

<div class="w-full flex flex-col gap-3">
    @foreach($agenda as $day)
        @continue($hideEmpty && empty($day['items']))
        <div wire:key="mobile-day-{{ $day['jKey'] }}" class="flex flex-col gap-2">
            <div class="flex items-center gap-2 px-1 py-1 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">calendar_today</span>
                <span class="text-sm font-black text-[var(--md-sys-color-on-surface)]">{{ $day['label'] }}</span>
            </div>

            @forelse($day['items'] as $item)
                <div wire:key="calendar-mobile-row-{{ $item['type'] }}-{{ $item['id'] }}" class="flex items-center gap-2">
                <button
                    type="button"
                    wire:key="mobile-item-{{ $item['type'] }}-{{ $item['id'] }}"
                    @if($item['clickable']) wire:click="editEvent({{ $item['id'] }})" @endif
                    @class([
                        'flex flex-1 min-w-0 items-center gap-3 rounded-xl p-3 border text-right transition-colors',
                        'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)] border-[color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)]' => $item['clickable'],
                        'bg-[color-mix(in_srgb,var(--md-sys-color-surface)_60%,transparent)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]' => !$item['clickable'],
                    ])
                >
                    <span class="material-symbols-rounded text-[20px] shrink-0" style="font-variation-settings: 'FILL' 1;">{{ $item['icon'] }}</span>
                    <span class="flex-1 truncate text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $item['title'] }}</span>
                    @unless($item['time'] === '00:00' && in_array($item['type'], ['birthday', 'anniversary', 'holiday'], true))
                        <span class="shrink-0 text-[11px] font-bold text-[var(--md-sys-color-primary)] tabular-nums">{{ convertToPersian($item['time']) }}</span>
                    @endunless
                </button>
                @if($item['can_sms'] ?? false)
                    <x-ui.buttons.form wire:key="calendar-mobile-sms-{{ $item['type'] }}-{{ $item['id'] }}" variant="none" size="icon" icon="sms"
                        title="ارسال پیامک تبریک" aria-label="ارسال پیامک تبریک" class="shrink-0 !w-7 !h-7 !rounded-lg border border-[var(--md-sys-color-outline-variant)] text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] [&>span]:!text-[16px]"
                        :data-sms="json_encode(['scope' => 'calendar', 'id' => $item['user_id'], 'name' => $item['sms_name'], 'type' => $item['type'], 'key' => $item['sms_key']])"
                        x-cloak x-show="!smsAlreadySent(JSON.parse($el.dataset.sms).key)"
                        x-on:click.stop="$dispatch('open-sms', JSON.parse($el.dataset.sms))" />
                @endif
                </div>
            @empty
                <div wire:key="calendar-mobile-day-empty" class="contents">
                    <x-ui.empty icon="calendar_today" title="رویدادی یافت نشد" description="برای این روز هنوز هیچ برنامه‌ای ثبت نشده است." variant="list" :fill="true" />
                </div>
            @endforelse
        </div>
    @endforeach

    @if($hideEmpty && $allEmpty)
        <div wire:key="calendar-mobile-month-empty" class="contents">
            <x-ui.empty icon="calendar_today" title="رویدادی یافت نشد" description="برای این ماه هنوز هیچ برنامه‌ای ثبت نشده است." variant="list" :fill="true" />
        </div>
    @endif
</div>
