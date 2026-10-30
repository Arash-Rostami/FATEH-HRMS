@php
    $fields = [
        [
            'icon' => 'label',
            'label' => 'عنوان',
            'tag' => 'نمایشی',
            'hint' => 'نام رویداد که در تقویم سازمانی و ستون جدول نمایش داده می‌شود. الزامی است و حداکثر ۲۵۵ نویسه دارد. در ستون جدول به ۵۰ نویسه قطع می‌شود و بقیهٔ عنوان با نگه‌داشتن نشانگر موس روی آن دیده می‌شود.',
        ],
        [
            'icon' => 'subject',
            'label' => 'توضیحات',
            'tag' => 'نمایشی',
            'hint' => 'جزئیات، مکان یا دستور جلسه. حداکثر ۳۰۰۰ نویسه. برای رویدادهای ساخته‌شده از طریق رزرو، این فیلد با عبارت «جلسه برنامه‌ریزی شده از طریق سیستم رزرواسیون #<id>» پر می‌شود که نشان‌دهندهٔ پیوند رزرو است.',
        ],
        [
            'icon' => 'calendar_month',
            'label' => 'تاریخ و ساعت',
            'tag' => 'زمان‌بندی',
            'hint' => 'در فرم، تاریخ (شمسی) و ساعت (پیش‌فرض ۰۸:۰۰) جدا وارد می‌شوند، اما سیستم آن‌ها را به‌صورت یک زمان واحد نگه می‌دارد.',
        ],
        [
            'icon' => 'lock',
            'label' => 'خصوصی',
            'tag' => 'دسترسی',
            'hint' => 'رویداد خصوصی فقط برای سازنده و کسانی که رویداد با آن‌ها به اشتراک گذاشته شده دیده می‌شود. رویداد عمومی برای همهٔ کاربران در تقویم دیده می‌شود. وقتی خصوصی را فعال کنید، فیلد «مخاطب» ظاهر می‌شود و انتخاب آن الزامی می‌گردد.',
        ],
        [
            'icon' => 'person',
            'label' => 'مخاطب',
            'tag' => 'دسترسی',
            'hint' => 'فقط برای رویدادهای خصوصی قابل مشاهده و الزامی است. کاربری که این رویداد خصوصی برای او نمایش داده می‌شود. برای رویدادهای عمومی این فیلد خالی می‌ماند.',
        ],
        [
            'icon' => 'alarm',
            'label' => 'یادآوری',
            'tag' => 'اختیاری',
            'hint' => 'اگر یادآوری را تنظیم کنید، به همان تعداد ساعت پیش از شروع رویداد، اعلانی برای سازنده، مخاطب رویداد خصوصی و کسانی که رویداد با آن‌ها به اشتراک گذاشته شده فرستاده می‌شود. گزینه‌ها: ۱، ۲، ۳، ۶، ۱۲ یا ۲۴ ساعت قبل.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«رویداد» یک ورودی تقویم سازمانی است — عمومی یا خصوصی، با یا بدون شمارش معکوس</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر مورد در این صفحه یک رویداد تقویم است که یا یک ادمین می‌سازد یا کاربر از تب «تقویم» پنل کاربری‌اش ایجاد می‌کند، یا سیستم رزرواسیون به‌صورت خودکار برای جلسات می‌سازد. شما در این صفحه همهٔ رویدادهای سازمان را می‌بینید، فیلتر می‌کنید، ویرایش یا حذف می‌کنید و خروجی اکسل می‌گیرید.
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
                جدول به‌طور پیش‌فرض رویدادهای تازه‌تر را بالاتر نشان می‌دهد.
            </p>
        </div>
    </div>
</div>