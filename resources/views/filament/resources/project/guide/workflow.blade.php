<div class="flex flex-col gap-5" dir="rtl">
    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">conversion_path</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">چرخهٔ کاری، نظارت است نه ویرایش</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانهٔ «چرخه‌های کاری» فهرست چرخه‌های واقعی همین پروژه را نشان می‌دهد (نه الگوها — آن‌ها فقط در پنل کاربری هر مالک دیده می‌شوند). مراحل و پیشرفتِ هر چرخه فقط از همان‌جا، توسط مالک پروژه و مسئولان هر مرحله، قابل تغییر است.
    </p>
    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">visibility</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">کار شما اینجا: مشاهده، لغو، اعمال الگو — بدون ویرایش خام</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach([
                ['icon' => 'visibility', 'label' => 'مشاهده', 'hint' => 'نام، وضعیت، پیشرفتِ مرحله‌ای و تاریخچهٔ کامل هر چرخه را فقط‌خواندنی نمایش می‌دهد.'],
                ['icon' => 'cancel', 'label' => 'لغو', 'hint' => 'فقط روی چرخه‌های فعال ظاهر می‌شود؛ همان اکشنِ لغوِ پنل کاربری را صدا می‌زند — یک راه امن برای متوقف‌کردن چرخه‌ای که گیر کرده، بدون دستکاری مستقیم رکورد.'],
                ['icon' => 'bookmark_add', 'label' => 'اعمال الگو (فقط اینجا)', 'hint' => 'از دکمهٔ بالای جدول، یکی از الگوهای موجود را انتخاب و برای همین پروژه اعمال می‌کنید — همان مسیر اعتبارسنجی/پاکسازیِ پنل کاربری، فقط از پنل ادمین اجرا می‌شود.'],
            ] as $op)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $op['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $op['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $op['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lock</span>
                عمداً هیچ اکشن ویرایشِ عمومی وجود ندارد: مراحل و وضعیت جاری هر چرخه یک ماشین‌حالتِ قفل‌شده‌اند که فقط داخل اکشن‌های پنل کاربری اعتبارسنجی می‌شود؛ یک فرم ویرایشِ عمومی همهٔ آن نگهبانی‌ها را در یک کلیک دور می‌زد.
            </p>
        </div>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        ساخت و ویرایش الگوهای چرخهٔ کاری فقط در پنل کاربری، توسط خودِ کاربران، انجام می‌شود — پنل ادمین هیچ صفحهٔ جداگانه‌ای برای مدیریت الگوها ندارد؛ الگوها ریسک پایینی دارند (بدون وضعیت زنده، بدون اعلان) و فقط از همین‌جا، هنگام «اعمال الگو» روی یک پروژه، قابل مشاهده‌اند.
    </p>
</div>
