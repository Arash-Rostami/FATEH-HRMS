@php
    $tiers = [
        [
            'icon' => 'star',
            'chip' => 'سطح ۱',
            'label' => 'مدیر ارشد (کلید «مدیر ارشد» روشن)',
            'color' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            'hint' => 'به همهٔ ماژول‌ها دسترسی دارد مگر آن‌هایی که در فهرست ماژول‌های محدودشده باشند. اگر هیچ ماژولی محدود نشود، همه‌چیز را می‌بیند. حداکثر حدود ۲۰٪ ماژول‌ها قابل محدود کردن هستند؛ بیش از آن یعنی کاربر در واقع مدیر عادی است و باید ماژول‌های مجازش را جداگانه انتخاب کنید (سیستم این را خودکار بررسی می‌کند).',
        ],
        [
            'icon' => 'rule',
            'chip' => 'سطح ۲',
            'label' => 'مدیر عادی (کلید «مدیر ارشد» خاموش)',
            'color' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]',
            'hint' => 'فقط به ماژول‌هایی دسترسی دارد که صریحاً برایش انتخاب شده‌اند. برای هر ماژول، کارهای مجاز (مشاهده، ایجاد، ویرایش، حذف، بازیابی) جداگانه انتخاب می‌شوند. انتخاب حداقل یک ماژول الزامی است؛ در غیر این صورت کاربر نمی‌تواند وارد پنل شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">key</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">یک کلید دو سطح را از هم جدا می‌کند؛ دسترسی هر کاربر فقط از یک سطح بررسی می‌شود</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        وقتی «مدیر ارشد» روشن است، فهرست ماژول‌های مجاز نادیده گرفته می‌شود و فقط ماژول‌های محدودشده بررسی می‌شوند؛ وقتی خاموش است، ماژول‌های محدودشده نادیده گرفته می‌شوند و فقط ماژول‌های مجاز بررسی می‌شوند. به همین دلیل بخشِ غیرفعال همیشه پاک می‌ماند تا اطلاعات گمراه‌کننده نماند.
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
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">جزئیات دسترسی: ماژول و کار مجاز</p>
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
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">اضافه کردن یک ماژول به «دسترسی به ماژول‌ها» یعنی کاربر به آن ماژول دسترسی دارد؛ برای هر ماژول باید حداقل یک کار مجاز انتخاب شود.</p>
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
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">در ماژولی که به کاربر داده شده، با برداشتن تیک یک کار می‌توانید همان کار را محدود کنید.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]">
                        <span class="material-symbols-rounded text-[20px]">lock</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">جلوگیری از قفل شدن حساب</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">مدیر عادی بدون هیچ ماژولی نمی‌تواند وارد پنل شود؛ به همین دلیل برای او انتخاب ماژول اجباری است و پیام خطا در همان فرم نمایش داده می‌شود.</p>
                </div>
            </div>
        </div>
    </div>
</div>