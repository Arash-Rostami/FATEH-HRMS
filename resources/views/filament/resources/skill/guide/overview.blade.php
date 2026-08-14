@php
    $d68 = convertToPersian('68');
    $d2000 = convertToPersian('2000');
    $d255 = convertToPersian('255');

    $fields = [
        ['icon' => 'label', 'label' => 'نام مهارت (name)', 'tag' => 'یکتا', 'hint' => 'نام فارسی مهارت در سراسر سیستم با این نام شناخته می‌شود. یکتایی هم روی نام فارسی و هم روی نام انگلیسی بررسی می‌شود (see «اشتراک نام»). حداکثر ' . $d255 . ' کاراکتر.'],
        ['icon' => 'translate', 'label' => 'نام انگلیسی (name_en)', 'tag' => 'یکتا', 'hint' => 'نام انگلیسی اختیاری است، اما اگر پر شود در همان بررسی یکتاییِ نام فارسی شرکت می‌کند — نمی‌شود دو مهارت نام فارسیِ یکسان یا نام انگلیسیِ یکسان داشت.'],
        ['icon' => 'category', 'label' => 'دسته‌بندی (category)', 'tag' => 'موضوعی', 'hint' => 'دسته‌بندیِ آزادمتن است: از دسته‌های موجود انتخاب می‌کنید یا با «ساخت گزینه» دستهٔ جدید می‌سازید. در فهرست مهارت کاربر و در زبانهٔ فهرست ادمین به‌صورت برچسب نشان داده می‌شود و گروه‌بندیِ جدول بر اساس همین فیلد است.'],
        ['icon' => 'palette', 'label' => 'نماد (icon)', 'tag' => 'متریال', 'hint' => 'یکی از ' . $d68 . ' نماد از پیش‌تعریف‌شدهٔ متریال (SkillIcon). پیش‌فرض «workspace_premium» است. این نماد در پنل کاربر کنار نام مهارت نشان داده می‌شود.'],
        ['icon' => 'subject', 'label' => 'توضیحات (description)', 'tag' => 'نمایشی', 'hint' => 'توضیح کوتاه دربارهٔ مهارت — حداکثر ' . $d2000 . ' کاراکتر، اختیاری.'],
        ['icon' => 'toggle_on', 'label' => 'فعال (is_active)', 'tag' => 'وضعیت', 'hint' => 'تاگلِ پیش‌فرضِ روشن. فقط مهارت‌های فعال در کاتالوگِ کاربر و زبانهٔ «کاتالوگ» ظاهر می‌شوند. مهارت غیرفعال در زبانهٔ «غیرفعال» می‌نشیند.'],
    ];

    $states = [
        ['icon' => 'check_circle', 'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]', 'label' => 'فعال', 'note' => 'is_active = true و is_ghost = false — در کاتالوگ کاربر و زبانهٔ «کاتالوگ» ظاهر می‌شود.'],
        ['icon' => 'remove_circle', 'chip' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]', 'label' => 'غیرفعال', 'note' => 'is_active = false و is_ghost = false — در زبانهٔ «غیرفعال» با نشانِ شمارشی ظاهر می‌شود. قابل ویرایش و فعال‌سازی.'],
        ['icon' => 'auto_awesome', 'chip' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]', 'label' => 'تأمین‌نشده (ghost)', 'note' => 'is_ghost = true — مهارتی که کاربر جستجو کرده اما در کاتالوگ نبوده؛ توسط جستجوی کاربر ساخته شده، نه ادمین. در زبانهٔ «مهارت‌های جستجوشده» ظاهر می‌شود (see زبانهٔ «جستجوی تأمین‌نشده»).'],
    ];

    $links = [
        ['icon' => 'person', 'label' => 'داشندگان (skill_user)', 'hint' => 'هر مهارت از طریق جدول محوری skill_user به کاربران وصل می‌شود. ستون «تعداد دارندگان» جدول (skill_users_count) با withCount بارگذاری می‌شود و ویرایش/حذفِ آن رابطه از صفحهٔ کاربر یا پروفایل انجام می‌شود، نه اینجا.'],
        ['icon' => 'verified_user', 'label' => 'درخواست‌های مهارت (SkillRequestResource)', 'hint' => 'صفِ تأیید/رد درخواست‌های مهارتِ کاربران در منبع جداگانهٔ SkillRequestResource (گروه ناوبری همان، نزولی ۱۴) قرار دارد — آن‌جا وضعیت Pending/Approved/Rejected را مدیریت می‌کنید.'],
        ['icon' => 'groups', 'label' => 'مهارت‌های کاربر (UserResource)', 'hint' => 'زیرِ صفحهٔ ویرایش هر کاربر، مدیریت ارتباط «مهارت‌ها» به‌صورت فقط‌خواندنی ظاهر می‌شود و مهارت‌های آن کاربر (وضعیت، سطح، تأییدها، آخرین استفاده) را فهرست می‌کند.'],
        ['icon' => 'workspace_premium', 'label' => 'پنل کاربر (Profile / استعدادها)', 'hint' => 'تجربهٔ کاربری مهارت‌ها — انتخاب، پیشنهاد، تأیید همکار، سطح‌بندی خودکار — در زبانهٔ «استعدادها»ی پروفایل و دایرکتوری استعدادها قرار دارد. راهنمای آن سمت در راهنمای ماژول «پروفایل» (زبانهٔ «تجربهٔ کاربر») و لجند همان ماژول پوشش داده شده است.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">bolt</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«مهارت» یک رکورد کاتالوگ است که پنل کاربر و دایرکتوری استعدادها از آن تغذیه می‌کنند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در این صفحه یک مهارت سازمانی است (مثلاً طراحی محصول، پایتون، مذاکره). این ماژول کاملاً مدیریتی است — شما کاتالوگ مهارت‌ها را تعریف و نگهداری می‌کنید؛ کاربران از زبانهٔ «استعدادها»ی پروفایل خود از همین کاتالوگ انتخاب می‌کنند یا مهارت جدید پیشنهاد می‌دهند. سه وضعیت برای یک مهارت وجود دارد: فعال، غیرفعال و تأمین‌نشده (ghost).
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای هر مهارت</p>
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
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">{{ $f['tag'] }}</span>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                کاتالوگِ فعال در کشِ یک‌روزه (skill_active_catalog) نگه داشته می‌شود و پس از هر ذخیره یا حذفِ مهارت، به‌صورت خودکار پاک می‌شود — تغییرِ شما بلافاصله در فهرستِ مهارتِ کاربر اثر می‌کند.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">toggle_on</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">سه وضعیت یک مهارت</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($states as $s)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $s['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $s['note'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">چه چیزهایی به یک مهارت وصل می‌شوند؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($links as $l)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                            <span class="material-symbols-rounded text-[20px]">{{ $l['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $l['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $l['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>