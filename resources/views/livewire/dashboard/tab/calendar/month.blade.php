
<div class="w-full h-full flex flex-col p-2 md:p-4 bg-transparent">
    <div class="grid grid-cols-7 mb-2 pb-2 px-1 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] slide-up" style="animation-delay: 0.1s">
        @foreach($this->weekLabels as $i => $dayName)
            <div class="flex items-center justify-center py-2">
                <span @class([
                    'text-[11px] font-bold tracking-wider',
                    'text-[var(--md-sys-color-error)]' => $i === 6,
                    'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_80%,transparent)]' => $i !== 6,
                ])>
                    {{ $dayName }}
                </span>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-7 gap-1.5 md:gap-2 flex-1 content-start py-2">
        @foreach($this->calendarDays as $index => $day)
            @if($day === null)
                <div wire:key="empty-{{ $index }}"
                     class="aspect-[1/0.85] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_30%,transparent)]  rounded-[14px]"></div>
            @else
                <button
                    wire:key="day-{{ $day['date'] }}"
                    wire:click="selectDate('{{ $day['date'] }}')"
                    @if($day['hasHoliday']) title="{{ $day['holidayTitle'] }}" @endif
                    @class([
                        'group relative aspect-[1/0.85] w-full rounded-[14px] transition-all duration-300 ease-out flex flex-col items-center justify-start p-1.5 md:p-0 md:pt-1.5 gap-0.5 overflow-hidden isolate outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)] slide-up',

                        'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_10px_28px_color-mix(in_srgb,var(--md-sys-color-primary)_50%,transparent)] dark:shadow-[0_10px_28px_rgba(0,0,0,0.6)] z-20 font-bold ring-1 ring-[var(--md-sys-color-primary)]' => $day['isSelected'],

                        'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] shadow-[0_8px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)] dark:shadow-[0_8px_20px_rgba(0,0,0,0.5)] z-10 ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-primary)_60%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_80%,transparent)]' => $day['isToday'] && !$day['isSelected'],

                        'bg-[color-mix(in_srgb,var(--md-sys-color-error)_8%,transparent)] text-[var(--md-sys-color-error)] shadow-[0_6px_16px_color-mix(in_srgb,var(--md-sys-color-error)_15%,transparent)] dark:shadow-[0_6px_16px_rgba(0,0,0,0.4)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_16%,transparent)] hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-error)_25%,transparent)] ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-error)_30%,transparent)] hover:ring-[color-mix(in_srgb,var(--md-sys-color-error)_50%,transparent)]' => ($index % 7 === 6 || $day['hasHoliday']) && !$day['isSelected'] && !$day['isToday'],

                        'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] shadow-[0_6px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] dark:shadow-[0_6px_16px_rgba(0,0,0,0.4)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)] ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] hover:ring-[color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' => !($day['isSelected'] || $day['isToday'] || $index % 7 === 6 || $day['hasHoliday']),

                        '!ring-[var(--md-sys-color-error)] !ring-2' => !empty($day['hasImminentShared']) && !$day['isSelected'] && !$day['isToday'],
                    ])
                    style="animation-delay: {{ 0.15 + (floor($index / 7) * 0.04) }}s"
                >
                    <span class="text-xs font-bold z-10 transition-colors {{ $day['isToday'] && !$day['isSelected'] ? 'text-[var(--md-sys-color-primary)]' : '' }}">
                        {{ convertToPersian($day['day']) }}
                    </span>

                    <div class="flex items-center justify-center min-h-[11px] sm:min-h-[13px] md:min-h-[16px]">
                        @if($day['hasHoliday'])
                            <span class="material-symbols-rounded text-[11px] sm:text-[13px] md:text-[16px] {{ $day['isSelected'] ? 'text-[var(--md-sys-color-on-primary)]' : 'text-[var(--md-sys-color-error)]' }} drop-shadow-sm"
                                  style="font-variation-settings: 'FILL' 1;">event_busy</span>
                        @elseif($day['hasBirthday'])
                            <span class="material-symbols-rounded text-[11px] sm:text-[13px] md:text-[16px] {{ $day['isSelected'] ? 'text-[var(--md-sys-color-on-primary)]' : 'text-[var(--tool-amethyst-on-surface-variant,var(--md-sys-color-tertiary))]' }} drop-shadow-sm"
                                  style="font-variation-settings: 'FILL' 1;">cake</span>
                        @elseif($day['hasAnniversary'])
                            <span class="material-symbols-rounded text-[11px] sm:text-[13px] md:text-[16px] {{ $day['isSelected'] ? 'text-[var(--md-sys-color-on-primary)]' : 'text-[var(--tool-gold-on-surface-variant,var(--md-sys-color-secondary))]' }} drop-shadow-sm"
                                  style="font-variation-settings: 'FILL' 1;">celebration</span>
                        @elseif($day['hasEvents'])
                            <span class="material-symbols-rounded text-[11px] sm:text-[13px] md:text-[16px] {{ $day['isSelected'] ? 'text-[var(--md-sys-color-on-primary)]' : 'text-[var(--md-sys-color-primary)]' }} drop-shadow-sm"
                                  style="font-variation-settings: 'FILL' 1;">event</span>
                        @elseif($day['hasReservations'])
                            <span class="material-symbols-rounded text-[11px] sm:text-[13px] md:text-[16px] {{ $day['isSelected'] ? 'text-[var(--md-sys-color-on-primary)]' : 'text-[var(--tool-sage-color,var(--md-sys-color-tertiary))]' }} drop-shadow-sm"
                                  style="font-variation-settings: 'FILL' 1;">event_seat</span>
                        @endif

                            @if($day['eventCount'] > 1)
                                <span class="text-[5.5px] sm:text-[6.5px] md:text-[7px] font-bold leading-none ml-0.5 {{ $day['isSelected'] ? 'text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_90%,transparent)]' : 'text-[var(--md-sys-color-on-surface-variant)]' }}">+{{ $day['eventCount'] - 1 }}</span>
                            @endif

                            @if(!empty($day['hasShared']))
                                @php $imminent = !empty($day['hasImminentShared']); @endphp
                                <span class="material-symbols-rounded text-[7.5px] sm:text-[8.5px] md:text-[10px] leading-none ml-0.5 {{ $day['isSelected']
                                    ? 'text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_90%,transparent)]'
                                    : ($imminent ? 'text-[var(--md-sys-color-error)] animate-pulse-slow' : 'text-[var(--md-sys-color-secondary)]') }}"
                                      style="font-variation-settings: 'FILL' {{ $imminent ? 1 : 0 }};">group</span>
                            @endif
                    </div>

                    @if($day['isToday'] && !$day['isSelected'] && !$day['hasBirthday'] && !$day['hasAnniversary'] && !$day['hasEvents'])
                        <div class="absolute bottom-1.5 w-1 h-1 rounded-full bg-[var(--md-sys-color-primary)] opacity-50"></div>
                    @endif

                    @if($day['isSelected'])
                        <div class="absolute inset-0 bg-gradient-to-b from-white/20 to-transparent pointer-events-none mix-blend-overlay rounded-[inherit]"></div>
                    @endif
                </button>
            @endif
        @endforeach
    </div>

    @php $d = $this->activeDate; @endphp
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mt-auto pt-4 px-2 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] slide-up" style="animation-delay: 0.4s">
        <div class="flex items-center gap-3 min-w-0 md:order-2">
            <div class="relative flex items-center justify-center shrink-0 text-[var(--md-sys-color-primary)]">
                <span class="material-symbols-rounded text-[22px] md:text-[28px] transition-colors duration-300 {{ $d['isToday'] ? 'text-[var(--md-sys-color-primary)]' : 'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_80%,transparent)]' }}"
                      style="font-variation-settings: 'FILL' 1;">{{ $d['isToday'] ? 'today' : 'calendar_month' }}
                </span>
            </div>
            <div class="flex flex-col justify-center min-w-0">
                <h3 class="text-sm md:text-base cursor-help tracking-tight truncate text-[var(--md-sys-color-primary)] font-bold"
                    title="{{ $d['gregorian'] }}">
                    {{ convertToPersian($d['jalali']) }}
                </h3>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-3 md:gap-4 px-2 md:order-1">
            <div class="flex items-center gap-1.5">
                <span class="material-symbols-rounded text-[12px] text-[var(--md-sys-color-error)]" style="font-variation-settings: 'FILL' 1;">event_busy</span>
                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold">تعطیل</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="material-symbols-rounded text-[12px] text-[var(--tool-amethyst-on-surface-variant,var(--md-sys-color-tertiary))]" style="font-variation-settings: 'FILL' 1;">cake</span>
                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold">تولد</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="material-symbols-rounded text-[12px] text-[var(--tool-gold-on-surface-variant,var(--md-sys-color-secondary))]" style="font-variation-settings: 'FILL' 1;">celebration</span>
                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold">سالگرد</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="material-symbols-rounded text-[12px] text-[var(--md-sys-color-primary)]" style="font-variation-settings: 'FILL' 1;">event</span>
                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold">رویداد</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="material-symbols-rounded text-[12px] text-[var(--tool-sage-color,var(--md-sys-color-tertiary))]" style="font-variation-settings: 'FILL' 1;">event_seat</span>
                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold">رزرو</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="material-symbols-rounded text-[12px] text-[var(--md-sys-color-secondary)]" style="font-variation-settings: 'FILL' 0;">group</span>
                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold">مشترک</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="material-symbols-rounded text-[12px] text-[var(--md-sys-color-error)] animate-pulse-slow" style="font-variation-settings: 'FILL' 1;">group</span>
                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold">نزدیک</span>
            </div>
        </div>
    </div>
</div>
