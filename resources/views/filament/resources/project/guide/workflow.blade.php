<div class="flex flex-col gap-5" dir="rtl">
    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">conversion_path</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">پیگیری و مدیریت مراحل چرخه</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانهٔ «چرخه‌های کاری» اجراهای مربوط به همین پروژه را نشان می‌دهد. برای نوشتن یا تغییر مراحل پیش‌نویس، از پنل کاربری استفاده می‌شود. اینجا با مجوز ویرایش پروژه می‌توانید چرخهٔ فعال را متوقف کنید، از مرحله عبور کنید یا مسئولان مرحله را تغییر دهید.
    </p>
    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">visibility</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">کارهایی که از این صفحه می‌توانید انجام دهید</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach([
                ['icon' => 'visibility', 'label' => 'مشاهده', 'hint' => 'نام، وضعیت، پیشرفت مرحله‌ای و تاریخچهٔ کامل چرخه را فقط برای مشاهده نشان می‌دهد.'],
                ['icon' => 'cancel', 'label' => 'لغو', 'hint' => 'فقط روی چرخه‌های فعال ظاهر می‌شود؛ همان لغوِ پنل کاربری را اجرا می‌کند؛ راهی امن برای متوقف کردن چرخه‌ای که گیر کرده است. وظیفهٔ بازِ مرحلهٔ فعلی متوقف می‌شود.'],
                ['icon' => 'forward', 'label' => 'عبور از مرحلهٔ فعلی', 'hint' => 'روی چرخهٔ فعال: مرحلهٔ جاری را دور می‌زند و به مرحلهٔ بعد می‌رود؛ وظیفهٔ بازِ آن مرحله متوقف («توقف») می‌شود. برای وقتی که مالک پروژه غایب یا غیرفعال است و چرخه گیر کرده.'],
                ['icon' => 'group', 'label' => 'تغییر مسئولان مرحله', 'hint' => 'مسئولان مرحلهٔ جاریِ چرخهٔ فعال را از میان اعضای فعال پروژه عوض می‌کنید؛ افراد تازه اعلان نوبت خود را و افراد حذف‌شده اعلان حذف را در بخش اعلان‌ها می‌گیرند و وظیفهٔ متصل مرحله هم همگام می‌شود.'],
                ['icon' => 'bookmark_add', 'label' => 'ساخت چرخه از الگو', 'hint' => 'از دکمهٔ بالای جدول یک الگو انتخاب کنید تا پیش‌نویس تازه ساخته شود. این کار، چرخه را شروع نمی‌کند؛ مالک باید پیش‌نویس و مسئولان را در پنل کاربری بررسی و سپس اجرا کند.'],
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
                این دکمه‌ها فقط با مجوز «ویرایش» پروژه در دسترس‌اند. عبور از مرحله می‌تواند بدون انجام کار ادامه دهد و وظیفهٔ ناتمام را متوقف کند؛ پیش از تأیید، نتیجهٔ این تصمیم را بررسی کنید.
            </p>
        </div>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربران الگوهای چرخهٔ کاری را فقط در پنل خود می‌سازند و ویرایش می‌کنند. پنل مدیریت صفحهٔ جداگانه‌ای برای الگوها ندارد؛ الگوها هنگام «اعمال الگو» روی پروژه دیده می‌شوند. الگو به‌تنهایی اجرا نمی‌شود و اعلان نمی‌فرستد.
    </p>
</div>
