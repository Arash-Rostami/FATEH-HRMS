@php
    $panels = [
        [
            'icon' => 'corporate_fare',
            'label' => 'نوارِ واحدها',
            'hint' => 'نوارِ افقیِ بالای فهرست، فقط واحدهایی را نشان می‌دهد که حداقل یک اختیار دارند — نه همهٔ واحدها. واحدِ پیش‌فرض برابر با واحدِ پروفایلِ کاربر است. با کلیک روی یک واحد، فهرست به اختیاراتِ همان واحد محدود می‌شود.',
        ],
        [
            'icon' => 'manage_accounts',
            'label' => 'دو منظر: اجمالی و مدیریتی',
            'hint' => 'زبانهٔ «منظر اجمالی» فقط خلاصهٔ هر اختیار را نشان می‌دهد (شرح وظیفه + مسئول + نشانِ تفویض). زبانهٔ «منظر مدیریتی» با باز کردنِ هر ردیف، چهار ردیفِ اضافی (روش اجرایی، فراوانی تکرار، شاخص اثر، تفویض پیشنهادی) را آشکار می‌کند.',
        ],
        [
            'icon' => 'expand_more',
            'label' => 'باز و بسته‌کردن',
            'hint' => 'هر ردیف با کلیک باز/بسته می‌شود. دکمهٔ «باز کردن همه» / «بستن همه» همهٔ ردیف‌ها را یکجا باز یا بسته می‌کند. وقتی ردیفی باز شود، تفویضِ مصوب و تفویضِ مشترک در یک نوارِ بالایی نمایش داده می‌شوند.',
        ],
        [
            'icon' => 'bolt',
            'label' => 'تمرکز روی یک رکورد (Command Palette)',
            'hint' => 'وقتی کاربر از پالتِ دستوری یک اختیار را انتخاب کند، فهرست به همان یک رکورد پین می‌شود (پارامتر open در URL) و خودکار باز می‌شود. با دکمهٔ «خروج از تمرکز» به فهرستِ کامل برمی‌گردد.',
        ],
    ];
    $roles = [
        [
            'icon' => 'person',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'وظیفهٔ اصلی (sub_duty خاموش)',
            'hint' => 'آیکونِ شخص و نامِ مسئولِ مستقیم در ردیف نمایش داده می‌شود. این وظیفه فقط برای همان واحد اعمال می‌شود.',
        ],
        [
            'icon' => 'groups',
            'chip' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
            'label' => 'وظیفهٔ زیرمجموعه (sub_duty روشن)',
            'hint' => 'آیکونِ گروه و برچسبِ «وظایف زیرمجموعه» جایگزینِ نامِ مسئول می‌شود — این وظیفه برای تمامی زیرمجموعه‌های سازمانی نیز اعمال می‌گردد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ اختیارات چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/authority</code> کاربر یک فهرستِ اختصاصی از اختیاراتِ واحدِ خودش است — فقط خواندنی، بدون دکمهٔ ساخت یا ویرایش. وقتی کاربری از وضعیتِ یک اختیار یا اینکه چرا وظیفه‌اش را نمی‌بیند شکایت می‌کند، این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است. ویرایش و ایجاد همگی در همین پنلِ مدیریت انجام می‌شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">widgets</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">قابلیت‌های پنل کاربر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($panels as $p)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $p['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $p['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $p['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">groups</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">وظیفهٔ اصلی در برابر زیرمجموعه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($roles as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $r['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $r['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر می‌گوید «وظیفه‌ام را نمی‌بینم»، اول واحدِ فعالش را بررسی کنید — فهرست فقط اختیاراتِ همان واحدِ انتخاب‌شده را نشان می‌دهد و فقط واحدهایی می‌آیند که اختیار دارند.
            </p>
        </div>
    </div>
</div>