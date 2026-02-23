<div class="w-full h-full overflow-y-auto p-4 md:p-8 custom-scrollbar">

    {{-- ═══════════════════════════════════════════════════
         GREETING & HERO
    ═══════════════════════════════════════════════════ --}}
    <div class="mb-8">
        <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-[var(--md-sys-color-on-surface)] mb-2">
            {{ $greeting }}، {{ auth()->user()->name }}
        </h1>
        <p class="text-[var(--md-sys-color-on-surface-variant)] text-sm leading-relaxed">
            به پنل کاربری خوش آمدید. خلاصه وضعیت سیستم در اختیار شماست.
        </p>
    </div>

    {{-- ═══════════════════════════════════════════════════
         QUICK TOOLS GRID
    ═══════════════════════════════════════════════════ --}}
    <div class="flex items-center gap-3 mb-4">
        <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center">
            <span class="material-symbols-rounded text-base font-fill">grid_view</span>
        </div>
        <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">ابزارهای سریع</h2>
        <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
        @foreach($this->quickTools as $tool)
            <button
                wire:click="{{ $tool['action'] }}"
                class="group relative flex flex-col items-start p-4 md:p-5 h-32 md:h-36 rounded-2xl
                       bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm
                       hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]
                       hover:border-[var(--md-sys-color-primary)]/30 hover:-translate-y-1
                       active:scale-[0.98] transition-all duration-300 text-right overflow-hidden"
            >
                <div class="mb-auto p-2.5 rounded-xl bg-[var(--md-sys-color-{{ $tool['color'] ?? 'primary' }}-container)] text-[var(--md-sys-color-on-{{ $tool['color'] ?? 'primary' }}-container)] group-hover:scale-110 transition-transform duration-300">
                    <span class="material-symbols-rounded text-2xl">{{ $tool['icon'] }}</span>
                </div>

                <div class="relative z-10 w-full">
                    <h3 class="text-sm font-bold leading-tight text-[var(--md-sys-color-on-surface)] group-hover:text-[var(--md-sys-color-primary)] transition-colors">{{ $tool['title'] }}</h3>
                    @if(!empty($tool['sub']))
                        <p class="text-[10px] mt-1 text-[var(--md-sys-color-on-surface-variant)] opacity-80 line-clamp-1">{{ $tool['sub'] }}</p>
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

    <div class="relative overflow-hidden rounded-2xl mb-8
                bg-[var(--md-sys-color-surface)]
                border border-[var(--md-sys-color-outline-variant)]/20
                shadow-sm">

        {{-- Left accent stripe --}}
        <div class="absolute top-0 right-0 bottom-0 w-1.5 bg-[var(--md-sys-color-secondary)]"></div>

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

    <div class="space-y-3 mb-8" x-data="{ active: null }">
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
                           hover:bg-[var(--md-sys-color-surface-container-low)]">

                    <div class="flex items-center gap-4">
                        {{-- Numbered badge --}}
                        <div class="relative flex-shrink-0">
                            <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300 shadow-sm"
                                    :class="active === {{ $index }}
                                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] scale-110'
                                    : 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]'">
                                <span
                                        class="material-symbols-rounded text-xl">{{ $module['icon'] ?? 'extension' }}</span>
                            </div>
                        </div>

                        <div class="text-right">
                            <span
                                    class="text-sm font-bold text-[var(--md-sys-color-on-surface)] block group-hover:text-[var(--md-sys-color-primary)] transition-colors">{{ $module['title'] }}</span>
                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5 block opacity-70"
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
                                : 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]'">
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
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div class="mx-4 mb-4 p-5 rounded-xl
                                bg-[var(--md-sys-color-surface-container-lowest)]
                                border border-[var(--md-sys-color-outline-variant)]/10">
                        <p class="text-sm leading-[2] text-[var(--md-sys-color-on-surface-variant)] text-justify">
                            {{ $module['content'] }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
