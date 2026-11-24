@php
    $rows = [
        ['icon' => 'edit_document', 'color' => 'error', 'label' => 'نیازمند تأیید دریافت', 'text' => 'سند تازه به کارتابل شما رسیده است. دریافت آن را تأیید کنید.'],
        ['icon' => 'menu_book', 'color' => 'tertiary', 'label' => 'نیازمند تأیید مطالعه', 'text' => 'دریافت سند را تأیید کرده‌اید. اکنون سند را بخوانید و مطالعهٔ آن را تأیید کنید.'],
        ['icon' => 'check_circle', 'color' => 'primary', 'label' => 'مطالعه‌شده', 'text' => 'دریافت و مطالعهٔ سند تأیید شده است. اقدام دیگری لازم نیست.'],
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
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">ابتدا «تأیید دریافت» را بزنید. سپس «مشاهده و تأیید» را بزنید و سند را بخوانید. در پایان «تأیید و ثبت مطالعه» را بزنید. تا هر دو مرحله انجام شود، سند در بخش «اقدام مورد نیاز» می‌ماند.</p>
        </div>
    </div>

    <div class="mt-4 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/40 space-y-2.5">
        <p class="px-1 text-[11px] font-bold text-[var(--md-sys-color-on-surface)]">نکات</p>
        <div class="flex items-start gap-2 px-1">
            <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">sync</span>
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">اگر سندی دوباره در «اقدام مورد نیاز» ظاهر شد، ممکن است فایل یا توضیحات بازبینی آن تغییر کرده باشد. نسخهٔ فعلی را بخوانید و تأیید کنید.</p>
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
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">اگر سند چند فایل از یک نوع داشته باشد، نام آن‌ها شماره دارد، مانند «فایل اکسل ۱» و «فایل اکسل ۲». برای انتخاب فایل درست، توضیحات بازبینی سند را بخوانید.</p>
        </div>
        <div class="flex items-start gap-2 px-1">
            <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">visibility</span>
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">اگر سند را بیش از یک بار باز کرده باشید، تعداد دفعات زیر «مشاهده مجدد» دیده می‌شود.</p>
        </div>
        <div class="flex items-start gap-2 px-1">
            <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">alarm</span>
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">برای ساخت یادآوری، نشانگر را روی ردیف سند ببرید و زنگ سمت چپ را بزنید. اگر سند یادآوری داشته باشد، زنگ همیشه دیده می‌شود و همان یادآوری را برای ویرایش باز می‌کند.</p>
        </div>
        <div class="flex items-start gap-2 px-1">
            <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">event</span>
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">برای دیدن سندهای بیشتر در بازهٔ دلخواه، اول فیلتر تاریخ را اعمال کنید و بعد «بارگذاری بیشتر» را بزنید.</p>
        </div>
    </div>
</div>
