
<x-filament-widgets::widget>
    <div class="rounded-3xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)] p-4 md:p-6" dir="rtl">
        <x-ui.title
            icon="hub"
            title="{{ __('راهنمای مدیریت ماژول‌ها در پنل ادمین') }}"
            :count="count(config('modules', []))"
            countLabel="آیتم آماری"
        />

        <p class="text-xs md:text-sm leading-7 text-[var(--md-sys-color-on-surface-variant)] mb-4">
            این راهنما ماژول‌ها را در ۴ دسته اصلی ادمین نمایش می‌دهد تا هم تصویر مدیریتی هر بخش روشن باشد و هم بدانید دقیقا برای نگهداری محتوا از کجا شروع کنید.
        </p>

        <div class="rounded-2xl border border-[var(--md-sys-color-primary)]/20 bg-[var(--md-sys-color-primary-container)]/35 p-4 mb-5">
            <p class="text-xs md:text-sm font-semibold text-[var(--md-sys-color-on-surface)] mb-3">
                راهنمای سریع کار در پنل ادمین
            </p>
            <div class="flex flex-wrap gap-2">
                @foreach([
                    'CRUD (ایجاد، ویرایش، حذف، مشاهده)',
                    'فیلترهای بالای جدول',
                    'Filter Group برای مدیریت فیلترها',
                    'Toggle Columns برای شخصی‌سازی جدول',
                    'Sort و Search برای بازیابی سریع',
                    'رابطه‌ها (Relation Managers)',
                    'Bulk Actions برای عملیات گروهی',
                    'Export / Print برای گزارش‌گیری',
                ] as $point)
                    <span class="inline-flex px-3 py-1.5 rounded-xl text-[11px] font-semibold bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50">
                        {{ $point }}
                    </span>
                @endforeach
            </div>
        </div>

        <div class="space-y-4" x-data="{ category: null, module: null }">
            @foreach($this->getGroupedModules() as $category => $modules)
                <div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden">
                    <button
                        @click="category = (category === '{{ md5($category) }}' ? null : '{{ md5($category) }}')"
                        class="w-full flex items-center justify-between px-4 py-3 text-right bg-[var(--md-sys-color-surface-variant)]/25 hover:bg-[var(--md-sys-color-surface-variant)]/40 transition"
                    >
                        <span class="font-bold text-sm text-[var(--md-sys-color-on-surface)]">{{ $category }}</span>
                        <span class="text-xs px-2 py-1 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">{{ $modules->count() }} ماژول</span>
                    </button>

                    <div x-show="category === '{{ md5($category) }}'" x-transition class="p-3 space-y-2">
                        @foreach($modules as $index => $module)
                            <div class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)] overflow-hidden">
                                <button
                                    @click="module = (module === '{{ $category.'-'.$index }}' ? null : '{{ $category.'-'.$index }}')"
                                    class="w-full flex items-center justify-between px-3 py-3 text-right hover:bg-[var(--md-sys-color-surface-variant)]/20 transition"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] flex items-center justify-center">
                                            <x-filament::icon :icon="$module['filament_icon'] ?? 'heroicon-o-squares-2x2'" class="w-5 h-5" />
                                        </div>
                                        <span class="text-sm font-semibold text-[var(--md-sys-color-on-surface)]">{{ $module['title'] }}</span>
                                    </div>
                                    <span class="material-symbols-rounded text-[var(--md-sys-color-outline)]">expand_more</span>
                                </button>

                                <div x-show="module === '{{ $category.'-'.$index }}'" x-transition class="px-4 pb-4">
                                    <div class="rounded-xl bg-[var(--md-sys-color-surface-variant)]/30 border border-[var(--md-sys-color-primary)]/15 p-4">
                                        <p class="text-xs md:text-sm leading-7 text-[var(--md-sys-color-on-surface-variant)] mb-2">
                                            {{ $module['summary'] }}
                                        </p>
                                        <p class="text-xs md:text-sm leading-7 text-[var(--md-sys-color-on-surface)] font-medium mb-1">
                                            کاربرد مدیریتی: {{ $module['admin_tip'] ?? 'این ماژول را با CRUD، فیلترها و گزارش‌گیری مستمر برای پایداری داده‌ها مدیریت کنید.' }}
                                        </p>
                                        <p class="text-xs md:text-sm leading-7 text-[var(--md-sys-color-on-surface)] font-medium">
                                            راهنمای محتوا: {{ $module['cms_hint'] ?? 'محتوا و توضیحات این ماژول را از بخش مربوطه بازبینی و به‌روزرسانی کنید.' }}
                                        </p>
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
