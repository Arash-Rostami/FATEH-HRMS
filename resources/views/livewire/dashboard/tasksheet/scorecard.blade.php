@php
    $sc = $report['scorecard'];
    $d = $presenter->scorecardBlocks($report);
    $tiles = $d['tiles'];
    $plainTiles = $d['plainTiles'];
    $priorityChips = $d['priorityChips'];
    $isPastWindow = $windowEnd->copy()->endOfDay()->isPast();
@endphp

<div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm p-5 flex flex-col gap-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)] flex flex-wrap items-center gap-2">
            {{ $presenter->windowStatement($windowStart, $windowEnd) }}
            @if($this->viewingBaseline)
                <button wire:key="tasksheet-baseline-banner" type="button" wire:click="toggleBaselineWindow" title="بازگشت به بازهٔ جاری"
                        class="print:hidden inline-flex items-center gap-1 rounded-full bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] px-2.5 py-1 text-[10px] font-bold transition-colors hover:brightness-95">
                    <span class="material-symbols-rounded text-[13px]">history</span>
                    در حال دیدن بازهٔ قبل؛ بازگشت
                </button>
            @endif
        </span>
        @if(array_sum($report['weekly_totals']) >= 3)
            <x-ui.decor.sparkline wire:key="tasksheet-weekly-chart" :values="$report['weekly_totals']" :width="160" :height="32"/>
        @else
            <span wire:key="tasksheet-chart-empty" class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">برای نمایش نمودار، دست‌کم ۳ کار باید انجام شده باشد.</span>
        @endif
    </div>

    <p class="text-xs leading-6 text-[var(--md-sys-color-on-surface-variant)]">چهار عدد اول مربوط به بازهٔ بالاست. «کارهای عقب‌افتاده» و «در حال انجام»، وضعیت فعلی کارهای مرتبط با گزارش را نشان می‌دهند.</p>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach($tiles as $tile)
            <div class="rounded-xl bg-[var(--md-sys-color-surface-container-low)] px-3.5 py-3 flex flex-col gap-1 animate-slide-up-fade animate-delay-{{ $loop->index * 100 }}">
                <div class="flex items-center gap-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">{{ $tile['icon'] }}</span>
                    {{ $tile['label'] }}
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">{{ convertToPersian($tile['value']) }}</span>
                    @if(!empty($tile['chip']['text']))
                        @php($chipClasses = match ($tile['chip']['tone']) {
                            'good' => 'text-[var(--md-sys-color-tertiary)]',
                            'bad' => 'text-[var(--md-sys-color-error)]',
                            default => 'text-[var(--md-sys-color-on-surface-variant)]',
                        })
                        @if($this->readOnly)
                            <span wire:key="tasksheet-tile-chip" class="inline-flex items-center gap-0.5 text-[10px] font-bold {{ $chipClasses }}">
                                <span class="material-symbols-rounded text-[12px]">{{ $tile['chip']['icon'] }}</span>
                                {{ $tile['chip']['text'] }}
                            </span>
                        @else
                            <button wire:key="tasksheet-tile-chip-btn" type="button" wire:click="toggleBaselineWindow"
                                    title="{{ $this->viewingBaseline ? 'بازگشت به بازهٔ انتخاب‌شده' : 'دیدن گزارش بازهٔ قبلی با همین تعداد روز' }}"
                                    class="inline-flex items-center gap-0.5 text-[10px] font-bold rounded-md px-1 -mx-1 transition-colors hover:bg-[var(--md-sys-color-surface-container-highest)] cursor-pointer {{ $chipClasses }}">
                                <span class="material-symbols-rounded text-[12px]">{{ $tile['chip']['icon'] }}</span>
                                {{ $tile['chip']['text'] }}
                            </button>
                        @endif
                    @endif
                    @if($tile['ring'] !== null)
                        <x-ui.decor.progress-ring wire:key="tasksheet-tile-ring" :percent="$tile['ring']" :size="28" :stroke="3"/>
                    @endif
                    @if($tile['spark'] !== null)
                        <x-ui.decor.sparkline wire:key="tasksheet-tile-spark" :values="$tile['spark']" :width="20" :height="14" color="var(--md-sys-color-outline)"/>
                    @endif
                </div>
            </div>
        @endforeach

        @foreach($plainTiles as $tile)
            <div class="rounded-xl bg-[var(--md-sys-color-surface-container-low)] px-3.5 py-3 flex flex-col gap-1 animate-slide-up-fade animate-delay-{{ (count($tiles) + $loop->index) * 100 }}">
                <div class="flex items-center gap-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-on-surface-variant)]">{{ $tile['icon'] }}</span>
                    {{ $tile['label'] }}
                    @if($isPastWindow && !empty($tile['live']))
                        <x-ui.badge wire:key="tasksheet-live-mark-{{ $loop->index }}" class="!px-1.5 !py-0.5 !text-[9px]">همین حالا</x-ui.badge>
                    @endif
                </div>
                <span class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">{{ convertToPersian($tile['value']) }}</span>
            </div>
        @endforeach
    </div>

    @if($priorityChips->isNotEmpty())
        <div wire:key="tasksheet-priority-chips" class="flex flex-wrap items-center gap-2">
            <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">کارهای انجام‌شده به تفکیک اولویت:</span>
            @foreach($priorityChips as $chip)
                <span wire:key="tasksheet-priority-chip-{{ crc32($chip['label']) }}" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $chip['classes'] }} animate-slide-up-fade animate-delay-{{ $loop->index * 100 }}">
                    {{ $chip['label'] }}
                    <span>{{ convertToPersian($chip['count']) }}</span>
                </span>
            @endforeach
        </div>
    @endif

    @unless($isPastWindow)
        <div wire:key="tasksheet-carry-over" class="flex items-center gap-2 rounded-xl bg-[var(--md-sys-color-surface-container-low)] px-3.5 py-2.5 text-xs text-[var(--md-sys-color-on-surface-variant)]">
            <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-tertiary)]">upcoming</span>
            <span>اکنون {{ convertToPersian($sc['in_progress']) }} کار در حال انجام است و مهلت {{ convertToPersian($sc['upcoming_deadline']) }} کار تا ۳ روز آینده است. یک کار می‌تواند در هر دو عدد شمرده شود.</span>
        </div>
    @endunless

    <p class="text-sm leading-7 text-[var(--md-sys-color-on-surface)]">{{ $report['narrative'] }}</p>
</div>
