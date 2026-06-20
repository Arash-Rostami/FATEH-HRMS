<section class="w-full" dir="rtl"
         x-data="energyChart(@js($history), @js($companyAverages), @js($sections), @js($latestTest ?? []))"
         x-init="init()">
    @empty($latestTest)
        <div class="flex flex-col items-center justify-center min-h-[400px] rounded-2xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)] p-8 text-center shadow-sm" role="status" aria-label="داده‌ای موجود نیست">
            <span class="material-symbols-rounded text-6xl text-[var(--md-sys-color-on-surface-variant)] mb-4" aria-hidden="true">query_stats</span>
            <h3 class="text-lg font-semibold text-[var(--md-sys-color-on-surface)] mb-2">داده‌ای موجود نیست</h3>
            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] max-w-md">هنوز پرسشنامه‌ای تکمیل نشده است. پس از تکمیل ارزیابی، نتایج به‌صورت خودکار در این بخش نمایش داده می‌شوند.</p>
        </div>
    @else
        <div class="flex flex-col gap-6 w-full">

            {{-- Row 1: Top Stats (Latest Result & Overall Score) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full max-w-4xl mx-auto">
                {{-- Latest Result --}}
                <div class="flex items-center gap-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)] p-4 shadow-sm justify-center md:justify-start">
                    <div class="flex size-12 items-center justify-center rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-2xl" aria-hidden="true">monitoring</span>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-[var(--md-sys-color-on-surface)] leading-tight">آخرین نتیجه ارزیابی</h2>
                        <time class="text-xs text-[var(--md-sys-color-on-surface-variant)]" datetime="{{ $latestTest['completed_at'] ?? '' }}">
                            {{ isset($latestTest['completed_at']) ? \Morilog\Jalali\Jalalian::fromDateTime($latestTest['completed_at'])->format('Y-m-d H:i:s') : '-' }}
                        </time>
                    </div>
                </div>

                {{-- Overall Score --}}
                @php $overall = $latestTest['overall_score'] ?? 0; @endphp
                <div class="flex items-center gap-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)] p-4 shadow-sm justify-center md:justify-start">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="text-xl font-bold">{{ $overall }}</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--md-sys-color-on-surface)]">شاخص کل عملکرد</h3>
                        <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] mt-0.5 leading-relaxed truncate max-w-[200px]" title="{{ $presenter->getOverallMessage($overall) }}">
                            {{ $presenter->getOverallMessage($overall) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Row 2: 4 Dimension Cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 w-full">
                @foreach($presenter->getDimensions() as $dim)
                    @php
                        $score = $latestTest[$dim.'_score'] ?? 0;
                        $colorVar = $presenter->getScoreColorVar($score);
                        $sectionTitle = $presenter->formatSectionTitle($sections[$dim] ?? $dim);
                    @endphp
                    <article class="group flex flex-col items-center justify-center gap-2 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)] p-4 shadow-sm transition-all hover:border-[var(--md-sys-color-primary)]/30 hover:shadow-md hover:-translate-y-1 focus-within:ring-2 focus-within:ring-[var(--md-sys-color-primary)]/50" tabindex="0" aria-label="امتیاز بخش {{ $sectionTitle }}: {{ $score }}">
                        <div class="flex size-10 items-center justify-center rounded-full bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-colors group-hover:bg-[var(--md-sys-color-primary-container)] group-hover:text-[var(--md-sys-color-on-primary-container)]">
                            <span class="material-symbols-rounded text-xl" aria-hidden="true">{{ $presenter->getIcon($dim) }}</span>
                        </div>
                        <p class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] text-center line-clamp-1">
                            {{ $sectionTitle }}
                        </p>
                        <p class="text-2xl font-bold tracking-tight" style="color: var({{ $colorVar }}, var(--md-sys-color-on-surface));">
                            {{ $score }}
                        </p>
                    </article>
                @endforeach
            </div>

            {{-- Row 3: Charts Side-by-Side --}}
            @if(count($history) > 0)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">
                    {{-- History Chart --}}
                    <div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden flex flex-col">
                        <header class="px-4 py-3 border-b border-[var(--md-sys-color-outline-variant)]/40 flex items-center gap-2">
                            <div class="flex size-7 items-center justify-center rounded-lg bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                                <span class="material-symbols-rounded text-sm" aria-hidden="true">trending_up</span>
                            </div>
                            <h2 class="text-xs font-semibold text-[var(--md-sys-color-on-surface)]">روند ۱۸ ماه اخیر</h2>
                        </header>
                        <div class="relative p-3 min-h-[260px] flex-grow">
                            <canvas id="historyChart" role="img" aria-label="نمودار روند ۱۸ ماهه امتیازات انرژی"></canvas>
                        </div>
                    </div>

                    {{-- Radar Chart --}}
                    <div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden flex flex-col">
                        <header class="px-4 py-3 border-b border-[var(--md-sys-color-outline-variant)]/40 flex items-center gap-2">
                            <div class="flex size-7 items-center justify-center rounded-lg bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                                <span class="material-symbols-rounded text-sm" aria-hidden="true">radar</span>
                            </div>
                            <h2 class="text-xs font-semibold text-[var(--md-sys-color-on-surface)]">مقایسه با میانگین سازمان</h2>
                        </header>
                        <div class="flex items-center justify-center p-3 flex-grow">
                            <div class="w-full max-w-[280px] relative">
                                <canvas id="radarChart" role="img" aria-label="نمودار راداری مقایسه عملکرد"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Row 4: Team Members Table (Compact) --}}
            @if($isManager && count($teamMembersData))
                <div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden w-full max-w-4xl mx-auto">
                    <header class="px-4 py-3 border-b border-[var(--md-sys-color-outline-variant)]/40 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex size-6 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                                <span class="material-symbols-rounded text-sm" aria-hidden="true">group</span>
                            </div>
                            <h2 class="text-xs font-semibold text-[var(--md-sys-color-on-surface)]">اعضای تیم</h2>
                        </div>
                        <span class="inline-flex items-center rounded-lg bg-[var(--md-sys-color-primary-container)] px-2 py-0.5 text-[10px] font-bold text-[var(--md-sys-color-on-primary-container)]">
                            {{ count($teamMembersData) }}
                        </span>
                    </header>
                    <div class="overflow-x-auto max-h-[220px] overflow-y-auto">
                        <table class="w-full text-xs" dir="rtl">
                            <caption class="sr-only">لیست امتیازات اعضای تیم</caption>
                            <thead class="sticky top-0 z-10 bg-[var(--md-sys-color-surface)] shadow-sm">
                            <tr>
                                <th scope="col" class="px-4 py-2 text-right font-semibold text-[var(--md-sys-color-on-surface-variant)]">نام عضو</th>
                                <th scope="col" class="px-2 py-2 text-center font-semibold text-[var(--md-sys-color-on-surface-variant)] w-24">امتیاز کل</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--md-sys-color-outline-variant)]/20">
                            @forelse($teamMembersData as $member)
                                <tr class="transition-colors hover:bg-[var(--md-sys-color-primary-container)]/10">
                                    <td class="px-4 py-2 font-medium text-[var(--md-sys-color-on-surface)] truncate max-w-[200px]" title="{{ $member['name'] }}">
                                        {{ $member['name'] }}
                                    </td>
                                    <td class="px-2 py-2 text-center">
                                            <span class="inline-flex size-6 items-center justify-center rounded-full text-[10px] font-bold text-white shadow-sm"
                                                  style="background-color: var({{ $presenter->getScoreColorVar($member['scores']['overall'] ?? 0) }}, var(--md-sys-color-primary));">
                                                {{ $member['scores']['overall'] ?? '-' }}
                                            </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-4 text-center text-[var(--md-sys-color-on-surface-variant)]">
                                        داده‌ای برای نمایش موجود نیست.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    @endempty
</section>
