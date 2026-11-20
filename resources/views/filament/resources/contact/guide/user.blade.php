@php
    $areas = [
        [
            'icon' => 'forum',
            'label' => 'نوار کناری و فیلترها',
            'hint' => 'با جستجوی نام، مخاطب را پیدا کنید. «خوانده‌نشده» گفتگوهای دارای پیام تازه را جدا می‌کند. «آرشیو» گفتگوهای قبلی با مخاطبانی را نشان می‌دهد که دیگر در فهرست فعال نیستند.',
        ],
        [
            'icon' => 'mark_chat_unread',
            'label' => 'نمایش و رسید خوانده‌شدن',
            'hint' => 'یک تیک یعنی پیام ارسال شده است. دو تیک یعنی برای گیرنده خوانده‌شده ثبت شده است.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش و حذف محدود به ۱۰ دقیقه',
            'hint' => 'هر فرد تا ۱۰ دقیقه پس از ارسال می‌تواند پیام خودش را ویرایش یا حذف کند. برای لغو حذف، همان لحظه «بازگشت» را بزند.',
        ],
        [
            'icon' => 'reply',
            'label' => 'پاسخ با نقل‌قول و پیوست',
            'hint' => 'می‌توانید بخشی از پیام را برای پاسخ انتخاب کنید. تا ۵ فایل، هرکدام حداکثر ۱۰ مگابایت، قابل ارسال است. تصویر کپی‌شده را هم می‌توانید در کادر پیام بچسبانید.',
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
        صفحهٔ پیام‌رسان کاربر یک رابطِ دوستونه دارد: نوار کناریِ فهرست همکاران + پنجرهٔ گفتگو. وقتی کاربری شکایت می‌کند — «پیامم را نخواند»، «نمی‌توانم ویرایش کنم»، «حذف کردم و برگشت» — این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
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
            کاربر در پنل خودش یک راهنمای آماده دارد: دکمهٔ راهنما (علامت سؤال) بالای صفحهٔ /contact یک راهنمای تب‌دار باز می‌کند که ویرایش/حذف، نوشتن و ضمیمه، جستجو و فیلتر، سنجاق، صدا و اعلان را توضیح می‌دهد. اگر کاربر سؤالی دربارهٔ نحوهٔ استفاده دارد، به همان دکمه ارجاع دهید.
        </p>
    </div>
</div>
