@use('App\Livewire\Dashboard\AiReport\Presentation\AiReportPresenter')
@php
    $limits = (new AiReportPresenter)->limits();

    $rows = [
        ['icon' => 'edit_off', 'color' => 'primary', 'label' => 'چه کارهایی در پنل مدیریت ممکن است؟', 'text' => 'ساخت و اصلاح گزارش در پنل کاربری انجام می‌شود. در این صفحه می‌توانید گزارش‌ها را بررسی کنید، متن کامل را از «مشاهده» ببینید و با داشتن دسترسی لازم، گزارش را فعال، غیرفعال یا حذف کنید. متن گزارش در این پنل قابل ویرایش نیست.'],
        ['icon' => 'toggle_on', 'color' => 'tertiary', 'label' => 'قطع دسترسی با غیرفعال کردن', 'text' => 'غیرفعال کردن، دسترسی گیرندگان را قطع می‌کند، اما گزارش را حذف نمی‌کند. مالک آن را در فهرست غیرفعال‌های پنل خود می‌بیند و می‌تواند دوباره فعال کند. تا فعال نشود، اصلاح، ویرایش دستی، تغییر عنوان و اشتراک‌گذاری ممکن نیست. فعال کردن دوباره، دسترسی گیرندگان قبلی را برمی‌گرداند.'],
        ['icon' => 'counter_1', 'color' => 'primary', 'label' => 'سهمیه و محدودیت فایل‌ها', 'text' => 'هر کاربر روزانه ' . convertToPersian(config('ai.report.daily_cap')) . ' نوبت ساخت، اصلاح یا تلاش دوباره دارد. ویرایش دستی، برگشت به نسخهٔ قبل، اشتراک‌گذاری، چاپ و کپی سهمیه مصرف نمی‌کنند. هر PDF یا تصویر تا ' . $limits['inline_mb'] . ' مگابایت و همهٔ آن‌ها با هم تا ' . $limits['inline_total_mb'] . ' مگابایت؛ هر فایل دیگر تا ' . $limits['per_file_mb'] . ' مگابایت و همهٔ فایل‌ها با هم تا ' . $limits['total_mb'] . ' مگابایت مجاز است. فایل‌های ورودی پس از پایان کار نگه داشته نمی‌شوند.'],
        ['icon' => 'filter_alt', 'color' => 'secondary', 'label' => 'پیش‌نویس با گزارش ذخیره‌شده فرق دارد', 'text' => 'پیش‌نویس یعنی کاربر هنوز گزارش را ذخیره یا به اشتراک نگذاشته است. فیلتر «پیش‌نویس‌های قدیمی‌تر از ' . $limits['stale_draft_days'] . ' روز» فقط برای پیدا کردن این گزارش‌هاست و به معنی حذف خودکار آن‌ها نیست. با فیلترهای وضعیت، مالک و ذخیره می‌توانید فهرست را محدود کنید.'],
        ['icon' => 'token', 'color' => 'error', 'label' => 'مدل و مصرف متن (توکن)', 'text' => 'مدل، نام هوش مصنوعی استفاده‌شده است. توکن، واحد شمارش بخش‌های متن ورودی و خروجی سرویس است و با سهمیهٔ نوبت‌های کاربر فرق دارد. عدد آن مبلغ پرداختی را نشان نمی‌دهد. تلاش ناموفق هم ممکن است مصرف ثبت‌شده داشته باشد.'],
        ['icon' => 'schedule', 'color' => 'secondary', 'label' => 'اگر ساخت گزارش طول کشید', 'text' => 'اگر گزارش بیش از ' . $limits['stale_minutes'] . ' دقیقه بدون پیشرفت بماند، هنگام بررسی آن در پنل کاربری به «ناموفق» تغییر می‌کند و نوبت مصرف‌شده به سهمیهٔ روز درخواست برمی‌گردد. تا زمان این بررسی، ممکن است در جدول مدیریت همچنان «در حال تولید» دیده شود. بستن صفحه به‌تنهایی ساخت را متوقف نمی‌کند.'],
        ['icon' => 'delete_forever', 'color' => 'error', 'label' => 'حذف گزارش و اطلاعات پشتیبانی', 'text' => 'گزارش در حال ساخت قابل حذف نیست؛ اگر در انتخاب گروهی چنین گزارشی باشد، حذف گروهی متوقف می‌شود. پس از پایان ساخت یا تغییر وضعیت به «ناموفق» می‌توانید آن را حذف کنید. حذف سهمیه را برنمی‌گرداند. در صفحهٔ مشاهده، آخرین درخواست و نام فایل‌های خوانده‌نشده برای بررسی مشکل آمده است؛ محتوای فایل‌های ورودی پس از پایان کار نگه داشته نمی‌شود.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">article</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">گزارش ساز هوشمند؛ تولید گزارش مدیریتی از درخواست و فایل‌های کاربر</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربران درخواست خود را در صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/ai-report</code> می‌نویسند و سرویس هوش مصنوعی گزارش را همراه عنوان، عددهای مهم، جدول و جمع‌بندی آماده می‌کند. این صفحه برای بررسی و مدیریت گزارش‌های ساخته‌شده است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">insights</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نکته‌های مهم</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rows as $row)
                @php
                    $chipClasses = match ($row['color']) {
                        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                    };
                @endphp
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $chipClasses }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $row['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $row['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $row['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
