<div class="w-full max-w-[50rem] mx-auto font-sans">
    <div class="relative overflow-hidden bg-[var(--md-sys-color-surface-container)] rounded-[1.75rem] p-3 shadow-xl ring-1 ring-[var(--md-sys-color-outline-variant)]/40 transition-all duration-300">

        <div class="flex items-center justify-between mb-3 px-1">
            <div class="flex flex-col">
                <span class="text-[10px] font-bold text-[var(--md-sys-color-primary)] tracking-widest uppercase opacity-80">
                    {{ $this->currentYear }}
                </span>
                <h2 class="text-xl font-black text-[var(--md-sys-color-on-surface)] tracking-tight">
                    {{ $this->currentMonthName }}
                </h2>
            </div>

            <div class="flex items-center gap-1">
                <button
                    wire:click="prevMonth"
                    class="group flex items-center justify-center w-8 h-8 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-all duration-200 active:scale-90"
                >
                    <span class="material-symbols-rounded text-sm">chevron_right</span>
                </button>
                <button
                    wire:click="nextMonth"
                    class="group flex items-center justify-center w-8 h-8 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-all duration-200 active:scale-90"
                >
                    <span class="material-symbols-rounded text-sm">chevron_left</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-7 gap-1 mb-1">
            @foreach(['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'] as $dayName)
                <div class="flex items-center justify-center py-1.5">
                    <span class="text-[10px] font-black text-[var(--md-sys-color-on-surface-variant)] opacity-60 tracking-wide">
                        {{ $dayName }}
                    </span>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-7 gap-1">
            @foreach($this->calendarDays as $index => $day)
                @if($day === null)
                    <div wire:key="empty-{{ $index }}" class="aspect-square bg-[var(--md-sys-color-surface-container-highest)]/20 rounded-[12px] opacity-20"></div>
                @else
                    <button
                        wire:key="day-{{ $day['date'] }}"
                        wire:click="selectDate('{{ $day['date'] }}')"
                        class="group relative aspect-square w-full rounded-[14px] shadow-sm transition-all duration-300 ease-out flex flex-col items-center justify-start pt-1 gap-0.5 overflow-hidden isolate outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]
                        {{ $day['isSelected']
                            ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md shadow-[var(--md-sys-color-primary)]/30 scale-[1.02] z-10'
                            : 'bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:scale-[0.96] border border-[var(--md-sys-color-outline-variant)]/30'
                        }}
                        {{ $day['isToday'] && !$day['isSelected'] ? 'ring-1 ring-[var(--md-sys-color-primary)]/50 bg-[var(--md-sys-color-surface-container-highest)]/60' : '' }}
                        "
                    >
                        <span class="text-xs font-bold z-10 transition-colors {{ $day['isToday'] && !$day['isSelected'] ? 'text-[var(--md-sys-color-primary)]' : '' }}">
                            {{ $day['day'] }}
                        </span>

                        <div class="flex items-center justify-center mt-0.5 min-h-[16px]">
                            @if($day['hasBirthday'])
                                <span class="material-symbols-rounded text-[16px] {{ $day['isSelected'] ? 'text-white' : 'text-pink-500' }} drop-shadow-sm" style="font-variation-settings: 'FILL' 1;">cake</span>
                            @elseif($day['hasAnniversary'])
                                <span class="material-symbols-rounded text-[16px] {{ $day['isSelected'] ? 'text-white' : 'text-amber-500' }} drop-shadow-sm" style="font-variation-settings: 'FILL' 1;">celebration</span>
                            @elseif($day['hasEvents'])
                                <span class="material-symbols-rounded text-[16px] {{ $day['isSelected'] ? 'text-white' : 'text-[var(--md-sys-color-primary)]' }} drop-shadow-sm" style="font-variation-settings: 'FILL' 1;">event</span>
                            @endif

                            @if($day['eventCount'] > 1)
                                <span class="text-[7px] font-bold leading-none ml-0.5 {{ $day['isSelected'] ? 'text-white' : 'text-[var(--md-sys-color-on-surface-variant)]' }}">+{{ $day['eventCount'] - 1 }}</span>
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

        <div class="flex items-center justify-between px-2 pt-3 mt-3 border-t border-[var(--md-sys-color-outline-variant)]/30">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1">
                    <span class="material-symbols-rounded text-[12px] text-pink-500" style="font-variation-settings: 'FILL' 1;">cake</span>
                    <span class="text-[9px] text-[var(--md-sys-color-on-surface-variant)] font-bold">تولد</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="material-symbols-rounded text-[12px] text-amber-500" style="font-variation-settings: 'FILL' 1;">celebration</span>
                    <span class="text-[9px] text-[var(--md-sys-color-on-surface-variant)] font-bold">سالگرد</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="material-symbols-rounded text-[12px] text-[var(--md-sys-color-primary)]" style="font-variation-settings: 'FILL' 1;">event</span>
                    <span class="text-[9px] text-[var(--md-sys-color-on-surface-variant)] font-bold">رویداد</span>
                </div>
            </div>
            <button
                wire:click="goToToday"
                class="text-[10px] font-bold text-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] bg-[var(--md-sys-color-primary)]/10 hover:bg-[var(--md-sys-color-primary)] transition-all px-3 py-1.5 rounded-lg"
            >
                امروز
            </button>
        </div>

    </div>
</div>
