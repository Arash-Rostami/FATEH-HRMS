<div class="grid grid-cols-7 gap-3 h-full pb-4 px-1">
    {{-- Days Header --}}
    @foreach(['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'] as $dayName)
        <div class="flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] text-xs font-bold py-3 opacity-70">
            {{ $dayName }}
        </div>
    @endforeach

    {{-- Calendar Days --}}
    @foreach($this->calendarDays as $day)
        @if($day === null)
            <div class="aspect-square bg-[var(--md-sys-color-surface-container-low)]/20 rounded-2xl opacity-20"></div>
        @else
            <button
                wire:click="selectDate('{{ $day['date'] }}')"
                class="group relative aspect-square rounded-[20px] transition-all duration-300 ease-out flex flex-col items-center justify-start pt-2 gap-1 overflow-hidden
                {{ $day['isSelected']
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-xl shadow-[var(--md-sys-color-primary)]/40 scale-105 z-10 ring-2 ring-[var(--md-sys-color-primary-container)]'
                    : 'bg-[var(--md-sys-color-surface-container-low)]/60 text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:scale-105 hover:shadow-lg border border-[var(--md-sys-color-outline-variant)]/30 backdrop-blur-sm'
                }}
                {{ $day['isToday'] && !$day['isSelected'] ? 'ring-2 ring-[var(--md-sys-color-primary)]/50 bg-[var(--md-sys-color-primary-container)]/30' : '' }}
                "
            >
                {{-- Day Number --}}
                <span class="text-sm font-bold z-10 {{ $day['isToday'] && !$day['isSelected'] ? 'text-[var(--md-sys-color-primary)]' : '' }}">
                    {{ $day['day'] }}
                </span>

                {{-- Event Indicators (Dots/Icons) --}}
                @if($day['hasEvents'])
                    <div class="flex flex-wrap justify-center gap-0.5 px-2 max-w-full">
                         {{-- We can't easily loop through events here without performance cost,
                              so we use a simple indicator or count.
                              The controller passes 'eventCount' --}}
                        @if($day['eventCount'] > 0)
                            <div class="flex -space-x-1 rtl:space-x-reverse items-center mt-1">
                                @for($i = 0; $i < min($day['eventCount'], 3); $i++)
                                    <span class="w-1.5 h-1.5 rounded-full {{ $day['isSelected'] ? 'bg-white' : 'bg-[var(--md-sys-color-primary)]' }} opacity-80 shadow-sm"></span>
                                @endfor
                                @if($day['eventCount'] > 3)
                                    <span class="text-[8px] font-bold {{ $day['isSelected'] ? 'text-white' : 'text-[var(--md-sys-color-on-surface-variant)]' }}">+</span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Today Indicator Dot (Top Right) --}}
                @if($day['isToday'] && !$day['isSelected'])
                    <div class="absolute top-2 right-2 w-2 h-2 rounded-full bg-[var(--md-sys-color-primary)] shadow-[0_0_8px_var(--md-sys-color-primary)]"></div>
                @endif

                {{-- Selection Glow Effect --}}
                @if($day['isSelected'])
                    <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent rounded-[20px] pointer-events-none"></div>
                @endif
            </button>
        @endif
    @endforeach
</div>
