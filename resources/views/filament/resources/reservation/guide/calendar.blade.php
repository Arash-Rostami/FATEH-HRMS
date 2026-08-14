@php
    $steps = [
        [
            'icon' => 'event',
            'label' => 'یک رویداد تقویم مشترک ساخته می‌شود',
            'hint' => 'برای رزرو فعال از نوع «ملاقات» (meeting)، EventSyncService::sync یک رویداد تقویم (Event) می‌سازد که مالکِ آن همان رزروکننده است (user_id = booker). کلید ثابت آن (user_id + description) است و description آیدی رزرو را در خود دارد (پیشوند «جلسه برنامه‌ریزی شده از طریق سیستم رزرواسیون #»). عنوان طرف‌ neutral است: «جلسه {booker} و {related}» و private = true.',
        ],
        [
            'icon' => 'group_add',
            'label' => 'یک اشتراک رویداد برای شخص مرتبط ساخته می‌شود',
            'hint' => 'همزمان یک EventShare برای relatedUserِ آن منبع (شخصی که نامش با نام منبع ملاقات مطابقت دارد) ثبت می‌شود. این اشتراک روی زیرساختِ ازپیش‌موجودِ shared-events سوار است — نیازی به لولهٔ اعلان جدیدی نیست. هر اشتراک اضافیِ خارج از relatedUser در همان sync پاک می‌شود.',
        ],
        [
            'icon' => 'notifications_active',
            'label' => 'زنگ بلافاصله، نشان قرمز ظرف ۲۴ ساعت',
            'hint' => 'به‌محض ساخت EventShare (تا زمانی که جلسه در آینده باشد) زنگِ shared-events برای شخص دعوت‌شده به صدا درمی‌آید. نشانِ قرمز (badge dot) تنها در پنجرهٔ ۲۴ ساعت قبل از جلسه روشن می‌شود — این رفتارِ ازپیش‌موجودِ سیستم تقویم است و رزرو فقط در آن مشارکت می‌کند.',
        ],
        [
            'icon' => 'lock',
            'label' => 'رویداد تقویمِ رزرو فقط‌خواندنی است',
            'hint' => 'در صفحهٔ تقویم کاربر، دکمه‌های ویرایش/حذف/اشتراک روی رویدادهای رزروشده غیرفعال‌اند (حتی برای رزروکنندهٔ مالک) و به‌جای آن‌ها یک دکمهٔ «مشاهده رزرو» نمایش داده می‌شود که به مسیر /reservation می‌رود. ویرایش/حذف دستی در تقویم، در sync بعدی بازنویسی می‌شود؛ بنابراین همهٔ تغییرات از همین صفحهٔ مدیریت رزرو انجام شود.',
        ],
        [
            'icon' => 'sync',
            'label' => 'لغو یا تغییر رزرو، تقویم را همگام می‌کند',
            'hint' => 'هوک‌های Reservation::saved و Reservation::deleted روی هر دو پنل (ادمین و کاربر) EventSyncService را فرامی‌خوانند. لغو رزرو → وضعیت از active خارج می‌شود → sync تقویم را پاک می‌کند (purge). آزادسازی هم همین‌طور. پس نیازی به همگام‌سازی دستی تقویم نیست؛ کافی است رزرو را در همین صفحه لغو/آزاد/ویرایش کنید.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">رزرو ملاقات به‌صورت خودکار در تقویم دعوت‌شده ظاهر می‌شود</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        وقتی یک کاربر منبعِ نوع «ملاقات» را رزرو می‌کند، سیستم یک رویداد تقویم مشترک می‌سازد و آن را با همکارِ مرتبط به اشتراک می‌گذارد — نه دو رویداد آینه‌ای جدا. این تنها برای نوع «ملاقات» و وضعیت «فعال» اتفاق می‌افتد؛ سایر انواع منبع (میز کار/پارکینگ/خودرو) تقویم نمی‌سازند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">timeline</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">از رزرو تا تقویم — پنج گام</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($steps as $i => $step)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $step['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] text-[10px] font-black">{{ convertToPersian((string)($i + 1)) }}</span>
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $step['label'] }}</p>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $step['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">history_edu</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نکتهٔ پاکسازی اشتراک‌های دستی</p>
        </div>
        <div class="p-5">
            <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium">
                sync هر اشتراکِ رویداد که به relatedUserِ فعلیِ منبع تعلق ندارد را پاک می‌کند، و pruneOtherOwners هر رویدادِ هم‌عنوانِ متعلق به کاربرِ دیگری را (به‌جز رزروکننده) برمی‌دارد. این یعنی اگر منبع ملاقات بعداً rename یا حذف شود و تطابق نام بشکند، sync همچنان درست کار می‌کند. رزروهای فعالِ پیش از این تغییر ممکن است یک رویدادِ باقی‌مانده از طرحِ قدیمیِ دو-رویدادی داشته باشند — این رویداد در sync/purge بعدی خودبه‌خود پاک می‌شود (هیچ دستور backfill روی دادهٔ تولید اجرا نشده).
            </p>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر می‌گوید «جلسه در تقویمم نیست»، اول وضعیت رزرو را بررسی کنید: فقط رزرو «فعال» از نوع «ملاقات» تقویم می‌سازد. لغو/آزادشده تقویم را پاک می‌کند.
            </p>
        </div>
    </div>
</div>