@php
    $insights = $this->analyticsChartData;
    $boot = $presenter->analyticsBoot($insights);
    $summary = $this->reportSummary;
    $tabs = $boot['tabs'];
    $jsChartData = $boot['jsChartData'];
    $verdicts = $boot['verdicts'];
    $matrix = $boot['matrix'];
    $gatedCharts = ['labelsDistribution', 'departmentCompletion'];
@endphp

<div class="mt-4 flex flex-col gap-4" wire:key="analytics-{{ $activeProjectId }}">
    @if((int) ($summary['total'] ?? 0) === 0)
        <x-ui.empty icon="monitoring" title="هنوز داده‌ای برای تحلیل نیست" description="بعد از ثبت وظیفه در این پروژه، نمودارها اینجا نمایش داده می‌شوند."/>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 p-2 rounded-2xl bg-[var(--md-sys-color-surface-container-low)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-xs">
            <div class="flex items-center gap-3 p-2.5 rounded-xl transition-colors hover:bg-[var(--md-sys-color-surface-container-high)]">
                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] shrink-0">
                    <span class="material-symbols-rounded text-lg">checklist</span>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] truncate">کل وظایف</span>
                    <span class="text-base font-bold tracking-tight text-[var(--md-sys-color-on-surface)] tabular-nums">{{ convertToPersian((int) $summary['total']) }}</span>
                </div>
            </div>

            <div class="flex items-center gap-3 p-2.5 rounded-xl transition-colors hover:bg-[var(--md-sys-color-surface-container-high)]">
                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-[var(--tool-sage-bg)] text-[var(--tool-sage-text)] shrink-0">
                    <span class="material-symbols-rounded text-lg">check_circle</span>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] truncate">انجام‌شده</span>
                    <span class="text-base font-bold tracking-tight text-[var(--md-sys-color-on-surface)] tabular-nums">{{ convertToPersian((int) ($summary['done'] ?? 0)) }} <span class="text-xs font-normal opacity-70">({{ convertToPersian((int) round($summary['percent'] ?? 0)) }}٪)</span></span>
                </div>
            </div>

            <div class="flex items-center gap-3 p-2.5 rounded-xl transition-colors hover:bg-[var(--md-sys-color-surface-container-high)]">
                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] shrink-0">
                    <span class="material-symbols-rounded text-lg">notification_important</span>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] truncate">سررسید گذشته</span>
                    <span class="text-base font-bold tracking-tight text-[var(--md-sys-color-on-surface)] tabular-nums">{{ convertToPersian((int) ($summary['overdue'] ?? 0)) }}</span>
                </div>
            </div>
        </div>

        <section class="w-full flex flex-col gap-4"
                 x-data="projectAnalytics(@js($jsChartData), @js($presenter->chartConfigMap()), @js($tabs), @js($presenter->drillParamMap()), {{ $activeProjectId }}, @js(route('tasks')))"
                 x-init="init()">

            <div class="flex flex-wrap gap-2 p-1 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 w-full sm:w-auto sm:inline-flex">
                @foreach($tabs as $tabKey => $tab)
                    <button type="button" @click="switchTab('{{ $tabKey }}')"
                            :class="activeTab === '{{ $tabKey }}'
                                ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm'
                                : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                            class="flex-1 sm:flex-initial flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold transition-all duration-200">
                        <span class="material-symbols-rounded text-[17px]" aria-hidden="true">{{ $tab['icon'] }}</span>
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>

            @foreach($tabs as $tabKey => $tab)
                <div x-show="activeTab === '{{ $tabKey }}'" x-cloak class="flex flex-col gap-3">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        @foreach($presenter->kpis($tabKey, $insights) as $kpi)
                            <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-[var(--md-sys-color-surface-container-low)] border border-[var(--md-sys-color-outline-variant)]/30">
                                <div class="flex items-center justify-center w-8 h-8 rounded-lg {{ $presenter->chartTone($kpi['tone']) }} shrink-0">
                                    <span class="material-symbols-rounded text-base" aria-hidden="true">{{ $kpi['icon'] }}</span>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] truncate">{{ $kpi['label'] }}</span>
                                    <span class="text-sm font-bold tabular-nums text-[var(--md-sys-color-on-surface)]">{{ convertToPersian($kpi['value']) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <p class="text-xs leading-6 text-[var(--md-sys-color-on-surface-variant)] px-1">{{ $verdicts[$tabKey] }}</p>

                    @if($tabKey === 'risk')
                        <div class="analytics-card rounded-3xl flex flex-col">
                            <header class="px-5 py-3.5 border-b border-[var(--md-sys-color-outline-variant)]/30 flex items-center gap-3 rounded-t-3xl shrink-0">
                                <div class="flex size-8 items-center justify-center rounded-xl bg-[var(--tool-amethyst-bg)] text-[var(--tool-amethyst-color)]">
                                    <span class="material-symbols-rounded text-base" aria-hidden="true">grid_view</span>
                                </div>
                                <h3 class="text-sm font-semibold text-[var(--md-sys-color-on-surface)]">ماتریس اولویت × وضعیت</h3>
                            </header>
                            <div class="p-4 overflow-x-auto">
                                <table class="w-full text-xs border-separate border-spacing-1">
                                    <thead>
                                        <tr>
                                            <th class="text-left align-bottom pb-2 text-[var(--md-sys-color-on-surface-variant)] font-semibold"></th>
                                            @foreach($matrix['statuses'] as $s)
                                                <th class="text-center align-bottom pb-2 text-[var(--md-sys-color-on-surface-variant)] font-semibold whitespace-nowrap">{{ $s['label'] }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($matrix['priorities'] as $i => $p)
                                            <tr>
                                                <th class="text-left align-middle pr-2 py-1 text-[var(--md-sys-color-on-surface)] font-semibold whitespace-nowrap">{{ $p['label'] }}</th>
                                                @foreach($matrix['statuses'] as $j => $s)
                                                    @php($cell = $presenter->heatmapCell($matrix, $p['value'], $s['value']))
                                                    <td class="text-center align-middle tabular-nums rounded-lg py-2.5 font-semibold transition-colors"
                                                        @class([
                                                            'text-[var(--md-sys-color-on-surface)]',
                                                            'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]' => $cell['isFire'],
                                                        ])
                                                        @if(!$cell['isFire'] && $cell['intensity'] > 0) style="background-color: color-mix(in srgb, var(--md-sys-color-primary) {{ $cell['intensity'] }}%, transparent)" @endif>
                                                        {{ $cell['count'] > 0 ? convertToPersian($cell['count']) : '·' }}
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if(!empty($matrix['fire']))
                                    <p class="mt-3 text-[11px] text-[var(--md-sys-color-on-error-container)] flex items-center gap-1.5">
                                        <span class="material-symbols-rounded text-[14px]">local_fire_department</span>
                                        خانه‌های قرمز کارهای فوریِ شروع‌نشده یا در حال انجام هستند.
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        @foreach($tab['charts'] as $chartKey)
                            @php($cfg = $presenter->chartConfig($chartKey))
                            @php($cdata = $jsChartData[$chartKey] ?? null)
                            @php($hasData = $cdata && !empty($cdata['labels']))
                            @if($hasData || !in_array($chartKey, $gatedCharts))
                                <div class="analytics-card rounded-3xl flex flex-col min-h-[300px]">
                                    <header class="px-5 py-3.5 border-b border-[var(--md-sys-color-outline-variant)]/30 flex items-center gap-3 rounded-t-3xl shrink-0">
                                        <div class="flex size-8 items-center justify-center rounded-xl bg-[var(--tool-amethyst-bg)] text-[var(--tool-amethyst-color)]">
                                            <span class="material-symbols-rounded text-base" aria-hidden="true">{{ $cfg['type'] === 'doughnut' ? 'donut_large' : ($cfg['type'] === 'line' ? 'show_chart' : 'bar_chart') }}</span>
                                        </div>
                                        <h3 class="text-sm font-semibold text-[var(--md-sys-color-on-surface)]">{{ $cfg['label'] }}</h3>
                                    </header>

                                    <p class="px-5 pt-3 text-xs leading-6 text-[var(--md-sys-color-on-surface-variant)] shrink-0">{{ $cfg['description'] }}</p>

                                    @if($hasData)
                                        <div class="relative w-full h-[280px] p-4 lg:p-5 flex items-center justify-center">
                                            <div class="h-full" :class="chartConfig['{{ $chartKey }}']?.type === 'doughnut' ? 'w-full max-w-[260px]' : 'w-full'">
                                                <canvas id="analytics-chart-{{ $chartKey }}" role="img" aria-label="{{ $cfg['label'] }}"></canvas>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex-1 flex items-center justify-center p-4">
                                            <x-ui.empty icon="hourglass_empty" title="داده کافی نیست" variant="list"/>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </section>
    @endif
</div>