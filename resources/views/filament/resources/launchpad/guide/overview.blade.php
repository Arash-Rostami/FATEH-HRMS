@php
    $rows = [
        ['icon' => 'edit_off', 'color' => 'primary', 'label' => 'این بخش فقط پایش است', 'text' => 'کاربران برنامه‌های خود را (یک مجموعهٔ فایل HTML همراه مخاطبان و تاریخ‌های اعتبار) در پنل کاربری بارگذاری، جایگزین یا حذف می‌کنند؛ در پنل مدیریت نه ساخت ممکن است نه ویرایش. هر ردیف یک برنامه است؛ حذف از این جدول فقط پاک‌سازی مدیریتی است و فایل‌های روی دیسک هم همراه ردیف پاک می‌شوند.'],
        ['icon' => 'link', 'color' => 'secondary', 'label' => 'دو نوع برنامه: استاتیک و پیوند', 'text' => 'برنامه‌های «استاتیک» مجموعهٔ فایل‌های HTML هستند؛ برنامه‌های «پیوند» به یکی از برنامه‌های خواهر (Siblings) اشاره می‌کنند و فایلی روی دیسک ندارند. کاربران پیوند را با انتخاب برنامهٔ مقصد و مسیر آن می‌سازند و مخاطب با بازکردنش به همان صفحه هدایت می‌شود؛ آدرس مقصد همیشه از تنظیمات سازمان می‌آید و دست کاربر نیست. سهمیه، عنوان یکتا، مخاطبان و تاریخ‌های اعتبار برای هر دو نوع یکسان است.'],
        ['icon' => 'toggle_on', 'color' => 'tertiary', 'label' => 'فعال یا غیرفعال‌کردن برنامه', 'text' => 'با کلید «فعال»، برنامه بی‌درنگ از دسترس مخاطبان خارج می‌شود؛ برنامه و فایل‌هایش حذف نمی‌شوند و مالک آن را در پنل خودش می‌بیند. با فعال‌کردن دوباره، نشانگر «دیده‌نشده» برای مخاطبانی که هنوز برنامه را باز نکرده‌اند دوباره روشن می‌شود.'],
        ['icon' => 'counter_1', 'color' => 'secondary', 'label' => 'سهمیهٔ هر واحد', 'text' => 'گروه‌بندی جدول بر اساس واحد است و عنوان هر گروه «واحد — تعداد استفاده‌شده / سقف» را نشان می‌دهد. سقف هر واحد ' . convertToPersian(config('launchpad.max_per_department')) . ' برنامه است؛ برنامه‌های غیرفعال هم سهمیه را اشغال می‌کنند و فقط ردیف‌های حذف‌شده آزاد می‌شوند.'],
        ['icon' => 'manage_accounts', 'color' => 'primary', 'label' => 'تغییر مالک', 'text' => 'با اقدام «تغییر مالک»، مالک برنامه را از میان کاربران فعال انتخاب می‌کنید (مثلاً هنگام خروج مالک از شرکت). اگر مالک جدید در فهرست مخاطبان برنامه باشد، خودکار از آن حذف می‌شود؛ مالک هرگز نشانگر «دیده‌نشده» نمی‌گیرد.'],
        ['icon' => 'lock_person', 'color' => 'error', 'label' => 'مخاطبان و تاریخ‌ها قابل ویرایش نیستند', 'text' => 'فهرست مخاطبان و تاریخ‌های اعتبار (شروع، پایان و انقضا) در هیچ پنلی ویرایش نمی‌شود؛ تغییر لازم یعنی حذف و ساخت دوبارهٔ برنامه توسط مالک در پنل کاربری؛ جایگزینی با همان عنوان فقط فایل‌ها را عوض می‌کند و مخاطبان و تاریخ‌ها را همان‌طور نگه می‌دارد.'],
        ['icon' => 'visibility', 'color' => 'secondary', 'label' => 'نشانگر «دیده‌نشده»', 'text' => 'وقتی برنامه‌ای با کاربری به اشتراک گذاشته شود و او هنوز آن را باز نکرده باشد، نقطهٔ کنار گزینهٔ «پیشخوان برنامه‌ها» در منوی او روشن می‌شود. این نشانگر برای مالک برنامه هرگز روشن نمی‌شود و با بازکردن همان برنامه خاموش می‌شود؛ غیرفعال‌شدن، انقضا یا حذف برنامه هم آن را خاموش می‌کند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">rocket_launch</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">پیشخوان برنامه‌ها؛ پایش برنامه‌های تعاملی بارگذاری‌شده توسط کاربران</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربران برنامه‌های تعاملی خود را در صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/launchpad</code> می‌سازند و با تعیین مخاطبان منتشر می‌کنند؛ مخاطبان برنامه را در قاب امن (Sandbox) باز می‌کنند یا به فضای کاری خود اضافه می‌کنند. شما در این صفحه فقط نتیجه را پایش می‌کنید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">monitoring</span>
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
