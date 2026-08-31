@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'صفحهٔ اینفولیست کاربر را باز می‌کند: شناسه، نام، ایمیل، تأیید ایمیل، نوع، نقش، وضعیت، حضور، حداکثر رزرو، مجوزهای رزرو، اطلاعات اضافه و تاریخ‌ها — همگی شمسی و راست‌چین.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'فرم کاربر را باز می‌کند. رمز عبور فقط هنگام «ساخت» نمایش داده می‌شود — در ویرایش قابل تغییر نیست. زیرِ صفحهٔ ویرایش، تا ده مدیریت ارتباط ظاهر می‌شود (پروفایل، اعتبارنامه، دسترسی‌ها، ...).',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'رکورد کاربر را کاملاً برمی‌دارد. بعد از حذف، کش گزینه‌های کاربران و کشِ گروه‌بندی پویا به‌صورت خودکار پاک می‌شود. قبل از حذف مطمئن شوید پروفایل یا تسک/تیکتِ باز به این کاربر وصل نیست.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از منوی bulk actions روی ردیف‌های انتخاب‌شده، خروجی اکسل می‌گیرید (UserExporter). ستون‌ها: شناسه، نام، ایمیل، نوع، نقش، وضعیت، حضور، حداکثر رزرو، آخرین بازدید، تاریخ ثبت. مقادیر enum به برچسب فارسی و تاریخ‌ها با toJalaliSmart شمسی‌سازی می‌شوند.',
        ],
        [
            'icon' => 'assignment_turned_in',
            'label' => 'مشاهده تسک‌شیت',
            'hint' => 'گزارش عملکرد همین کاربر را در تب جدید باز می‌کند.',
        ],
        [
            'icon' => 'send',
            'label' => 'اشتراک‌گذاری تسک‌شیت با مدیر',
            'hint' => 'پس از تأیید، یک اعلان حاوی گزارش این کاربر برای مدیرِ حل‌شدهٔ او ارسال می‌کند.',
        ],
    ];
    $rms = [
        ['label' => 'پروفایل', 'icon' => 'contact_page', 'note' => 'مشخصات پرسنلی: هویت، تماس، استخدام، رسانه، درباره. یک رکورد HasOne.'],
        ['label' => 'اعتبارنامه‌ها', 'icon' => 'key', 'note' => 'اعتبارنامه‌های سرویسهای بیرونی (نام اپ، کاربر، رمز، لینک، یادداشت).'],
        ['label' => 'دسترسی‌ها', 'icon' => 'shield_person', 'note' => 'فقط برای مدیران — سوپرادمین/abilities/ماژولهای مستثنی.'],
        ['label' => 'مهارت‌ها', 'icon' => 'school', 'note' => 'فقط‌خواندنی — تنها اگر کاربر مهارتی داشته باشد ظاهر می‌شود.'],
        ['label' => 'تسک‌ها', 'icon' => 'task_alt', 'note' => 'تسکهای محول‌شده به این کاربر (assigned_to) — شامل فیلتر زباله‌دان.'],
        ['label' => 'تیکت‌ها', 'icon' => 'confirmation_number', 'note' => 'تیکت‌هایی که این کاربر درخواست‌کنندهٔ آنهاست (requester_id).'],
        ['label' => 'رزروها', 'icon' => 'event', 'note' => 'رزروهای منابع این کاربر — با عملیات لغو و آزادسازی.'],
        ['label' => 'گزارش‌ها', 'icon' => 'description', 'note' => 'گزارش‌های منتشرشدهٔ این کاربر.'],
        ['label' => 'پیشنهادها', 'icon' => 'lightbulb', 'note' => 'پیشنهادهای ثبت‌شده — دکمهٔ ساخت فقط اگر واحد کاربر MA نباشد.'],
        ['label' => 'تستهای انرژی', 'icon' => 'bolt', 'note' => 'نتایج تست انرژی — فقط مشاهده و حذف.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما: ساخت کاربر، تنظیم دسترسی و نظارت بر فعالیت‌های او</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        روی هر ردیف سه دکمهٔ عملیات (پس از سلول‌ها) وجود دارد: مشاهده، ویرایش، حذف. دکمهٔ «ساخت کاربر» در هدر صفحه قرار دارد. رمز عبور فقط در زمان ساخت وارد می‌شود. زیرِ صفحهٔ ویرایش، تا ده مدیریت ارتباط ظاهر می‌شود که فعالیت‌های کاربر را در یک جا نشان می‌دهد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">عملیات روی هر ردیف</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($ops as $op)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $op['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $op['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $op['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">ده مدیریت ارتباط زیرِ صفحهٔ ویرایش</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rms as $rm)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                            <span class="material-symbols-rounded text-[20px]">{{ $rm['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $rm['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $rm['note'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                در فرم ویرایش، فیلد «اطلاعات اضافه» فقط بخش admin را ویرایش می‌کند — ترجیحات کاربر (preferences) دست‌نخورده می‌ماند. کلید «all» در مجوزهای رزرو یک دکمهٔ جامع است.
            </p>
        </div>
    </div>
</div>