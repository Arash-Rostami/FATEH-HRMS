@php
    $fields = [
        [
            'icon' => 'badge',
            'label' => 'کد واحد (code)',
            'tag' => 'کلید اصلی',
            'hint' => 'کد منحصربه‌فرد شناسایی واحد است — فقط حروف انگلیسی، اعداد، خط تیره و زیرخط مجاز است (alphaDash) و حداکثر ۱۰ کاراکتر. کد کلیدِ اصلی رکورد است (نه عدد افزایشی)، پس نمی‌توان آن را بعد از ساخت عوض کرد. کاربران از طریق «کد واحد» به این رکورد وصل می‌شوند.',
        ],
        [
            'icon' => 'label',
            'label' => 'نام واحد (name)',
            'tag' => 'نمایشی',
            'hint' => 'نام رسمی واحد سازمانی آن‌طور که در ساختار سازمانی شناخته می‌شود. در جستجوی سراسری پنل و در فهرست‌ها برای عنوان استفاده می‌شود (اگر توضیحات خالی باشد).',
        ],
        [
            'icon' => 'subject',
            'label' => 'توضیحات (description)',
            'tag' => 'نمایشی',
            'hint' => 'توضیحات تکمیلی دربارهٔ ماهیت، وظایف یا حوزهٔ فعالیت واحد. اگر پر باشد، به‌جای «نام» در عنوان جستجوی سراسری و در برچسبِ نمایشی واحد (displayLabel) ظاهر می‌شود.',
        ],
        [
            'icon' => 'widgets',
            'label' => 'واحدها (units)',
            'tag' => 'آرایه‌ای',
            'hint' => 'فهرست واحدهای زیرمجموعه — فیلد TagsInput است؛ نام را تایپ کنید و Enter بزنید. مقادیر تکراری و خالی به‌صورت خودکار حذف می‌شوند و به‌صورت آرایهٔ JSON ذخیره می‌گردد.',
        ],
        [
            'icon' => 'splitscreen',
            'label' => 'بخش‌ها (sections)',
            'tag' => 'آرایه‌ای',
            'hint' => 'فهرست بخش‌های زیرمجموعه — همان رفتار «واحدها» را دارد. هنگام طبقه‌بندی وظایف و تسک‌ها قابل انتخاب است.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«واحد سازمانی» کلیدِ وصلِ پرسنل، سمت‌ها و تیکت‌هاست</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در این صفحه یک واحد سازمانی است (مثلاً فناوری اطلاعات، مالی، مدیریت). کلیدِ هر رکورد «کد» است (نه آیدی عددی) و پرسنل از طریق پروفایل خود به همین کد به یک واحد وصل می‌شوند. این واحدِ سازمانی ماژولی کاملاً مدیریتی است — پنل کاربری جداگانه‌ای ندارد؛ شما واحدها را تعریف می‌کنید و سایر ماژول‌ها (کاربران، تیکت‌ها، گزارش‌ها، سمت‌ها) بر اساس همان کد به آن وابسته می‌شوند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای هر واحد سازمانی</p>
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
                برچسبِ نمایشی واحد در سراسر سیستم «توضیحات ← نام ← کد» است (displayLabel)؛ ستونِ جدول و عنوان جستجو همین برچسب را نشان می‌دهند.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">چه ماژول‌هایی به یک واحد وصل می‌شوند؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">groups</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">پرسنل (Users)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">کاربران از طریق پروفایل (Profile.department_id) به کد واحد وصل می‌شوند. زیرِ صفحهٔ ویرایش هر واحد، مدیریت ارتباط «پرسنل» واحد ظاهر می‌شود و شمارش پرسنل در ستون «تعداد پرسنل» جدول می‌نشیند.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">verified_user</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">سمت‌ها (Authorities)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">مدیریت ارتباط «سمت‌ها» زیرِ صفحهٔ ویرایش هر واحد، اختیارات و وظایف محول‌شدهٔ پرسنلِ آن واحد را فهرست می‌کند.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">description</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">گزارش‌ها (Reports)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">مدیریت ارتباط «گزارش‌ها» گزارش‌های منتشرشدهٔ این واحد را زیرِ صفحهٔ ویرایش نشان می‌دهد. تیکت‌های کاربران نیز بر اساس کد واحد، گزینه‌های سفارشی خود را از همین رکورد می‌خوانند (زبانهٔ «گزینه‌های تیکت» را ببینید).</p>
                </div>
            </div>
        </div>
    </div>
</div>