@php
    $rows = [
        ['icon' => 'edit_document', 'color' => 'error', 'label' => 'نیازمند تأیید دریافت', 'text' => 'سند تازه به کارتابل شما رسیده است. دریافت آن را تأیید کنید.'],
        ['icon' => 'menu_book', 'color' => 'tertiary', 'label' => 'نیازمند تأیید مطالعه', 'text' => 'دریافت سند را تأیید کرده‌اید. اکنون سند را بخوانید و مطالعهٔ آن را تأیید کنید.'],
        ['icon' => 'check_circle', 'color' => 'primary', 'label' => 'مطالعه شده', 'text' => 'دریافت و مطالعهٔ سند تأیید شده است. اقدام دیگری لازم نیست.'],
    ];
@endphp

<div class="space-y-2">
    @foreach($rows as $row)
        @php
            $chipClasses = match ($row['color']) {
                'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            };
        @endphp
        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
                <span class="material-symbols-rounded text-[16px]">{{ $row['icon'] }}</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
            </div>
        </div>
    @endforeach

    <div class="mt-5 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/40 space-y-2">
        <div class="flex items-start gap-2 px-1">
            <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">تأیید سند در دو مرحله ثبت می‌شود: ابتدا دریافت سند و سپس مطالعه و پذیرش محتوای آن را تأیید کنید. تا ثبت هر دو مرحله، سند در بخش «اقدام مورد نیاز» باقی می‌ماند.</p>
        </div>
    </div>

    <div class="mt-4 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/40 space-y-2.5">
        <p class="px-1 text-[11px] font-bold text-[var(--md-sys-color-on-surface)]">نکات</p>
        <div class="flex items-start gap-2 px-1">
            <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">sync</span>
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">اگر سندی را که قبلاً خوانده‌اید دوباره در کارتابل «اقدام مورد نیاز» دیدید، فایل یا توضیحات بازبینیِ آن به‌روزرسانی شده و تأیید شما بازنشانی شده است — باید نسخهٔ جدید را دوباره مطالعه و تأیید کنید.</p>
        </div>
        <div class="flex items-start gap-2 px-1">
            <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">sort</span>
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">اسنادِ تأییدنشده یا خوانده‌نشده خودکار بالای فهرست می‌آیند تا اقدام مورد نیاز از قلم نیفتد.</p>
        </div>
        <div class="flex items-start gap-2 px-1">
            <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">link_off</span>
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">اگر فایل سند در دسترس نیست، ممکن است سند غیرفعال شده باشد یا دسترسی واحد شما تغییر کرده باشد. برای بررسی، با مدیر سامانه تماس بگیرید.</p>
        </div>
        <div class="flex items-start gap-2 px-1">
            <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">file_present</span>
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">اگر سندی چند فایل هم‌فرمت دارد (مثلاً دو Excel)، در فهرست نسخه‌ها با شماره می‌آیند («فایل اکسل ۱»، «فایل اکسل ۲») و فایل دریافتی هم همان شماره را در نامش دارد؛ مفهوم هر شماره را در توضیحات بازبینی سند ببینید.</p>
        </div>
        <div class="flex items-start gap-2 px-1">
            <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">visibility</span>
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">اگر بیش از یک‌بار سندی را مطالعه کرده باشید، زیر دکمهٔ «مشاهده مجدد» تعداد دفعات («بازدید شما: N بار») نمایش داده می‌شود.</p>
        </div>
        <div class="flex items-start gap-2 px-1">
            <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">alarm</span>
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">آیکون زنگ یادآوری در گوشهٔ چپ هر سند فقط با نگه‌داشتن نشانگر روی ردیف نمایان می‌شود؛ اگر برای آن سند یادآوری تنظیم کرده باشید، همیشه با رنگ اصلی برنامه نمایان می‌ماند و با کلیک، همان یادآوری موجود برای ویرایش باز می‌شود.</p>
        </div>
        <div class="flex items-start gap-2 px-1">
            <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">event</span>
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">تاریخی که در فیلتر وارد کرده‌اید اما هنوز اعمال نکرده‌اید، با «نمایش بیشتر» از بین می‌رود — ابتدا تاریخ را اعمال کنید، بعد فهرست را بیشتر کنید.</p>
        </div>
    </div>
</div>
