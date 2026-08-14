@php
    $tabs = [
        ['chip' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]', 'label' => 'همه + ۸ مرحله', 'hint' => 'نه زبانه: «همه» و یکی به ازای هر مرحله. هر زبانه شمارش آن مرحله را به‌صورت نشان می‌دهد (صفر پنهان است). زبانه‌ها با یک کوئری آماری واحد و <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">once()</code> محاسبه می‌شوند — نه یک کوئری به ازای هر زبانه.'],
    ];
    $filters = [
        ['icon' => 'person', 'label' => 'ثبت‌کننده', 'hint' => 'فیلتر بر اساس کاربر ثبت‌کننده (SelectFilter با preload).'],
        ['icon' => 'corporate_fare', 'label' => 'واحد ذی‌نفع', 'hint' => 'فیلتر با <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">whereJsonContains(departments, code)</code> — پیشنهادهایی که واحد انتخاب‌شده در فهرست ذی‌نفعان آن‌هاست.'],
        ['icon' => 'edit_note', 'label' => 'تکمیل شخصی', 'hint' => 'فیلتر سه‌حالتی روی پرچم <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">self_fill</code> — بله/خیر/هر دو.'],
        ['icon' => 'attach_file', 'label' => 'دارای پیوست', 'hint' => 'فیلتر سه‌حالتی روی وجود فایل پیوست (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">attachment</code> پر یا خالی).'],
        ['icon' => 'schedule', 'label' => 'تاریخ ثبت', 'hint' => 'فیلتر بازهٔ تاریخ ساخت (الگوی مشترک تاریخ پنل).'],
        ['icon' => 'forward_to_inbox', 'label' => 'دارای ارجاع', 'hint' => 'فیلتر toggle — پیشنهادهایی که حداقل یک ردیف بررسیِ واحد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">MA</code> با <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">referral</code> پر دارند.'],
    ];
    $groups = [
        ['icon' => 'route', 'label' => 'گروه‌بندی بر اساس مرحله', 'hint' => 'پیشنهادها را بر اساس مرحله دسته می‌کند — برای دیدن توزیع کارهای در‌جریان.'],
        ['icon' => 'person', 'label' => 'گروه‌بندی بر اساس ثبت‌کننده', 'hint' => 'پیشنهادها را بر اساس کاربر ثبت‌کننده دسته می‌کند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">view_module</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">فهرست، زبانه‌ها و فیلترها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانه‌های بالای فهرست، پیشنهادها را بر اساس مرحله جدا می‌کنند و شمارش زنده نشان می‌دهند. شش فیلتر و دو گروه‌بندی هم برای تحلیل دقیق‌تر در دسترس‌اند. زبانه‌ها با ترجیح کاربر قابل پنهان‌شدن‌اند.
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
                ستون «شناسه» قابل کپی و جستجوی پیشرفته است — الگوی <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">SN-YYYYMMDD-NNNNNN</code> با یک <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">whereRaw</code> روی CONCAT می‌گردد، پس با <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">SN-</code> هم یافت می‌شود.
            </p>
        </div>
    </div>
</div>