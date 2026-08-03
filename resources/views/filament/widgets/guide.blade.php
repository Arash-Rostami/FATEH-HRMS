<x-filament-widgets::widget>
    <x-filament::section
        icon="heroicon-o-book-open"
        heading="{{ __('resources/dashboard/strings.guide.heading') }}"
        description="{{ count(config('modules', [])) }} {{ __('resources/dashboard/strings.guide.module_count_suffix') }}"
        collapsible
        collapsed
    >
    <div dir="rtl">
        <p class="text-sm leading-8 text-[var(--md-sys-color-on-surface-variant)] mb-5 text-justify">
            این بستر مدیریتی بر پایه معماری قدرتمند معماری ماژولار (مبتنی بر دامنه - Domain Driven) توسعه یافته است تا
            چابکی و دقت را در فرآیندهای مدیریت داده‌های سازمان به ارمغان آورد. ماژول‌ها در ۴ قلمرو اصلی دسته‌بندی
            شده‌اند تا مدیران سیستم بتوانند با دیدگاهی فرآیند‌محور، پایداری جریان اطلاعات و یکپارچگی محتوا (CMS) را
            تضمین کنند. این راهنما منطق عملیاتی و بهترین شیوه‌های راهبری هر زیرسیستم را ارائه می‌دهد.
        </p>

        <div class="relative overflow-hidden rounded-3xl mb-8 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm">
            <div class="absolute top-0 right-0 bottom-0 w-1.5 rounded-r-3xl bg-[var(--md-sys-color-primary)]"></div>

            <div class="grid md:grid-cols-[auto_1fr] gap-0">
                <div class="flex items-start justify-center p-6 md:p-8 md:border-l border-[var(--md-sys-color-outline-variant)]/50">
                    <div class="w-14 h-14 rounded-2xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shadow-sm">
                        <span class="material-symbols-rounded text-3xl font-fill">speed</span>
                    </div>
                </div>

                <div class="p-6 md:p-8 md:pr-6">
                    <p class="text-sm font-semibold text-[var(--md-sys-color-on-surface)] mb-4 flex items-center gap-2">
                        <span class="material-symbols-rounded text-primary">layers</span>
                        {{ __('resources/dashboard/strings.guide.tools_heading') }}
                    </p>

                    <p class="text-[13px] leading-[2.2] text-[var(--md-sys-color-on-surface-variant)] text-justify mb-6">
                        هسته این پلتفرم ابزارهای قدرتمندی را برای مدیریت حجم کلان داده‌ها ارائه می‌دهد. یکپارچه‌سازی
                        ابزارهای پایه‌ای نظیر <strong>جداول اطلاعات پایه (Table)</strong>، <strong>فرم‌های قابل ویرایش
                            (Form)</strong> و <strong>نمای ساختاریافته (Info List)</strong> در کنار قابلیت‌های
                        پیشرفته‌ای چون <strong>مدیریت روابط (Relation Managers)</strong>، <strong>فیلترینگ مرکب
                            (Filters)</strong> و <strong>جستجوی سراسری و اختصاصی (Search)</strong>، دستیابی به اطلاعات
                        را سرعت می‌بخشد. همچنین با اضافه شدن زیرساخت‌های هوشمندی مانند <strong>مدیریت تو در تو (Nested
                            Resources)</strong>، <strong>خلاصه‌سازی ستون‌ها (Table Summaries)</strong> و <strong>بارگذاری
                            تدریجی بخش‌های تحلیلی (Deferred Analytics)</strong>، کنترل داده‌ها در سطح استاندارد
                        انترپرایز تضمین شده است.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($this->getTools() as $tool)
                            <div class="flex items-start gap-3 p-3 rounded-2xl bg-[var(--md-sys-color-surface-variant)]/40 border border-[var(--md-sys-color-outline-variant)]/30 hover:bg-[var(--md-sys-color-surface-variant)] transition-colors">
                                <span class="material-symbols-rounded text-[20px] text-primary mt-1">{{ $tool['icon'] }}</span>
                                <div>
                                    <div class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $tool['label'] }}</div>
                                    <div class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">{{ $tool['desc'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 mb-5">
            <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center">
                <span class="material-symbols-rounded text-base font-fill">account_tree</span>
            </div>
            <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">{{ __('resources/dashboard/strings.guide.architecture_heading') }}</h2>
            <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
        </div>

        <div class="space-y-4" x-data="{ category: null, module: null }">
            @foreach($this->getGroupedModules() as $hashedCategory => $categoryData)
                <div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden bg-[var(--md-sys-color-surface)] shadow-sm">
                    <button @click="category = (category === '{{ $hashedCategory }}' ? null : '{{ $hashedCategory }}')" class="w-full flex items-center justify-between px-5 py-4 text-right bg-[var(--md-sys-color-surface-variant)]/20 hover:bg-[var(--md-sys-color-surface-variant)]/40 transition focus:outline-none">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-sm text-[var(--md-sys-color-on-surface)]">{{ $categoryData['name'] }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/30">
                                {{ $categoryData['modules']->count() }} {{ __('resources/dashboard/strings.guide.subsystem_count_suffix') }}
                            </span>
                            <span class="material-symbols-rounded text-xl transition-transform duration-300 text-[var(--md-sys-color-outline)]" :class="category === '{{ $hashedCategory }}' ? 'rotate-180 text-[var(--md-sys-color-primary)]' : ''">
                                expand_more
                            </span>
                        </div>
                    </button>

                    <div x-show="category === '{{ $hashedCategory }}'" x-collapse x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-[-10px]" x-transition:enter-end="opacity-100 translate-y-0" class="p-4 space-y-3 bg-[var(--md-sys-color-surface)]">
                        @foreach($categoryData['modules'] as $index => $module)
                            <div class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden transition-all duration-200" :class="module === '{{ $hashedCategory.'-'.$index }}' ? 'border-[var(--md-sys-color-primary)]/30 shadow-sm' : 'hover:border-[var(--md-sys-color-outline-variant)]'">
                                <button @click="module = (module === '{{ $hashedCategory.'-'.$index }}' ? null : '{{ $hashedCategory.'-'.$index }}')" class="w-full flex items-center justify-between px-4 py-3 text-right hover:bg-[var(--md-sys-color-surface-variant)]/10 transition focus:outline-none">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors" :class="module === '{{ $hashedCategory.'-'.$index }}' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'">
                                            <x-filament::icon :icon="$module['filament_icon'] ?? 'heroicon-o-squares-2x2'" class="w-5 h-5" />
                                        </div>
                                        <span class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $module['title'] }}</span>
                                    </div>
                                    <span class="material-symbols-rounded text-lg transition-transform text-[var(--md-sys-color-outline)]" :class="module === '{{ $hashedCategory.'-'.$index }}' ? 'rotate-180 text-[var(--md-sys-color-primary)]' : ''">
                                        expand_more
                                    </span>
                                </button>

                                <div x-show="module === '{{ $hashedCategory.'-'.$index }}'" x-collapse class="px-4 pb-4">
                                    <div class="rounded-xl bg-[var(--md-sys-color-surface-variant)]/20 border border-[var(--md-sys-color-outline-variant)]/30 border-r-2 border-r-[var(--md-sys-color-primary)]/70 p-4">
                                        <div class="flex items-start gap-2 mb-3">
                                            <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)] mt-0.5">info</span>
                                            <p class="text-[13px] leading-[2] text-[var(--md-sys-color-on-surface-variant)] text-justify">
                                                {{ $module['summary'] }}
                                            </p>
                                        </div>

                                        <div class="bg-[var(--md-sys-color-surface)] rounded-lg p-3 border border-[var(--md-sys-color-outline-variant)]/20 mt-3">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-secondary)]">settings_suggest</span>
                                                <span class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)]">{{ __('resources/dashboard/strings.guide.strategy_heading') }}</span>
                                            </div>
                                            <p class="text-[12px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)] text-justify pr-6">
                                                {{ $module['admin_tip'] ?? 'مدیریت پیوسته جریان داده‌ها و اعتبارسنجی دوره‌ای با استفاده از ابزارهای بومی پنل.' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    </x-filament::section>
</x-filament-widgets::widget>
