@php
    $types = [
        ['icon' => 'support_agent', 'color' => 'primary', 'label' => 'پشتیبانی نرم‌افزار', 'code' => 'support', 'hint' => 'درخواست راهنمایی یا رفع مشکل فنی — برای زمانی که کاربر به کمک تیم پشتیبانی نیاز دارد.'],
        ['icon' => 'lightbulb', 'color' => 'secondary', 'label' => 'پیشنهاد ماژول', 'code' => 'recommendation', 'hint' => 'ایدهٔ کاربر برای افزودن یا بهبود یک ماژول سیستم. این نوع، پیش‌فرضِ فرم ثبت است.'],
        ['icon' => 'bug_report', 'color' => 'error', 'label' => 'گزارش باگ', 'code' => 'bug', 'hint' => 'گزارش خطا یا رفتار غیرمنتظرهٔ سیستم؛ اولویت بازبینی بالاتری دارد.'],
    ];

    $statuses = [
        ['icon' => 'forum', 'color' => 'primary', 'label' => 'باز', 'code' => 'open', 'hint' => 'وضعیت اولیهٔ هر درخواستِ ثبت‌شده (کاربر یا ادمین). منتظر بازبینی ادمین است.'],
        ['icon' => 'schedule', 'color' => 'tertiary', 'label' => 'در حال بررسی', 'code' => 'in_review', 'hint' => 'ادمین در حال پیگیری درخواست است؛ هنوز پاسخ نهایی ثبت نشده.'],
        ['icon' => 'check_circle', 'color' => 'secondary', 'label' => 'حل‌شده', 'code' => 'resolved', 'hint' => 'درخواست پایش کامل شده و (در صورت نیاز) پاسخ ادمین برای کاربر نمایش داده می‌شود.'],
        ['icon' => 'cancel', 'color' => 'error', 'label' => 'رد شد', 'code' => 'rejected', 'hint' => 'وضعیت پایانی و غیرقابل‌بازگشت — در فرم ویرایش، فیلد «وضعیت» قفل می‌شود و گزینهٔ «رد شد» در فهرست وضعیت‌ها نمی‌آید.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">auto_awesome</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«درخواست‌ها» کانال ارتباطی کاربر با تیم پشتیبانی است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربر از پنل خود از طریق دکمهٔ پشتیبانی یک درخواست (پشتیبانی، پیشنهاد ماژول یا گزارش باگ) ثبت می‌کند و آن در جدول این صفحه با وضعیت <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">open</code> ظاهر می‌شود. شما (ادمین) درخواست‌ها را بازبینی می‌کنید، وضعیت را تغییر می‌دهید، پاسخ می‌نویسید و در صورت لزوم رد می‌کنید. عنوان و متن درخواست هنگام ذخیره از تگ‌های HTML پاک‌سازی می‌شوند و فایل‌های پیوست روی دیسک <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">public</code> نگهداری می‌شوند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">category</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">سه نوع درخواست</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($types as $t)
                @php
                    $typeChip = match ($t['color']) {
                        'primary'   => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                        'error'     => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                    };
                @endphp
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $typeChip }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $t['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                            <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $t['code'] }}</code>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">flag</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">چهار وضعیت — «رد شد» پایانی است</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($statuses as $s)
                @php
                    $statusChip = match ($s['color']) {
                        'primary'   => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                        'tertiary'  => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                        'error'     => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                    };
                @endphp
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $statusChip }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                            <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $s['code'] }}</code>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $s['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                وقتی درخواستی «رد شد» می‌شود، فیلد وضعیت در فرم ویرایش غیرفعال می‌شود و اکشن «رد کردن» از ردیف ناپدید می‌گردد — این وضعیت برگشت‌ناپذیر است.
            </p>
        </div>
    </div>
</div>