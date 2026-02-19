<div class="w-full max-w-[50rem] mx-auto font-sans">
    <div class="relative overflow-hidden bg-[var(--md-sys-color-surface-container)] rounded-[1.75rem] p-3 shadow-xl ring-1 ring-[var(--md-sys-color-outline-variant)]/40  transition-all duration-300">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-3 px-1">
            <div class="flex flex-col">
                <span class="text-[10px] font-bold text-[var(--md-sys-color-primary)] tracking-widest uppercase opacity-80">
                    {{ $this->currentYear }}
                </span>
                <h2 class="text-xl font-black text-[var(--md-sys-color-on-surface)] tracking-tight">
                    {{ $this->currentMonth }}
                </h2>
            </div>

            <div class="flex items-center gap-1">
                <button
                    wire:click="previousMonth"
                    class="group flex items-center justify-center w-8 h-8 rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-all duration-200 active:scale-90"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button
                    wire:click="nextMonth"
                    class="group flex items-center justify-center w-8 h-8 rounded-full bg-[var()] text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-all duration-200 active:scale-90"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Days Header --}}
        <div class="grid grid-cols-7 gap-1 mb-1">
            @foreach(['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'] as $dayName)
                <div class="flex items-center justify-center py-1.5">
                    <span class="text-[9px] font-black text-[var(--md-sys-color-on-surface-variant)] opacity-60 tracking-wide">
                        {{ substr($dayName, 0, 2) }}
                    </span>
                </div>
            @endforeach
        </div>

        {{-- Calendar Grid --}}
        <div class="grid grid-cols-7 gap-1">
            @foreach($this->calendarDays as $day)
                @if($day === null)
                    <div class="aspect-square bg-[var(--md-sys-color-surface-container-highest)]/20 rounded-[12px] opacity-20"></div>
                @else
                    <button
                        wire:click="selectDate('{{ $day['date'] }}')"
                        class="group relative aspect-square w-full rounded-[14px] shadow-sm transition-all duration-300 ease-out flex flex-col items-center justify-start pt-1 gap-0.5 overflow-hidden isolate outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]
                        {{ $day['isSelected']
                            ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md shadow-[var(--md-sys-color-primary)]/30 scale-[1.02] z-10'
                            : 'bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:scale-[0.96] border border-[var(--md-sys-color-outline-variant)]/30'
                        }}
                        {{ $day['isToday'] && !$day['isSelected'] ? 'ring-1 ring-[var(--md-sys-color-primary)]/50 bg-[var(--md-sys-color-surface-container-highest)]/60' : '' }}
                        "
                    >
                        {{-- Day Number --}}
                        <span class="text-xs font-bold z-10 transition-colors {{ $day['isToday'] && !$day['isSelected'] ? 'text-[var(--md-sys-color-primary)]' : '' }}">
                            {{ $day['day'] }}
                        </span>

                        {{-- Event Indicators (Your Logic: Max 2 + Counter) --}}
                        @if($day['hasEvents'] ?? false)
                            <div class="flex gap-0.5 items-center justify-center w-full px-0.5 mt-0.5">
                                {{-- Loop for first 2 dots --}}
                                @for($i = 0; $i < min(2, $day['eventCount'] ?? 0); $i++)
                                    <div class="w-1 h-1 rounded-full shadow-sm transition-colors
                                        {{ $day['isSelected'] ? 'bg-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-tertiary)]' }}">
                                    </div>
                                @endfor

                                {{-- Counter if more than 2 --}}
                                @if(($day['eventCount'] ?? 0) > 2)
                                    <span class="text-[7px] font-black leading-none
                                        {{ $day['isSelected'] ? 'text-[var(--md-sys-color-on-primary)] opacity-90' : 'text-[var(--md-sys-color-tertiary)] opacity-80' }}">
                                        +{{ ($day['eventCount'] ?? 0) - 2 }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        {{-- Today Dot --}}
                        @if($day['isToday'] && !$day['isSelected'])
                            <div class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-primary)] shadow-[0_0_6px_var(--md-sys-color-primary)] animate-pulse"></div>
                        @endif

                        {{-- Selection Overlay --}}
                        @if($day['isSelected'])
                            <div class="absolute inset-0 bg-gradient-to-b from-white/20 to-transparent pointer-events-none mix-blend-overlay rounded-[inherit]"></div>
                        @endif
                    </button>
                @endif
            @endforeach
        </div>

        {{-- Footer (Your Specific Footer) --}}
        <div class="flex items-center justify-between px-1 pt-3 mt-3 border-t border-[var(--md-sys-color-outline-variant)]/30">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <div class="w-2 h-2 rounded-full bg-[var(--md-sys-color-primary)]"></div>
                    <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold">Today</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-2 h-2 rounded-full bg-[var(--md-sys-color-tertiary)]"></div>
                    <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold">Events</span>
                </div>
            </div>
            <button
                wire:click="resetToToday"
                class="text-[10px] font-bold text-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] bg-[var(--md-sys-color-primary)]/10 hover:bg-[var(--md-sys-color-primary)] transition-all px-3 py-1.5 rounded-lg"
            >
                Today
            </button>
        </div>

    </div>
</div>
