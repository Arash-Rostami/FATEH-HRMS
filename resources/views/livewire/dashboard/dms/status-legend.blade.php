@php
    $rows = [
        ['icon' => 'edit_document', 'color' => 'error', 'label' => 'نیازمند تایید دریافت', 'text' => 'سند تازه به کارتابل شما رسیده و هنوز تایید نکرده‌اید که آن را دریافت کرده‌اید.'],
        ['icon' => 'menu_book', 'color' => 'tertiary', 'label' => 'نیازمند تایید مطالعه', 'text' => 'دریافت سند را تایید کرده‌اید؛ اکنون باید محتوای آن را مطالعه و تایید مطالعه کنید.'],
        ['icon' => 'check_circle', 'color' => 'primary', 'label' => 'مطالعه شده', 'text' => 'هر دو تایید (دریافت و مطالعه) ثبت شده و اقدامی از شما لازم نیست.'],
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
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">این دو تایید جداگانه، معادل امضای دیجیتال است: تایید دریافت یعنی «سند را دیده‌ام»، تایید مطالعه یعنی «محتوای آن را خوانده و پذیرفته‌ام». تا هر دو ثبت نشود، سند در کارتابل به‌عنوان اقدام مورد نیاز باقی می‌ماند.</p>
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
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">اگر فایل سند باز نمی‌شود (صفحهٔ ۴۰۴)، آن سند برای واحد شما منتشر نشده یا فعال نیست — دسترسی فایل از مسیر امن بررسی می‌شود.</p>
        </div>
    </div>
</div>
