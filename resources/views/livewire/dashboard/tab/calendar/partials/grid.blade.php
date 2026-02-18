<div class="grid grid-cols-7 gap-2 h-full pb-4">
    {{-- Days Header --}}
    @foreach(['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'] as $dayName)
        <div class="flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] text-xs font-medium py-2">
            {{ $dayName }}
        </div>
    @endforeach

    {{-- Calendar Days --}}
    @foreach($this->calendarDays as $day)
        @if($day === null)
            <div class="aspect-square bg-[var(--md-sys-color-surface-dim)]/30 rounded-lg opacity-40"></div>
        @else
            <button
                wire:click="selectDate('{{ $day['date'] }}')"
                class="relative aspect-square rounded-xl transition-all active:scale-95 flex flex-col items-center justify-center
                {{ $day['isSelected']
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-lg shadow-[var(--md-sys-color-primary)]/30 scale-105 z-10'
                    : 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-highest)] border border-[var(--md-sys-color-outline-variant)]'
                }}"
            >
                <span class="text-sm font-semibold {{ $day['isToday'] && !$day['isSelected'] ? 'text-[var(--md-sys-color-primary)]' : '' }}">
                    {{ $day['day'] }}
                </span>

                @if($day['hasEvents'])
                    <div class="absolute bottom-2 flex gap-0.5">
                        <div class="w-1.5 h-1.5 rounded-full {{ $day['isSelected'] ? 'bg-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-tertiary)]' }}"></div>
                    </div>
                @endif

                @if($day['isToday'] && !$day['isSelected'])
                    <div class="absolute top-1 right-1 w-2 h-2 rounded-full bg-[var(--md-sys-color-primary)]"></div>
                @endif
            </button>
        @endif
    @endforeach
</div>
