@php
    $sc = $report['scorecard'];
    $d = $presenter->scorecardBlocks($report);
    $tiles = $d['tiles'];
    $plainTiles = $d['plainTiles'];
    $priorityChips = $d['priorityChips'];
@endphp

<div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm p-5 flex flex-col gap-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)] flex flex-wrap items-center gap-2">
            {{ $presenter->windowStatement($windowStart, $windowEnd) }}
            @if($this->viewingBaseline)
                <button type="button" wire:click="toggleBaselineWindow" title="بازگشت به بازهٔ جاری"
                        class="print:hidden inline-flex items-center gap-1 rounded-full bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] px-2.5 py-1 text-[10px] font-bold transition-colors hover:brightness-95">
                    <span class="material-symbols-rounded text-[13px]">history</span>
                    بازهٔ قبلی فعال است — بازگشت
                </button>
            @endif
        </span>
        @if(array_sum($report['weekly_totals']) >= 3)
            <x-ui.decor.sparkline :values="$report['weekly_totals']" :width="160" :height="32"/>
        @else
            <span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">داده کافی برای نمودار هفتگی نیست</span>
        @endif
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach($tiles as $tile)
            <div class="rounded-xl bg-[var(--md-sys-color-surface-container-low)] px-3.5 py-3 flex flex-col gap-1 animate-slide-up-fade animate-delay-{{ $loop->index * 100 }}">
                <div class="flex items-center gap-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">{{ $tile['icon'] }}</span>
                    {{ $tile['label'] }}
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-lg font-bold text-[var(--md-sys-color-on-surface)] tabular-nums">{{ $tile['value'] }}</span>
                    @if(!empty($tile['chip']['text']))
                        @php($chipClasses = match ($tile['chip']['direction']) {
                            'up' => 'text-[var(--md-sys-color-tertiary)]',
                            'down' => 'text-[var(--md-sys-color-error)]',
                            default => 'text-[var(--md-sys-color-on-surface-variant)]',
                        })
                        @if($this->readOnly)
                            <span class="inline-flex items-center gap-0.5 text-[10px] font-bold {{ $chipClasses }}">
                                <span class="material-symbols-rounded text-[12px]">{{ $tile['chip']['icon'] }}</span>
                                {{ $tile['chip']['text'] }}
                            </span>
                        @else
                            <button type="button" wire:click="toggleBaselineWindow"
                                    title="{{ $this->viewingBaseline ? 'بازگشت به بازهٔ جاری' : 'مشاهدهٔ همین عدد در بازهٔ قبلی' }}"
                                    class="inline-flex items-center gap-0.5 text-[10px] font-bold rounded-md px-1 -mx-1 transition-colors hover:bg-[var(--md-sys-color-surface-container-highest)] cursor-pointer {{ $chipClasses }}">
                                <span class="material-symbols-rounded text-[12px]">{{ $tile['chip']['icon'] }}</span>
                                {{ $tile['chip']['text'] }}
                            </button>
                        @endif
                    @endif
                    @if($tile['ring'] !== null)
                        <x-ui.decor.progress-ring :percent="$tile['ring']" :size="28" :stroke="3"/>
                    @endif
                    @if($tile['spark'] !== null)
                        <x-ui.decor.sparkline :values="$tile['spark']" :width="20" :height="14" color="var(--md-sys-color-outline)"/>
                    @endif
                </div>
            </div>
        @endforeach

        @foreach($plainTiles as $tile)
            <div class="rounded-xl bg-[var(--md-sys-color-surface-container-low)] px-3.5 py-3 flex flex-col gap-1 animate-slide-up-fade animate-delay-{{ (count($tiles) + $loop->index) * 100 }}">
                <div class="flex items-center gap-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-on-surface-variant)]">{{ $tile['icon'] }}</span>
                    {{ $tile['label'] }}
                </div>
                <span class="text-lg font-bold text-[var(--md-sys-color-on-surface)] tabular-nums">{{ $tile['value'] }}</span>
            </div>
        @endforeach
    </div>

    @if($priorityChips->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">ترکیب اولویتِ تکمیل‌شده‌ها:</span>
            @foreach($priorityChips as $chip)
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $chip['classes'] }} animate-slide-up-fade animate-delay-{{ $loop->index * 100 }}">
                    {{ $chip['label'] }}
                    <span class="tabular-nums">{{ convertToPersian($chip['count']) }}</span>
                </span>
            @endforeach
        </div>
    @endif

    <div class="flex items-center gap-2 rounded-xl bg-[var(--md-sys-color-surface-container-low)] px-3.5 py-2.5 text-xs text-[var(--md-sys-color-on-surface-variant)]">
        <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-tertiary)]">upcoming</span>
        <span>{{ convertToPersian($sc['in_progress']) }} وظیفهٔ در حال انجام و {{ convertToPersian($sc['upcoming_deadline']) }} وظیفه با مهلت نزدیک، به بازهٔ بعد منتقل می‌شود.</span>
    </div>

    <p class="text-sm leading-7 text-[var(--md-sys-color-on-surface)]">{{ $report['narrative'] }}</p>
</div>
