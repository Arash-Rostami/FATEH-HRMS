@php
    $tiers = [
        [
            'icon' => 'star',
            'chip' => 'سطح ۱',
            'label' => 'مدیر ارشد (is_super_admin = روشن)',
            'color' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            'hint' => 'به همهٔ ماژول‌ها دسترسی دارد مگر آن‌هایی که در لیست سیاه excluded_modules قرار گرفته باشند. صفر استثناء یعنی «همه‌چیز را می‌بیند». حداکثر حدود ۲۰٪ ماژول‌ها قابل استثنا هستند — بیش از آن یعنی این در واقع یک مدیر عادی است و باید از abilities استفاده کنید (توسط قانون SuperAdminExclusion اجرا می‌شود).',
        ],
        [
            'icon' => 'rule',
            'chip' => 'سطح ۲',
            'label' => 'مدیر عادی (is_super_admin = خاموش)',
            'color' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]',
            'hint' => 'فقط به ماژول‌هایی دسترسی دارد که در لیست سفید abilities صریحاً ذکر شده‌اند. برای هر ماژول، عملیات‌های مجاز (view/create/update/delete/restore) به‌صورت جداگانه انتخاب می‌شوند. حداقل یک ماژول الزامی است — در غیر این صورت کاربر از پنل قفل می‌شود (گارد lockout).',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">key</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">یک Toggle دو سطح را از هم جدا می‌کند — هر ردیف فقط از یک سطح خوانده می‌شود</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        ارزیابِ دسترسی (Permission::can) تنها مرجع تصمیم است: وقتی is_super_admin روشن است، abilities نادیده گرفته می‌شود و فقط excluded_modules بررسی می‌گردد؛ وقتی خاموش است، excluded_modules نادیده گرفته می‌شود و فقط abilities خوانده می‌شود. به همین دلیل سمتِ غیرفعال باید همیشه پاک باشد تا ردیف گمراه‌کننده نشود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">balance</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">دو سطح دسترسی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tiers as $t)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $t['color'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $t['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                                {{ $t['chip'] }}
                            </span>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                هنگام toggling کردن is_super_admin، سمتِ غیرفعال به‌صورت خودکار پاک می‌شود (بعد از تغییر وضعیت) و یک قلبهٔ saving در مدل همین invariant را تضمین می‌کند — حتی اگر ردیف از مسیری غیر از فرم نوشته شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">rule</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">دانه‌بندی ماژول و عملیات</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">check_circle</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">سطح ماژول</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">اضافه کردن یک ماژول به abilities به‌معنای دسترسی به آن ماژول است؛ حداقل یک عملیات برای هر ماژول باید انتخاب شود.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">remove_circle</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">سطح عملیات</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">داخل یک ماژولِ اعطاشده، برداشتن تیکِ یک عملیات خاص آن را محدود می‌کند — گامِ دومِ ریزدانه روی سطح ماژول.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]">
                        <span class="material-symbols-rounded text-[20px]">lock</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">گارد قفل‌شدن (lockout)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">یک مدیر عادی بدون هیچ ماژولی از پنل ۴۰۳ می‌خورد — به همین دلیل abilities برای غیرِ مدیر ارشد اجباری است و پیامِ خطای آن در همان فرم نمایش داده می‌شود.</p>
                </div>
            </div>
        </div>
    </div>
</div>