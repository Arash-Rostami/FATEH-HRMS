@php
    $rows = [
        [
            'icon' => 'description',
            'label' => '«یک سند» چیست؟',
            'hint' => 'برای ثبت سند، عنوان، کد، نسخه و فایل اصلی را وارد کنید. مخاطبان سند را در «مالکیت و دسترسی» مشخص کنید. در صورت نیاز، پیوست یا توضیحات بازبینی اضافه کنید.',
        ],
        [
            'icon' => 'account_tree',
            'label' => 'دو نوع سند: اسناد و سوابق',
            'hint' => 'نوع «اسناد» برای اسناد رسمی سازمان با کد و نسخه است؛ نوع «سوابق» برای نگهداری سوابق کاربرد دارد. کاربران هر نوع را در زبانه‌ای جدا می‌بینند. با تغییر نوع، مورد از یک زبانه به زبانهٔ دیگر منتقل می‌شود.',
        ],
        [
            'icon' => 'sync',
            'label' => 'سه وضعیت: فعال / در بررسی / منسوخ',
            'hint' => 'فقط سند «فعال» به مخاطبان نمایش داده می‌شود. سندهای «در حال بررسی» و «منسوخ شده» در فهرست پنل مدیریت باقی می‌مانند.',
        ],
        [
            'icon' => 'verified_user',
            'label' => 'دو تأیید جداگانه برای هر سند',
            'hint' => '«تأیید دریافت» و «تأیید مطالعه» جدا ثبت می‌شوند. تا انجام هر دو مرحله، سند در «اقدام مورد نیاز» باقی می‌ماند. جزئیات را در زبانهٔ «تجربهٔ کاربر» ببینید.',
        ],
        [
            'icon' => 'sync',
            'label' => 'تغییر فایل یا بازبینی، تأییدها را بازنشانی می‌کند',
            'hint' => 'با تغییر فایل اصلی یا توضیحات بازبینی، تأییدهای قبلی پاک می‌شوند و شمار مطالعه به صفر برمی‌گردد. کاربران باید نسخهٔ تازه را دوباره دریافت و مطالعه کنند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«مدیریت اسناد» سامانهٔ انتشار و تأیید اسناد رسمی سازمان است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        شما (مدیر سامانه) سند را با کد، نسخه، فایل و مالکیت واحدها ثبت می‌کنید؛ کاربران آن را در پنل کاربری دیده و در دو مرحله تأیید می‌کنند. این صفحه نظارت کل‌سازمان است: همهٔ اسناد (فعال/در بررسی/منسوخ) را می‌بینید، آمار مطالعه را بازبینی می‌کنید، فایل‌ها را جایگزین می‌کنید و خروجی اکسل می‌گیرید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">folder_open</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">مفاهیم پایه</p>
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
                تنها اسنادِ «فعال» به کاربران می‌رسد؛ برای جمع‌آوری یک سند از دسترس کاربران، کافی است وضعیت را به «منسوخ» تغییر دهید — حذف سند لازم نیست.
            </p>
        </div>
    </div>
</div>
