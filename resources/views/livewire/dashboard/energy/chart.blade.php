<section class="w-full h-full flex flex-col gap-5 lg:gap-6" dir="rtl"
         x-data="energyChart(@js($history), @js($companyAverages), @js($sections), @js($latestTest ?? []))"
         x-init="init()">
    @if(empty($latestTest))
        <div
            class="flex flex-col items-center justify-center min-h-[400px] rounded-3xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)] p-8 text-center shadow-sm"
            role="status" aria-label="داده‌ای موجود نیست">
            <span class="material-symbols-rounded text-6xl text-[var(--md-sys-color-on-surface-variant)] mb-4"
                  aria-hidden="true">query_stats</span>
            <h3 class="text-lg font-semibold text-[var(--md-sys-color-on-surface)] mb-2">داده‌ای موجود نیست</h3>
            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] max-w-md">هنوز پرسشنامه‌ای تکمیل نشده است.
                پس از تکمیل ارزیابی، نتایج به‌صورت خودکار در این بخش نمایش داده می‌شوند.</p>
        </div>
    @else
        @php $overall = $latestTest['overall_score'] ?? 0; @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-6 items-start flex-1 min-h-[300px] w-full">
            <div x-data="{ max: false }" @keydown.escape.window="if(max) toggleMaximize($data)" class="order-2 lg:order-1 h-full w-full">
                <x-ui.modals.max-backdrop/>

                <div :class="{ 'max-widget': max }" class="w-full h-full rounded-3xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)] shadow-sm flex flex-col">
                    <header
                        class="px-5 py-4 border-b border-[var(--md-sys-color-outline-variant)]/30 flex items-center gap-3 bg-[var(--md-sys-color-surface)] rounded-t-3xl">
                        <div
                            class="flex size-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-secondary-container)]/60 text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-base" aria-hidden="true">trending_up</span>
                        </div>
                        <h2 class="text-sm font-semibold text-[var(--md-sys-color-on-surface)]">روند ۱۸ ماه اخیر</h2>
                        <button @click="toggleMaximize($data)"
    :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
    class="flex size-8 items-center justify-center rounded-lg hover:bg-[var(--md-sys-color-surface-variant)] transition-colors text-[var(--md-sys-color-on-surface-variant)]">
    <span class="material-symbols-rounded text-[20px]" x-text="max ? 'close_fullscreen' : 'open_in_full'"></span>
</button>
                    </header>

                    <div class="relative w-full flex-1 p-4 lg:p-5 min-h-[280px]">
                        <div x-ref="historyInlineHost" class="w-full h-full">
                            <canvas id="historyChart" role="img"
                                    aria-label="نمودار روند ۱۸ ماهه امتیازات انرژی"></canvas>
                        </div>
                    </div>
                </div>



            </div>

            <div x-data="{ max: false }"
                 @keydown.escape.window="max = false"
                 class="order-1 lg:order-2 h-full w-full">

                <div :class="{ 'max-widget': max }" class="w-full h-full rounded-3xl border border-[var(--md-sys-color-outline-variant)]/40 p-5 lg:p-8 overflow-hidden shadow-sm flex flex-col items-center text-center relative justify-center bg-[var(--md-sys-color-surface)]">
                    <button @click="toggleMaximize($data)"
    :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
    class="flex size-8 items-center justify-center rounded-lg hover:bg-[var(--md-sys-color-surface-variant)] transition-colors text-[var(--md-sys-color-on-surface-variant)]">
    <span class="material-symbols-rounded text-[20px]" x-text="max ? 'close_fullscreen' : 'open_in_full'"></span>
