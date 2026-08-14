@php
    $panels = [
        [
            'icon' => 'campaign',
            'label' => 'کارتِ برگشت‌پذیر',
            'hint' => 'هر آگهی یک کارت است. رویِ کارت «عنوان، مدرک تحصیلی و توضیحات تکمیلی» دیده می‌شود؛ با دکمهٔ «مشاهده جزئیات» کارت برمی‌گردد و «سابقه کاری و مهارت‌ها» همراه با کپیِ لینک ظاهر می‌شود. برگشت با یک چک‌باکسِ مخفی و CSS انجام می‌شود (بدون جاوااسکریپت).',
        ],
        [
            'icon' => 'verified',
            'label' => 'فیلتر فعال / بایگانی شده',
            'hint' => 'دو دکمهٔ بالا: «فعال» (primary) و «بایگانی شده» (secondary). «بایگانی شده» همان آگهی‌های غیرفعالِ شماست (active=false) — حذف نشده‌اند، فقط پنهان شده‌اند. شمارشِ زندهٔ هر دکمه از یک کوئریِ stats می‌آید.',
        ],
        [
            'icon' => 'search',
            'label' => 'جستجو',
            'hint' => 'جستجوی آزاد در «عنوان، مهارت و مدرک» (like). تایپ کردن، حالتِ تمرکز را لغو می‌کند و فهرست کامل برمی‌گردد.',
        ],
        [
            'icon' => 'content_copy',
            'label' => 'کپی لینک ثبت‌نام',
            'hint' => 'روی پشتِ کارت، لینکِ آگهی در یک فیلد readonly نشان داده می‌شود و دکمهٔ کپی آن را در کلیپ‌بورد می‌گذارد. کاربر مستقیم روی لینک کلیک نمی‌کند — می‌تواند کپی کند و در مرورگر باز کند.',
        ],
        [
            'icon' => 'next_plan',
            'label' => 'حالت تمرکز (focus)',
            'hint' => 'با پارامتر <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">?open=&lt;id&gt;</code> در URL، فهرست به یک آگهیِ واحد پین می‌شود (از طریق پالتِ فرمان). تغییر فیلتر یا جستجو، تمرکز را لغو می‌کند.',
        ],
    ];

    $avatars = [
        [
            'icon' => 'manage_accounts',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'آقایان (Male)',
            'hint' => 'آگهی جنسیت Male — آیکون manage_accounts و عنوان «آقایان».',
        ],
        [
            'icon' => 'badge',
            'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            'label' => 'خانم‌ها (Female)',
            'hint' => 'آگهی جنسیت Female — آیکون badge و عنوان «خانم‌ها».',
        ],
        [
            'icon' => 'group',
            'chip' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
            'label' => 'همه (Any)',
            'hint' => 'آگهی بدون محدودیت جنسیتی — آیکون group و عنوان «همه».',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ /ads چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/ads</code> کاربر یک تختهٔ فرصت‌های شغلی است — کارت‌های برگشت‌پذیر، فیلتر فعال/بایگانی، جستجو و کپیِ لینک. وقتی کاربری از یک آگهی یا پنهان بودن آن شکایت می‌کند، این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
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
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">group</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">آیکون کارت بر اساس جنسیت</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($avatars as $a)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $a['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $a['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $a['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $a['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر می‌گوید «آگهی را نمی‌بینم»، ابتدا ستون «وضعیت» همین رکورد را بررسی کنید — «غیرفعال» در پنل کاربر همان «بایگانی شده» است و از فهرستِ فعال خارج می‌شود.
            </p>
        </div>
    </div>
</div>