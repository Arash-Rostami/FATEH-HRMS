@php
    $fields = [
        [
            'icon' => 'badge',
            'label' => 'عنوان',
            'tag' => 'نمایشی',
            'hint' => 'عنوانی که زیر آیکون لینک در پنل کاربری نمایش داده می‌شود. در جدول و در جستجوی سراسری پنل هم می‌توانید لینک را با عنوانش پیدا کنید.',
        ],
        [
            'icon' => 'subject',
            'label' => 'توضیحات',
            'tag' => 'نمایشی',
            'hint' => 'توضیح اختیاری که کاربران با نگه‌داشتن نشانگر موس روی لینک می‌بینند. در جستجوی سراسری پنل هم پیدا می‌شود.',
        ],
        [
            'icon' => 'swap_horiz',
            'label' => 'نوع لینک',
            'tag' => 'جایگاه نمایش',
            'hint' => 'دو حالت دارد: «داخلی» (سبز، آیکون ساختمان) و «خارجی» (آبی، آیکون کره). نوع لینک تعیین می‌کند لینک در کدام بخش صفحهٔ کاربران نمایش داده شود. انتخاب آن الزامی است.',
        ],
        [
            'icon' => 'link',
            'label' => 'آدرس خارجی',
            'tag' => 'آدرس',
            'hint' => 'آدرس کامل مقصد، مانند https://example.com. وارد کردن آن الزامی است. آدرس‌هایی که با mailto: یا tel: شروع می‌شوند (برای ایمیل و تلفن) هم پذیرفته می‌شوند. حداکثر ۲۰۴۸ نویسه.',
        ],
        [
            'icon' => 'apartment',
            'label' => 'آدرس داخلی',
            'tag' => 'مسیریابی هوشمند',
            'hint' => 'اختیاری است. اگر آن را وارد کنید، کاربرانی که داخل شبکهٔ سازمان هستند به این آدرس می‌روند و کادر «آی‌پی‌های شبکه داخلی» نمایش داده می‌شود. اگر خالی باشد، همهٔ کاربران به «آدرس خارجی» می‌روند.',
        ],
        [
            'icon' => 'reorder',
            'label' => 'ترتیب نمایش',
            'tag' => 'ترتیب',
            'hint' => 'عددی از ۰ تا ۱۰۰؛ عدد کمتر زودتر نمایش داده می‌شود. می‌توانید آن را در فرم وارد کنید یا ردیف‌ها را در جدول بکشید. مقدار پیش‌فرض ۰ است و جدول از کمترین عدد شروع می‌شود.',
        ],
        [
            'icon' => 'image',
            'label' => 'تصویر بنر و آیکون',
            'tag' => 'تصویر',
            'hint' => 'هر دو الزامی‌اند. تصویر بنر (حداکثر ۵ مگابایت) روی کارت لینک در پنل کاربری دیده می‌شود. آیکون (حداکثر ۱ مگابایت) تصویری جداگانه است که فقط وقتی تصویر بنر نباشد نمایش داده می‌شود. اگر هیچ‌کدام نباشند، یک آیکون ثابت جایگزین می‌شود.',
        ],
        [
            'icon' => 'badge',
            'label' => 'توضیح تصویر بنر',
            'tag' => 'نمایشی',
            'hint' => 'متنی کوتاه که تصویر بنر را برای افرادی که آن را نمی‌بینند توصیف می‌کند. اگر خالی بماند، عنوان لینک به‌جای آن استفاده می‌شود.',
        ],
        [
            'icon' => 'badge',
            'label' => 'آیکون جایگزین',
            'tag' => 'نمایشی',
            'hint' => 'آیکونی که از فهرست انتخاب می‌کنید و وقتی تصویر بنر نباشد روی کارت لینک نمایش داده می‌شود. نیازی به نوشتن توضیح نیست.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">هر لینک یک میان‌بر است که در صفحهٔ «لینک‌ها» پنل کاربری نمایش داده می‌شود</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        شما لینک‌ها را اینجا تعریف می‌کنید و کاربران آن‌ها را در زبانهٔ «لینک‌ها و مسیرهای دیجیتال سازمان» می‌بینند. نوع لینک (داخلی یا خارجی) تعیین می‌کند لینک در کدام بخش صفحهٔ کاربران قرار بگیرد. اگر «آدرس داخلی» و «آی‌پی‌های شبکه داخلی» را پر کنید، مسیریابی هوشمند فعال می‌شود (زبانهٔ «مسیریابی هوشمند» را ببینید).
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اطلاعات هر لینک</p>
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
                نوع لینک فقط جایگاه نمایش را تعیین می‌کند؛ مقصد نهایی با «آدرس داخلی» و «آی‌پی‌های شبکه داخلی» مشخص می‌شود. مثلاً لینک «داخلی» برای کاربری که بیرون از شبکه است به آدرس خارجی می‌رود.
            </p>
        </div>
    </div>
</div>