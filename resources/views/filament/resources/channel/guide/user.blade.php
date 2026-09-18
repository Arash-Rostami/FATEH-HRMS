@php
    $panels = [
        [
            'icon' => 'view_sidebar',
            'label' => 'نوار کناری و فیلتر',
            'hint' => 'نوار کناری فهرست گروه‌های کاربر را نشان می‌دهد — با فیلتر «همه/خوانده‌نشده» و جستجوی نام. هر گروه یک نشان خوانده‌نشده (بر اساس <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">last_read_message_id</code>) و زمان آخرین پیام می‌گیرد. گروه عمومی با آیکون <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">campaign</code> و خصوصی با <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">lock</code> نمایش داده می‌شود.',
        ],
        [
            'icon' => 'explore',
            'label' => 'کاوش و پیوستن',
            'hint' => 'دکمهٔ «کاوش» فقط گروه‌های عمومی را لیست می‌کند — گروه‌های خصوصی در این فهرست نیستند. کاربر با یک کلیک روی «پیوستن» عضو گروه عمومی می‌شود. گروه‌هایی که قبلاً عضو آن‌هاست در فهرست کاوش نمی‌آیند.',
        ],
        [
            'icon' => 'add_circle',
            'label' => 'ساخت گروه',
            'hint' => 'دکمهٔ «ساخت گروه جدید» فرم ساخت را باز می‌کند. کاربر نام، توضیحات و نوع (عمومی/خصوصی) را وارد می‌کند؛ شناسه (slug) خودکار از نام ساخته می‌شود. کاربرِ ایجادکننده خودکار مالک گروه می‌شود.',
        ],
        [
            'icon' => 'alternate_email',
            'label' => 'اشاره با @',
            'hint' => 'در باکس پیام، بعد از تایپ @ فهرست اعضای گروه باز می‌شود و نام انتخاب‌شده هایلایت می‌شود. اگر در گروهی که باز نیست با @ به کاربری اشاره شود، یک اعلان پایین صفحه ظاهر می‌شود؛ با «رفتن به پیام» به همان پیام پرش می‌کند و باز کردن گروه اعلان را خودکار پاک می‌کند.',
        ],
        [
            'icon' => 'edit_note',
            'label' => 'ویرایش و حذف پیام — محدود به ۱۰ دقیقه',
            'hint' => 'کاربر فقط پیام‌های خودشان را تا ۱۰ دقیقه (۶۰۰ ثانیه) پس از ارسال می‌تواند ویرایش یا حذف کند. حذف یک دکمهٔ «بازگشت» ۴ ثانیه‌ای ظاهر می‌کند. پیام‌های ویرایش‌شده نشان «ویرایش‌شده» می‌گیرند.',
        ],
    ];

    $roles = [
        [
            'icon' => 'shield_person',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'مالک گروه',
            'hint' => 'از «اطلاعات گروه» → «مدیریت اعضا» می‌تواند عضو اضافه یا حذف کند. دکمهٔ «خروج از گروه» برای مالک نمایش داده نمی‌شود — مالک نمی‌تواند از گروه خودش خارج شود.',
        ],
        [
            'icon' => 'person',
            'chip' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
            'label' => 'عضو عادی',
            'hint' => 'مدیریت اعضا در اختیار او نیست. در عوض هر زمان بخواهد می‌تواند از دکمهٔ «خروج از گروه» استفاده کند و از گروه خارج شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ گروه چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/channel</code> کاربر یک پنل گفتگو است — نوار کناریِ گروه‌ها، پنجرهٔ پیام‌ها، و قابلیت کاوش/ساخت گروه. وقتی کاربری از وضعیت گروه یا دسترسی‌اش شکایت می‌کند، این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
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
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">shield_person</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نقش مالک در برابر عضو عادی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($roles as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $r['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $r['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر می‌گوید «خروج از گروه را نمی‌بینم»، احتمالاً مالکِ آن گروه است — مالک نمی‌تواند خارج شود. فقط ادمین می‌تواند با حذفِ گروه یا تغییر مالک، این وضعیت را برطرف کند.
            </p>
        </div>
    </div>
</div>