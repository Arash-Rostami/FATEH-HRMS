<div class="flex flex-col gap-5" dir="rtl">
    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">conversion_path</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">چرخهٔ کاری فقط برای نظارت است، نه ویرایش</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانهٔ «چرخه‌های کاری» فهرست چرخه‌های واقعی همین پروژه را نشان می‌دهد (نه الگوها — آن‌ها فقط در پنل کاربری هر مالک دیده می‌شوند). مراحل و پیشرفتِ هر چرخه فقط از همان‌جا، توسط مالک پروژه و مسئولان هر مرحله، قابل تغییر است.
    </p>
    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">visibility</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">کار شما اینجا: مشاهده، لغو، پیشروی اجباری، تغییر مسئولان و اعمال الگو؛ بدون ویرایش مستقیم</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach([
                ['icon' => 'visibility', 'label' => 'مشاهده', 'hint' => 'نام، وضعیت، پیشرفتِ مرحله‌ای و تاریخچهٔ کامل هر چرخه را فقط‌ نمایشی (فقط برای مشاهده) نمایش می‌دهد.'],
                ['icon' => 'cancel', 'label' => 'لغو', 'hint' => 'فقط روی چرخه‌های فعال ظاهر می‌شود؛ همان لغوِ پنل کاربری را اجرا می‌کند؛ راهی امن برای متوقف کردن چرخه‌ای که گیر کرده است. وظیفهٔ بازِ مرحلهٔ فعلی متوقف می‌شود.'],
                ['icon' => 'forward', 'label' => 'پیشروی اجباری', 'hint' => 'روی چرخهٔ فعال: مرحلهٔ جاری را دور می‌زند و به مرحلهٔ بعد می‌رود؛ وظیفهٔ بازِ آن مرحله متوقف («توقف») می‌شود. برای وقتی که مالک پروژه غایب یا غیرفعال است و چرخه گیر کرده.'],
                ['icon' => 'group', 'label' => 'تغییر مسئولان مرحله', 'hint' => 'مسئولان مرحلهٔ جاریِ چرخهٔ فعال را از میان اعضای فعال پروژه عوض می‌کنید؛ افراد تازه اعلان نوبت خود را و افراد حذف‌شده اعلان حذف را در بخش اعلان‌ها می‌گیرند و وظیفهٔ متصل مرحله هم همگام می‌شود.'],
                ['icon' => 'bookmark_add', 'label' => 'اعمال الگو (فقط اینجا)', 'hint' => 'با دکمهٔ بالای جدول، یکی از الگوهای موجود را برای همین پروژه اعمال می‌کنید. همان بررسی‌هایی که در پنل کاربری انجام می‌شود، اینجا هم انجام می‌شود.'],
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
                لغو، پیشروی اجباری، تغییر مسئولان و اعمال الگو فقط با مجوز «ویرایش» پروژه دیده می‌شوند. عمداً دکمهٔ ویرایش آزاد وجود ندارد: مراحل و وضعیت هر چرخه فقط از مسیرهای کنترل‌شدهٔ پنل کاربری تغییر می‌کند؛ یک فرم ویرایش آزاد همهٔ این کنترل‌ها را دور می‌زد.
            </p>
        </div>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        ساخت و ویرایش الگوهای چرخهٔ کاری فقط در پنل کاربری، توسط خودِ کاربران، انجام می‌شود — پنل ادمین هیچ صفحهٔ جداگانه‌ای برای مدیریت الگوها ندارد؛ الگوها خطر کمی دارند (در حال اجرا نیستند و اعلانی نمی‌فرستند) و فقط همین‌جا، هنگام «اعمال الگو» روی یک پروژه، دیده می‌شوند.
    </p>
</div>
