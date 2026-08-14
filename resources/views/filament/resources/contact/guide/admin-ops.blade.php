@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» صفحهٔ اینفولیست را باز می‌کند: فرستنده، گیرنده، ویرایش‌شده، تاریخ ارسال، زمان خواندن، در پاسخ به، متن پیام، پیوست‌ها (با لینک دریافت)، آخرین بروزرسانی، تاریخ حذف، و وضعیت هرس خودکار. همهٔ تاریخ‌ها شمسی و راست‌چین.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش (فقط متن)',
            'hint' => 'فرم ویرایش فقط بدنهٔ پیام را قابل ویرایش می‌کند؛ فرستنده، گیرنده، پیش‌نمایش پاسخ و پیوست‌ها همگی غیرفعال و فقط‌خواندنی‌اند. با ذخیره، فیلد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">is_edited</code> خودکار <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">true</code> می‌شود — در نتیجه ستون «ویرایش‌شده» و نشان اینفولیست روشن می‌گردد. سقف متن ۱۰٬۰۰۰ کاراکتر است.',
        ],
        [
            'icon' => 'autorenew',
            'label' => 'بازیابی',
            'hint' => 'دکمهٔ «بازیابی» فقط روی پیام‌های حذف‌شدهٔ نرم (سبد زباله) ظاهر می‌شود. با تأیید، <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">deleted_at</code> پاک می‌شود و پیام به فهرست عادی برمی‌گردد.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف نرم',
            'hint' => 'دکمهٔ «حذف» فقط روی پیام‌های حذف‌نشده ظاهر می‌شود. این یک حذف نرم است — <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">deleted_at</code> را پر می‌کند و پیام از دید کاربر می‌رود ولی رکورد محفوظ می‌ماند. بعد از ۳۰ روز خودکار هرس می‌شود (زبانهٔ «حذف و هرس خودکار» را ببینید).',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از منوی bulk actions روی ردیف‌های انتخاب‌شده خروجی اکسل می‌گیرید (ContactExporter). ستون‌ها: شناسه، فرستنده، گیرنده، متن، تعداد پیوست، نام فایل‌های پیوست، ویرایش‌شده، زمان خواندن، تاریخ ارسال، تاریخ حذف. تاریخ‌ها با toJalaliSmart شمسی‌سازی می‌شوند.',
        ],
    ];
    $filters = [
        ['label' => 'وضعیت خواندن', 'hint' => 'فیلتر سه‌حالته: خوانده‌شده / خوانده‌نشده (روی read_at اعمال می‌شود).'],
        ['label' => 'وضعیت ویرایش', 'hint' => 'فیلتر سه‌حالته: ویرایش‌شده / ویرایش‌نشده (روی is_edited).'],
        ['label' => 'نوع پیام', 'hint' => 'فیلتر سه‌حالته: پاسخ (reply_to_id پر) / پیام اصلی.'],
        ['label' => 'وضعیت پیوست', 'hint' => 'فیلتر سه‌حالته: دارای پیوست / بدون پیوست (روی attachments JSON).'],
        ['label' => 'فرستنده / گیرنده', 'hint' => 'دو SelectFilter مستقل برای فیلتر بر اساس شخص خاص — با جستجو و پیش‌بارگذاری.'],
        ['label' => 'تاریخ ثبت', 'hint' => 'فیلتر بازهٔ تاریخ ایجاد پیام.'],
        ['label' => 'در آستانه حذف', 'hint' => 'پیام‌های حذف‌شدهٔ نرمی که از ۳۰ روز گذشته‌اند و در آستانهٔ هرس خودکار‌اند.'],
    ];
    $groups = [
        ['label' => 'بر اساس فرستنده', 'hint' => 'پیام‌ها را به ازای هر فرستنده گروه‌بندی می‌کند — برای بررسی فعالیت یک شخص.'],
        ['label' => 'بر اساس گیرنده', 'hint' => 'پیام‌ها را به ازای هر گیرنده گروه‌بندی می‌کند — برای دیدن حجم پیام دریافتی یک همکار.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: نظارت، ویرایش متن، حذف/بازیابی و خروجی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در جدول یک پیام است. چهار دکمهٔ عملیات روی هر ردیف موجود است (پس از سلول‌ها). جستجوی سراسری پنل نیز پیام‌ها را با متن بدنه پیدا می‌کند و در نتیجه فرستنده و گیرنده را نشان می‌دهد و مستقیم به ویرایش می‌رود.
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
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $op['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلترها و گروه‌بندی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($filters as $f)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">filter_list</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
            @foreach($groups as $g)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">folder</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $g['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $g['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">reply</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">زنجیرهٔ پاسخ‌ها کجا دیده می‌شود؟</p>
        </div>
        <div class="p-5">
            <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium">
                روی صفحهٔ ویرایش یک پیام که پاسخ‌هایی دارد، مدیریت ارتباط «پاسخ‌ها» (RepliesRelationManager) زیرِ صفحه ظاهر می‌شود و تمام پیام‌هایی که به این پیام پاسخ داده‌اند را فهرست می‌کند — با همان دکمه‌های مشاهده/ویرایش/حذف. اگر می‌خواهید دنبال پاسخ‌های یک پیامِ خاص بگردید، به جای فیلتر در فهرست، روی پیامِ مادر بروید و زبانهٔ «پاسخ‌ها» را باز کنید.
            </p>
        </div>
    </div>
</div>