@php
    $statuses = [
        [
            'icon' => 'check_circle',
            'label' => 'فعال',
            'code' => 'active',
            'chip' => 'bg-success-100 text-success-700',
            'hint' => 'رزرو لغو یا آزاد نشده است. برای زمان استفاده، تاریخ و ساعت آن را ببینید. کاربر فقط رزرو فعالِ در مهلت لغو را می‌تواند لغو کند.',
        ],
        [
            'icon' => 'autorenew',
            'label' => 'آزادشده',
            'code' => 'released',
            'chip' => 'bg-info-100 text-info-700',
            'hint' => 'مدیریت رزرو را آزاد کرده است؛ سابقه و مصرف سهمیه باقی می‌مانند. رزرو پیوستهٔ شروع‌شده در زمان آزادسازی پایان می‌یابد. در سایر موارد، آزاد شدن زمان برای دیگران به تنظیمات نوع بستگی دارد.',
        ],
        [
            'icon' => 'cancel',
            'label' => 'لغو توسط کاربر',
            'code' => 'cancelled_user',
            'chip' => 'bg-warning-100 text-warning-700',
            'hint' => 'صاحب رزرو آن را لغو کرده است. رزرو از سهمیهٔ ماهانه کنار می‌رود و در تعداد لغوهای کاربر حساب می‌شود.',
        ],
        [
            'icon' => 'shield_person',
            'label' => 'لغو توسط مدیریت',
            'code' => 'cancelled_admin',
            'chip' => 'bg-danger-100 text-danger-700',
            'hint' => 'مدیریت رزرو را لغو کرده است. این مورد به تعداد لغوهای خود کاربر اضافه نمی‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">چهار وضعیت — رنگ و آیکون هرکدام در جدول همین است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        وضعیت نشان می‌دهد رزرو پابرجاست، آزاد شده یا لغو شده است. رزروهای لغوشدهٔ کاربر و مدیریت، هر دو در زبانهٔ «لغو شده» دیده می‌شوند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">flag</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">چهار وضعیت رزرو</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($statuses as $s)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $s['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                            <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $s['code'] }}</code>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $s['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">balance</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">قانون سقف ماهانه — کدام وضعیت‌ها می‌شمارند؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">add</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">فعال و آزادشده هر دو می‌شمارند</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">رزروهای فعال و آزادشدهٔ ماه شروع، هر دو سهمیه مصرف می‌کنند. آزادسازی سهمیه را برنمی‌گرداند. لغو می‌تواند سهمیه را باز کند، اما سقف لغو را هم جداگانه بررسی کنید.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">remove</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">لغوها می‌شمارند ولی در سقفِ دیگری</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">لغو، رزرو را از شمارش سهمیهٔ ماهانه خارج می‌کند. لغو خود کاربر در سقف جداگانهٔ لغو حساب می‌شود؛ لغو مدیریتی به آن اضافه نمی‌شود.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">lock</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">فقط «فعال» قابل‌لغو توسط کاربر است</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">لغو برای رزرو فعال است. کاربر باید هنوز در مهلت لغو باشد و از سقف لغو عبور نکرده باشد. رزرو آزادشده دیگر توسط کاربر قابل لغو نیست.</p>
                </div>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر سهمیه هنوز پر است، نوع رزرو، ماه شروع و رزروهای آزادشده را بررسی کنید. مانع رزرو تازه ممکن است سقف لغو باشد، نه سهمیهٔ ماهانه.
            </p>
        </div>
    </div>
</div>
