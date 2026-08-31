@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» صفحهٔ اینفولیست رزرو را باز می‌کند. دو زبانه دارد: «اطلاعات رزرو» (کاربر، منبع، وضعیت، تمام‌روز، رزرو اصلی، تعداد تکرارها) و «زمان و لغو» (شروع/پایان، لغوکننده، تاریخ لغو، دلیل لغو، تاریخ ثبت). همهٔ تاریخ‌ها شمسی و راست‌چین نمایش داده می‌شوند.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'دکمهٔ «ویرایش» فرم مدیریت را باز می‌کند: کاربر، منبع، رزرو اصلی (parent_id)، وضعیت، گزینهٔ تکراری با الگو/تعداد، تمام‌روز، تاریخ/ساعت شروع و پایان، و دلیل لغو. تغییر وضعیت به لغو، فیلد «دلیل لغو» را ظاهر می‌کند. تغییرات از طریق هوک مدل Reservation به‌صورت خودکار با تقویم همگام می‌شود (زبانهٔ تقویم را ببینید).',
        ],
        [
            'icon' => 'cancel',
            'label' => 'لغو رزرو',
            'hint' => 'دکمهٔ قرمز «لغو رزرو» فقط روی رزروهای «فعال» ظاهر می‌شود. یک پنجرهٔ تأیید با فیلد «دلیل لغو» (از فهرست اشارات CancelReason) باز می‌کند. با تأیید، CancelAction اجرا می‌شود و چون شما ادمین هستید، وضعیت به «لغو توسط ادمین» (cancelled_admin) ثبت می‌شود، «لغوکننده» روی شما می‌نشیند و تاریخ لغو ثبت می‌گردد.',
        ],
        [
            'icon' => 'autorenew',
            'label' => 'آزادسازی',
            'hint' => 'دکمهٔ «آزادسازی» فقط روی رزروهای «فعال» ظاهر می‌شود. با تأیید، وضعیت به «آزادشده» (released) تغییر می‌کند، رزرو در سوابق می‌ماند، علیه سقف ماهانه می‌شمارد و کاربر دیگر نمی‌تواند آن را لغو کند. رفتارِ آزادسازی بسته به نوع رزرو فرق می‌کند: برای رزروهای بلندمدتِ در‌حال‌انجام، تاریخ پایان به همین لحظه کوتاه می‌شود و باقی‌ماندهٔ بازه برای رزروهای دیگر آزاد می‌گردد. برای رزروهای ساعتی، منبع فقط در صورت فعال‌بودنِ «مجوز رزرو در زمان آزادشده» (در قوانین رزرو) آزاد می‌شود؛ در غیر این صورت وضعیت آزادشده می‌ماند ولی منبع همچنان اشغال است. برای رزرو بلندمدتِ آینده (هنوز شروع‌نشده) به جای آزادسازی، «لغو رزرو» را بزنید.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'دکمهٔ «حذف» رکورد را کاملاً از پایگاه داده برمی‌دارد (برخلاف لغو که رکورد را نگه می‌دارد). با حذف، هوک مدل Reservation::deleted تقویمِ مرتبط را پاک می‌کند (EventSyncService::purge). معمولاً برای رزروهای اشتباه یا تستی به کار می‌رود؛ برای رزروهای واقعی «لغو» را ترجیح دهید تا سابقه باقی بماند.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از منوی bulk actions روی ردیف‌های انتخاب‌شده، خروجی اکسل می‌گیرید (ReservationExporter). ستون‌ها: شناسه، کاربر، منبع، نوع، شروع، پایان، تمام‌روز، وضعیت، دلیل لغو. تاریخ‌ها با toJalaliSmart شمسی‌سازی می‌شوند. اعمال روی کل فهرست فیلترشده نیز ممکن است.',
        ],
    ];
    $filters = [
        ['label' => 'وضعیت', 'hint' => 'فیلتر بر اساس یکی از چهار وضعیت.'],
        ['label' => 'نوع منبع', 'hint' => 'فیلتر بر اساس نوع منبع (میز کار/پارکینگ/خودرو/ملاقات) — روی resource.type اعمال می‌شود.'],
        ['label' => 'تمام‌روز', 'hint' => 'فیلتر سه‌حالتهٔ تمام‌روز/ساعتی.'],
        ['label' => 'دارای تکرار', 'hint' => 'فیلتر سه‌حالتهٔ سری‌ها: فقط رزروهای تکراری (parent_id پر) یا فقط مستقل.'],
        ['label' => 'تاریخ ثبت', 'hint' => 'فیلتر بازهٔ تاریخ ایجاد رزرو.'],
    ];
    $groups = [
        ['label' => 'بر اساس کاربر', 'hint' => 'رزروها را به ازای هر کاربر گروه‌بندی می‌کند — مناسب برای بررسی فعالیت یک شخص.'],
        ['label' => 'بر اساس وضعیت', 'hint' => 'رزروهای فعال/آزادشده/لغو را در گروه‌های جداگانه می‌نمایاند.'],
        ['label' => 'بر اساس منبع', 'hint' => 'رزروها را به ازای هر منبع گروه‌بندی می‌کند — برای دیدن تقویم اشغال یک منبع خاص.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: نظارت، لغو، آزادسازی و خروجی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در جدول یک رزرو است. شش دکمهٔ عملیات روی هر ردیف موجود است (پس از سلول‌ها). جستجوی سراسری پنل نیز رزروها را با نام کاربر یا نام منبع پیدا می‌کند و مستقیم به ویرایش می‌رود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">عملیات روی هر ردیف</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($ops as $op)
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
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">event_available</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">رزرو بلندمدت (ماهانه/سالانه)</p>
        </div>
        <div class="p-5 flex flex-col gap-2.5">
            <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium">
                برای اشغالِ پیوستهٔ یک منبع (مثلاً میز کارِ یک نفر برای یک سال)، در فرم «ایجاد/ویرایش» گزینهٔ «تمام‌روز» را خاموش بگذارید و «شروع» را امروز و «پایان» را تا ۱۰ سال آینده انتخاب کنید. کل بازه به‌عنوان <span class="font-bold text-[var(--md-sys-color-on-surface)]">یک رکورد</span> ذخیره می‌شود و منبع برای کل مدت روی نامِ آن کاربر قفل می‌شود — هیچ‌کس دیگر نمی‌تواند همان منبع را در این بازه رزرو کند.
            </p>
            <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">
                وقتی «پایان» بیش از یک روز بعد از «شروع» باشد، گزینهٔ «تکراری» و «تمام‌روز» خودکار غیرفعال می‌شوند (رزرو بلندمدت با تکرار قابل ترکیب نیست) و سقفِ ساعاتِ کاری دیگر اعمال نمی‌شود چون این یک تخصیصِ پیوسته است، نه یک جلسهٔ ساعتی. طول بازه با مقدار «حداکثر مدت رزرو بلندمدت (روز)» در قوانین رزرو محدود می‌شود (خالی = بدون محدودیت)؛ رزروی که طول‌تر از آن باشد رد می‌شود.
            </p>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)] flex flex-col gap-1.5">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">info</span>
                لغو = کل بازه یک‌جا؛ کوتاه‌سازی = «پایان» را عقب بکشید؛ آزادسازی = پایانِ رزروِ در‌حال‌انجام را به همین لحظه کوتاه می‌کند و باقی‌مانده آزاد می‌شود.
            </p>
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">cancel</span>
                برای رزرو بلندمدتِ آینده (هنوز شروع‌نشده) از «لغو رزرو» استفاده کنید، نه آزادسازی.
            </p>
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">block</span>
                یک روز/ساعتِ وسطِ بازه را نمی‌توان مستثنی کرد — برای آزادکردنِ بازهٔ میانی، «پایان» را تا شروع آن بازه کوتاه کنید و سپس یک رزرو دوم برای ادامه بسازید.
            </p>
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">person_lock</span>
                این امکان فقط در پنل ادمین است؛ کاربر در پنل خود فقط رزروی ساعتی/تمام‌روز می‌سازد.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلترها و گروه‌بندی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($filters as $f)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">filter_list</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
            @foreach($groups as $g)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">folder</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $g['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $g['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">کجا تقویمِ یک منبع را ببینید؟</p>
        </div>
        <div class="p-5">
            <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium">
                فهرستِ تمام رزروهای یک منبعِ خاص در صفحهٔ «منابع» (ResourceResource) و در مدیریت ارتباط «رزروها» (ReservationsRelationManager) زیرِ صفحهٔ ویرایش آن منبع قرار دارد — با همان دکمه‌های مشاهده/ویرایش/لغو/آزادسازی/حذف. اگر می‌خواهید بدانید یک منبعِ خاص در چه بازه‌هایی اشغال بوده، به جای فیلتر در این صفحه، روی منبع مربوطه بروید و زبانهٔ «رزروها» را باز کنید؛ همان‌جا پروفایل زمانی کامل آن منبع را می‌بینید.
            </p>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                روی صفحهٔ ویرایش یک رزروِ اصلی، مدیریت ارتباط «تکرارها» (OccurrencesRelationManager) ظاهر می‌شود و رزروهای فرزندِ آن سری را فهرست می‌کند.
            </p>
        </div>
    </div>
</div>