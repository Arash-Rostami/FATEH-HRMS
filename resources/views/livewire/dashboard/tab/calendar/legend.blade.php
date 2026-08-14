@php
    $rows = [
        ['icon' => 'lock', 'color' => 'gold', 'label' => 'رویداد خصوصی', 'text' => 'فقط شما و کسانی که مستقیماً با آن‌ها به اشتراک گذاشته‌اید این رویداد را می‌بینند.'],
        ['icon' => 'public', 'color' => 'sapphire', 'label' => 'رویداد عمومی', 'text' => 'همهٔ کاربران این رویداد را در تقویم خود می‌بینند.'],
        ['icon' => 'edit', 'color' => 'amethyst', 'label' => 'مالکیت و ویرایش', 'text' => 'دکمه‌های «اشتراک‌گذاری»، «ویرایش» و «حذف» فقط برای سازندهٔ رویداد نمایش داده می‌شوند؛ سایر کاربران فقط می‌بینند و نمی‌توانند تغییری اعمال کنند.'],
        ['icon' => 'event_seat', 'color' => 'sage', 'label' => 'از طریق رزرو', 'text' => 'رزرو یک منبع جلسه به‌صورت خودکار این رویداد را برای رزروکننده و طرف مقابل ایجاد و به اشتراک می‌گذارد؛ برای تغییر یا لغو آن باید از تب «رزرو» اقدام کنید.'],
        ['icon' => 'alarm', 'color' => 'error', 'label' => 'یادآوری', 'text' => 'در صورت تنظیم یادآوری، از همان ساعت مشخص‌شده پیش از رویداد، شمارش معکوس در ابزار تایمر شما فعال می‌شود و در لحظهٔ شروع رویداد، زنگ هشدار به‌صدا درمی‌آید.'],
    ];
@endphp

<div class="space-y-2">
    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">راهنمای آیکون‌های روز (تعطیل/تولد/سالگرد/رویداد/مشترک) زیر همین شبکهٔ تقویم نمایش داده می‌شود؛ این بخش قوانین دیدن و ویرایش رویدادها را توضیح می‌دهد.</p>

    @foreach($rows as $row)
        @php
            $chipClasses = match ($row['color']) {
                'sapphire' => 'bg-[var(--tool-sapphire-bg)] text-[var(--tool-sapphire-color)]',
                'gold' => 'bg-[var(--tool-gold-bg)] text-[var(--tool-gold-color)]',
                'amethyst' => 'bg-[var(--tool-amethyst-bg)] text-[var(--tool-amethyst-color)]',
                'sage' => 'bg-[var(--tool-sage-bg)] text-[var(--tool-sage-color)]',
                'error' => 'bg-[var(--md-sys-color-error-container)]/50 text-[var(--md-sys-color-error)]',
            };
        @endphp
        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
                <span class="material-symbols-rounded text-[16px]">{{ $row['icon'] }}</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
            </div>
        </div>
    @endforeach

    <div class="mt-4 pt-3 border-t border-[var(--md-sys-color-outline-variant)]/40">
        <div class="flex items-start gap-2 px-1">
            <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">به‌اشتراک‌گذاری یک رویداد خصوصی آن را فقط برای گیرندگان انتخاب‌شده قابل مشاهده می‌کند؛ رویداد همچنان از دید بقیهٔ کاربران پنهان می‌ماند.</p>
        </div>
    </div>
</div>
