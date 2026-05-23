<x-filament-widgets::widget>
    <div class="rounded-3xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)] p-4 md:p-6 shadow-sm shadow-[var(--md-sys-elevation-1)]" dir="rtl">
        <x-ui.title
            icon="hub"
            title="{{ __('راهنمای راهبری و مدیریت سیستم (ERP Base)') }}"
            :count="count(config('modules', []))"
            countLabel="ماژول یکپارچه"
        />

        <p class="text-sm leading-8 text-[var(--md-sys-color-on-surface-variant)] mb-5 text-justify">
            این بستر مدیریتی بر پایه معماری قدرتمند Filament توسعه یافته است تا چابکی و دقت را در فرآیندهای مدیریت داده‌های سازمان به ارمغان آورد. ماژول‌ها در ۴ قلمرو اصلی دسته‌بندی شده‌اند تا مدیران سیستم بتوانند با دیدگاهی فرآیند‌محور، پایداری جریان اطلاعات و یکپارچگی محتوا (CMS) را تضمین کنند. این راهنما منطق عملیاتی و بهترین شیوه‌های راهبری هر زیرسیستم را ارائه می‌دهد.
        </p>

        {{-- ═══════════════════════════════════════════════════
             INTERFACE GUIDE — elevated split card (Similar to User Panel Home)
        ═══════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden rounded-3xl mb-8 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm">
            <div class="absolute top-0 right-0 bottom-0 w-1.5 rounded-r-3xl bg-[var(--md-sys-color-primary)]"></div>

            <div class="grid md:grid-cols-[auto_1fr] gap-0">
                <div class="flex items-start justify-center p-6 md:p-8 md:border-l border-[var(--md-sys-color-outline-variant)]/50">
                    <div class="w-14 h-14 rounded-2xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shadow-sm">
                        <span class="material-symbols-rounded text-3xl font-fill">speed</span>
                    </div>
                </div>

                <div class="p-6 md:p-8 md:pr-6">
                    <p class="text-sm font-semibold text-[var(--md-sys-color-on-surface)] mb-3">
                        ابزارهای کلیدی مدیریت داده در پنل ادمین
                    </p>
                    <p class="text-[13px] leading-[2] text-[var(--md-sys-color-on-surface-variant)] text-justify mb-5">
                        رابط کاربری ادمین ابزارهای قدرتمندی برای مدیریت حجم وسیع داده‌ها ارائه می‌دهد. استفاده از <strong>مدیریت روابط (Relation Managers)</strong> امکان ویرایش موجودیت‌های مرتبط را مستقیماً از درون صفحه یک رکورد (Infolist/Form) فراهم می‌کند. برای بازیابی هدفمند داده‌ها از <strong>گروه‌بندی فیلترها (Filter Groups)</strong>، <strong>مرتب‌سازی (Sorting)</strong> و <strong>جستجوی سراسری (Global Search)</strong> بهره بگیرید. همچنین به‌منظور تمرکز بر داده‌های حساس، می‌توانید نمایش ستون‌ها را با <strong>Toggle Columns</strong> شخصی‌سازی کرده و برای اعمال تغییرات در سطح کلان، از <strong>Bulk Actions</strong> نظیر تغییر وضعیت یا خروجی‌گیری (Export) استفاده نمایید.
                    </p>

                    <div class="flex flex-wrap gap-2">
                        @foreach([
                            ['icon' => 'schema', 'label' => 'Relation Managers (روابط یکپارچه)'],
                            ['icon' => 'filter_list', 'label' => 'Filters & Groups (پایش هوشمند)'],
                            ['icon' => 'view_column', 'label' => 'Toggle Columns (مدیریت نما)'],
                            ['icon' => 'fact_check', 'label' => 'Bulk Actions (عملیات گروهی)'],
                            ['icon' => 'sort', 'label' => 'Sort & Search (بازیابی سریع)'],
                            ['icon' => 'table_rows', 'label' => 'Infolist & Tables (نمایش ساختاریافته)'],
                        ] as $tool)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-semibold bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">
                                <span class="material-symbols-rounded text-[14px]">{{ $tool['icon'] }}</span>
                                {{ $tool['label'] }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════
             MODULES ACCORDION
        ═══════════════════════════════════════════════════ --}}
        <div class="flex items-center gap-3 mb-5">
            <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center">
                <span class="material-symbols-rounded text-base font-fill">account_tree</span>
            </div>
            <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">معماری ماژول‌های سیستم</h2>
            <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
        </div>

        <div class="space-y-4" x-data="{ category: null, module: null }">
            @foreach($this->getGroupedModules() as $category => $modules)
                <div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden bg-[var(--md-sys-color-surface)] shadow-sm">
                    <button
                        @click="category = (category === '{{ md5($category) }}' ? null : '{{ md5($category) }}')"
                        class="w-full flex items-center justify-between px-5 py-4 text-right bg-[var(--md-sys-color-surface-variant)]/20 hover:bg-[var(--md-sys-color-surface-variant)]/40 transition focus:outline-none"
                    >
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-sm text-[var(--md-sys-color-on-surface)]">{{ $category }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/30">
                                {{ $modules->count() }} زیرسیستم
                            </span>
                            <span class="material-symbols-rounded text-xl transition-transform duration-300 text-[var(--md-sys-color-outline)]" :class="category === '{{ md5($category) }}' ? 'rotate-180 text-[var(--md-sys-color-primary)]' : ''">
                                expand_more
                            </span>
                        </div>
                    </button>

                    <div x-show="category === '{{ md5($category) }}'"
                         x-collapse
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-[-10px]"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="p-4 space-y-3 bg-[var(--md-sys-color-surface)]">

                        @foreach($modules as $index => $module)
                            <div class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden transition-all duration-200"
                                 :class="module === '{{ $category.'-'.$index }}' ? 'border-[var(--md-sys-color-primary)]/30 shadow-sm' : 'hover:border-[var(--md-sys-color-outline-variant)]'">

                                <button
                                    @click="module = (module === '{{ $category.'-'.$index }}' ? null : '{{ $category.'-'.$index }}')"
                                    class="w-full flex items-center justify-between px-4 py-3 text-right hover:bg-[var(--md-sys-color-surface-variant)]/10 transition focus:outline-none"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors"
                                             :class="module === '{{ $category.'-'.$index }}' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'">
                                            <x-filament::icon :icon="$module['filament_icon'] ?? 'heroicon-o-squares-2x2'" class="w-5 h-5" />
                                        </div>
                                        <span class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $module['title'] }}</span>
                                    </div>
                                    <span class="material-symbols-rounded text-lg transition-transform text-[var(--md-sys-color-outline)]" :class="module === '{{ $category.'-'.$index }}' ? 'rotate-180 text-[var(--md-sys-color-primary)]' : ''">
                                        expand_more
                                    </span>
                                </button>

                                <div x-show="module === '{{ $category.'-'.$index }}'" x-collapse class="px-4 pb-4">
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
                                                <span class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)]">راهبرد مدیریتی Filament</span>
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
</x-filament-widgets::widget>
