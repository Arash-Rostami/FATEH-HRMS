@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده (اینفولیست)',
            'hint' => 'دکمهٔ «مشاهده» جزئیات کامل را می‌گیرد: شناسهٔ ترکیبی (برند + سازمان + ID)، عنوان، متن (HTML ایمن)، پیوست‌ها به‌صورت لینک، ثبت‌کننده، نوع، وضعیت (با رنگ/آیکون) و پاسخ. فیلد «پاسخ» فقط در صورتی نمایش داده می‌شود که پر شده باشد، و در وضعیت «رد شد» به رنگ قرمز درمی‌آید.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش — تغییر وضعیت و پاسخ',
            'hint' => 'در فرم ویرایش می‌توانید «وضعیت»، «نوع» و «پاسخ» را تغییر دهید. فیلد «ثبت‌کننده» فقط‌خوانتنی است و به‌طور خودکار روی کاربر ایجادکننده قفل است. فیلد «وضعیت» گزینهٔ «رد شد» را در فهرست ندارد — برای رد کردن از اکشن اختصاصی «رد کردن» استفاده کنید. وقتی وضعیت رد شده باشد، فیلد وضعیت کاملاً غیرفعال می‌شود.',
        ],
        [
            'icon' => 'cancel',
            'label' => 'رد کردن (اکشن ردیف)',
            'hint' => 'اکشن «رد کردن» یک مودال با فیلد متن «پاسخ» (اختیاری، حداکثر ۱۰۰۰ کاراکتر) باز می‌کند و وضعیت را روی <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">rejected</code> می‌گذارد. این اکشن فقط روی درخواست‌های هنوز‌رد‌نشده و با مجوز ویرایش نمایش داده می‌شود. پاسخِ ثبت‌شده در پنل کاربر با هشدار قرمز ظاهر می‌شود.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف — پاک‌سازی پیوست‌ها',
            'hint' => 'دکمهٔ «حذف» ردیف را برای همیشه پاک می‌کند. پیش از حذف، همهٔ فایل‌های پیوستِ آن درخواست از دیسک <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">public</code> هم حذف می‌شوند — حذفِ نرم نیست.',
        ],
        [
            'icon' => 'send',
            'label' => 'ثبت درخواست از طرف ادمین',
            'hint' => 'دکمهٔ «ثبت درخواست» در هدر صفحه یک مودال با نوع، عنوان و متن باز می‌کند و درخواستی با ثبت‌کنندهٔ «خودِ شما» (کاربر ادمینِ وارد‌شده) ثبت می‌کند. این مسیر پیوست نمی‌پذیرد و وضعیت را روی <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">open</code> می‌گذارد. دکمهٔ «ایجاد درخواست» کنار آن، فرم کامل ایجاد را باز می‌کند.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'با اکشن گروهی «خروجی Excel» می‌توانید درخواست‌های انتخاب‌شده را صادر کنید — شامل ID، نوع، عنوان، متن، وضعیت، پاسخ، نام ثبت‌کننده و تاریخ شمسی. خروجی کوئری را با <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">user</code> eager-load می‌کند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">عملیات مدیریتی شما روی درخواست‌ها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        شما می‌توانید درخواست‌ها را مشاهده و ویرایش کنید، وضعیت را به «در حال بررسی» یا «حل‌شده» تغییر دهید، پاسخ بنویسید، درخواست‌های خارج از ضوابط را رد کنید و خروجی اکسل بگیرید. کوئری پایهٔ جدول، رابطهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">user</code> را eager-load می‌کند تا ستون «ثبت‌کننده» بدون کوئری اضافیِ هر ردیف پر شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اکشن‌های ردیف و هدر</p>
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
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $op['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                برای رد کردن یک درخواست، از اکشن قرمز «رد کردن» ردیف استفاده کنید — نه از فیلد وضعیتِ فرم. اکشن، مودالِ پاسخ را باز می‌کند و وضعیت را به‌صورت یک‌باره روی «رد شد» می‌گذارد.
            </p>
        </div>
    </div>
</div>