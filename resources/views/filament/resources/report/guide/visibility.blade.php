@php
    $modes = [
        [
            'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            'icon' => 'public',
            'label' => 'عمومی',
            'hint' => 'وقتی «دسترسی واحدها» (departments) خالی باشد، گزارش برای همهٔ کاربران در زبانهٔ «گزارشات» قابل دید است. در جدول با آیکون کره و رنگ success نشان داده می‌شود.',
            'rule' => 'departments = خالی',
        ],
        [
            'chip' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]',
            'icon' => 'lock',
            'label' => 'محدود به یک واحد',
            'hint' => 'وقتی فقط یک واحد در «دسترسی واحدها» انتخاب شده باشد، گزارش فقط برای کاربران همان واحد قابل دید است. در جدول با آیکون قفل و رنگ warning.',
            'rule' => 'departments = [یک کد]',
        ],
        [
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'icon' => 'groups',
            'label' => 'محدود به چند واحد',
            'hint' => 'وقتی چند واحد در «دسترسی واحدها» انتخاب شده باشد، گزارش برای کاربران همهٔ آن واحدها قابل دید است. در جدول با آیکون گروه و رنگ info.',
            'rule' => 'departments = [کد۱، کد۲، ...]',
        ],
    ];

    $publishing = [
        [
            'icon' => 'bookmark',
            'label' => 'سنجاق (pinned)',
            'hint' => 'گزارش‌های مهم را سنجاق کنید تا در صدر فهرستِ زبانهٔ کاربر (بالای ترتیبِ تاریخ) بنشینند. مناسب گزارش‌های رسمیِ دوره‌ای که دیرنگام می‌خواهید بالا بمانند.',
        ],
        [
            'icon' => 'calendar_month',
            'label' => 'تاریخ گزارش (report_date)',
            'hint' => 'تاریخ یا دورهٔ شمسی که گزارش به آن تعلق دارد (مستقل از تاریخ بارگذاری). یک گزارشِ ماهانه که دیر بارگذاری شده، همچنان با تاریخِ همان ماه نشان داده می‌شود. در جدول sortable و دارای فیلترِ بازه‌ای است.',
        ],
        [
            'icon' => 'event_busy',
            'label' => 'تاریخ انقضا (expires_at)',
            'hint' => 'تاریخ شمسیِ پس از آن گزارش به‌صورت خودکار از زبانهٔ کاربر مخفی می‌شود (بدون حذف). خالی = بدون انقضا. ادمین همچنان گزارش را در پنل می‌بیند و می‌تواند بازنشانی کند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">lock</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">دسترسیِ گزارش: عمومی یا محدود به واحدها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        دیدِ یک گزارش با فیلدِ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">departments</code> (آرایهٔ JSON از کدهای واحدها) تعیین می‌شود. توجه: فیلدِ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">department_id</code> «موضوع» گزارش است (این گزارش دربارهٔ کدام واحد است)، نه مخاطبِ آن؛ پس یک گزارش دربارهٔ واحدِ الف می‌تواند فقط برای واحدهای ب و ج قابل‌مشاهده باشد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tune</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">سه حالتِ دسترسی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($modes as $m)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $m['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $m['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $m['label'] }}</p>
                            <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $m['rule'] }}</code>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $m['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                ستون «مخاطبان» جدول و اینفولیست، مقادیر را از اکسسورِ <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">audience_departments</code> می‌خواند که از کشِ واحدها (Department::getCachedModels) ساخته می‌شود — بدون کوئری اضافه per-row.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">publish</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">کنترل‌های انتشار</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($publishing as $p)
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلترها و عملیاتِ گروهیِ دسترسی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">visibility</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">فیلتر دسترسی (Ternary)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">«محدود» فقط رکوردهایی که <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">departments</code> غیرخالی است (<code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">JSON_LENGTH &gt; 0</code>)؛ «عمومی» فقط رکوردهای بدون مخاطب.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">share</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">اشتراکِ گروهی با واحدها (Bulk)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">از منوی bulk actions روی چند گزارش، «اشتراک با واحدها» را بزنید، واحدهای مخاطب را انتخاب کنید و ذخیره کنید — دسترسیِ همهٔ گزارش‌های انتخاب‌شده در یک گام به‌روز می‌شود. خالی گذاشتن = عمومی.</p>
                </div>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                کاربری که گزارش را نمی‌بیند: در زبانهٔ «گزارشات» کاربر، کوئری فقط رکوردهای عمومی یا آن‌هایی که واحدِ کاربر در <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">departments</code> دارند می‌آورد (<code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">orWhereJsonContains</code>)؛ گزارش‌های محدودِ واحدِ دیگر هرگز برایش قابل‌مشاهده یا دانلود نیست (دانلود هم با ۴۰۳ مسدود است).
            </p>
        </div>
    </div>
</div>