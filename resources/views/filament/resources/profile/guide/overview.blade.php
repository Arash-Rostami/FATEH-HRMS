@php
    $fields = [
        ['icon' => 'badge', 'label' => 'کد پرسنلی', 'tag' => 'یکتا', 'hint' => 'کد پرسنلی یکتا؛ در جستجوی سراسری پنل و در جزئیات نتیجهٔ جستجو نمایش داده می‌شود. مقدار تکراری هنگام ذخیره پذیرفته نمی‌شود.'],
        ['icon' => 'person', 'label' => 'کاربر', 'tag' => 'اتصال به کاربر', 'hint' => 'هر پروفایل به یک کاربر (حساب ورود) تعلق دارد و هر کاربر فقط یک پروفایل دارد. مهارت‌ها هم روی همین کاربر ثبت می‌شوند. با تغییر کاربرِ یک پروفایل، همهٔ این اطلاعات به کاربر دیگر جابه‌جا می‌شود.'],
        ['icon' => 'domain', 'label' => 'واحد', 'tag' => 'اتصال به واحد', 'hint' => 'هر پروفایل به یک واحد وصل است. واحد انتخاب‌شده، گزینه‌های «واحد» و «بخش» را در زبانهٔ اطلاعات تکمیلی مشخص می‌کند.'],
        ['icon' => 'badge', 'label' => 'شماره ملی و شناسنامه', 'tag' => 'یکتا', 'hint' => 'شماره ملی و شمارهٔ شناسنامه هر دو یکتا هستند و در جستجوی سراسری پنل هم یافت می‌شوند. در نتایج جستجو کنار نام کاربر نمایش داده می‌شوند.'],
        ['icon' => 'workspace_premium', 'label' => 'مهارت‌ها', 'tag' => 'روی کاربر', 'hint' => 'مهارت‌ها روی رکورد SkillUserِ کاربر ذخیره می‌شوند (نه روی پروفایل). زبانهٔ مهارت‌ها در فرم، آن‌ها را از user.skillUsers می‌خواند و Diff-Sync می‌کند — ردیفِ حذف‌شده از فرم، از SkillUser هم پاک می‌شود.'],
        ['icon' => 'list_alt', 'label' => 'اطلاعات تکمیلی', 'tag' => 'HasMany', 'hint' => 'ردیف‌های «عنوان و مقدار» با نوع‌های متنوع (متن، عدد، متن بلند، انتخابی، تاریخ). نوع و گزینه‌ها از فهرست آمادهٔ اطلاعات تکمیلی تعیین می‌شود. عنوان‌های «واحد»، «بخش»، «عنوان نمایشی» و «ترتیب در چارت» را فقط مدیر می‌تواند تنظیم کند (ترتیب در چارت: عدد، یا * برای مدیرعامل شرکتِ زیرمجموعه).'],
        ['icon' => 'cloud_upload', 'label' => 'تصویر و پیوست‌ها', 'tag' => 'public', 'hint' => 'تصویر پروفایل (حداکثر ۵ مگابایت) و پیوست‌ها (حداکثر ۱۰ مگابایت) ذخیره می‌شوند. اگر تصویری نباشد، حروف اول نام به‌جای آن نمایش داده می‌شود.'],
        ['icon' => 'psychology', 'label' => 'درباره من', 'tag' => 'JSON', 'hint' => 'شش موضوع ثابت (بیوگرافی، فیلم، موسیقی، سرگرمی‌ها، غذا و ورزش) به‌علاوهٔ موضوع‌هایی که کاربر خودش اضافه می‌کند. موضوع‌های ثابت در فرم قابل تغییر نیستند و فقط توضیحشان ویرایش می‌شود.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«پروفایل» اطلاعات پرسنلی حساب کاربر را کامل می‌کند — داده‌های هویتی، تماس، شغل و مهارت</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در این صفحه پروفایلِ یک کاربر است: شامل مشخصات هویتی، اطلاعات تماس، وضعیت شغلی، تصویر، مهارت‌ها و اطلاعات تکمیلی. کاربر خودش بخش بزرگی از این داده‌ها را در پنل کاربری‌اش ویرایش می‌کند؛ شما نظارت کل‌سازمان دارید و می‌توانید هر فیلدی را اصلاح، تأیید یا تکمیل کنید. حذف پروفایل دائمی است و بازگردانی ندارد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای کلیدی هر پروفایل</p>
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
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $f['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                جستجوی سراسری پنل پروفایل‌ها را با نام کاربر، کد پرسنلی، شماره ملی، شناسنامه یا موبایل پیدا می‌کند و مستقیم به ویرایش می‌رود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">چه ماژول‌هایی به پروفایل وصل می‌شوند؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">person</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">کاربر</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">حساب ورود و مهارت‌های کاربر. زیر صفحهٔ ویرایش، زبانهٔ «کاربر» نمایش داده می‌شود تا نام، ایمیل، نقش، وضعیت و حضور کاربر را همان‌جا ویرایش کنید.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">domain</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">واحد</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">واحد انتخاب‌شده، گزینه‌های «واحد» و «بخش» در اطلاعات تکمیلی و فیلتر و گروه‌بندی فهرست پروفایل‌ها را مشخص می‌کند.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">workspace_premium</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">دایرکتوری مهارت‌ها</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">مهارت‌های تأییدشدهٔ کاربر در دایرکتوری همکاران قابل‌جستجو است؛ «خصوصی» بودن یک مهارت آن را از دایرکتوری پنهان می‌کند. مدیر می‌تواند وضعیت هر مهارت را در زبانهٔ مهارت‌ها تأیید یا رد کند.</p>
                </div>
            </div>
        </div>
    </div>
</div>