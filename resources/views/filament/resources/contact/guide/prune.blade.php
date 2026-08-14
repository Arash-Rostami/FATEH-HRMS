@php
    $stages = [
        ['icon' => 'delete', 'color' => 'primary', 'label' => 'حذف نرم', 'text' => 'دکمهٔ «حذف» <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">deleted_at</code> را پر می‌کند. پیام از دید کاربر می‌رود ولی رکورد در پایگاه داده باقی می‌ماند و در زبانهٔ «حذف‌شده» قابل مشاهده و بازیابی است.'],
        ['icon' => 'autorenew', 'color' => 'tertiary', 'label' => 'بازیابی', 'text' => 'دکمهٔ «بازیابی» فقط روی پیام‌های حذف‌شده ظاهر می‌شود و <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">deleted_at</code> را پاک می‌کند. پیام به فهرست عادی برمی‌گردد و زمان شمارش ۳۰ روزه دوباره از صفر آغاز می‌شود.'],
        ['icon' => 'history', 'color' => 'secondary', 'label' => 'هشدار (۲۰ تا ۳۰ روز)', 'text' => 'وقتی پیام حذف‌شده بین ۲۰ تا ۳۰ روز بگذرد، نشان وضعیت هرس به رنگ زرد (warning) و با متن «تا حذف خودکار: ...» نمایش داده می‌شود — یعنی هرس خودکار نزدیک است.'],
        ['icon' => 'warning', 'color' => 'error', 'label' => 'در آستانه حذف (۳۰ روز+)', 'text' => 'وقتی پیام حذف‌شده بیش از ۳۰ روز بگذرد، نشان به رنگ قرمز (danger) و با متن «در آستانه حذف» نمایش داده می‌شود. زبانهٔ «در آستانه حذف» و فیلترِ هم‌نام این ردیف‌ها را جدا می‌کنند.'],
        ['icon' => 'delete_forever', 'color' => 'error', 'label' => 'هرس خودکار (Force Delete)', 'text' => 'فرمان Prunable لاراول ردیف‌هایی که <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">deleted_at &lt;= now()-۳۰روز</code> باشند را برای همیشه حذف می‌کند (ForceDeleteMessageAction). در این لحظه، فایل‌های پیوستِ آن پیام هم از دیسک public پاک می‌شوند (هوک forceDeleted). دیگر قابل بازیابی نیستند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">delete_sweep</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">حذف نرم + هرس خودکارِ ۳۰روزه</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        پیام‌رسان یک چرخهٔ دو مرحله‌ای دارد: «حذف نرم» رکورد را نگه می‌دارد و فقط از دید کاربر می‌گیرد؛ «هرس خودکار» بعد از ۳۰ روز رکورد و فایل‌های پیوستش را برای همیشه پاک می‌کند. بازهٔ ۲۰ تا ۳۰ روز یک هشدار زرد می‌دهد و بعد از ۳۰ روز وضعیت قرمز می‌شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">timeline</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">چرخهٔ حیات یک پیام حذف‌شده</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($stages as $s)
                @php
                    $stageChip = match ($s['color']) {
                        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                    };
                @endphp
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $stageChip }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $s['text'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">visibility</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            تفاوت دید ادمین و کاربر: ادمین پیام‌های حذف‌شدهٔ نرم را در زبانهٔ «حذف‌شده» می‌بیند و می‌تواند بازیابی کند؛ کاربر در پنل خودش فقط پیام‌های <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-tertiary-container)]">withoutTrashed</code> را می‌بیند — یعنی به‌محض حذف، پیام از نگاه کاربر ناپدید می‌شود و او ابزار بازیابی ندارد.
        </p>
    </div>
</div>