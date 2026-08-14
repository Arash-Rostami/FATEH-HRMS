@php
    $panels = [
        [
            'icon' => 'view_carousel',
            'label' => 'تایم‌لاین افقی',
            'hint' => 'فیدها در یک تایم‌لاین اسکرول افقی snap-x نمایش داده می‌شوند. کارت فعال بزرگ‌تر (۱.۱۵×) می‌شود و نقطهٔ روی خط زمان نشان می‌دهد. دکمه‌های چپ/راست بین فیدها جابه‌جا می‌شوند و کلید Esc کارت بازشده را کوچک می‌کند. فیدهای قدیمی‌تر با رسیدن به انتهای تایم‌لاین خودکار بارگذاری می‌شوند.',
        ],
        [
            'icon' => 'ballot',
            'label' => 'رأی‌گیری نظرسنجی',
            'hint' => 'در حالت «تک‌انتخابی»، رأی جدید رأی قبلی را جایگزین می‌کند (کلیک روی همان گزینه رأی را پس می‌گیرد). در حالت «چندانتخابی»، هر گزینه مستقل toggle می‌شود. درصد فقط وقتی رأی ثبت شده باشد نمایش داده می‌شود و گزینهٔ انتخابی کاربر با check_circle نشان داده می‌شود. وقتی دستهٔ «نظرسنجی» باشد، بخش نظر و واکنش فقط در صورت فعال بودن در تنظیمات نظرسنجی ظاهر می‌شوند.',
        ],
        [
            'icon' => 'forum',
            'label' => 'نظرات زنجیره‌ای',
            'hint' => 'بخش نظرات در اولین باز کردن بارگذاری می‌شود (نه همهٔ فیدها). پاسخ‌ها به‌صورت زنجیره‌ای نمایش داده می‌شوند. کاربر فقط نظرات خودش را می‌تواند ویرایش یا حذف کند؛ حذف یک نظر، پاسخ‌های آن را به نظر بالاتر منتقل می‌کند (نه حذف). میان‌بر: Enter ارسال، Shift+Enter خط جدید، **متن** پررنگ.',
        ],
        [
            'icon' => 'add_reaction',
            'label' => 'واکنش با ایموجی',
            'hint' => 'هر کاربر فقط یک واکنش برای هر فید دارد: کلیک روی ایموجی جدید، واکنش قبلی را جایگزین می‌کند و کلیک روی همان ایموجی، آن را پس می‌گیرد. نوار ایموجی صفحه‌بندی است و با دکمهٔ کناری چرخیده می‌شود. نشان شمارش واکنش‌ها با عنوان «کسانی که واکنش دادند» نام کاربران را نمایش می‌دهد.',
        ],
        [
            'icon' => 'mark_chat_read',
            'label' => 'خوانده‌شده در ورود',
            'hint' => 'با باز کردن صفحهٔ فید، همهٔ فیدها برای کاربر به‌صورت خوانده‌شده علامت‌گذاری می‌شوند و نشان اعلان فید در منو پس از commit پاک می‌شود. به همین دلیل نشان فید فقط فیدهای منتشرشده پس از آخرین بازدید را شمارش می‌کند.',
        ],
        [
            'icon' => 'open_in_full',
            'label' => 'بزرگ‌نمایی و گسترش متن',
            'hint' => 'دکمهٔ گوشه هر فید، کارت را تمام‌صفحه می‌کند. متن‌های طولانی‌تر از ۱۶۰ کاراکتر با گرادیان جمع می‌شوند و دکمهٔ «مشاهده بیشتر» متن کامل را باز می‌کند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در زبانهٔ اخبار چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فیدها در پنل کاربری به‌صورت یک تایم‌لاین افقی نمایش داده می‌شوند — کاربر می‌خواند، نظر می‌دهد، واکنش نشان می‌دهد و در نظرسنجی رأی می‌دهد. وقتی کاربری از رفتن فید یا نظرسنجی شکایت می‌کند، این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
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
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $p['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                وقتی کاربر جستجو یا فیلتر دسته فعال می‌کند، صفحه‌بندی غیرفعال می‌شود و همهٔ فیدهای منطبق یک‌جا بارگذاری می‌شوند — تایم‌لاین افقی فقط در حالت بدون فیلتر دسته‌بندی به‌شکل صفحه‌بندی‌شده (۳ فید در هر دسته) کار می‌کند.
            </p>
        </div>
    </div>
</div>