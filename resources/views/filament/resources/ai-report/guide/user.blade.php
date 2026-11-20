@use('App\Livewire\Dashboard\AiReport\Presentation\AiReportPresenter')
@php
    $limits = (new AiReportPresenter)->limits();

    $steps = [
        ['icon' => 'edit', 'label' => '۱. نوشتن درخواست و دادن اطلاعات', 'text' => 'کاربر موضوع، دورهٔ زمانی و نتیجهٔ موردنیاز را می‌نویسد و در صورت نیاز فایل پیوست می‌کند. انتخاب نوع و میزان توضیحات، قالب و طول تقریبی گزارش را تعیین می‌کند. انتخاب گروه مخاطب فقط شیوهٔ نوشتن را عوض می‌کند و به کسی دسترسی نمی‌دهد. متن و فایل‌ها به سرویس هوش مصنوعی خارج از سامانه فرستاده می‌شوند؛ اطلاعات محرمانه نباید وارد شود.'],
        ['icon' => 'progress_activity', 'label' => '۲. ساخت و بررسی نتیجه', 'text' => 'ساخت معمولاً ' . $limits['eta'] . ' ثانیه طول می‌کشد. هر بار فقط یک درخواست کاربر اجرا می‌شود. می‌تواند صفحه را ببندد و بعداً گزارش را از فهرست باز کند. اگر فایلی خوانده نشده یا فقط بخشی از آن خوانده شده باشد، بالای گزارش پیام می‌بیند. عددها و نتیجه‌گیری باید پیش از استفاده با منبع اصلی بررسی شوند.'],
        ['icon' => 'history', 'label' => '۳. اصلاح و نگهداری نسخه‌ها', 'text' => 'کاربر می‌تواند کل گزارش یا یک بخش را با هوش مصنوعی اصلاح کند. ساخت، اصلاح و تلاش دوباره هرکدام یک نوبت از سهمیهٔ روزانهٔ ' . convertToPersian(config('ai.report.daily_cap')) . ' نوبتی کم می‌کنند. ویرایش دستی متن و عددها و برگشت به نسخهٔ قبل سهمیه مصرف نمی‌کنند. تا ' . $limits['versions'] . ' نسخهٔ آخر نگه داشته می‌شود. فایل‌های ورودی پس از پایان کار نگه داشته نمی‌شوند و برای اصلاح وابسته به فایل، باید دوباره پیوست شوند.'],
        ['icon' => 'forward_to_inbox', 'label' => '۴. ذخیره و دادن دسترسی به همکاران', 'text' => '«ذخیره گزارش» فقط نشان پیش‌نویس را به ذخیره‌شده تغییر می‌دهد. برای دسترسی همکاران، کاربر آن‌ها را در پنجرهٔ اشتراک انتخاب می‌کند و «ارسال» را می‌زند؛ این کار گزارش را هم ذخیره می‌کند. برداشتن تیک و ارسال دوباره، دسترسی فرد را قطع می‌کند. پایان اعتبار پیوند اعلان پس از ' . $limits['link_days'] . ' روز، اشتراک را قطع نمی‌کند؛ گزارش از فهرست دریافتی قابل باز کردن است.'],
        ['icon' => 'lock', 'label' => 'تغییر گزارش مشترک چگونه انجام می‌شود؟', 'text' => 'تا وقتی گزارش گیرنده دارد، تغییر عنوان، ویرایش دستی و بازگشت به نسخهٔ قبل بسته‌اند. اصلاح با هوش مصنوعی، گزارشی تازه و خصوصی می‌سازد و گزارش همکاران عوض نمی‌شود. برای تغییر مستقیم همان گزارش، مالک باید دسترسی همهٔ گیرندگان را بردارد. غیرفعال کردن هم دسترسی گیرندگان را قطع می‌کند، اما با فعال‌سازی دوباره دسترسی قبلی برمی‌گردد.'],
        ['icon' => 'print', 'label' => 'چاپ، PDF و خلاصهٔ گزارش', 'text' => 'با دکمهٔ چاپ، فقط گزارش چاپ می‌شود. برای PDF، کاربر در همان پنجرهٔ چاپ مقصد «ذخیره به‌صورت PDF» را انتخاب می‌کند. دکمهٔ رونوشت، متن و جدول‌ها را کپی می‌کند. کارت خلاصهٔ بالای گزارش هم دکمهٔ رونوشت جدا دارد؛ از همان متن گزارش ساخته می‌شود و سهمیه مصرف نمی‌کند.'],
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
