@php
    $ops = [
        ['icon' => 'visibility', 'label' => 'مشاهده', 'hint' => 'دکمهٔ «مشاهده» اینفولیستِ زبانه‌دار را باز می‌کند: هویت، تماس، شغل، تصویر/پیوست، مهارت‌ها، درباره من و اطلاعات تکمیلی — همگی شمسی و راست‌چین.'],
        ['icon' => 'edit', 'label' => 'ویرایش', 'hint' => 'فرم ویرایش شش زبانه دارد: هویت/تماس/شغل، رسانه، مهارت‌ها، درباره من، و اطلاعات تکمیلی. زیرِ صفحه، مدیریت ارتباط «کاربر» ظاهر می‌شود تا نام، ایمیل، نقش، وضعیت، حضور و سهمیهٔ کاربرِ این پروفایل را ویرایش کنید.'],
        ['icon' => 'delete', 'label' => 'حذف (سخت)', 'hint' => 'حذف رکورد پروفایل را کامل برمی‌دارد (SoftDelete ندارد). حذفِ پروفایل به‌تنهایی حساب کاربر را پاک نمی‌کند — رابطه و سمتِ مهارت‌ها روی User است. قبل از حذف مطمئن شوید رکوردهای وابسته (مهارت‌ها، details) متناسب هستند.'],
        ['icon' => 'download', 'label' => 'خروجی اکسل', 'hint' => 'از منوی bulk actions روی ردیف‌های انتخاب‌شده، خروجی اکسل بگیرید (ProfileExporter). اعمال روی کل فهرست فیلترشده نیز ممکن است.'],
    ];
    $skillsOps = [
        ['icon' => 'workspace_premium', 'label' => 'Diff-Sync مهارت‌ها', 'hint' => 'زبانهٔ مهارت‌ها در فرم، ردیف‌ها را با user.skillUsers مقایسه می‌کند: ردیفِ نگه‌داشته‌شده update، ردیفِ جدید create و ردیفِ حذف‌شده از فرم، از SkillUser پاک می‌شود. موفقیت/رد بودن و آخرین استفاده و پرچم‌های mentoring/private را اینجا تنظیم می‌کنید.'],
        ['icon' => 'verified_user', 'label' => 'تأیید و رد', 'hint' => 'وضعیت هر مهارت از SkillRequestStatus می‌آید (پیش‌فرض Approved). وقتی وضعیت Approved است، approved_at و approved_by خودکار روی زمان/کاربرِ فعلی ثبت می‌شود.'],
        ['icon' => 'list_alt', 'label' => 'اطلاعات تکمیلی', 'hint' => 'هر ردیف یک کلید از ProfileDetailCatalog است؛ نوع ورودی (text/number/textarea/select/date) خودکار از کلید تعیین می‌شود. گزینه‌های «واحد» و «بخش» از واحدِ سازمانیِ همین پروفایل خوانده می‌شوند. کلید سفارشی هم می‌توانید بسازید.'],
        ['icon' => 'cloud_upload', 'label' => 'تصویر و پیوست', 'hint' => 'تصویر پروفایل (avatar، حداکثر ۲ مگابایت) و پیوست‌ها (PDF/تصویر/سند، حداکثر ۵ مگابایت) روی دیسک public ذخیره می‌شوند. پیوست‌ها در اینفولیست با لینک «مشاهده فایل» باز می‌شوند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما: تکمیل و نظارت بر پروندهٔ پرسنلی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        سه دکمهٔ عملیات روی هر ردیف (پس از سلول‌ها): مشاهده، ویرایش، حذف. دکمهٔ «ساخت پروفایل» در هدر قرار دارد. جستجوی سراسری پنل هم پروفایل‌ها را با نام کاربر، کد پرسنلی، شماره ملی، شناسنامه یا موبایل پیدا می‌کند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">عملیات روی هر ردیف</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($ops as $op)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $op['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $op['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $op['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">tune</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نکات ویرایش: مهارت‌ها، اطلاعات تکمیلی، رسانه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($skillsOps as $op)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $op['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $op['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $op['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                کلیدهای «واحد» و «بخش» در اطلاعات تکمیلی فقط ادمین می‌تواند تنظیم کند — کاربر آن‌ها را در پنل خودش فقط می‌بیند، نه ویرایش.
            </p>
        </div>
    </div>
</div>