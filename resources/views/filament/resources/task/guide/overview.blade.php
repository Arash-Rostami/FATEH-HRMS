@php
    $rows = [
        [
            'icon' => 'view_column',
            'label' => 'یک وظیفهٔ فعال',
            'tag' => 'اصلی',
            'hint' => 'هر ردیف یک کار قابل پیگیری است. ایجادکننده آن را ثبت می‌کند و مسئول انجام آن را انجام می‌دهد. اگر مسئول انتخاب نشود، کار با ایجادکننده است. سپردن کار به فردی دیگر، آن را واگذارشده به دیگری می‌کند.',
        ],
        [
            'icon' => 'bar_chart',
            'label' => 'زبانهٔ اطلاعات تکمیلی',
            'tag' => 'جزئیات وظیفه',
            'hint' => 'بخش سازمانی، زیرواحد، منبع درخواست، همکاران و مسئول پیگیری را در اطلاعات تکمیلی ثبت کنید. نام نمایشی پروژه فقط یک متن است؛ اتصال به پروژهٔ ثبت‌شده از گزینهٔ جداگانهٔ پروژه انجام می‌شود.',
        ],
        [
            'icon' => 'archive',
            'label' => 'وظیفهٔ آرشیو‌شده',
            'tag' => 'آرشیو',
            'hint' => 'آرشیو، کارت انجام‌شده را از فهرست فعال کنار می‌گذارد و اطلاعات آن را نگه می‌دارد. با فیلتر آرشیو آن را پیدا کنید. تغییر وضعیت از انجام‌شده، کارت را از آرشیو خارج می‌کند.',
        ],
        [
            'icon' => 'delete',
            'label' => 'وظیفهٔ حذف‌شده و قابل بازیابی',
            'tag' => 'حذف موقت',
            'hint' => 'وظیفهٔ حذف‌شده تا پیش از حذف دائمی از پنل مدیریت قابل بازیابی است. وظیفهٔ بدون پروژه پس از ۳۰ روز از حذف، خودکار برای همیشه پاک می‌شود. شمارندهٔ مهلت‌گذشته، کارهای انجام‌شده، در انتظار، متوقف، آرشیوشده و حذف‌شده را حساب نمی‌کند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«وظیفه» یک کار قابل پیگیری است؛ عناصر اصلی‌اش دو نفر (ایجادکننده و مسئول انجام) و یک سررسید است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        در پنل مدیریت، وظایف و افراد مسئول آن‌ها را بررسی کنید. برای آشنایی با آنچه کارکنان در برد خود می‌بینند، زبانهٔ «تجربهٔ کاربر» را بخوانید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">هر ردیف چیست؟</p>
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
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">{{ $r['tag'] }}</span>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $r['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                نام و رنگ وضعیت در فهرست، جزئیات و فایل Excel هماهنگ است؛ برای پیگیری، وضعیت و مهلت کار را کنار هم بخوانید.
            </p>
        </div>
    </div>
</div>
