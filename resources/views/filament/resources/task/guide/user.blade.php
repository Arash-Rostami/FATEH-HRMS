@php
    $panels = [
        [
            'icon' => 'view_column',
            'label' => 'دو زبانه و چهار ستون',
            'hint' => '«وظایف من» کارهایی را نشان می‌دهد که انجامشان با شماست یا همکار آن‌ها هستید. «محول شده» برای کارهایی است که خودتان ساخته و به دیگری سپرده‌اید. هر ستون وضعیت، صفحه‌های جداگانه دارد.',
        ],
        [
            'icon' => 'search',
            'label' => 'پیدا کردن کارهای قدیمی',
            'hint' => 'جستجو، عنوان و توضیح وظیفه را بررسی می‌کند؛ کارهای آرشیوشده و انجام‌شده‌های قدیمی‌تر از ۴۵ روز هم در نتایج می‌آیند.',
        ],
        [
            'icon' => 'checklist',
            'label' => 'انتخاب گروهی',
            'hint' => 'کارت‌ها را انتخاب کنید و سپس مسئول انجام یا وضعیت آن‌ها را تغییر دهید یا حذفشان کنید. فقط مواردی تغییر می‌کنند که اجازهٔ آن را دارید. کارت‌های تیکت، آرشیوشده یا قفل‌شده ممکن است از عملیات کنار گذاشته شوند.',
        ],
        [
            'icon' => 'logout',
            'label' => 'محول‌کردن و واگردانی',
            'hint' => 'وقتی کار را به دیگری می‌سپارید، برای پیگیری به «محول شده» می‌رود. ایجادکننده می‌تواند واگذاری کارِ انجام‌نشده را لغو کند؛ این کار برای وظیفهٔ ساخته‌شده از تیکت ممکن نیست.',
        ],
        [
            'icon' => 'forum',
            'label' => 'گفتگوی وظیفه',
            'hint' => 'ایجادکننده، مسئول انجام، همکاران و مسئول پیگیری به گفتگو دسترسی دارند. در وظیفهٔ پروژه، افراد مجاز پروژه هم آن را می‌بینند. همکاران می‌توانند متن یا وضعیت کار را تغییر دهند؛ اختیار واگذاری، جابه‌جایی پروژه و حذف جداگانه بررسی می‌شود. پیام تازه برای افراد مرتبط اعلان می‌فرستد.',
        ],
        [
            'icon' => 'history',
            'label' => 'تاریخچهٔ وظیفه',
            'hint' => 'تغییرهای مهم با نام فرد و زمان ثبت می‌شوند. برای وظیفهٔ پروژه، «فعالیت‌ها و نظرات» پروژه را باز کنید؛ برای وظیفهٔ بدون پروژه، زبانهٔ «تاریخچه» داخل پنجرهٔ وظیفه را ببینید.',
        ],
        [
            'icon' => 'task_alt',
            'label' => 'نتیجهٔ کار و مسئول پیگیری',
            'hint' => 'پیش از انتقال کارت به «انجام‌شده»، نتیجهٔ کار را انتخاب کنید. مسئول پیگیری، نتیجه را دنبال می‌کند و با مسئول انجام فرق دارد. بخش سازمانی و منبع درخواست برای دسته‌بندی و پیدا کردن کارها هستند.',
        ],
        [
            'icon' => 'archive',
            'label' => 'آرشیو و کارهای قدیمی‌تر',
            'hint' => 'آرشیو، کارت را از فهرست فعال کنار می‌گذارد و حذف نمی‌کند. ستون انجام‌شده به‌طور معمول ۴۵ روز اخیر را نشان می‌دهد؛ دکمهٔ تاریخ، کارهای قدیمی‌تر و دکمهٔ آرشیو، کارت‌های آرشیوشده را باز می‌کند.',
        ],
        [
            'icon' => 'description',
            'label' => 'کارت‌های «از تیکت»',
            'hint' => 'این کارت‌ها از درخواست پشتیبانی ساخته شده‌اند. متن، وضعیت و گفتگو را در خود تیکت تغییر دهید. کارت انجام‌شده را با داشتن دسترسی لازم می‌توان آرشیو کرد یا برگرداند.',
        ],
        [
            'icon' => 'percent',
            'label' => 'مراحل کار و درصد پیشرفت',
            'hint' => 'چک‌لیست، مراحل کار و سهم هر مرحله از پیشرفت را نشان می‌دهد. در برد کاربر، افزودن یا حذف مرحله سهم‌ها را برابر می‌کند؛ درصدها هم قابل تغییرند. تیک زدن همهٔ مراحل، وضعیت کارت را عوض نمی‌کند. پیوست‌ها از داخل وظیفه یا نشان پیوست روی کارت باز می‌شوند.',
        ],
    ];

    $roles = [
        [
            'icon' => 'person',
            'chip' => 'primary',
            'label' => 'ایجادکننده',
            'hint' => 'وظیفه را ثبت کرده است. اگر مسئول دیگری انتخاب نشود، انجام کار هم با اوست. پس از سپردن کار به دیگری، می‌تواند گفتگو و پیگیری کند و در صورت داشتن دسترسی، واگذاری را لغو یا کار را حذف کند.',
        ],
        [
            'icon' => 'assignment_ind',
            'chip' => 'tertiary',
            'label' => 'مسئول انجام',
            'hint' => 'فردی است که باید کار را انجام دهد. وظیفه در «وظایف من» او دیده می‌شود و می‌تواند متن و وضعیت آن را با رعایت محدودیت‌های پروژه تغییر دهد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کارکنان در برد وظایف چه می‌بینند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        در برد وظایف، هر کار یک کارت است و ستون آن مرحلهٔ انجام کار را نشان می‌دهد. راهنمای زیر به شما کمک می‌کند هنگام پاسخ‌گویی به کارکنان، تفاوت واگذاری، آرشیو و کارت‌های تیکت را توضیح دهید.
    </p>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        وظیفهٔ متصل به پروژه، در برد همان پروژه هم دیده می‌شود. برای مدیریت کامل کارت، از دکمهٔ رفتن به برد وظایف استفاده کنید. تغییر در هرکدام از این دو صفحه روی همان وظیفه اعمال می‌شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">widgets</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">قابلیت‌های پنل کاربر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($panels as $p)
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
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">bolt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نشانگر فوری روی کارت</p>
        </div>
        <div class="p-5">
            <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">
                نوار کنار کارت، کارهایی را که نیاز به توجه دارند مشخص می‌کند. نزدیک شدن یا گذشتن مهلت، چند روز بدون تغییر ماندن و اولویت بالا یا فوری روی آن اثر دارند. برای کارهای انجام‌شده، در انتظار یا متوقف، هشدار معمول تأخیر نمایش داده نمی‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-secondary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-secondary-container)]">flag</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-secondary-container)]">اولویت چه اثری دارد؟</p>
        </div>
        <div class="p-5">
            <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">
                با زدن نشان اولویت روی کارت، آن را میان کم، متوسط، بالا و فوری تغییر دهید. جای کارت هم متناسب با اولویت به‌روز می‌شود؛ بنابراین کارت تازه یا تغییریافته همیشه بالای ستون قرار نمی‌گیرد.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">groups</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">نقش ایجادکننده در برابر مسئول انجام</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($roles as $r)
                @php
                    $chipClasses = match ($r['chip']) {
                        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                        default => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]',
                    };
                @endphp
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $chipClasses }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $r['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">help</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            برای توضیح روش استفاده به کارکنان، آن‌ها را به دکمهٔ راهنمای بالای برد وظایف راهنمایی کنید. تفاوت نقش‌ها، ثبت انجام کار، آرشیو، جستجو و نکات کاربردی در همان‌جا آمده است.
        </p>
    </div>
</div>
