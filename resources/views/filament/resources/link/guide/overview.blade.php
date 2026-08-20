@php
    $fields = [
        [
            'icon' => 'badge',
            'label' => 'عنوان (url_title)',
            'tag' => 'نمایشی',
            'hint' => 'عنوانی که زیر آیکون لینک در پنل کاربری نمایش داده می‌شود. کلیدِ عنوانِ رکورد در جستجوی سراسری پنل است و در جدول هم قابل جستجو و مرتب‌سازی است.',
        ],
        [
            'icon' => 'subject',
            'label' => 'توضیحات (url_description)',
            'tag' => 'نمایشی',
            'hint' => 'توضیح اختیاری که با نگه‌داشتن نشانگر روی لینک در پنل کاربری ظاهر می‌شود. در جستجوی سراسری پنل هم جستجو می‌شود.',
        ],
        [
            'icon' => 'swap_horiz',
            'label' => 'نوع لینک (link)',
            'tag' => 'کلید رفتار',
            'hint' => 'دو حالت دارد: «داخلی» (سبز، آیکون ساختمان) و «خارجی» (آبی، آیکون کره). این نوع تعیین می‌کند لینک در کدام بخشِ صفحهٔ کاربر (داخلی یا خارجی) ظاهر شود. فیلد رادیویی است و اجباری.',
        ],
        [
            'icon' => 'link',
            'label' => 'آدرس خارجی (url)',
            'tag' => 'مسیر',
            'hint' => 'آدرس کامل مقصد — همانند الگو https://example.com. اجباری است و با یک عبارت منظمِ میزبان اعتبارسنجی می‌شود (http(s)://، mailto:، tel: یا نام میزبان خالص). حداکثر ۲۰۴۸ کاراکتر.',
        ],
        [
            'icon' => 'apartment',
            'label' => 'آدرس داخلی (internal_url)',
            'tag' => 'مسیر هوشمند',
            'hint' => 'اختیاری است؛ اگر پر شود، لینک «مسیریابی هوشمند» می‌گیرد و کاربرانِ داخل شبکه به این آدرس هدایت می‌شوند. در این حالت فیلد «آی‌پی‌های شبکه داخلی» فعال می‌شود. اگر خالی باشد، همه کاربران به همان «آدرس خارجی» می‌روند.',
        ],
        [
            'icon' => 'reorder',
            'label' => 'ترتیب نمایش (sequence)',
            'tag' => 'ترتیب',
            'hint' => 'عدد ۰ تا ۱۰۰ — عدد کمتر = نمایش زودتر. می‌توانید این عدد را در فرم تنظیم کنید یا در جدول با کشیدن ردیف‌ها reorderable مرتب کنید. پیش‌فرض ۰ و مرتب‌سازی پیش‌فرض جدول صعودی است.',
        ],
        [
            'icon' => 'image',
            'label' => 'تصویر بنر (image) و آیکون (icon)',
            'tag' => 'رسانه',
            'hint' => 'هر دو اجباری‌اند. تصویر بنر (حداکثر ۱ مگابایت، پوشهٔ link/image) روی کارت کاربر می‌نشیند؛ آیکون (حداکثر ۵۱۲ کیلوبایت، پوشهٔ link/icon) یک تصویر جداگانه است که فقط وقتی تصویر بنر وجود نداشته باشد نمایش داده می‌شود. اگر هیچ‌کدام تنظیم نشده باشند، یک آیکونِ عمومیِ ثابت (Material Symbols) جایگزین می‌شود.',
        ],
        [
            'icon' => 'badge',
            'label' => 'توضیح تصویر بنر (image_description)',
            'tag' => 'نمایشی',
            'hint' => 'متن جایگزین (alt) تصویرِ بنر برای دسترسی‌پذیری. اگر خالی بماند، عنوان لینک (url_title) به‌جای آن استفاده می‌شود.',
        ],
        [
            'icon' => 'badge',
            'label' => 'توضیح آیکون (icon_description)',
            'tag' => 'نمایشی',
            'hint' => 'متن جایگزین (alt) تصویرِ آیکون برای دسترسی‌پذیری — دقیقاً مثل «توضیح تصویر بنر». روی نام لایگچرِ Material Symbols یا هر رفتار دیگری اثر ندارد؛ صرفاً attribute متنیِ alt همان <img> است.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">هر ردیف لینک یک میان‌بر دیجیتال است که در صفحهٔ «لینک‌ها» پنل کاربری ظاهر می‌شود</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        این ماژول دو طرفه است: شما لینک‌ها را اینجا تعریف می‌کنید و کاربران آن‌ها را در زبانهٔ «لینک‌ها و مسیرهای دیجیتال سازمان» می‌بینند. کلیدِ هر رکورد «عنوان» است و نوعِ لینک (داخلی/خارجی) تعیین می‌کند لینک در کدام بخشِ صفحهٔ کاربر نشیند. «آدرس داخلی» و «آی‌پی‌ها» اگر پر شوند، مسیریابی هوشمند را فعال می‌کنند (زبانهٔ «مسیریابی هوشمند» را ببینید).
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای هر لینک</p>
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
                نوع لینک فقط جایگاه نمایش را تعیین می‌کند؛ مقصدِ نهایی با «آدرس داخلی» و «آی‌پی‌ها» کنترل می‌شود — یک لینکِ «داخلی» می‌تواند برای کاربرِ بیرون از شبکه به آدرس خارجی برود.
            </p>
        </div>
    </div>
</div>