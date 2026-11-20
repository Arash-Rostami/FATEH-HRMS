@php
    $tabs = [
        ['chip' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]', 'label' => 'همه + هشت مرحله', 'hint' => 'زبانهٔ «همه» و یک زبانه برای هر مرحله وجود دارد. تعداد هر مرحله روی زبانه دیده می‌شود؛ تعداد صفر نمایش داده نمی‌شود.'],
    ];
    $filters = [
        ['icon' => 'person', 'label' => 'ثبت‌کننده', 'hint' => 'پیشنهادهای یک پیشنهاددهندهٔ مشخص را نشان می‌دهد.'],
        ['icon' => 'corporate_fare', 'label' => 'واحد مرتبط', 'hint' => 'پیشنهادهایی را نشان می‌دهد که واحد انتخاب‌شده در فهرست واحدهای مرتبط آن‌هاست.'],
        ['icon' => 'edit_note', 'label' => 'ثبت نظر توسط پیشنهاددهنده', 'hint' => 'با بله، خیر یا همه مشخص کنید نظرها هنگام ثبت توسط پیشنهاددهنده وارد شده‌اند یا بعداً از واحدها گرفته شده‌اند.'],
        ['icon' => 'attach_file', 'label' => 'دارای پیوست', 'hint' => 'پیشنهادهای دارای فایل، بدون فایل یا هر دو را ببینید.'],
        ['icon' => 'schedule', 'label' => 'تاریخ ثبت', 'hint' => 'فیلتر بازهٔ تاریخ ثبت پیشنهاد.'],
        ['icon' => 'forward_to_inbox', 'label' => 'دارای ارجاع', 'hint' => 'پیشنهادهایی را ببینید که برای اجرای کار به یک یا چند واحد سپرده شده‌اند.'],
    ];
    $groups = [
        ['icon' => 'route', 'label' => 'گروه‌بندی بر اساس مرحله', 'hint' => 'پیشنهادهای هر مرحله را کنار هم ببینید تا موارد منتظر نظر، تصمیم یا اجرا را پیدا کنید.'],
        ['icon' => 'person', 'label' => 'گروه‌بندی بر اساس ثبت‌کننده', 'hint' => 'پیشنهادهای هر فرد را کنار هم ببینید.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">view_module</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">فهرست، زبانه‌ها و فیلترها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        با زبانهٔ مرحله شروع کنید. سپس ثبت‌کننده، واحد، تاریخ یا وجود پیوست را مشخص کنید تا فهرست کوتاه‌تر شود. گروه‌بندی، موارد مشابه را کنار هم می‌آورد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tabs</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">زبانه‌های مرحله</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tabs as $t)
                <div class="flex items-start gap-4 p-5">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center px-3 h-10 rounded-xl {{ $t['chip'] }}">
                            <span class="text-[12px] font-black">{{ convertToPersian('9') }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $t['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">filter_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">شش فیلتر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($filters as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $f['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-secondary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-secondary-container)]">workspaces</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-secondary-container)]">دو گروه‌بندی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($groups as $g)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $g['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $g['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $g['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">tips_and_updates</span>
                ستون «شناسه» قابل کپی است و با جستجو هم پیدا می‌شود — الگوی <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">SN-YYYYMMDD-NNNNNN</code> با یک <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">whereRaw</code> روی CONCAT می‌گردد، پس با <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">SN-</code> هم یافت می‌شود.
            </p>
        </div>
    </div>
</div>
