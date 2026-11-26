@php
    $rows = [
        [
            'icon' => 'manage_search',
            'label' => 'نامه را پیدا کنید',
            'text' => 'در جدول، شمارهٔ نامه یا نام نویسنده را جست‌وجو کنید. با فیلترهای وضعیت، نوع، ورودی یا خروجی، الگو و تاریخ ایجاد، فهرست را کوتاه‌تر کنید. «پیش‌نویس‌های قدیمی» موارد ناتمامِ بیش از ' . convertToPersian(config('ai.correspondence.stale_draft_days')) . ' روز را بر اساس تاریخ ایجاد نشان می‌دهد. الگوها در این فیلتر نمی‌آیند.',
        ],
        [
            'icon' => 'visibility',
            'label' => 'جزئیات و سابقه را بررسی کنید',
            'text' => 'با «مشاهده»، مشخصات، افراد و گیرندگان، تاریخچه و فهرست نسخه‌ها را ببینید. پنل مدیریت برای بررسی است. نوشتن، امضا، ارسال و چاپ نامه در پنل کاربر انجام می‌شود.',
        ],
        [
            'icon' => 'lock',
            'label' => 'نامهٔ محرمانه',
            'text' => 'موضوع نامه‌های محرمانه و فوق‌محرمانه با «نامه محرمانه» جایگزین می‌شود و پیش‌نمایش در دسترس نیست. در تاریخچه، نام افراد، زمان و نوع اقدامات دیده می‌شود. متن نظرها و دلیل رد نامه پوشانده می‌شود.',
        ],
        [
            'icon' => 'draw',
            'label' => 'پیگیری تأیید و امضا',
            'text' => 'در «افراد و گیرندگان»، نقش افراد را بررسی کنید. افراد دارای نقش «فقط خواندن و ثبت نظر» نامه را امضا نمی‌کنند. در روش ترتیبی هر تأییدکننده منتظر نوبت خود می‌ماند. در روش هم‌زمان می‌توانند بدون انتظار برای نوبت تأیید کنند. تغییر متن، تأییدهای قبلی را پاک می‌کند.',
        ],
        [
            'icon' => 'history',
            'label' => 'پیگیری تحویل و پیوند نامه',
            'text' => 'روش تحویل و زمان ارسال یا باز شدن پیوند را کنار گیرندهٔ بیرونی ببینید. ابزار امنیتی ایمیل هم ممکن است پیوند را باز کند. برای بررسی تحویل خارج از سامانه، توضیح ثبت‌شده در تاریخچه را بخوانید.',
        ],
        [
            'icon' => 'link',
            'label' => 'ارسال و لغو پیوند',
            'text' => 'ارسال از راه ایمیل یا پیوند فقط برای نامهٔ عادیِ تأییدشده ممکن است. هرکس پیوند را داشته باشد، بدون ورود به سامانه می‌تواند تا پایان اعتبار یا لغو آن نامه را باز کند. لغو پیوند فقط دسترسی از همان پیوند را می‌بندد و فایل‌های قبلاً دانلودشده را پس نمی‌گیرد.',
        ],
        [
            'icon' => 'print',
            'label' => 'دبیرخانه و امضای دستی',
            'text' => 'کاربر دارای دسترسی دبیرخانه، نامهٔ دریافتی را در پنل کاربر ثبت و ارجاع می‌کند. نامهٔ دارای امضای دستی ابتدا در سامانه تأیید و شماره‌گذاری می‌شود. چاپ و امضا روی کاغذ انجام می‌شود و نویسنده یا مسئول دبیرخانه انجام آن را ثبت می‌کند. فرستادن ایمیل پس از ثبت امضای دستی ممکن است.',
        ],
        [
            'icon' => 'content_copy',
            'label' => 'الگوهای نامه',
            'text' => 'با فیلتر «الگو»، الگوها را جدا از نامه‌های معمولی ببینید. کاربر می‌تواند در پنل خود از یک الگو پیش‌نویس تازه بسازد. ساخت پیش‌نویس، خود الگو را تغییر نمی‌دهد.',
        ],
        [
            'icon' => 'badge',
            'label' => 'تنظیم امضا و دسترسی دبیرخانه',
            'text' => 'تصویر امضا و «دسترسی دبیرخانه» را در فرم ویرایش کاربر تنظیم کنید. «کد امضای سری» برای تأیید به نمایندگی از کاربر است. برای تعیین یا حذف کد، رمز ورود خودتان را وارد کنید.',
        ],
        [
            'icon' => 'description',
            'label' => 'مدیریت سربرگ‌ها',
            'text' => 'برای تنظیم ظاهر و شماره‌گذاری، «سربرگ‌ها» را از بالای فهرست باز کنید و راهنمای همان صفحه را ببینید.',
        ],
    ];
@endphp

<div dir="rtl">
    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">co_present</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نکته‌های مهم</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rows as $row)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $row['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $row['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $row['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
