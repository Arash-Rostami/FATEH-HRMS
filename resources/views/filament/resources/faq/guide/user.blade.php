@php
    $panels = [
        [
            'icon' => 'search',
            'label' => 'جستجو و فیلتر',
            'hint' => 'کاربر در زبانهٔ «پرسش‌های متداول» یک نوار جستجو دارد که هم در متن سوال و هم در متن پاسخ می‌گردد (like %کلمه%). دو ردیف فیلتر چپی هم برای دسته‌بندی و هم برای واحد سازمانی به‌صورت دکمه‌های چپی نمایش داده می‌شود. فیلترِ دسته‌بندی و واحد با تطبیقِ جزیی (like) کار می‌کند، نه تطبیق دقیق — «مال» با «مالی» هم‌خوان می‌شود.',
        ],
        [
            'icon' => 'expand_more',
            'label' => 'آکاردئون و باز شدن',
            'hint' => 'هر پرسش یک کارت آکاردئونی است — با کلیک روی کارت، پاسخ با انیمیشن x-collapse باز می‌شود. لینک‌های داخل پاسخ خودکار در زبانهٔ جدید (target=_blank) باز می‌شوند. نشانِ فلش در گوشهٔ راست با باز شدن ۱۸۰ درجه می‌چرخد.',
        ],
        [
            'icon' => 'center_focus_strong',
            'label' => 'حالت تمرکز (Focus)',
            'hint' => 'وقتی کاربر از طریق پالت فرمان یک پرسش را انتخاب می‌کند، فهرست به همان یک رکورد محدود می‌شود (open$) و آکاردئونِ آن خودکار باز می‌شود. هر گونه جستجو، تغییر دسته‌بندی یا تغییر واحد، حالت تمرکز را پاک می‌کند و فهرست کامل برمی‌گردد.',
        ],
        [
            'icon' => 'edit_calendar',
            'label' => 'برچسب «ثبت» و «به‌روزرسانی»',
            'hint' => 'هر کارت یک برچسب تاریخ دارد: اگر پرسش هیچ‌وقت ویرایش نشده باشد، برچسب «ثبت» با تاریخ ایجاد و آیکون calendar_today نمایش داده می‌شود؛ اگر حداقل یک‌بار ویرایش شده باشد (updated_at بزرگتر از created_at)، برچسب «به‌روزرسانی» با آیکون edit_calendar و رنگ ثانویه ظاهر می‌شود.',
        ],
        [
            'icon' => 'view_list',
            'label' => 'بارگذاری بیشتر',
            'hint' => 'فهرست به‌صورت صفحه‌بندی با ' . convertToPersian('10') . ' ردیف شروع می‌شود و دکمهٔ «بارگذاری بیشتر» هر بار ' . convertToPersian('10') . ' ردیف دیگر بارگذاری می‌کند. در حالت تمرکز، اندازهٔ صفحه به ' . convertToPersian('50') . ' ردیف افزایش می‌یابد تا پرسشِ هدف سریع قابل دسترس باشد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در زبانهٔ «پرسش‌های متداول» چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        پرسش‌های متداول یک زبانه از صفحهٔ اصلی داشبورد کاربری است (نه یک صفحهٔ جداگانه). کاربر همهٔ پرسش‌ها را به‌صورت آکاردئونی می‌بیند — بدون توجه به واحد خودش — مگر آنکه بر اساس دسته‌بندی یا واحد فیلتر کند. وقتی کاربری از وضعیت نمایش یا دسترسی شکایت می‌کند، این زبانه مرجع شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">widgets</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">قابلیت‌های پنل کاربر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($panels as $p)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $p['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $p['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $p['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                برخلاف بسیاری از ماژول‌ها، کاربر نمی‌تواند پرسشی بسازد یا ویرایش کند — نگهداریِ محتوا فقط در این صفحهٔ ادمین انجام می‌شود. اگر کاربر گزارش یک پرسشِ اشتباه می‌دهد، باید اینجا اصلاح شود.
            </p>
        </div>
    </div>
</div>