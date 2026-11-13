@use('App\Livewire\Dashboard\AiReport\Presentation\AiReportPresenter')
@php
    $limits = (new AiReportPresenter)->limits();

    $rows = [
        ['icon' => 'edit_off', 'color' => 'primary', 'label' => 'مشاهده و نظارت بر گزارش‌ها', 'text' => 'کاربران گزارش‌ها را از پنل کاربری می‌سازند. در پنل مدیریت نمی‌توانید گزارش بسازید، ویرایش یا اصلاح کنید. هر ردیف متعلق به یک گزارش است. برای دیدن متن کامل، صفحهٔ «مشاهده» را باز کنید.'],
        ['icon' => 'toggle_on', 'color' => 'tertiary', 'label' => 'فعال یا غیرفعال‌کردن گزارش', 'text' => 'با غیرفعال‌کردن گزارش، دسترسی گیرندگان قطع می‌شود؛ گزارش حذف نمی‌شود. مالک آن را با فیلتر «غیرفعال‌ها» می‌بیند و می‌تواند دوباره فعال کند. این گزینه پایین گزارش مالک نیز قرار دارد. گزارش غیرفعال قابل اصلاح، ویرایش دستی، تغییر عنوان یا اشتراک‌گذاری نیست.'],
        ['icon' => 'counter_1', 'color' => 'primary', 'label' => 'سهمیه و فایل‌ها', 'text' => 'سقف سهمیهٔ روزانهٔ هر کاربر ' . convertToPersian(config('ai.report.daily_cap')) . ' بار است (ساخت، اصلاح کل یا یک بخش و تلاش دوباره هرکدام یک واحد؛ ویرایش دستی، برگشت، اشتراک، چاپ و رونوشت رایگان) و در تنظیمات ماژول ثبت شده است. سقف حجم فایل‌ها در گروه تنظیمات آپلود نگه داشته می‌شود: هر PDF یا تصویر تا ' . $limits['inline_mb'] . '، همهٔ PDFها و تصویرها روی‌هم تا ' . $limits['inline_total_mb'] . '، سایر انواع تا ' . $limits['per_file_mb'] . ' و مجموع تا ' . $limits['total_mb'] . ' مگابایت.'],
        ['icon' => 'filter_alt', 'color' => 'secondary', 'label' => 'فیلترها و گروه‌بندی', 'text' => 'فیلتر وضعیت، مالک، ذخیره/پیش‌نویس و «پیش‌نویس‌های قدیمی‌تر از ' . $limits['stale_draft_days'] . ' روز» برای یافتن گزارش‌های رهاشده در دسترس است؛ گروه‌بندی بر اساس وضعیت و مالک هم فعال است.'],
        ['icon' => 'token', 'color' => 'error', 'label' => 'مدل و توکن', 'text' => 'ستون‌های مدل و مجموع توکن‌ها نشان می‌دهد هر گزارش با کدام مدل و با چه هزینهٔ تقریبی ساخته شده است؛ گزارش‌های ناموفق توکن ثبت نمی‌کنند.'],
        ['icon' => 'schedule', 'color' => 'secondary', 'label' => 'تولید بیش از زمان مجاز', 'text' => 'گزارشی که بیش از ' . $limits['stale_minutes'] . ' دقیقه روی «در حال تولید» بماند، با باز شدن همان گزارش به «ناموفق» تغییر می‌کند و سهمیهٔ کاربر برمی‌گردد؛ تا آن زمان در این جدول «در حال تولید» دیده می‌شود.'],
        ['icon' => 'delete_forever', 'color' => 'error', 'label' => 'حذف', 'text' => 'گزارشی که هنوز «در حال تولید» است حذف نمی‌شود (دکمهٔ حذف پنهان است و حذف گروهی متوقف می‌شود)؛ چون سهمیهٔ کاربر برگشت نمی‌خورد. پس از پایان یا ناموفق‌شدن، یا اگر مدت زیادی بدون پیشرفت مانده باشد (گیر کرده)، می‌توانید حذفش کنید.'],
        ['icon' => 'support_agent', 'color' => 'primary', 'label' => 'درخواست و فایل‌های کاربر', 'text' => 'برای پشتیبانی و بررسی تخلف، آخرین درخواست کاربر و فهرست فایل‌های خوانده‌نشدهٔ او در صفحهٔ هر گزارش دیده می‌شود؛ محتوای فایل‌ها جایی نگه داشته نمی‌شود. پیش‌نمایش این صفحه همان قالب چاپ کاربر است؛ در چاپ، نام مالک و تاریخ بالای صفحهٔ اول می‌آید و در کروم پایین هر صفحه شمارهٔ صفحه.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">article</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">گزارش ساز هوشمند؛ تولید گزارش مدیریتی از درخواست و فایل‌های کاربر</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربران درخواست خود را در صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/ai-report</code> می‌نویسند و سرویس هوش مصنوعی یک گزارش ساختاریافته (عنوان، شاخص‌ها، جدول‌ها، جمع‌بندی و گزینه‌های تصمیم) می‌سازد. شما در این صفحه فقط نتیجه را پایش می‌کنید.
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
