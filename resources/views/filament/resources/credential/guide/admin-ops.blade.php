@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» صفحهٔ اینفولیست رکورد را باز می‌کند: نام سامانه، صاحب (فقط ادمین)، نام کاربری، رمز (مونو/قابل‌کپی)، لینک، یادداشت و تاریخ‌های ایجاد/ویرایش — همگی شمسی و راست‌چین.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'دکمهٔ «ویرایش» فرم را باز می‌کند. ادمین می‌تواند «کاربر» را عوض کند و اعتبارنامه را به کاربر دیگری منتسب کند؛ کاربر عادی فیلد «کاربر» را نمی‌بیند و فقط سایر فیلدها را ویرایش می‌کند. رمز هنگام ذخیره دوباره رمزنگاری می‌شود.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'دکمهٔ «حذف» رکورد را کاملاً برمی‌دارد. حذف یک اعتبارنامه باعث می‌شود کاربرِ صاحب دیگر آن را در زبانهٔ «دسترسی و امنیتی» پروفایل خود نبیند — قبل از حذف مطمئن شوید کاربر به آن نیاز ندارد.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از منوی bulk actions روی ردیف‌های انتخاب‌شده، خروجی اکسل می‌گیرید (CredentialExporter). ستون‌ها: شناسه، شناسه کاربر، نام برنامه، نام کاربری، رمز، لینک، یادداشت و تاریخ ایجاد. تاریخ‌ها با toJalaliSmart شمسی می‌شوند. اعمال روی کل فهرست فیلترشده نیز ممکن است.',
        ],
        [
            'icon' => 'search',
            'label' => 'جستجوی سراسری',
            'hint' => 'جستجوی سراسری پنل، اعتبارنامه‌ها را با نام برنامه، نام کاربری یا نامِ کاربرِ صاحب پیدا می‌کند و مستقیم به ویرایش می‌رود. عنوان نتیجه «نام برنامه» است و نام کاربری + نام کاربر به‌عنوان جزئیات نمایش داده می‌شود.',
        ],
    ];
    $create = [
        [
            'icon' => 'person_add',
            'label' => 'انتساب خودکار صاحب',
            'hint' => 'وقتی کاربر عادی یک اعتبارنامه می‌سازد، user_id خالی به‌صورت خودکار با auth()->id پر می‌شود — یعنی رکورد روی خودش ثبت می‌شود. ادمین می‌تواند به‌جای آن از فیلد «کاربر» هر کاربری را انتخاب کند.',
        ],
        [
            'icon' => 'manage_accounts',
            'label' => 'مدیریت از صفحهٔ کاربر',
            'hint' => 'از زیرِ صفحهٔ ویرایش هر کاربر، مدیریت ارتباط «اطلاعات ورود» همان رکوردها را نشان می‌دهد. فرمِ همان‌جا فیلد «کاربر» ندارد (صاحب از روی رکوردِ والد مشخص است) — ساختن از این مسیر، اعتبارنامه را مستقیم روی همان کاربر ثبت می‌کند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: تعریف، انتساب و نظارت بر اعتبارنامه‌ها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در جدول یک اعتبارنامه است. سه دکمهٔ عملیات روی هر ردیف (پس از سلول‌ها) قرار دارد: مشاهده، ویرایش، حذف. دکمهٔ «ساخت اطلاعات ورود» در هدر صفحه است. خروجی اکسل از منوی bulk actions و جستجوی سراسری پنل نیز در دسترس است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">عملیات روی هر ردیف و فهرست</p>
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">add_circle</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">نکاتِ ساخت و انتساب</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($create as $c)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $c['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $c['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $c['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                خروجی اکسل رمزها را به‌صورت متن ساده می‌آورد — فایل خروجی را محرمانه نگه‌دارید و در مخزنِ مشترک رها نکنید.
            </p>
        </div>
    </div>
</div>