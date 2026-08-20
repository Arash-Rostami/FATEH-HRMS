@php
    $scope = $scope ?? 'day';
    $agenda = $this->agendaItems($scope);
@endphp

<div class="w-full flex flex-col gap-3">
    @foreach($agenda as $day)
        <div wire:key="mobile-day-{{ $day['jKey'] }}" class="flex flex-col gap-2">
            <div class="flex items-center gap-2 px-1 py-1 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">calendar_today</span>
                <span class="text-sm font-black text-[var(--md-sys-color-on-surface)]">{{ $day['label'] }}</span>
            </div>

            @forelse($day['items'] as $item)
                <button
                    type="button"
                    wire:key="mobile-item-{{ $item['type'] }}-{{ $item['id'] }}"
                    @if($item['clickable']) wire:click="editEvent({{ $item['id'] }})" @endif
                    @class([
                        'flex items-center gap-3 rounded-xl p-3 border text-right transition-colors',
                        'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)] border-[color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)]' => $item['clickable'],
                        'bg-[color-mix(in_srgb,var(--md-sys-color-surface)_60%,transparent)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]' => !$item['clickable'],
                    ])
                >
                    <span class="material-symbols-rounded text-[20px] shrink-0" style="font-variation-settings: 'FILL' 1;">{{ $item['icon'] }}</span>
                    <span class="flex-1 truncate text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $item['title'] }}</span>
                    <span class="shrink-0 text-[11px] font-bold text-[var(--md-sys-color-primary)] tabular-nums">{{ convertToPersian($item['time']) }}</span>
                </button>
            @empty
                <x-ui.empty icon="calendar_today" title="رویدادی یافت نشد" description="برای این روز هنوز هیچ برنامه‌ای ثبت نشده است." variant="list" :fill="true" />
            @endforelse
        </div>
    @endforeach
</div>