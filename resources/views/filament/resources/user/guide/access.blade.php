@php
    $roles = [
        [
            'code' => 'user',
            'label' => 'کاربر',
            'chip' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]',
            'note' => 'بدون دسترسی به پنل ادمین — canAccessPanel برای این نقش false برمی‌گرداند. فقط وارد پنل کاربری می‌شود.',
        ],
        [
            'code' => 'admin',
            'label' => 'مدیر',
            'chip' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
            'note' => 'ورود به پنل ادمین فقط اگر رکورد Permission او super_admin باشد یا حداقل یک ability داشته باشد. زیرِ صفحهٔ ویرایش، مدیریت ارتباط «دسترسی‌ها» برایش ظاهر می‌شود.',
        ],
        [
            'code' => 'developer',
            'label' => 'توسعه‌دهنده',
            'chip' => 'bg-[var(--md-sys-color-warning-container)] text-[var(--md-sys-color-on-warning-container)]',
            'note' => 'سوپرادمینِ خودکار — هر ماژول، هر عملیات، بدون استثنا. در فرم ساخت، گزینهٔ «توسعه‌دهنده» غیرفعال است و فقط از دیتابیس قابل افزودن است.',
        ],
    ];
    $tabMap = [
        ['tab' => 'تیکت‌ها', 'module' => 'ths'],
        ['tab' => 'پیشنهادها', 'module' => 'suggestion'],
        ['tab' => 'تسک‌ها', 'module' => 'task'],
        ['tab' => 'گزارش‌ها', 'module' => 'report'],
        ['tab' => 'اعتبارنامه‌ها', 'module' => 'credential'],
        ['tab' => 'تست‌های انرژی', 'module' => 'energy_test'],
        ['tab' => 'پروفایل', 'module' => 'profile'],
        ['tab' => 'رزروها', 'module' => 'reservation'],
        ['tab' => 'مهارت‌ها', 'module' => 'skill_request'],
        ['tab' => 'دسترسی‌ها', 'module' => 'فقط مدیر ارشد / developer'],
    ];
    $perm = [
        [
            'icon' => 'verified_user',
            'label' => 'سوپرادمین (is_super_admin)',
            'hint' => 'اگر روشن باشد، دسترسی کامل به همهٔ ماژول‌ها دارد مگر ماژولهایی که در «ماژولهای مستثنی» فهرست شده‌اند. بخش abilities برای سوپرادمین نمایش داده نمی‌شود.',
        ],
        [
            'icon' => 'block',
            'label' => 'ماژولهای مستثنی (excluded_modules)',
            'hint' => 'فقط برای سوپرادمین — فهرست ماژولهایی که می‌خواهید این مدیر به آن‌ها دسترسی نداشته باشد.',
        ],
        [
            'icon' => 'key',
            'label' => 'توانایی‌ها (abilities)',
            'hint' => 'فقط برای مدیر غیرِسوپرادمین — دسترسیِ دقیقِ به‌ازای ماژول و عملیات. وقتی is_super_admin خاموش باشد این بخش نمایش داده می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">shield_person</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">نقش تعیین می‌کند چه‌کس وارد پنل ادمین می‌شود</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        دسترسی به پنل ادمین مبتنی بر نقش است نه تنها وضعیت. کاربر عادی به پنل ادمین راه ندارد؛ مدیر تنها اگر رکورد «دسترسی» (Permission) داشته باشد وارد می‌شود؛ توسعه‌دهنده همیشه سوپرادمین است. مدیریت ارتباط «دسترسی‌ها» فقط برای مدیران ظاهر می‌شود — برای کاربر عادی یا توسعه‌دهنده پنهان است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade ring-2 ring-[var(--md-sys-color-primary)]/30">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">help</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">چرا بعضی زبانه‌ها را نمی‌بینم؟</p>
        </div>
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium px-5 pt-4">
            هر زبانهٔ زیرِ صفحهٔ ویرایش کاربر که ماژول جدا دارد، فقط وقتی دیده می‌شود که «مشاهدهٔ» همان ماژول را داشته باشید؛ دکمه‌های ساخت/ویرایش/حذفِ آن زبانه هم از همان ماژول پیروی می‌کنند. مثلاً مدیری که فقط user:view دارد، کاربر را می‌بیند ولی تیکت‌ها، تسک‌ها و پیشنهادهایش را نه؛ و مدیری که task:update دارد ولی ماژول user را ندارد، اصلاً به کاربران راه ندارد. صفحه این موضوع را خودکار بالای محتوا نشان می‌دهد و زبانه‌های پنهان‌شده و ماژول موردنیازشان را نام می‌برد.
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 divide-y divide-[var(--md-sys-color-outline-variant)] sm:divide-y-0 mt-2">
            @foreach($tabMap as $m)
                <div class="flex items-center gap-3 px-5 py-2.5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">subdirectory_arrow_left</span>
                    <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $m['tab'] }}</p>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold font-mono bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">{{ $m['module'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)] mt-2">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                زبانهٔ «چرخه‌های کاری» ماژول جدا ندارد و برای هر کسی که رکورد کاربر را باز کند دیده می‌شود. جزئیات کامل (دکمه‌ها، مثال‌ها، آنچه اجرا نمی‌شود) در راهنمای ماژول «دسترسی‌ها»، زبانهٔ «قوانین دسترسی در عمل» است.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">groups</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">سه نقش و دسترسیِ پنل</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($roles as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $r['chip'] }}">
                            <span class="text-[12px] font-black font-mono">{{ $r['code'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $r['note'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                مدیریت ارتباط «دسترسی‌ها» تنها زمانی ظاهر می‌شود که کاربرِ انتخاب‌شده نقش «مدیر» داشته باشد و شما خودتان سوپرادمین یا توسعه‌دهنده باشید.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">lock_person</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">بخشهای فرمِ دسترسی (Permission)</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($perm as $p)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $p['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $p['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $p['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                یک مدیر می‌تواند سوپرادمین باشد یا دارای abilities — نه هر دو. سوپرادمین بخش abilities را نمی‌بیند و برعکس.
            </p>
        </div>
    </div>
</div>