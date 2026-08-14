@php
    $fields = [
        [
            'icon' => 'person',
            'label' => 'کاربر (user_id)',
            'tag' => 'کلید اصلی',
            'hint' => 'کاربری که این مجموعه دسترسی به او اختصاص می‌خورد. انتخاب فقط محدود به کاربران با نقش admin است (نه developer و نه user عادی) و یکتاست — هر کاربر فقط یک رکورد دسترسی می‌تواند داشته باشد (unique).',
        ],
        [
            'icon' => 'toggle_on',
            'label' => 'مدیر ارشد (is_super_admin)',
            'tag' => 'کلید سوئیچ',
            'hint' => 'یک Toggle است که تعیین می‌کند این ردیف از کدام سطح خوانده می‌شود: روشن = مدیر ارشد (سطح ۱) با لیست سیاه excluded_modules؛ خاموش = مدیر عادی (سطح ۲) با لیست سفید abilities. toggling کردن این کلید، سمتِ غیرفعال را پاک می‌کند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">verified_user</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">هر ردیف یک «کاربریِ ادمین» و سطح دسترسی اوست — نه نقش</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        این ماژول کاملاً مدیریتی است و پنل کاربری ندارد. هر ردیف در جدول «دسترسی‌ها» متعلق به یک کاربر با نقش admin است و تعیین می‌کند او به کدام ماژول‌های پنل ادمین دسترسی دارد. نقشِ کاربر (role) اولین دروازه است؛ این ردیف فقط یک پالایشِ ریزتر روی همان نقش admin است. developerها به‌طور کامل از این سیستم عبور می‌کنند — هیچ ردیفی برایشان لازم نیست و همه‌چیز را می‌بینند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای کلیدی هر ردیف</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($fields as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                                {{ $f['tag'] }}
                            </span>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                دسترسی به این صفحه فقط برای developerها و مدیران ارشد باز است؛ یک مدیر عادی حتی نمی‌تواند این صفحه را ببیند.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">این ردیف کجا ظاهر می‌شود؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">shield</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">صفحهٔ مستقل «دسترسی‌ها»</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">همین صفحه — فهرست همهٔ ردیف‌های دسترسی با زبانه‌ها، فیلترها و گروه‌بندی ماژول.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">manage_accounts</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">زیرِ صفحهٔ ویرایش کاربر</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">مدیریت ارتباط «دسترسی‌ها» زیرِ صفحهٔ ویرایش هر کاربر با نقش admin ظاهر می‌شود و همان فرم را نمایش می‌دهد — یک ویرایش، هر دو مسیر را پوشش می‌دهد. وقتی کاربرِ در حال ویرایش developer یا user عادی باشد، این مدیریت ارتباط مخفی می‌شود.</p>
                </div>
            </div>
        </div>
    </div>
</div>