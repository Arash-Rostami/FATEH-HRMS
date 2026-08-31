@php
    $formTabs = [
        [
            'icon' => 'assignment',
            'label' => 'زبانهٔ اصلی',
            'hint' => 'ایجادکننده، مسئول انجام، وضعیت (پیش‌فرض: انجام‌نشده؛ انتقال به «انجام‌شده» تنها در صورتی پذیرفته می‌شود که «تعیین تکلیف» در زبانهٔ اطلاعات سازمانی مشخص باشد) + ضرب‌الاجل (تاریخ شمسی از PersianDateFieldService، بدون فیلد ساعت — همیشه ساعت ۱۲:۰۰ ذخیره می‌شود) + عنوان و توضیحات.',
        ],
        [
            'icon' => 'bar_chart',
            'label' => 'زبانهٔ اطلاعات سازمانی (detail)',
            'hint' => 'واحد، بخش (گزینه‌ها از همان واحد می‌آید — تغییر واحد، بخش را ریست می‌کند)، پروژه، طرح، مبدأ اقدام، همکاران (چندانتخابی از پرسنل فعال)، مسئول ردگیری، وضعیت فرآیندی (تمدید/توقف/تکمیل)، چک‌لیست (Repeater: متن هر گام + وضعیت انجام‌شده + وزن ۰–۱۰۰ برای پیشرفت وزنی — پیشنهاد مجموع وزن‌ها ۱۰۰) و پیوست‌ها. این زبانه روی رابطهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">detail</code> ذخیره می‌شود.',
        ],
        [
            'icon' => 'label',
            'label' => 'زبانهٔ پروژه',
            'hint' => 'پروژهٔ مرتبط، اولویت (enum) و برچسب‌ها — حداکثر ' . convertToPersian('10') . ' برچسب، هرکدام تا ' . convertToPersian('30') . ' کاراکتر.',
        ],
        [
            'icon' => 'tune',
            'label' => 'زبانهٔ دیتای سفارشی (meta)',
            'hint' => 'فیلد KeyValue برای کلید/مقدارهای آزادِ وظیفه، ذخیره‌شده روی رابطهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">detail.meta</code>. کلید فقط حروف کوچک انگلیسی، رقم و زیرخط می‌پذیرد (قانون یکسان با پنل کاربر) و برچسبِ نمایش آن از <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">custom_schema</code> تنظیمات پروژه می‌آید، اگر تعریف شده باشد. هر تغییرِ این مقادیر — از هر پنلی — به‌صورت خودکار در تاریخچهٔ فعالیت وظیفه ثبت می‌شود. ستون «دیتای سفارشی» جدول همین مقادیر را به‌صورت برچسب نشان می‌دهد.',
        ],
    ];

    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'اینفولیست با دو زبانهٔ اصلی/سازمانی: وضعیت، ایجادکننده، مسئول، نشان محول‌شده، عنوان، توضیحات، ضرب‌الاجل (رنگ از فاصلهٔ زمانی)، تاریخ‌های ایجاد/بروزرسانی/حذف/آرشیو، هشدار هرس، و فیلدهای detail.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'فرم با دو زبانه (اصلی + اطلاعات سازمانی). ضرب‌الاجل فقط تاریخ شمسی است، بدون ساعت؛ در ایجاد و ویرایش نمی‌تواند از سقف مهلت پروژهٔ انتخابی فراتر باشد (روزِ خودِ سقف مجاز است). تغییر واحد، گزینه‌های بخش را خالی می‌کند.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف نرم',
            'hint' => 'فقط روی ردیف‌های حذف‌نشده ظاهر می‌شود. <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">deleted_at</code> را پر می‌کند؛ رکورد محفوظ می‌ماند و پس از ' . convertToPersian('30') . ' روز هرس خودکار می‌شود.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از bulk actions روی ردیف‌های انتخاب‌شده (TaskExporter). ستون‌ها: شناسه، عنوان، توضیحات، وضعیت (برچسب enum)، ایجادکننده، مسئول، ضرب‌الاجل (فقط تاریخ)، تاریخ ایجاد/حذف/آرشیو (تاریخ+ساعت) — همگی شمسی‌سازی می‌شوند.',
        ],
        [
            'icon' => 'check_badge',
            'label' => 'تأیید انجام',
            'hint' => 'دکمهٔ سبز تأیید فقط روی ردیف‌هایی ظاهر می‌شود که هم «منتظر تأیید» باشند (انجام‌شده، بدون تأییدکننده، در پروژه‌ای با تنظیم «نیازمند تأیید مدیر») و هم کاربر فعلی مدیر همان پروژه باشد. با زدن آن، تأییدکننده و زمان تأیید ثبت و یک ردیف فعالیت «تأیید» در تاریخچه وظیفه می‌نشیند؛ ستون «تاریخ تأیید» جدول و اینفولیست وضعیت را به‌روز نشان می‌دهند. همان مسیر از پنل کاربر (کارت و کانبان پروژه) هم در دسترس مدیر است و یک منطق مشترک پشت آن است.',
        ],
        [
            'icon' => 'assignment_turned_in',
            'label' => 'مشاهده تسک‌شیت',
            'hint' => 'گزارش عملکرد مسئولِ انجام همین وظیفه (یا ایجادکننده، اگر هنوز محول نشده) را در تب جدید باز می‌کند.',
        ],
    ];

    $attachments = [
        'Repeater با حداکثر ' . convertToPersian('5') . ' فایل؛ هر فایل تا ' . convertToPersian('4') . ' مگابایت (تصویر/PDF/Word/Excel).',
        'ذخیره روی دیسک public در مسیر <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">task/attachments</code>؛ نام فایل به‌صورت TASK-تاریخ-رشته‌تصادفی.',
        'برای هر پیوست، نام/mime/اندازه در فیلدهای مخفی کنار path ذخیره می‌شود (شکل یکسان {path,name,mime,size}).',
        'هنگام حذفِ دائمی (forceDelete)، فایل‌های پیوست از دیسک public هم پاک می‌شوند.',
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما: نظارت، ویرایش، حذف/بازیابی و خروجی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کوئریِ پایهٔ این صفحه حذف‌شده‌های نرم را هم می‌بیند (<code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">withoutGlobalScope(SoftDeletingScope)</code>) و پنج رابطه را eager-load می‌کند — creator، assignee، detail، detail.department و detail.responsibleUser. جستجوی سراسری پنل روی عنوان، توضیحات و نام ایجادکننده/مسئول کار می‌کند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فرم و زبانه‌ها</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($formTabs as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $f['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">attach_file</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">پیوست‌ها</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($attachments as $a)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">file_present</span>
                    </div>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium flex-1">{!! $a !!}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">reply</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">عملیات روی هر ردیف</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($ops as $op)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[20px]">{{ $op['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $op['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{!! $op['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                ضرب‌الاجل در ستون و اینفولیست رنگ می‌گیرد: انجام‌شده=سبز، در انتظار یا گذشته=قرمز، {{ convertToPersian('2') }} روز مانده=زرد، بقیه=آبی.
            </p>
        </div>
    </div>
</div>