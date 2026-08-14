@php
    $fields = [
        [
            'icon' => 'sell',
            'label' => 'دسته‌بندی (category)',
            'tag' => 'موضوعی',
            'hint' => 'برچسب موضوعیِ سوال — مثلاً «حقوق»، «مرخصی»، «فناوری». فیلد Select است: از دسته‌بندی‌های موجود انتخاب کنید یا با تایپِ یک عنوان جدید، دسته‌بندی تازه بسازید (createOptionForm). این فیلد کلیدِ فیلتر و زبانهٔ فهرست است و در جدول، فیلترها و گروه‌بندی به‌صورت برچسب نمایش داده می‌شود.',
        ],
        [
            'icon' => 'forum',
            'label' => 'سوال (question)',
            'tag' => 'محتوایی',
            'hint' => 'متنِ کاملِ سوال — RichEditor با حداقل ارتفاع ۱۲۰ پیکسل و حداکثر ' . convertToPersian('1000') . ' کاراکتر. متن قالب‌بندی‌شده (بولد، ایتالیک، لینک و لیست) مجاز است. در جدول با html و limit(60) خلاصه می‌شود؛ جستجوی سراسری پنل پرسش‌ها را از طریق دسته‌بندی یا نام/توضیحاتِ واحد پیدا می‌کند (نه متنِ سوال).',
        ],
        [
            'icon' => 'article',
            'label' => 'پاسخ (answer)',
            'tag' => 'محتوایی',
            'hint' => 'متنِ کاملِ پاسخ — RichEditor با حداقل ارتفاع ۲۸۰ پیکسل و حداکثر ' . convertToPersian('5000') . ' کاراکتر. جدول، کدبلوک، خط افقی، هایلایت و پیوند در دسترس است. نوار ابزار شناور هنگام انتخاب متن ظاهر می‌شود. در جدول به‌صورت html با lineClamp(2) و limit(60) نمایش داده می‌شود و پیش‌فرض مخفی است.',
        ],
        [
            'icon' => 'person',
            'label' => 'ثبت‌کننده (user_id)',
            'tag' => 'سیستمی',
            'hint' => 'کاربری که سوال را ایجاد کرده — به‌طور خودکار برابر کاربرِ فعلی (auth()->id()) تنظیم می‌شود و قفل است (disabled + dehydrated). نمی‌توانید ثبت‌کننده را بعد از ساخت عوض کنید؛ فقط ادمینِ درگیر در رکورد مالک می‌ماند.',
        ],
        [
            'icon' => 'apartment',
            'label' => 'واحد سازمانی (department_id)',
            'tag' => 'دسترسی',
            'hint' => 'اختیاری. در صورت انتخاب، این سوال در پنل کاربری فقط برای کارکنانِ همان واحد نمایش داده می‌شود (فیلتر کاربر بر اساس department_id). خالی گذاشتن یعنی سوال «عمومی» است و همهٔ کاربران آن را می‌بینند. زبانهٔ «عمومی» و «مربوط به دپارتمان» در فهرست همین تفکیک را نشان می‌دهد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«پرسش متداول» یک ورودیِ دانشِ سازمانی است که کاربران در پنل خود می‌بینند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در این صفحه یک جفت سوال-پاسخ است که در زبانهٔ «پرسش‌های متداول» پنل کاربری به‌صورت آکاردئون نمایش داده می‌شود. شما سوال و پاسخ را می‌سازید، دسته‌بندی و واحد مرتبط را مشخص می‌کنید، و کاربران بر اساس دسته‌بندی یا واحدِ خود آن را فیلتر و جستجو می‌کنند. این ماژول دو طرفی است: نگهداریِ محتوا اینجا (ادمین) و نمایش و جستجو در پنل کاربری.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای هر پرسش متداول</p>
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
                متن سوال و پاسخ هنگام ذخیره از طریق ContentSanitizerService پاکسازی می‌شود — تگ‌های خطرناک خودکار حذف می‌شوند ولی قالب‌بندی RichEditor حفظ می‌ماند.
            </p>
        </div>
    </div>
</div>