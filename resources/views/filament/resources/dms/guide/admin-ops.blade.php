@php
    $rows = [
        [
            'icon' => 'description',
            'label' => 'فرم ویرایش — دو بخش',
            'hint' => 'بخش «مشخصات سند»: نوع، عنوان، کد، نسخه، وضعیت، فایل اصلی، فایل‌های الحاقی و توضیحات بازبینی. بخش «مالکیت و دسترسی»: واحدهای مالک، کاربران اختصاصی، پیش‌نمایش، فیلدهای کلید/مقدار <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">tags</code> و <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">extra</code>.',
        ],
        [
            'icon' => 'file_present',
            'label' => 'نام‌گذاری خودکار فایل',
            'hint' => 'هنگام آپلود، نام فایل به الگوی <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">FATEH-DMS-YYYYMMDD-random12-timestamp-kebab-name.ext</code> بازنویسی می‌شود — نام اصلی کاربر هرگز روی دیسک ذخیره نمی‌شود. فایل اصلی و فایل‌های الحاقی هر دو در دایرکتوری <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">dms/</code> دیسک <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">public</code> قرار می‌گیرند.',
        ],
        [
            'icon' => 'rule',
            'label' => 'یکتایی کد+نسخه فقط برای اسناد فعال',
            'hint' => 'قاعدهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">UniqueLiveDocument</code> ترکیب (code, version) را فقط در میان اسنادِ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">live</code> یکتا می‌کند — اسناد منسوخ یا در بررسی می‌توانند کد/نسخهٔ تکراری داشته باشند. این قاعده روی سه فیلد code/version/status هم‌زمان می‌نشیند تا تغییر هرکدام، کل سه‌تایی را دوباره بررسی کند. MySQL ایندکس یکتای فیلترشده ندارد، بنابراین این لایهٔ اعتبارسنجی فقط در فرم است.',
        ],
        [
            'icon' => 'visibility',
            'label' => 'مدیریت ارتباط «آمار مطالعه»',
            'hint' => 'روی صفحهٔ ویرایش، RelationManager «آمار مطالعه» ظاهر می‌شود: کاربر، وضعیت خوانده‌شده، تعداد مطالعه و آخرین زمان. کوئری این جدول <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">reads.user</code> را eager-load می‌کند. اینفولیست اصلی سند هم شمارش کلی و فهرست مطالعه‌کنندگان (قابل جمع‌شدن) را نشان می‌دهد.',
        ],
        [
            'icon' => 'account_tree',
            'label' => 'گروه‌بندی پویا از کلیدهای JSON',
            'hint' => 'گروه‌بندی‌های پویا (دینامیک) از کلیدهای داخل <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">extra</code> و <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">tags</code> ساخته می‌شوند (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">DmsKeyGrouper</code>) — کلیدهایی که فقط در فضای‌های خالی یا بزرگی حروف متفاوت‌اند در یک گروه ادغام می‌شوند. فهرست گروه‌ها به‌مدت ' . convertToPersian('15') . ' دقیقه کش می‌شود و با هر ذخیرهٔ سند خودکار بازنشانی می‌شود.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'با اکشن گروهی «خروجی Excel» می‌توانید اسناد انتخاب‌شده را صادر کنید — شامل کد، نسخه، نوع، وضعیت، واحدهای مالک (به‌صورت متن)، شمارش کاربران اختصاصی، مجموع مطالعه، توضیحات بازبینی، تگ‌ها و تاریخ ایجاد (شمسی).',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">عملیات مدیریتی شما روی اسناد</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        شما اسناد را می‌سازید، ویرایش می‌کنید، فایل‌ها را جایگزینی می‌کنید، آمار مطالعه را بازبینی می‌کنید و خروجی اکسل می‌گیرید. تغییر فایل یا توضیحات بازبینی، تأیید همهٔ کاربران را بازنشانی می‌کند — این رفتار خواسته‌شده است تا نسخهٔ جدید حتماً دوباره خوانده شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اکشن‌های ردیف و فرم</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rows as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $r['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر فایل یک سند را جایگزینی می‌کنید، بدانید همهٔ تأییدهای کاربران صفر می‌شوند و سند دوباره در کارتابل آنها «اقدام مورد نیاز» می‌شود — برای اصلاح جزئی فایل بدون بازنشانی، وضعیت سند را موقتاً به «در بررسی» ببرید، فایل را جایگزین کنید، سپس به «فعال» برگردانید.
            </p>
        </div>
    </div>
</div>