@php
    $limits = (new \App\Livewire\Dashboard\AiReport\Presentation\AiReportPresenter)->limits();

    $steps = [
        ['icon' => 'edit', 'label' => 'درخواست و پیوست', 'text' => 'کاربر درخواست را می‌نویسد (حداقل ' . $limits['prompt_min'] . ' حرف) و تا ' . $limits['files'] . ' فایل (PDF، تصویر، Word، Excel، CSV، متن؛ هر PDF یا تصویر تا ' . $limits['inline_mb'] . ' و روی‌هم تا ' . $limits['inline_total_mb'] . ' مگابایت، سایر انواع تا ' . $limits['per_file_mb'] . ' مگابایت، مجموع تا ' . $limits['total_mb'] . ' مگابایت) پیوست می‌کند. متن و فایل‌ها برای تولید گزارش به سرویس هوش مصنوعی خارجی ارسال می‌شود. هر کاربر روزی تا ' . convertToPersian(config('ai.report.daily_cap')) . ' بار می‌تواند بسازد یا اصلاح کند: ساخت، اصلاح کل گزارش، اصلاح یک بخش و تلاش دوباره هرکدام یک واحد مصرف می‌کنند؛ ویرایش دستی، برگشت به نسخهٔ قبل، اشتراک‌گذاری، چاپ و رونوشت رایگان‌اند.'],
        ['icon' => 'progress_activity', 'label' => 'تولید', 'text' => 'گزارش با وضعیت «در حال تولید» ساخته می‌شود و صفحهٔ کاربر خودکار به‌روز می‌شود. تولید معمولاً ' . $limits['eta'] . ' ثانیه طول می‌کشد؛ اگر نیمه‌کاره رها شود وضعیت «ناموفق» ثبت می‌شود و سهمیهٔ کاربر برمی‌گردد.'],
        ['icon' => 'history', 'label' => 'اصلاح و نسخه‌ها', 'text' => 'کاربر می‌تواند درخواست اصلاح بدهد؛ هر اصلاح یک نسخهٔ تازه می‌سازد و ' . $limits['versions'] . ' نسخهٔ آخر نگه داشته می‌شوند. کنار هر بخش هم دو دکمهٔ «اصلاح همین بخش» (بازنویسی همان بخش با هوش مصنوعی، با هزینهٔ سهمیه) و «ویرایش دستی» (تغییر مستقیم متن و ارقام بدون سهمیه، با نشان «دستی») است؛ عنوان، ویرایش دستی و برگشت به نسخهٔ قبل فقط برای گزارش فعال و به‌اشتراک‌نگذاشته در دسترس‌اند؛ گزارش غیرفعال را نمی‌شود اصلاح، به اشتراک گذاشت یا تغییر نام داد تا دوباره فعال شود. هر نسخهٔ آماده قابل بازیابی است و با دکمهٔ «ذخیره گزارش» یا اشتراک‌گذاری، گزارش از پیش‌نویس خارج می‌شود. فایل‌های پیوست پس از تولید نگه داشته نمی‌شوند و اصلاح، خودِ گزارش را بازنویسی می‌کند.'],
        ['icon' => 'forward_to_inbox', 'label' => 'اشتراک‌گذاری', 'text' => 'گزارش آماده با همکاران منتخب به اشتراک گذاشته می‌شود؛ برای گیرندگان زنگوله می‌رود و لینک هر گیرنده تا ' . $limits['link_days'] . ' روز از لحظهٔ افزودن او معتبر است. اگر لینک منقضی شود، گزارش در فهرست دریافتی همان کاربر باقی می‌ماند. حذف یک گیرنده با برداشتن تیک او و زدن ارسال انجام می‌شود. اصلاح گزارش به‌اشتراک‌گذاشته‌شده یک پیش‌نویس تازه می‌سازد و نسخهٔ دریافتی همکاران تغییر نمی‌کند.'],
        ['icon' => 'table_rows', 'label' => 'جدول، فیلتر و جست‌وجو', 'text' => 'با کلید نما در سربرگ صفحه، کاربر بین سبک «فشرده» (گزارش و فرم در یک صفحه با فهرست کناری) و «کلاسیک» (زبانه‌های «گزارش و فرم» و «جدول گزارش‌ها») جابه‌جا می‌شود؛ جدول همهٔ گزارش‌های خود و دریافتی‌اش را با فیلتر، جست‌وجو در عنوان، مرتب‌سازی، سنجاق و رنگ‌آمیزی نشان می‌دهد و هر ردیف نشان «ذخیره‌شده»، «پیش‌نویس» یا «غیرفعال» دارد. گزارش‌ها از جست‌وجوی سراسری هم پیدا می‌شوند. مالک می‌تواند گزارش خود را غیرفعال و دوباره فعال کند.'],
        ['icon' => 'print', 'label' => 'چاپ و PDF', 'text' => 'تنها یک دکمهٔ چاپ در پنل کاربر هست و گزارش را بدون فرم و فهرست چاپ می‌کند (کاغذ A4؛ نام مالک و تاریخ بالای صفحهٔ اول، شمارهٔ صفحه در کروم). برای PDF دکمهٔ جداگانه‌ای نیست؛ در همان پنجرهٔ چاپ مقصد «ذخیره به‌صورت PDF» فایلی با نام عنوان گزارش می‌سازد. پابرگ هر گزارش یادآور تولید با هوش مصنوعی و لزوم بازبینی ارقام است.'],
        ['icon' => 'mail', 'label' => 'کارت خلاصه', 'text' => 'بالای هر گزارش آماده (برای مالک و گیرندگان) کارتی با عنوان، یک رقم کلیدی، تصمیم و مفروضات نشان داده می‌شود و با دکمهٔ رونوشت کنارش برای ارسال کپی می‌شود. کارت از خودِ گزارش ساخته می‌شود؛ رایگان است، سهمیه مصرف نمی‌کند و تماسی با هوش مصنوعی ندارد و در چاپ نمی‌آید.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">آنچه کاربر تجربه می‌کند</p>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">route</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">مسیر کاربر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($steps as $step)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $step['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $loop->iteration }}. {{ $step['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $step['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">shield</span>
                فایل‌های ناخوانا در پنل کاربر با علت (خراب، رمزگذاری‌شده، حجیم، بدون متن) فهرست می‌شوند و گزارش بدون آن‌ها ساخته می‌شود.
            </p>
        </div>
    </div>
</div>