</button>

                    <div
                        class="absolute inset-0 bg-gradient-to-b from-[var(--md-sys-color-primary-container)]/10 to-transparent pointer-events-none rounded-3xl"></div>

                    <div
                        class="z-10 flex flex-col items-center justify-center space-y-4 flex-1 w-full max-w-xl mt-4 lg:mt-0">
                        <div class="flex flex-col items-center">
                            <h2 class="text-base font-semibold text-[var(--md-sys-color-on-surface)]">آخرین نتیجه
                                ارزیابی</h2>
                            <time class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)] mt-1"
                                  datetime="{{ $latestTest['completed_at'] ?? '' }}" dir="ltr">
                                {{ \Morilog\Jalali\Jalalian::forge($latestTest['completed_at'] ?? now())->format('Y-m-d H:i:s') }}
                            </time>
                        </div>
                        <div class="flex flex-col items-center justify-center mt-2">
                            <div
                                class="flex size-24 lg:size-28 items-center justify-center rounded-full bg-gradient-to-br from-[var(--md-sys-color-primary)] to-[var(--md-sys-color-primary)]/80 text-[var(--md-sys-color-on-primary)] shadow-lg shadow-[var(--md-sys-color-primary)]/20 ring-4 ring-[var(--md-sys-color-primary)]/10 mb-4 relative">
                                <span class="text-5xl lg:text-6xl font-black tracking-tighter">{{ $overall }}</span>
                                <div class="absolute inset-0 rounded-full border-2 border-white/20"></div>
                            </div>
                            <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">شاخص کل عملکرد</h3>
                            <p class="text-sm font-medium mt-2 px-4 py-1.5 rounded-full bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] inline-flex items-center gap-2">
                                <span class="size-2 rounded-full bg-[var(--md-sys-color-primary)] animate-pulse"></span>
                                {{ $presenter->getOverallMessage($overall) }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="w-full mt-6 pt-5 border-t border-[var(--md-sys-color-outline-variant)]/30 z-10 grid grid-cols-2 gap-3 lg:gap-4 max-w-xl">
                        @foreach($presenter->getDimensions() as $dim)
                            @php
                                $score = $latestTest[$dim.'_score'] ?? 0;
                                $colorVar = $presenter->getScoreColorVar($score);
                                $sectionTitle = $presenter->formatSectionTitle($sections[$dim] ?? $dim);
                            @endphp
                            <div
                                class="flex flex-col items-center justify-center gap-1 group bg-[var(--md-sys-color-surface-variant)]/20 p-3 rounded-2xl">
                                <div
                                    class="flex size-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)] group-hover:bg-[var(--md-sys-color-primary-container)] group-hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                                    <span class="material-symbols-rounded text-base"
                                          aria-hidden="true">{{ $presenter->getIcon($dim) }}</span>
                                </div>
                                <p class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] text-center line-clamp-1">{{ $sectionTitle }}</p>
                                <p class="text-lg font-bold leading-none"
                                   style="color: var({{ $colorVar }}, var(--md-sys-color-on-surface));">{{ $score }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>



                @if($isManager && count($teamMembersData))
                    <div x-data="{ max: false }" @keydown.escape.window="if(max) toggleMaximize($data)" class="w-full flex flex-col flex-1">
                        <x-ui.modals.max-backdrop/>
                        <div :class="{ 'max-widget': max }" class="w-full h-full rounded-3xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden flex flex-col">
                                <header
                                    class="px-5 py-3 border-b border-[var(--md-sys-color-outline-variant)]/30 flex items-center justify-between  bg-[var(--md-sys-color-primary)]/60  top-0 z-10 rounded-t-3xl">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex size-7 items-center justify-center rounded-xl bg-[var(--md-sys-color-primary-container)]/60 text-[var(--md-sys-color-on-primary-container)]">
                                            <span class="material-symbols-rounded text-sm"
                                                  aria-hidden="true">group</span>
                                        </div>
                                        <h2 class="text-sm font-semibold text-[var(--md-sys-color-on-surface)]">اعضای
                                            تیم</h2>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-flex items-center rounded-md bg-[var(--md-sys-color-primary-container)] px-2 py-0.5 text-[11px] font-bold text-[var(--md-sys-color-on-primary-container)]">
                                            {{ count($teamMembersData) }} نفر
                                        </span>
                                        <button @click="toggleMaximize($data)"
    :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
    class="flex size-8 items-center justify-center rounded-lg hover:bg-[var(--md-sys-color-surface-variant)] transition-colors text-[var(--md-sys-color-on-surface-variant)]">
    <span class="material-symbols-rounded text-[20px]" x-text="max ? 'close_fullscreen' : 'open_in_full'"></span>
</button>
                                    </div>
                                </header>
                                <div class="overflow-x-auto overflow-y-auto flex-1 custom-scrollbar">
                                    <table class="w-full text-[13px]" dir="rtl">
                                        <caption class="sr-only">لیست امتیازات اعضای تیم</caption>
                                        <tbody class="divide-y divide-[var(--md-sys-color-outline-variant)]/10">
                                        @foreach($teamMembersData as $member)
                                            <tr class="transition-colors hover:bg-[var(--md-sys-color-primary-container)]/10 group">
                                                <td class="px-5 py-3 font-medium text-[var(--md-sys-color-on-surface)] truncate max-w-[140px] group-hover:text-[var(--md-sys-color-primary)] transition-colors"
                                                    title="{{ $member['name'] }}">{{ $member['name'] }}</td>
                                                <td class="px-5 py-3 text-left w-16">
                                                    <span
                                                        class="inline-flex min-w-[28px] h-6 items-center justify-center rounded-full px-2 text-xs font-bold text-white shadow-[0_2px_4px_rgba(0,0,0,0.1)]"
                                                        style="background-color: var({{ $presenter->getScoreColorVar($member['scores']['overall'] ?? 0) }}, var(--md-sys-color-primary));">
                                                        {{ $member['scores']['overall'] ?? '-' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>


                    </div>
                @endif
            </div>
        </div>
    @endif
</section>
