@php
    $fields = [
        ['icon' => 'badge', 'label' => 'کد پرسنلی (personnel_id)', 'tag' => 'یکتا', 'hint' => 'شناسهٔ پرسنلی منحصربه‌فرد؛ در جستجوی سراسری پنل و در جزئیات نتیجهٔ جستجو نمایش داده می‌شود. مقدار تکراری روی ذخیره رد می‌شود (unique).'],
        ['icon' => 'person', 'label' => 'کاربر (user_id)', 'tag' => 'کلید رابطه', 'hint' => 'هر پروفایل به یک کاربر (حساب ورود) وصل است — رابطهٔ یک‌به‌یک. همین فیلد تعیین می‌کند مهارت‌ها روی کدام کاربر ثبت می‌شوند. تغییر کاربرِ یک پروفایل، جابجایی کاملِ داده‌هاست.'],
        ['icon' => 'domain', 'label' => 'واحد سازمانی (department_id)', 'tag' => 'رابطه', 'hint' => 'پروفایل از طریق کد واحد به Department وصل می‌شود. گزینه‌ها از کشِ واحدها (getCachedOptions) می‌آیند. واحد تعیین‌کنندهٔ گزینه‌های «واحد/بخش» در زبانهٔ اطلاعات تکمیلی است.'],
        ['icon' => 'badge', 'label' => 'کارت ملی و شناسنامه', 'tag' => 'یکتا', 'hint' => 'id_card_number و id_booklet_number هر دو یکتا هستند و در جستجوی سراسری پنل هم یافت می‌شوند. در نتایج جستجو کنار نام کاربر نمایش داده می‌شوند.'],
        ['icon' => 'workspace_premium', 'label' => 'مهارت‌ها (skills)', 'tag' => 'روی کاربر', 'hint' => 'مهارت‌ها روی رکورد SkillUserِ کاربر ذخیره می‌شوند (نه روی پروفایل). زبانهٔ مهارت‌ها در فرم، آن‌ها را از user.skillUsers می‌خواند و Diff-Sync می‌کند — ردیفِ حذف‌شده از فرم، از SkillUser هم پاک می‌شود.'],
        ['icon' => 'list_alt', 'label' => 'اطلاعات تکمیلی (details)', 'tag' => 'HasMany', 'hint' => 'ردیف‌های کلید/مقدار با نوع‌های متنوع (text/number/textarea/select/date). نوع و گزینه‌ها از ProfileDetailCatalog تعیین می‌شود. کلیدهای «واحد» و «بخش» فقط ادمین می‌تواند تنظیم کند.'],
        ['icon' => 'cloud_upload', 'label' => 'تصویر و پیوست‌ها', 'tag' => 'public', 'hint' => 'تصویر پروفایل در profiles/images (حداکثر ۲ مگابایت) و پیوست‌ها در profiles/docs (حداکثر ۵ مگابایت) روی دیسک public ذخیره می‌شوند. در صورت نبود تصویر، آواتار از حروف اول نام ساخته می‌شود.'],
        ['icon' => 'psychology', 'label' => 'درباره من (about_me)', 'tag' => 'JSON', 'hint' => 'شیء JSON با ۶ کلید ثابت (bio/movies/music/hobbies/food/sports) به‌علاوهٔ کلیدهای اضافه‌ای که کاربر خودش اضافه می‌کند. کلیدهای ثابت در فرم غیرفعال‌اند و فقط مقدارشان ویرایش می‌شود.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«پروفایل» مکملِ HR حسابِ کاربر است — داده‌های هویتی، تماس، شغل و مهارت</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در این صفحه پروفایلِ یک کاربر است: شامل مشخصات هویتی، اطلاعات تماس، وضعیت شغلی، تصویر، مهارت‌ها و اطلاعات تکمیلی. کاربر خودش بخش بزرگی از این داده‌ها را در پنل کاربری‌اش ویرایش می‌کند؛ شما نظارت کل‌سازمان دارید و می‌توانید هر فیلدی را اصلاح، تأیید یا تکمیل کنید. حذفِ یک پروفایل سخت است (SoftDelete ندارد) و ردیف کامل را برمی‌دارد.
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
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">کاربر (User)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">حساب ورود به‌علاوهٔ مهارت‌ها (SkillUser). زیرِ صفحهٔ ویرایش، مدیریت ارتباط «کاربر» ظاهر می‌شود تا نام، ایمیل، نقش، وضعیت و حضورِ کاربرِ این پروفایل را در همانجا ویرایش کنید.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">domain</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">واحد سازمانی (Department)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">از طریق department_id. واحدِ انتخابی، گزینه‌های «واحد/بخش» در اطلاعات تکمیلی و فیلتر/گروه‌بندیِ جدولِ پروفایل‌ها را تغذیه می‌کند.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">workspace_premium</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">دایرکتوری مهارت‌ها (Talent Pool)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">مهارت‌های تأییدشدهٔ کاربر در دایرکتوری همکاران قابل‌جستجو است؛ «خصوصی» بودن یک مهارت آن را از دایرکتوری پنهان می‌کند. آدمین می‌تواند وضعیت هر مهارت را در زبانهٔ مهارت‌ها تأیید یا رد کند.</p>
                </div>
            </div>
        </div>
    </div>
</div>