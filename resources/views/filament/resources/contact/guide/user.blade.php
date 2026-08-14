@php
    $areas = [
        [
            'icon' => 'forum',
            'label' => 'نوار کناری و فیلترها',
            'hint' => 'کاربر در نوار سمت راست لیست همکاران را می‌بیند — هر مخاطب با آواتار، نقطهٔ حضور رنگی، شمارش خوانده‌نشده، و پیش‌نمایش آخرین پیام. سه فیلتر بالای لیست: «همه»، «خوانده‌نشده»، «آنلاین». جستجوی نام همکار در همان نوار.',
        ],
        [
            'icon' => 'mark_chat_unread',
            'label' => 'نمایش و رسید خوانده‌شدن',
            'hint' => 'پیام‌های خودِ کاربر دو حالت رسید می‌گیرند: <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">done</code> (ارسال شد) و <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">done_all</code> (خوانده شد). وقتی گیرنده پیام را باز کند، <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">read_at</code> پر می‌شود و تیک به حالت خاکستریِ دوگانه تغییر می‌کند.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش و حذف محدود به ۱۰ دقیقه',
            'hint' => 'کاربر فقط می‌تواند پیام‌های خودش را ویرایش یا حذف کند و تنها تا ۱۰ دقیقه (۶۰۰ ثانیه) پس از ارسال. بعد از آن، دکمه‌های ویرایش/حذف ناپدید می‌شوند. حذف یک دکمهٔ «بازگشت» ۴ ثانیه‌ای ظاهر می‌کند که حذف را واگرد می‌زند.',
        ],
        [
            'icon' => 'reply',
            'label' => 'پاسخ با نقل‌قول و پیوست',
            'hint' => 'کاربر می‌تواند به یک پیام خاص پاسخ دهد (نقل‌قول بالای حباب نمایش داده می‌شود) و تا ۵ فایل ضمیمه کند (تصویر/PDF/Office/zip، هر کدام تا ۱۰ مگابایت). تصویر را می‌توان مستقیم در باکس پیام paste کرد.',
        ],
    ];
    $presence = [
        ['dot' => 'bg-emerald-500', 'label' => 'در دفتر', 'hint' => 'حضور فیزیکی'],
        ['dot' => 'bg-blue-500', 'label' => 'دورکار', 'hint' => 'کار از منزل'],
        ['dot' => 'bg-amber-500', 'label' => 'مأموریت', 'hint' => 'خارج از شرکت'],
        ['dot' => 'bg-rose-500', 'label' => 'مشغول', 'hint' => 'عدم مزاحمت'],
        ['dot' => 'bg-violet-600', 'label' => 'بی‌حوصله', 'hint' => 'کم‌انرژی'],
        ['dot' => 'bg-red-600', 'label' => 'عصبانی', 'hint' => 'نزدیک نشوید'],
        ['dot' => 'bg-gray-500', 'label' => 'مرخصی', 'hint' => 'در مرخصی'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ /contact چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحهٔ پیام‌رسان کاربر یک رابطِ دو‌ستونه دارد: نوار کناریِ لیست همکاران + پنجرهٔ گفتگو. وقتی کاربی شکایت می‌کند — «پیامم را نخواند»، «نمی‌توانم ویرایش کنم»، «حذف کردم و برگشت» — این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">chat</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">رفتارهای کلیدی از دید کاربر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($areas as $a)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $a['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $a['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $a['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">circle</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">نقطهٔ حضور روی آواتار</p>
        </div>
        <div class="p-5">
            <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium mb-4">نقطهٔ رنگی پایین آواتار هر مخاطب، وضعیت حضور او را نشان می‌دهد — هفت حالت:</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($presence as $p)
                    <div class="flex items-center gap-2.5 rounded-lg bg-[var(--md-sys-color-surface-container-low)] px-3 py-2">
                        <span class="h-3 w-3 rounded-full {{ $p['dot'] }} shrink-0"></span>
                        <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $p['label'] }}</p>
                        <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mr-auto">{{ $p['hint'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                نقطهٔ «آنلاین» با انیمیشن pulse نشان داده می‌شود؛ سایر حالت‌ها ثابت‌اند.
            </p>
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">help</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            کاربر در پنل خودش یک راهنمای آماده دارد: دکمهٔ راهنما (آیکون help) بالای صفحهٔ /contact یک راهنمای تب‌دار باز می‌کند که ویرایش/حذف، نوشتن و ضمیمه، جستجو و فیلتر، سنجاق، صدا و اعلان را توضیح می‌دهد. اگر کاربر سؤالی دربارهٔ نحوهٔ استفاده دارد، به همان دکمه ارجاع دهید.
        </p>
    </div>
</div>