@php
    $panels = [
        ['icon' => 'view_sidebar', 'label' => 'ویزارد مرحله‌ای', 'hint' => 'صفحهٔ /energy کاربر یک ویزارد چهارمرحله‌ای است — هر مرحله یک بعد (جسم، احساس، ذهن، روح). نقطه‌های پیشرفت در سرصفحه نشان می‌دهند کاربر کجاست. دکمهٔ «بعدی» تا وقتی حداقل یک گزینه انتخاب نشده غیرفعال می‌ماند.'],
        ['icon' => 'checklist', 'label' => 'گزینهٔ آخر انحصاری', 'hint' => 'گزینهٔ آخر هر بخش («هیچ‌کدام از موارد بالا») با انتخاب هر گزینهٔ دیگر پاک می‌شود و برعکس. این گزینه در شمارش امتیاز هم نمی‌خورد — امتیاز بعد فقط تعداد گزینه‌های غیرآخر است.'],
        ['icon' => 'event_repeat', 'label' => 'قفل ۲۵ روزهٔ سمت سرور', 'hint' => 'اگر کاربر در ۲۵ روز گذشته پاسخ‌نامه ثبت کرده باشد، فرم پنهان می‌شود و فقط زبانهٔ «نتایج» باز است. این قفل با lockForUpdate در یک تراکنش بررسی می‌شود، نه فقط یک پنهان‌سازی سمت کلاینت.'],
        ['icon' => 'poll', 'label' => 'زبانهٔ پرسشنامه و نتایج', 'hint' => 'دو زبانه است: «پرسشنامه» و «نتایج». زبانهٔ فعال در URL ذخیره می‌شود ( ?tab=chart )، پس کاربر می‌تواند مستقیم به نتایج پیوند بدهد.'],
    ];

    $chart = [
        ['icon' => 'trending_up', 'label' => 'روند ۱۸ ماهه', 'text' => 'نمودار روند، امتیازهای ۱۸ ماه اخیرِ کاربر را در هر چهار بعد و امتیاز کلی نشان می‌دهد. پنجرهٔ ۱۸ ماهه هم برای میانگین شرکت و هم برای روندِ شخصی یکسان است.'],
        ['icon' => 'groups', 'label' => 'میانگین شرکت، بدونِ خودِ کاربر', 'text' => 'میانگین شرکت در نمودار، کاربرِ فعلی را مستثنی می‌کند — یعنی کاربر با همکارانش مقایسه می‌شود، نه با خودش. این یک مقایسهٔ همتاست.'],
        ['icon' => 'shield_person', 'label' => 'دیدِ مدیر از تیم', 'text' => 'تنها مدیر بخش (سرپرست)، نمودار اعضای تیمش را می‌بیند — آخرین امتیاز هر عضویی که حداقل یک پاسخ‌نامه ثبت کرده باشد. اعضای بدون پاسخ‌نامه از فهرست حذف می‌شوند. قوانین تیم از سرویس تیم‌بندی اعمال می‌شود.'],
        ['icon' => 'bolt', 'label' => 'بازخوانی بدون بازبارگذاری', 'text' => 'پس از ثبت پاسخ، یک رویداد Livewire نمودار را بازخوانی می‌کند — میانگین‌ها، روند و آخرین امتیاز بدون بازبارگذاریِ صفحه به‌روز می‌شوند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ انرژی چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/energy</code> کاربر یک ویزارد چهارمرحله‌ای برای ثبت پاسخ و یک نمودار نتایج است. وقتی کاربر از وضعیت پرسشنامه یا دسترسی‌اش شکایت می‌کند، این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
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
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">bar_chart</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">زبانهٔ نتایج و نمودار</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($chart as $p)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $p['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $p['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $p['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر می‌گوید «نمودار خالی است»، یا هنوز پاسخ‌نامه‌ای ثبت نکرده یا در ۱۸ ماه اخیر پاسخی ندارد. آخرین پاسخ‌نامهٔ او را در جدول ادمین بیابید و تاریخ تکمیل را بازبینی کنید.
            </p>
        </div>
    </div>
</div>