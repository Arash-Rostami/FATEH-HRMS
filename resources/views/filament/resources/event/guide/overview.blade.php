@php
    $fields = [
        [
            'icon' => 'label',
            'label' => 'عنوان (title)',
            'tag' => 'نمایشی',
            'hint' => 'نام رویداد که در تقویم سازمانی و ستون جدول نمایش داده می‌شود. حداکثر ۲۵۵ کاراکتر و الزامی است. در ستون جدول به ۵۰ کاراکتر قطع می‌شود و بقیتهٔ عنوان در tooltip نمایش داده می‌شود.',
        ],
        [
            'icon' => 'subject',
            'label' => 'توضیحات (description)',
            'tag' => 'نمایشی',
            'hint' => 'جزئیات، مکان یا دستور جلسه. حداکثر ۳۰۰۰ کاراکتر. برای رویدادهای ساخته‌شده از طریق رزرو، این فیلد با عبارت «جلسه برنامه‌ریزی شده از طریق سیستم رزرواسیون #<id>» پر می‌شود که نشان‌دهندهٔ پیوند رزرو است.',
        ],
        [
            'icon' => 'calendar_month',
            'label' => 'تاریخ و ساعت (date)',
            'tag' => 'زمان‌بندی',
            'hint' => 'تاریخ و ساعت شروع در یک فیلد `date` ذخیره می‌شود ولی در فرم به‌صورت دو فیلد جداگانه نمایش داده می‌شود: تاریخ شمسی (PersianDateFieldService) و ساعت (TimePicker با پیش‌فرض ۰۸:۰۰). دو اکسسور `date_jalali` و `date_time_part` مقدار را از همان ستون `date` می‌خوانند و می‌نویسند — ستون جداگانه‌ای وجود ندارد.',
        ],
        [
            'icon' => 'lock',
            'label' => 'خصوصی (private)',
            'tag' => 'دسترسی',
            'hint' => 'رویداد خصوصی فقط برای سازنده و کسانی که مستقیماً با آن‌ها به اشتراک گذاشته شده دیده می‌شود. رویداد عمومی برای همهٔ کاربران در تقویم دیده می‌شود. وقتی خصوصی را فعال کنید، فیلد «مخاطب» ظاهر می‌شود و انتخاب آن الزامی می‌گردد.',
        ],
        [
            'icon' => 'person',
            'label' => 'مخاطب (user_id)',
            'tag' => 'دسترسی',
            'hint' => 'فقط برای رویدادهای خصوصی قابل مشاهده و الزامی است. کاربری که این رویداد خصوصی برای او نمایش داده می‌شود. برای رویدادهای عمومی این فیلد خالی می‌ماند.',
        ],
        [
            'icon' => 'alarm',
            'label' => 'یادآوری (remind_hours)',
            'tag' => 'اختیاری',
            'hint' => 'اگر تنظیم شود، اعلانی `remind_hours` ساعت قبل از شروع رویداد برای سازنده، مخاطب خصوصی و گیرندگان اشتراک ارسال می‌شود. مقادیر مجاز: ۱، ۲، ۳، ۶، ۱۲ یا ۲۴ ساعت قبل.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«رویداد» یک ورودی تقویم سازمانی است — عمومی یا خصوصی، با یا بدون شمارش معکوس</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در این صفحه یک رویداد تقویم است که یا یک ادمین می‌سازد یا کاربر از تب «تقویم» پنل کاربری‌اش ایجاد می‌کند، یا سیستم رزرواسیون به‌صورت خودکار برای جلسات می‌سازد. شما در این صفحه همهٔ رویدادهای سازمان را می‌بینید، فیلتر می‌کنید، ویرایش یا حذف می‌کنید و خروجی اکسل می‌گیرید. کلید هر رکورد `id` عددی است و تاریخ در یک ستون `date` (datetime) ذخیره می‌شود ولی در فرم به‌صورت تاریخ شمسی + ساعت جداگانه نمایش داده می‌شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای هر رویداد</p>
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
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $f['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                ستون `date` پیش‌فرض sort نزولی جدول است (جدیدترین تاریخ اول)؛ تقویم کاربر نیز همین ترتیب را برای رویدادهای هر روز نشان می‌دهد.
            </p>
        </div>
    </div>
</div>