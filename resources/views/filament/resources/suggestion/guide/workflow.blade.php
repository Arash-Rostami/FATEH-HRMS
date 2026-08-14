@php
    $stages = [
        ['icon' => 'hourglass_empty', 'chip' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]', 'code' => 'pending', 'label' => 'ارسال شده', 'hint' => 'ثبت اولیه؛ هنوز هیچ واحدی بازخورد نداده.'],
        ['icon' => 'group', 'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]', 'code' => 'team_remarks', 'label' => 'منتظر هم‌تیمی‌ها', 'hint' => 'واحد ثبت‌کننده (اولین واحد ذی‌نفع) هنوز بازخورد موافق/نیمه‌موافق/مخالف نداده است.'],
        ['icon' => 'corporate_fare', 'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]', 'code' => 'dept_remarks', 'label' => 'منتظر ذینفعان', 'hint' => 'بازخورد واحد ثبت‌کننده ثبت شده ولی حداقل یکی از سایر واحدهای ذی‌نفع هنوز پاسخ نداده.'],
        ['icon' => 'gavel', 'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]', 'code' => 'awaiting_decision', 'label' => 'منتظر تصمیم نهایی', 'hint' => 'همهٔ واحدها بازخورد داده‌اند و منتظر تصمیم مدیریت ارشد است.'],
        ['icon' => 'check_circle', 'chip' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]', 'code' => 'accepted', 'label' => 'پذیرفته شده', 'hint' => 'مدیریت ارشد پذیرفت. اگر واحدی برای ارجاع مشخص شده باشد، در این مرحله می‌مانَد تا همهٔ واحدهای ارجاع‌شده اقدام را تکمیل‌شده اعلام کنند.'],
        ['icon' => 'cancel', 'chip' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]', 'code' => 'rejected', 'label' => 'پذیرفته نشده', 'hint' => 'مدیریت ارشد رد کرد. فرآیند در این مرحله پایان می‌یابد.'],
        ['icon' => 'find_in_page', 'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]', 'code' => 'under_review', 'label' => 'نیازمند تکمیل', 'hint' => 'مدیریت ارشد درخواست تکمیل مجدد کرده (بازخورد incomplete). با ثبت بازخورد جدید توسط واحد، مرحله دوباره محاسبه می‌شود.'],
        ['icon' => 'lock', 'chip' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]', 'code' => 'closed', 'label' => 'پایان‌یافته', 'hint' => 'پذیرفته‌شده و همهٔ واحدهای ارجاع‌شده اقدام را تکمیل کرده‌اند. بایگانی است.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">account_tree</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">مرحلهٔ پیشنهاد را <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">syncStage</code> خودکار محاسبه می‌کند — ادمین آن را دستی عوض نمی‌کند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر بار که بازخورد جدیدی ثبت شود، <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">syncStage</code> بازخوردهای همهٔ واحدها را بررسی می‌کند و مرحلهٔ پیشنهاد را بر اساس قواعد زیر تعیین می‌کند. هشت مرحله وجود دارد:
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">route</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">هشت مرحلهٔ پیشنهاد</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($stages as $s)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $s['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] font-mono" dir="ltr">{{ $s['code'] }}</span>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $s['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">smart_toy</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">اتوماسیون پنهان</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">hourglass_empty</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">سکوت = نیمه‌موافق پس از {{ convertToPersian('48') }} ساعت</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">اگر واحدی در بازهٔ {{ convertToPersian('48') }} ساعته پاسخ ندهد، بازخورد او به‌صورت خودکار «نیمه‌موافق» ثبت می‌شود و یادداشتِ «خودکار» می‌گیرد. مهلت بررسی در اینفولیست با علامت ⚠ نشان داده می‌شود وقتی گذشته باشد.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">inventory_2</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">پذیرش با ارجاع، تا تکمیل همهٔ واحدها «accepted» می‌ماند</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">وقتی مدیریت ارشد پیشنهاد را می‌پذیرد و واحدهایی برای اجرای اقدام ارجاع می‌دهد، مرحله روی <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">accepted</code> می‌مانَد تا همهٔ واحدهای ارجاع‌شده اقدام را تکمیل‌شده (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">complete=true</code>) اعلام کنند — آن‌گاه مرحله به <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">closed</code> تغییر می‌کند.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">bolt</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">رد کردن فقط با تصمیم مدیریت ارشد</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">مرحلهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">rejected</code> فقط وقتی رخ می‌دهد که بازخورد واحد MA برابر <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">disagree</code> باشد. <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">under_review</code> یعنی «نیازمند تکمیل مجدد» — نه رد شدن.</p>
                </div>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">tips_and_updates</span>
                زبانهٔ اینفولیست «روند بررسی» همین مسیر را به‌صورت یک تایم‌لاین بصری روی هر رکورد نشان می‌دهد — مرحلهٔ فعلی با انیمیشن مشخص است (تایم‌لاین دیداری، متفاوت از این هشت مرحلهٔ وضعیت).
            </p>
        </div>
    </div>
</div>