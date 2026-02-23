<div class="w-full max-w-8xl md:scale-[0.95] mx-auto p-4 md:p-8 !pt-0 pb-10 text-[var(--md-sys-color-on-surface)]"
     dir="rtl"
     x-data="greeting('{{ addslashes(shortGreeting()) }}')">


    {{-- ═══════════════════════════════════════════════════
             HERO BANNER
        ═══════════════════════════════════════════════════ --}}
    <div class="relative overflow-hidden rounded-2xl mb-4
                bg-[var(--md-sys-color-primary-container)]
                border border-[var(--md-sys-color-primary)]/20
                shadow-[0_2px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]">

        {{-- Decorative mesh --}}
        <div class="absolute inset-0 pointer-events-none opacity-[0.06]"
             style="background-image: radial-gradient(circle, var(--md-sys-color-primary) 1px, transparent 1px); background-size: 28px 28px;"></div>

        {{-- Large ghost icon --}}
        <div class="absolute -left-6 top-1/2 -translate-y-1/2 opacity-[0.07] pointer-events-none select-none">
            <span class="material-symbols-rounded font-fill" style="font-size: 220px; line-height:1;">menu_book</span>
        </div>
        {{-- Glow orbs --}}
        <div class="absolute top-0 right-0 w-64 h-64 rounded-full blur-3xl opacity-30 pointer-events-none"
             style="background: var(--md-sys-color-primary); transform: translate(30%, -30%);"></div>
        <div class="absolute bottom-0 left-1/3 w-48 h-48 rounded-full blur-3xl opacity-20 pointer-events-none"
             style="background: var(--md-sys-color-tertiary); transform: translateY(40%);"></div>

        <div class="relative z-10 px-8 py-10 md:py-14 flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
            <div class="space-y-4">
                {{-- Eyebrow --}}
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl
                            bg-[var(--md-sys-color-primary)]/15 border border-[var(--md-sys-color-primary)]/20">
                    <span class="w-2 h-2 rounded-full bg-[var(--md-sys-color-primary)] animate-pulse"></span>
                    <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-[var(--md-sys-color-on-primary-container)]">
                      {{ config('app.company_name') }}
                    </span>
                </div>

                <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-[var(--md-sys-color-on-primary-container)] leading-snug"
                    x-text="displayed"></h1>

                <p class="text-sm md:text-base text-[var(--md-sys-color-on-primary-container)]/70 leading-relaxed max-w-md">
                    ✨
                    ابزارهای کلیدی، ماژول‌ها و راهنمای سیستم همه در یک‌جا
                </p>
            </div>

            {{-- Stat chips --}}
            <div class="flex flex-wrap gap-3 shrink-0">
                @foreach([
                    ['icon' => 'group',       'label' => 'کارمندان',    'value' => $stats['employees']  ?? '—'],
                    ['icon' => 'task_alt',    'label' => 'درخواست‌ها',  'value' => $stats['requests']   ?? '—'],
                    ['icon' => 'event_note',  'label' => 'رویدادها',    'value' => $stats['events']     ?? '—'],
                ] as $stat)
                    <div class="flex flex-col items-center justify-center gap-1 px-5 py-3 rounded-2xl
                            bg-[var(--md-sys-color-on-primary-container)]/8
                            border border-[var(--md-sys-color-on-primary-container)]/12
                            backdrop-blur-sm min-w-[88px]">
                        <span
                                class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]">{{ $stat['icon'] }}</span>
                        <span
                                class="text-xl font-bold text-[var(--md-sys-color-on-primary-container)]">{{ $stat['value'] }}</span>
                        <span
                                class="text-[10px] font-medium text-[var(--md-sys-color-on-primary-container)]/60 tracking-wide">{{ $stat['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         SECTION LABEL: QUICK TOOLS
    ═══════════════════════════════════════════════════ --}}
    <div class="flex items-center gap-3 mb-4">
        <div
                class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center">
            <span class="material-symbols-rounded text-base font-fill">bolt</span>
        </div>
        <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">دسترسی سریع</h2>
        <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         QUICK ACCESS TOOLS — M3 Expressive tonal cards
    ═══════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-8">
        @foreach($this->tools as $index => $tool)
            <button
                    type="button"
                    @if($tool['action'] === 'profile')
                        @click="window.open('{{ route('profile') }}', '_blank')"
                    @else
                        wire:click='$dispatch("switch-tab", { tab: "{{ $tool['action'] }}" })'
                    @endif
                    class="group relative overflow-hidden rounded-2xl p-5 md:p-6 text-right
                       bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm
                       transition-all duration-300 ease-out
                       hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]
                       hover:-translate-y-1 hover:border-[var(--md-sys-color-primary)]/30
                       active:scale-[0.97] active:translate-y-0
                       focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40"
                    style="color: {{ $tool['text'] }};">

                {{-- Subtle inner grid texture --}}
                <div class="absolute inset-0 opacity-[0.04] pointer-events-none"
                     style="background-image: repeating-linear-gradient(45deg, currentColor 0, currentColor 1px, transparent 0, transparent 50%); background-size: 12px 12px;"></div>

                {{-- Arrow reveal --}}
                <div
                        class="absolute top-4 left-4 opacity-0 -translate-x-2 group-hover:opacity-60 group-hover:translate-x-0 transition-all duration-300">
                    <span class="material-symbols-rounded text-base">touch_app</span>
                </div>

                <div class="relative z-10">
                    <div class="mb-4 w-12 h-12 rounded-xl flex items-center justify-center
                                bg-current/10 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6">
                        <span class="material-symbols-rounded text-2xl font-fill" style="color: {{ $tool['text'] }}">
                            {{ $tool['icon'] }}
                        </span>
                    </div>
                    <h3 class="text-sm md:text-base font-bold leading-tight">{{ $tool['title'] }}</h3>
                    @if(!empty($tool['sub']))
                        <p class="text-[11px] mt-1 opacity-60">{{ $tool['sub'] }}</p>
                    @endif
                </div>
            </button>
        @endforeach
    </div>

    {{-- ═══════════════════════════════════════════════════
         INTERFACE GUIDE — elevated split card
    ═══════════════════════════════════════════════════ --}}
    <div class="flex items-center gap-3 mb-4">
        <div
                class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center">
            <span class="material-symbols-rounded text-base font-fill">info</span>
        </div>
        <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">راهنمای رابط کاربری</h2>
        <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
    </div>

    <div class="relative overflow-hidden rounded-3xl mb-8
                bg-[var(--md-sys-color-surface)]
                border border-[var(--md-sys-color-outline-variant)]/20
                shadow-sm">

        {{-- Left accent stripe --}}
        <div class="absolute top-0 right-0 bottom-0 w-1.5 rounded-r-3xl bg-[var(--md-sys-color-secondary)]"></div>

        <div class="grid md:grid-cols-[auto_1fr] gap-0">
            {{-- Icon column --}}
            <div
                    class="flex items-start justify-center p-6 md:p-8 md:border-l border-[var(--md-sys-color-outline-variant)]/50">
                <div class="w-14 h-14 rounded-2xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]
                            flex items-center justify-center shadow-sm">
                    <span class="material-symbols-rounded text-3xl font-fill">map</span>
                </div>
            </div>

            {{-- Content --}}
            <div class="p-6 md:p-8 md:pr-6">
                <p class="text-sm leading-[2] text-[var(--md-sys-color-on-surface-variant)] text-justify">
                    {{--                    {{ strip_tags($guideText ?? 'نوار کناری راست در حالت دسکتاپ و نوار پیمایش پایین، دسترسی سریع به ابزارهای ضروری روزمره‌ای را فراهم می‌کنند که بیشترین استفاده را در اپلیکیشن خواهید داشت. نوار کناری چپ برای بارگذاری ابزارهای کاربردی و قابلیت‌های اصلی پرتکرار طراحی شده است. نوار پیمایش بالا نیز دسترسی راحت به ابزارهای کم‌کاربرد و تنظیمات را در یک مکان مشخص فراهم می‌کند.') }}--}}
                    {{ superClean('نوار کناری راست در حالت دسکتاپ و نوار پیمایش پایین، دسترسی سریع به ابزارهای ضروری روزمره‌ای را فراهم می‌کنند که بیشترین استفاده را در اپلیکیشن خواهید داشت. نوار کناری چپ برای بارگذاری ابزارهای کاربردی و قابلیت‌های اصلی پرتکرار طراحی شده است. نوار پیمایش بالا نیز دسترسی راحت به ابزارهای کم‌کاربرد و تنظیمات را در یک مکان مشخص فراهم می‌کند. این نوار میان‌برهایی برای تنظیمات وضعیت و دسترسی، حالت نمایش، تم و رنگ‌بندی، افکت‌های پس‌زمینه، بازنشانی حافظه و پالت دستورات جهت یافتن سریع ابزارهای مورد نظر به زبان فارسی یا انگلیسی در اختیار شما قرار می‌دهد.', 2000) }}
                </p>

                {{-- Navigation landmark chips --}}
                <div class="flex flex-wrap gap-2 mt-5">
                    @foreach([
                        ['icon' => 'view_sidebar', 'label' => 'نوار کناری راست', 'color' => 'primary'],
                        ['icon' => 'view_sidebar', 'label' => 'نوار کناری چپ', 'color' => 'secondary', 'class' => '-scale-x-100'],
                        ['icon' => 'menu', 'label' => 'نوار بالا', 'color' => 'tertiary'],
                        ['icon' => 'bottom_navigation', 'label' => 'نوار پایین', 'color' => 'secondary'],
                    ] as $nav)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-semibold bg-[var(--md-sys-color-{{ $nav['color'] }}-container)] text-[var(--md-sys-color-on-{{ $nav['color'] }}-container)] border border-[var(--md-sys-color-{{ $nav['color'] }})]/15">
                            <span class="material-symbols-rounded text-[13px] {{ $nav['class'] ?? '' }}">{{ $nav['icon'] }}</span>
                                     {{ $nav['label'] }}
                             </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         MODULES ACCORDION
    ═══════════════════════════════════════════════════ --}}
    <div class="flex items-center gap-3 mb-4">
        <div
                class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center">
            <span class="material-symbols-rounded text-base font-fill">layers</span>
        </div>
        <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">ماژول‌های سیستم</h2>
        <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
        <span class="text-[11px] font-bold px-2.5 py-1 rounded-xl
                     bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
            {{ count($this->modules) }} ماژول
        </span>
    </div>

    <div class="space-y-2 mb-8" x-data="{ active: null }">
        @foreach($this->modules as $index => $module)
            <div class="rounded-2xl overflow-hidden
                        border border-[var(--md-sys-color-outline-variant)]/20
                        bg-[var(--md-sys-color-surface)]
                        transition-all duration-300 shadow-sm"
                 :class="active === {{ $index }}
                    ? 'shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] border-[var(--md-sys-color-primary)]/30 ring-1 ring-[var(--md-sys-color-primary)]/20'
                    : 'hover:border-[var(--md-sys-color-primary)]/30 hover:shadow-md'">

                <button
                        @click="active = (active === {{ $index }} ? null : {{ $index }})"
                        class="w-full flex items-center justify-between p-4 md:p-5 text-right
                           transition-colors duration-200 focus:outline-none
                           hover:bg-[var(--md-sys-color-surface-variant)]/30">

                    <div class="flex items-center gap-4">
                        {{-- Numbered badge --}}
                        <div class="relative flex-shrink-0">
                            <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300"
                                    :class="active === {{ $index }}
                                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] scale-110'
                                    : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'">
                                <span
                                        class="material-symbols-rounded text-xl">{{ $module['icon'] ?? 'extension' }}</span>
                            </div>
                        </div>

                        <div class="text-right">
                            <span
                                    class="text-sm font-bold text-[var(--md-sys-color-on-surface)] block">{{ $module['title'] }}</span>
                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5 block"
                                  x-show="active !== {{ $index }}">
                                کلیک کنید برای اطلاعات بیشتر
                            </span>
                        </div>
                    </div>

                    {{-- Counter badge + chevron --}}
                    <div class="flex items-center gap-3">
                        <span
                                class="hidden sm:flex text-[10px] font-bold px-2 py-0.5 rounded-lg transition-all duration-200"
                                :class="active === {{ $index }}
                                ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'
                                : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'">
                            {{ sprintf('%02d', $index + 1) }}
                        </span>
                        <span class="material-symbols-rounded text-2xl transition-all duration-300"
                              :class="active === {{ $index }}
                                ? 'rotate-180 text-[var(--md-sys-color-primary)]'
                                : 'text-[var(--md-sys-color-outline)]'">
                            expand_more
                        </span>
                    </div>
                </button>

                {{-- Content panel --}}
                <div x-show="active === {{ $index }}"
                     x-transition:enter="transition ease-out duration-250"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div class="mx-4 mb-4 p-5 rounded-xl
                                bg-[var(--md-sys-color-surface-variant)]/30
                                border border-[var(--md-sys-color-primary)]/10
                                border-r-2 border-r-[var(--md-sys-color-primary)]">
                        <p class="text-sm leading-[2] text-[var(--md-sys-color-on-surface-variant)] text-justify">
                            {{ $module['content'] }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
