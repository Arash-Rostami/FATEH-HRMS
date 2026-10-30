@php
    $d14 = convertToPersian('14');

    $tabs = [
        ['label' => 'همه', 'hint' => 'همهٔ درخواست‌ها بدون فیلتر اضافه — به‌ترتیب پیش‌فرض (در حال بررسی‌ها بالاتر).'],
        ['label' => 'در حال بررسی', 'hint' => 'فقط درخواست‌های در حال بررسی. عدد کنار زبانه تعداد آن‌ها را نشان می‌دهد.'],
        ['label' => 'تایید شده', 'hint' => 'فقط درخواست‌های تایید شده. عدد کنار زبانه تعداد آن‌ها را نشان می‌دهد.'],
        ['label' => 'رد شده', 'hint' => 'فقط درخواست‌های رد شده. عدد کنار زبانه تعداد آن‌ها را نشان می‌دهد. درخواست ردشده با «درخواست مجدد» از پنل کاربر دوباره باز می‌شود.'],
    ];
    $filters = [
        ['label' => 'وضعیت (پیش‌فرض: در حال بررسی)', 'hint' => 'فیلتر بر اساس یکی از سه وضعیت. هنگام باز شدن صفحه، فیلتر روی «در حال بررسی» است — تا فقط درخواست‌های منتظر بررسی را ببینید.'],
        ['label' => 'واحد سازمانی', 'hint' => 'فیلتر بر اساس واحدِ کاربرِ درخواست‌کننده.'],
        ['label' => 'کهنه (بیش از ' . $d14 . ' روز)', 'hint' => 'درخواست‌های «در حال بررسی»ای که بیش از ' . $d14 . ' روز از ثبتشان گذشته — برای رسیدگی زودتر به درخواست‌های قدیمی.'],
        ['label' => 'بازهٔ تاریخ ایجاد', 'hint' => 'درخواست‌هایی را نشان می‌دهد که در بازهٔ تاریخ انتخابی ثبت شده‌اند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">filter_alt</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">زبانه‌ها و فیلترها: درخواست‌های منتظر بررسی بالای جدول، قدیمی‌ها جدا</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        چهار زبانهٔ وضعیت بالای جدول، با نشانِ شمارش از یک کوئریِ تجمیعیِ واحد. مرتب‌سازی پیش‌فرض، درخواست‌های «در حال بررسی» را بالای جدول و قدیمی‌ترین‌ها را اول می‌آورد — تا قدیمی‌ترین درخواست منتظر بررسی از چشم دور نماند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tab</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">زبانه‌های فهرست</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tabs as $t)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">tab</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_list</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلترها و مرتب‌سازی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($filters as $f)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">filter_list</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                ترتیب پیش‌فرض: ابتدا درخواست‌های «در حال بررسی»، سپس قدیمی‌ترین‌ها — وقتی زبانهٔ «همه» را باز می‌کنید هم همین ترتیب برقرار است.
            </p>
        </div>
    </div>
</div>