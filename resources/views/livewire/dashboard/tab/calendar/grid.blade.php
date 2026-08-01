<div class="w-full max-w-[50rem] mx-auto p-2">
    <div class="bg-[var(--md-sys-color-surface)]">
        <div class="relative overflow-hidden !bg-[var(--md-sys-color-surface-container-low)]/60 rounded-[1.75rem] p-3 shadow-xl ring-1 ring-[var(--md-sys-color-outline-variant)]/40 transition-all duration-300">

            <div class="flex items-center justify-between mb-3 px-1">
                <div class="flex flex-row items-center gap-3.5 min-w-0">
                    <h2 class="text-xl md:text-2xl font-black text-[var(--md-sys-color-on-surface)] tracking-tight shrink-0 flex items-baseline gap-1.5 bg-[var(--md-sys-color-surface-container-high)] px-3 py-1 rounded-xl border border-[var(--md-sys-color-outline-variant)]/30 mt-0.5">
                        <span>{{ convertToPersian($this->currentMonthName) }}</span>
                    </h2>
                </div>

                <div class="flex items-center bg-[var(--md-sys-color-surface-container-high)] rounded-2xl p-1.5 border border-[var(--md-sys-color-outline-variant)]/30">
                    <button
                        wire:click="prevMonth"
                        class="w-10 h-10 flex items-center justify-center hover:bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface-variant)] rounded-xl transition-all active:scale-90"
                    >
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>

                    <button
                        wire:click="goToToday"
                        class="px-4 text-sm font-bold text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/5 rounded-lg py-2 transition-colors"
                    >
                        امروز
                    </button>

                    <button
                        wire:click="nextMonth"
                        class="w-10 h-10 flex items-center justify-center hover:bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface-variant)] rounded-xl transition-all active:scale-90"
                    >
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-7 mb-2 pb-2 px-1 border-b border-[var(--md-sys-color-outline-variant)]/30">
                @foreach(['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'] as $i => $dayName)
                    <div class="flex items-center justify-center py-2">
                        <span @class([
                            'text-[11px] font-bold tracking-wider',
                            'text-rose-500 dark:text-rose-400'              => $i === 6,
                            'text-[var(--md-sys-color-on-surface-variant)]' => $i !== 6,
                        ])>
                            {{ $dayName }}
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-7 gap-1">
                @foreach($this->calendarDays as $index => $day)
                    @if($day === null)
                        <div wire:key="empty-{{ $index }}"
                             class="aspect-square bg-[var(--md-sys-color-surface-container-highest)]/20 rounded-[12px] opacity-20"></div>
                    @else
                        <button
                            wire:key="day-{{ $day['date'] }}"
                            wire:click="selectDate('{{ $day['date'] }}')"
                            @if($day['hasHoliday']) title="{{ $day['holidayTitle'] }}" @endif
                            class="group relative aspect-[1/0.85] w-full rounded-[14px] shadow-sm transition-all duration-300 ease-out flex flex-col items-center justify-start pt-1 gap-0.5 overflow-hidden isolate outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]
                                {{ $day['isSelected']
                                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_10px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] z-10 font-bold'
                                    : ($day['isToday']
                                        ? '!bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)] z-10'
                                        : (($index % 7 === 6 || $day['hasHoliday'])
                                            ? 'bg-[color-mix(in_srgb,#f43f5e_6%,var(--md-sys-color-surface))] text-[var(--md-sys-color-on-surface)] shadow-[0_4px_10px_color-mix(in_srgb,#f43f5e_8%,transparent)] hover:bg-[color-mix(in_srgb,#f43f5e_12%,var(--md-sys-color-surface))]'
                                            : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] shadow-[0_4px_10px_rgba(0,0,0,0.04)] dark:shadow-[0_4px_12px_rgba(0,0,0,0.25)] hover:bg-[var(--md-sys-color-surface-container-highest)]'))
                                }}
                                {{ $day['isToday'] && !$day['isSelected'] ? 'ring-1 ring-[var(--md-sys-color-primary)]/50 bg-[var(--md-sys-color-surface-container-highest)]/60' : '' }}
                                {{ (!empty($day['hasImminentShared']) && !$day['isSelected'] && !$day['isToday']) ? 'ring-1 ring-[var(--md-sys-color-error)]/70' : '' }}
                                {{ ($day['hasHoliday'] && !$day['isSelected']) ? 'ring-1 ring-rose-400/50' : '' }}
                            "
                        >
                            <span class="text-xs font-bold z-10 transition-colors {{ $day['isToday'] && !$day['isSelected'] ? 'text-[var(--md-sys-color-primary)]' : '' }}">
                                {{ convertToPersian($day['day']) }}
                            </span>

                            <div class="flex items-center justify-center mt-0.5 min-h-[16px]">
                                @if($day['hasHoliday'])
                                    <span class="material-symbols-rounded text-[16px] {{ $day['isSelected'] ? 'text-white' : 'text-rose-500' }} drop-shadow-sm"
                                          style="font-variation-settings: 'FILL' 1;">event_busy</span>
                                @elseif($day['hasBirthday'])
                                    <span class="material-symbols-rounded text-[16px] {{ $day['isSelected'] ? 'text-white' : 'text-pink-500' }} drop-shadow-sm"
                                          style="font-variation-settings: 'FILL' 1;">cake</span>
                                @elseif($day['hasAnniversary'])
                                    <span class="material-symbols-rounded text-[16px] {{ $day['isSelected'] ? 'text-white' : 'text-amber-500' }} drop-shadow-sm"
                                          style="font-variation-settings: 'FILL' 1;">celebration</span>
                                @elseif($day['hasEvents'])
                                    <span class="material-symbols-rounded text-[16px] {{ $day['isSelected'] ? 'text-white' : 'text-[var(--md-sys-color-primary)]' }} drop-shadow-sm"
                                          style="font-variation-settings: 'FILL' 1;">event</span>
                                @endif

                                @if($day['eventCount'] > 1)
                                    <span class="text-[7px] font-bold leading-none ml-0.5 {{ $day['isSelected'] ? 'text-white' : 'text-[var(--md-sys-color-on-surface-variant)]' }}">+{{ $day['eventCount'] - 1 }}</span>
                                @endif

                                @if(!empty($day['hasShared']))
                                    @php $imminent = !empty($day['hasImminentShared']); @endphp
                                    <span class="material-symbols-rounded text-[10px] leading-none ml-0.5 {{ $day['isSelected']
                                            ? 'text-white'
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
            <div class="flex flex-row-reverse items-center justify-between gap-3 mt-4 pt-4 px-2 border-t border-[var(--md-sys-color-outline-variant)]/30">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="relative flex items-center justify-center shrink-0 text-[var(--md-sys-color-primary)]">
                        <span class="material-symbols-rounded text-[28px] transition-colors {{ $d['isToday'] ? 'text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)]' }}"
                              style="font-variation-settings: 'FILL' 1;">
                            {{ $d['isToday'] ? 'today' : 'calendar_month' }}
                        </span>
                    </div>
                    <div class="flex flex-col justify-center min-w-0">
                        <h3 class="text-sm md:text-base cursor-help tracking-tight truncate text-[var(--md-sys-color-primary)]"
                            title="{{ $d['gregorian'] }}">
                            {{ convertToPersian($d['jalali']) }}
                        </h3>
                    </div>
                </div>
                <div class="flex items-center justify-between px-2 mt-3 mb-1">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1">
                        <span class="material-symbols-rounded text-[12px] text-rose-500"
                              style="font-variation-settings: 'FILL' 1;">event_busy</span>
                            <span class="text-[9px] text-[var(--md-sys-color-on-surface-variant)] font-bold">تعطیل</span>
                        </div>
                        <div class="flex items-center gap-1">
                        <span class="material-symbols-rounded text-[12px] text-pink-500"
                              style="font-variation-settings: 'FILL' 1;">cake</span>
                            <span class="text-[9px] text-[var(--md-sys-color-on-surface-variant)] font-bold">تولد</span>
                        </div>
                        <div class="flex items-center gap-1">
                        <span class="material-symbols-rounded text-[12px] text-amber-500"
                              style="font-variation-settings: 'FILL' 1;">celebration</span>
                            <span class="text-[9px] text-[var(--md-sys-color-on-surface-variant)] font-bold">سالگرد</span>
                        </div>
                        <div class="flex items-center gap-1">
                        <span class="material-symbols-rounded text-[12px] text-[var(--md-sys-color-primary)]"
                              style="font-variation-settings: 'FILL' 1;">event</span>
                            <span class="text-[9px] text-[var(--md-sys-color-on-surface-variant)] font-bold">رویداد</span>
                        </div>
                        <div class="flex items-center gap-1">
                        <span class="material-symbols-rounded text-[12px] text-[var(--md-sys-color-secondary)]"
                              style="font-variation-settings: 'FILL' 0;">group</span>
                            <span class="text-[9px] text-[var(--md-sys-color-on-surface-variant)] font-bold">مشترک</span>
                        </div>
                        <div class="flex items-center gap-1">
                        <span class="material-symbols-rounded text-[12px] text-[var(--md-sys-color-error)] animate-pulse-slow"
                              style="font-variation-settings: 'FILL' 1;">group</span>
                            <span class="text-[9px] text-[var(--md-sys-color-on-surface-variant)] font-bold">نزدیک</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
