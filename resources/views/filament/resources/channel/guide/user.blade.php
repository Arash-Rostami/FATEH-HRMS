@php
    $panels = [
        [
            'icon' => 'view_sidebar',
            'label' => 'نوار کناری و فیلتر',
            'hint' => 'گروه را با نامش پیدا کنید. فیلتر «خوانده‌نشده» گروه‌هایی را نشان می‌دهد که پیام تازه دارند. باز کردن گروه، نشان پیام‌های خوانده‌نشدهٔ آن را پاک می‌کند.',
        ],
        [
            'icon' => 'explore',
            'label' => 'کاوش و پیوستن',
            'hint' => 'در «کاوش» می‌توانید گروه‌های عمومی‌ای را که هنوز عضو نشده‌اید پیدا کنید. «پیوستن» را بزنید تا عضو شوید.',
        ],
        [
            'icon' => 'add_circle',
            'label' => 'ساخت گروه',
            'hint' => 'نام و نوع گروه را انتخاب کنید. توضیح اختیاری است و کد گروه از روی نام ساخته می‌شود. سازنده، مالک گروه است.',
        ],
        [
            'icon' => 'alternate_email',
            'label' => 'اشاره با @',
            'hint' => 'برای اشاره به یک عضو، @ را در کادر پیام بنویسید و نام او را انتخاب کنید. اگر گروه باز نباشد، فرد می‌تواند از اعلان به همان پیام برود.',
        ],
        [
            'icon' => 'edit_note',
            'label' => 'ویرایش و حذف پیام — محدود به ۱۰ دقیقه',
            'hint' => 'کاربر تا ۱۰ دقیقه پس از ارسال می‌تواند پیام خود را ویرایش یا حذف کند. پس از حذف، دکمهٔ «بازگشت» تا ۴ ثانیه دیده می‌شود. پیام‌های ویرایش‌شده نشان «ویرایش‌شده» دارند.',
        ],
    ];

    $roles = [
        [
            'icon' => 'shield_person',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'مالک گروه',
            'hint' => 'مالک می‌تواند عضو اضافه یا حذف کند و مدیران را تعیین کند. کلید گروه را هم مالک می‌گذارد. تا وقتی مالک است، نمی‌تواند از گروه خارج شود.',
        ],
        [
            'icon' => 'shield_person',
            'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            'label' => 'مدیر گروه',
            'hint' => 'مدیر می‌تواند اعضای عادی را اضافه یا حذف کند و نوع گروه را تغییر دهد. تعیین مدیر جدید و تغییر کلید با مالک است.',
        ],
        [
            'icon' => 'person',
            'chip' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
            'label' => 'عضو عادی',
            'hint' => 'عضو می‌تواند گفتگو کند یا از گروه خارج شود. برای اضافه کردن فرد به گروه خصوصی، از مالک یا مدیر کمک بگیرد.',
        ],
        [
            'icon' => 'visibility',
            'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            'label' => 'همراه',
            'hint' => 'عضو با نقش «همراه» می‌تواند پیام‌ها را بخواند، جست‌وجو کند و از گروه خارج شود. برای ارسال پیام، واکنش یا سنجاق کردن باید اجازهٔ نوشتن داشته باشد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ گروه چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/channel</code> کاربر یک پنل گفتگو است — نوار کناریِ گروه‌ها، پنجرهٔپیام‌ها، و قابلیت کاوش/ساخت گروه. وقتی کاربری از وضعیت گروه یا دسترسی‌اش شکایت می‌کند، این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
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
                اگر کاربر می‌گوید «خروج از گروه را نمی‌بینم»، احتمالاً مالکِ آن گروه است — مالک نمی‌تواند خارج شود. فقط مدیر سامانه می‌تواند با حذفِ گروه یا تغییر مالک، این وضعیت را برطرف کند.
            </p>
        </div>
    </div>
</div>
