@php
    $fields = [
        [
            'icon' => 'label',
            'label' => 'عنوان',
            'tag' => 'الزامی',
            'hint' => 'نام گالری که در فهرست، جستجوی سراسری و پنل کاربری نمایش داده می‌شود. حداکثر ۲۵۵ نویسه.',
        ],
        [
            'icon' => 'event',
            'label' => 'تاریخ رویداد',
            'tag' => 'اختیاری',
            'hint' => 'تاریخ مناسبت یا اتفاقِ محتوای گالری. با تقویم شمسی ثبت و در جدول نمایش داده می‌شود. جدول به‌صورت پیش‌فرض از جدیدترین تاریخ رویداد مرتب است؛ اگر خالی باشد، گالری در زبانهٔ «بدون تاریخ رویداد» می‌آید.',
        ],
        [
            'icon' => 'collections',
            'label' => 'محتوا',
            'tag' => 'الزامی',
            'hint' => 'می‌توانید چند فایل را با هم بارگذاری کنید. تصاویر (JPG، PNG، GIF، WEBP) و ویدیوها (MP4، WEBM، MOV) در یک گالری کنار هم قرار می‌گیرند؛ حداکثر ۵۰ مورد؛ هر تصویر تا ۵ مگابایت و هر ویدیو تا ۹۵ مگابایت. نام فایل‌ها هنگام ذخیره به‌صورت خودکار تغییر می‌کند. ترتیب فایل‌ها قابل تغییر است و پیش‌نمایش آن‌ها به‌صورت شبکه‌ای نشان داده می‌شود.',
        ],
        [
            'icon' => 'subject',
            'label' => 'توضیحات',
            'tag' => 'اختیاری',
            'hint' => 'توضیح کوتاه دربارهٔ محتوا یا مناسبت گالری؛ حداکثر ۲۰۰۰ نویسه. در صفحهٔ جزئیات و روی کارتِ پنل کاربری نمایش داده می‌شود.',
        ],
        [
            'icon' => 'share',
            'label' => 'واحد سازمانی',
            'tag' => 'دسترسی',
            'hint' => 'دو فیلد مجزا برای تعیین دیدِ گالری: یکی برای «تک‌واحدی» و دیگری برای «چندواحدی». انتخابِ هر کدام دیگری را خودکار خالی/غیرفعال می‌کند. خالی بودن هر دو = گالری عمومی. جزئیات کامل در زبانهٔ «دسترسی و اشتراک».',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">photo_library</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">هر مورد یک «گالری» از تصاویر و ویدیوهاست</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        مدلِ این ماژول <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">Photo</code> است (نه Gallery) — جدول <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">photos</code>. هر ردیف یک گالری مستقل است که می‌تواند تصاویر و ویدیوها را در یک آرایهٔ JSON (ستون <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">path</code>) نگه دارد. این ماژول دو طرفی است: ادمین محتوا را بارگذاری می‌کند و کاربران آن را در زبانهٔ «گالری» پنل خود می‌بینند (زبانهٔ «تجربهٔ کاربر» را ببینید).
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای هر گالری</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($fields as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                                {{ $f['tag'] }}
                            </span>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                ستون <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">path</code> از نوع longText با collation باینری (<code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">utf8mb4_bin</code>) است تا JSON روی ترتیب فایل‌ها پایدار بماند؛ ویدیو و تصویر را با هم در همان آرایه نگه می‌دارد و نوع فایل از پسوند شناسایی می‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">تصویر و ویدیو — یک فهرست، دو رفتار</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">image</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">تصاویر در ستون پیش‌نمایش</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">اولین فایلِ گالری به‌عنوان پیش‌نمایش در جدول نشان داده می‌شود؛ اگر اولین فایل ویدیو باشد، پیش‌نمایش خالی می‌ماند.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">videocam</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">ویدیوها در صفحهٔ جزئیات</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">ویدیوها (MP4/WEBM/MOV) از آرایه جدا شده و در صفحهٔ جزئیات به‌صورت پخش‌کنندهٔ HTML5 درون‌خطی (<code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">&lt;video controls&gt;</code>) با URL عمومیِ دیسک public نمایش داده می‌شوند؛ تصاویر هم به‌صورت استکِ محدود به ۱۲ مورد می‌نشینند.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">photo</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">شمارش موارد</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">ستون «تعداد موارد» در جدول و اینفولیست، کلِ اندازهٔ آرایهٔ <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">path</code> را می‌شمارد (تصویر + ویدیو با هم) — نه فقط تصاویر را.</p>
                </div>
            </div>
        </div>
    </div>
</div>