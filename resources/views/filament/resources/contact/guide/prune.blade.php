@php
    $stages = [
        ['icon' => 'delete', 'color' => 'primary', 'label' => 'حذف قابل بازیابی', 'text' => 'با حذف پیام، کاربر دیگر آن را نمی‌بیند. پیام در زبانهٔ «حذف‌شده» باقی می‌ماند و می‌توانید آن را بازیابی کنید.'],
        ['icon' => 'autorenew', 'color' => 'tertiary', 'label' => 'بازیابی', 'text' => 'با دکمهٔ «بازیابی»، پیام حذف‌شده به فهرست عادی برمی‌گردد. اگر دوباره حذف شود، مهلت ۳۰ روزه از نو شروع می‌شود.'],
        ['icon' => 'history', 'color' => 'secondary', 'label' => 'هشدار', 'text' => 'وقتی پیام حذف‌شده بین ۲۰ تا ۳۰ روز بگذرد، نشان وضعیت زرد می‌شود و عبارت «تا حذف خودکار: ...» دیده می‌شود؛ یعنی حذف خودکار نزدیک است.'],
        ['icon' => 'warning', 'color' => 'error', 'label' => 'در آستانهٔ حذف (بیش از ۳۰ روز)', 'text' => 'وقتی پیام حذف‌شده بیش از ۳۰ روز بگذرد، نشان قرمز می‌شود و عبارت «در آستانهٔ حذف» دیده می‌شود. زبانهٔ «در آستانهٔ حذف» و فیلترِ هم‌نام این ردیف‌ها را جدا می‌کنند.'],
        ['icon' => 'delete_forever', 'color' => 'error', 'label' => 'حذف خودکار (دائمی)', 'text' => 'پیام‌های حذف‌شده پس از ۳۰ روز برای همیشه پاک می‌شوند. فایل‌های پیوست آن‌ها هم پاک می‌شوند و دیگر قابل بازیابی نیستند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">delete_sweep</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">حذف قابل بازیابی و حذف خودکار پس از ۳۰ روز</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        حذف پیام دو مرحله دارد: ابتدا پیام فقط از دید کاربر پنهان می‌شود و نگه داشته می‌شود؛ بعد از ۳۰ روز، پیام و فایل‌های پیوستش برای همیشه پاک می‌شوند. بازهٔ ۲۰ تا ۳۰ روز هشدار زرد نشان می‌دهد و بعد از ۳۰ روز وضعیت قرمز می‌شود.
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
            مدیر می‌تواند پیام حذف‌شده را در زبانهٔ «حذف‌شده» ببیند و بازیابی کند. کاربر پیام را پس از حذف نمی‌بیند و نمی‌تواند آن را بازیابی کند.
        </p>
    </div>
</div>