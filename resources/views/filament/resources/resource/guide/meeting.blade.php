@php
    $steps = [
        [
            'icon' => 'person',
            'title' => 'نام منبع باید نام شخص باشد، نه نام اتاق',
            'body' => 'برای نوع ملاقات، نام منبع باید دقیقاً با نام یک کاربر واقعی سامانه یکسان باشد — حرف‌به‌حرف. سامانه از روی این تطابق می‌فهمد چه کسی را به جلسه دعوت کند. منبع ملاقات را به‌نام خود شخص ثبت کنید، نه «اتاق جلسات ۱» یا «جلسه با مدیریت». اگر نام مطابقت نداشته باشد، دعوتنامه ساخته نمی‌شود.',
        ],
        [
            'icon' => 'link',
            'title' => 'تطابق نام، خودکار است',
            'body' => 'سامانه نام منبع را با نام کاربر تطبیق می‌دهد. لازم نیست کاربری را دستی انتخاب کنید؛ فقط نام را درست وارد کنید. وقتی رزرو ثبت شود، یک رویداد تقویم مشترک برای آن شخص ساخته و با او به اشتراک گذاشته می‌شود.',
        ],
        [
            'icon' => 'share',
            'title' => 'رزرو = رویداد تقویم + دعوتنامه',
            'body' => 'با ثبت یک رزرو ملاقات، خودکار یک رویداد تقویم مشترک ساخته می‌شود که مالک آن رزروکننده است و یک سهم (EventShare) برای شخصِ تطبیق‌خورده ثبت می‌شود. این دعوتنامه روی زنگ تقویم همان شخص لحظه‌ای اطلاع می‌رساند و تا ۲۴ ساعت مانده به جلسه روی نشانگر تقویم هم نمایش داده می‌شود. ویرایش و لغو جلسه فقط از سمت رزروکننده ممکن است؛ شخص دعوت‌شده آن را فقط می‌بیند.',
        ],
        [
            'icon' => 'photo_camera',
            'title' => 'تصویر کارت = تصویر پروفایل شخص',
            'body' => 'اگر برای منبع ملاقات تصویری تنظیم نکنید، کارت آن در صفحهٔ رزرو خودکار تصویر پروفایل همان شخص را نشان می‌دهد. اگر بعداً شخص تصویر پروفایلش را عوض کرد، کارت منبع هم به‌روز می‌شود. می‌توانید تصویر دلخواه هم بگذارید؛ آن‌وقت تصویر پروفایل نادیده گرفته می‌شود.',
        ],
        [
            'icon' => 'schedule',
            'title' => 'تنها نوع ساعتی',
            'body' => 'ملاقات تنها نوعی است که رزرو ساعتی دارد؛ کاربر ساعت شروع و پایان را انتخاب می‌کند. سه نوع دیگر تمام‌روز هستند. بازهٔ ساعات مجاز، حداقل/حداکثر مدت و سقف رزرو ماهانه از منوی «سیاست رزرو» تنظیم می‌شود و نیازی به تغییر ندارد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">person</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">نکتهٔ هوشمند منبع «ملاقات»</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        منبع ملاقات با سه نوع دیگر فرق دارد: نام آن باید نام یک کاربر باشد، رزرو آن ساعتی است، و با ثبت رزرو خودکار یک دعوتنامهٔ جلسه در تقویم همان شخص ساخته می‌شود. این بخش مهم‌ترین نکتهٔ راه‌اندازی است؛ آن را کامل بخوانید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">menu_book</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">چه می‌شود اگر نام درست نباشد؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($steps as $i => $s)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[20px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ convertToPersian((string) ($i + 1)) }}. {{ $s['title'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $s['body'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                قانون کوتاه: نام منبع ملاقات = نام دقیق کاربر. در غیر این صورت دعوتنامه ساخته نمی‌شود و فقط یک رزرو خام می‌ماند.
            </p>
        </div>
    </div>
</div>