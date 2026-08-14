@php
    $fields = [
        [
            'icon' => 'corporate_fare',
            'label' => 'واحد سازمانی (department_id)',
            'tag' => 'کلید خارجی',
            'hint' => 'واحدی که این اختیار یا وظیفه برای آن تعریف می‌شود. کلید خارجی به کدِ واحد (Department.code) وصل می‌شود، نه به آیدی عددی. گزینه‌ها از کشِ واحدها (getCachedOptions) بارگذاری می‌شوند و قابل جستجو هستند.',
        ],
        [
            'icon' => 'person',
            'label' => 'مسئول (user_id)',
            'tag' => 'کلید خارجی',
            'hint' => 'کاربرِ دارندهٔ این اختیار یا مسئول مستقیم اجرای این وظیفه. پیش‌فرضِ فرم برابر با کاربرِ وارد‌شده (auth) است. در جستجوی سراسری پنل، اختیارات با نام کاربر و نام واحد پیدا می‌شوند.',
        ],
        [
            'icon' => 'groups',
            'label' => 'وظایف زیرمجموعه (sub_duty)',
            'tag' => 'بولی',
            'hint' => 'اگر روشن باشد، این وظیفه برای تمامی زیرمجموعه‌های سازمانیِ همان واحد نیز اعمال می‌شود. در جدول به‌صورت ستونِ تغییرحالت (ToggleColumn) قابل عوض‌کردن است و زبانه‌های فهرست «وظایف اصلی» و «وظایف فرعی» بر اساس همین فیلد از هم جدا می‌شوند.',
        ],
        [
            'icon' => 'subject',
            'label' => 'شرح وظیفه (details.duty)',
            'tag' => 'JSON · RichEditor',
            'hint' => 'متنِ غنیِ شرح وظیفه — حداکثر ' . convertToPersian('2000') . ' کاراکتر. قالب‌بندی، جدول، لینک و رنگِ متن پشتیبانی می‌شود. قبل از ذخیره با ContentSanitizerService پاک‌سازی می‌شود تا نشانه‌گذاری خطرناک حذف گردد.',
        ],
        [
            'icon' => 'handshake',
            'label' => 'تفویض مشترک (details.co_delegate)',
            'tag' => 'JSON · متن',
            'hint' => 'نام شخصِ مشترک در اجرای این اختیار — یک فیلد متنی ساده (حداکثر ' . convertToPersian('255') . ' کاراکتر). در پنل کاربر، هنگام باز کردن یک ردیف، در صورت وجود در کنار «تفویض مصوب» نمایش داده می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">هر ردیف یک «اختیار سازمانی» است: یک واحد + یک مسئول + شرح وظیفه</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        ماژول «اختیارات» فهرستِ اختیارات و وظایفِ محول‌شده به پرسنل را در سطحِ واحد‌های سازمانی نگه می‌دارد. سه فیلدِ سطحِ رکورد (واحد، مسئول، زیرمجموعه) مستقیماً روی جدول پایگاه‌داده ذخیره می‌شوند؛ اما هفت فیلدِ توضیحیِ دیگر (شرح وظیفه، روش اجرایی، فراوانی تکرار، شاخص اثر، تفویض پیشنهادی و تفویض مصوب و تفویض مشترک) همگی داخل یک ستونِ JSON به نام <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">details</code> قرار دارند. فیلترها، گروه‌بندی‌ها و جستجوی جدول مستقیماً روی همین کلیدهای JSON با <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">JSON_EXTRACT</code> کار می‌کنند — نه روی ستون‌های مجزا.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای هر اختیار</p>
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
                کوئریِ پایهٔ جدول خودکار <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">user</code> و <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">department</code> را eager-load می‌کند — ستون‌های «مسئول» و «واحد» بدون پرس‌وجوی اضافی روی هر ردیف پر می‌شوند.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">این ماژول به چه چیزی وصل است؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">corporate_fare</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">واحدهای سازمانی</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">واحد از طریق کلید خارجی <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">department_id</code> به کدِ واحد وصل می‌شود. اختیاراتی که واحدِ خالی دارند در پنل کاربر فیلترِ واحدها ظاهر نمی‌شوند — فقط واحدهایی که حداقل یک اختیار دارند در نوارِ کاربر می‌آیند.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">groups</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">کاربران</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">مسئول از طریق <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">user_id</code> به جدول کاربران وصل می‌شود. یک کاربر می‌تواند چند اختیار در چند واحدِ مختلف داشته باشد.</p>
                </div>
            </div>
        </div>
    </div>
</div>