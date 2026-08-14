@php
    $types = [
        [
            'icon' => 'campaign',
            'label' => 'عمومی',
            'code' => 'open',
            'chip' => 'bg-success-100 text-success-700',
            'hint' => 'هر کاربری می‌تواند از زبانهٔ «کاوش» در پنل کاربری آن را ببیند و با یک کلیک عضو شود. برای پیوستن به کانال عمومی نیازی به دعوت نیست — عضویت باز است.',
        ],
        [
            'icon' => 'lock',
            'label' => 'خصوصی',
            'code' => 'private',
            'chip' => 'bg-warning-100 text-warning-700',
            'hint' => 'در فهرست «کاوش» ظاهر نمی‌شود و کاربر نمی‌تواند خودش عضو شود. تنها راه عضویت، دعوت‌شدن توسط مالک کانال از «مدیریت اعضا» است. عضو دعوت‌شده تا وقتی وارد کانال نشود، یک نقطهٔ «جدید» در نوار کناری می‌گیرد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">lock</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">دو نوع کانال — عمومی و خصوصی — تفاوت در نحوهٔ عضویت</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        نوع کانال (<code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">type</code>) فقط نحوهٔ عضویت را تعیین می‌کند؛ روی محتوای پیام‌ها یا دسترسی اعضای فعلی اثری ندارد. رنگ و آیکون ستون «نوع» در جدول همین‌جا تعریف شده است. این فیلد در زمان ویرایش توسط ادمین قابل تغییر است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">category</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">دو نوع کانال</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($types as $t)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $t['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $t['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                            <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $t['code'] }}</code>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">group</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">مدل عضویت — دعوت‌شده در برابر واردشده</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">person_add</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">دعوت‌شده</p>
                        <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">entered_at = NULL</code>
                    </div>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">وقتی مالک از «مدیریت اعضا» کاربری را اضافه می‌کند، ردیف عضویت با <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">entered_at</code> خالی ثبت می‌شود. کاربر دعوت‌شده در نوار کناری یک نقطهٔ «جدید» می‌گیرد تا وارد کانال شود؛ تا آن زمان پیام‌های کانال برایش خوانده‌نشده می‌مانند.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">login</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">واردشده</p>
                        <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">entered_at ≠ NULL</code>
                    </div>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">با ورود به کانال، <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">entered_at</code> پر می‌شود و نقطهٔ «جدید» از بین می‌رود. از این پس پیام‌های جدید بر اساس <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">last_read_message_id</code> خوانده‌نشده محاسبه می‌شوند. در جدول اعضای کانال (روی صفحهٔ ویرایش) ستون «تاریخ ورود» این وضعیت را نشان می‌دهد.</p>
                </div>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                مالک کانال خودکار عضو می‌شود و <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">entered_at</code> او از همان لحظهٔ ساخت پر است — او هرگز «دعوت‌شده» نمی‌ماند.
            </p>
        </div>
    </div>
</div>