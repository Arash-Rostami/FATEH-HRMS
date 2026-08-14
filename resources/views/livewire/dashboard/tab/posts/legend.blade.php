@php
    $tabs = [
        ['id' => 'pins', 'icon' => 'keep', 'label' => 'سنجاق و فهرست'],
        ['id' => 'read', 'icon' => 'new_releases', 'label' => 'برچسب و خوانده‌شدن'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $pinRows = [
        ['icon' => 'keep', 'color' => 'tertiary', 'label' => 'نوار سنجاق‌شده', 'text' => 'اعلانات مهم در نوار ویژه‌ای بالای فهرست نمایش داده می‌شوند — با نوار رنگی بالای کارت و برچسب «مهم». این نوار فقط یک اعلان (جدیدترینِ سنجاق‌شده) را نشان می‌دهد؛ بقیهٔ سنجاق‌ها در فهرست عادی می‌مانند. سنجاق را فقط ادمین روشن می‌کند.'],
        ['icon' => 'feed', 'color' => 'primary', 'label' => 'فهرست تازه‌ترین‌ها', 'text' => 'اعلانات غیرسنجاق از جدیدترین به قدیمی‌ترین در یک شبکهٔ کارتی می‌آیند — هر کارت تصویر، عنوان، خلاصه و تاریخ نسبی دارد. دکمهٔ «نمایش بیشتر» هر بار ' . convertToPersian('3') . ' کارت دیگر بارگذاری می‌کند.'],
        ['icon' => 'open_in_full', 'color' => 'secondary', 'label' => 'صفحهٔ جزئیات', 'text' => 'با کلیک روی کارت یا «ادامه مطلب»، یک پنل از کنار باز می‌شود: تصویر بزرگ، عنوان، تاریخ، نویسنده و متن کامل. دکمهٔ بزرگ‌نمایی، تصویر را تمام‌صفحه نشان می‌دهد و با ESC یا کلیک بیرون بسته می‌شود.'],
        ['icon' => 'share', 'color' => 'primary', 'label' => 'اشتراک‌گذاری', 'text' => 'در پنل جزئیات، دکمهٔ «اشتراک‌گذاری» دو گزینه دارد: کپی متن اعلان در کلیپ‌بورد، یا ارسال با ایمیل.'],
    ];

    $readRows = [
        ['icon' => 'new_releases', 'color' => 'primary', 'label' => 'برچسب «جدید»', 'text' => 'اعلانی که کمتر از ' . convertToPersian('30') . ' روز از انتشارش می‌گذرد، برچسب «جدید» می‌گیرد. این برچسب فقط روی کارت‌های تازه است؛ اعلان قدیمی‌تر از ' . convertToPersian('30') . ' روز هیچ برچسبی نمی‌گیرد.'],
        ['icon' => 'check_circle', 'color' => 'tertiary', 'label' => 'برچسب «دیده شد»', 'text' => 'به‌محض باز کردن صفحهٔ جزئیات یک اعلان، برچسب آن از «جدید» به «دیده شد» تغییر می‌کند. این یعنی اعلان خوانده‌شده ثبت شده و از شمارش خوانده‌نشده خارج می‌شود.'],
        ['icon' => 'mark_chat_unread', 'color' => 'secondary', 'label' => 'نشان منو و زنگوله', 'text' => 'اعلان تازه دو سیگنال می‌دهد: یک نقطه روی آیتم «اعلانات» در منو (تا وقتی خوانده‌نشده هست می‌ماند) و یک ردیف در زنگوله. رد کردن زنگوله، نقطهٔ منو را خاموش نمی‌کند — فقط باز کردن خود اعلان این کار را می‌کند.'],
    ];

    $notes = [
        'نوار سنجاق فقط جدیدترینِ سنجاق‌شده را نشان می‌دهد؛ اگر ادمین چند اعلان را سنجاق کند، بقیه در فهرست عادی ظاهر می‌شوند.',
        'برچسب «جدید» پس از ' . convertToPersian('30') . ' روز به‌طور خودکار خاموش می‌شود، ولی خود اعلان در فهرست می‌ماند.',
        'خوانده‌شدن فقط با باز کردن صفحهٔ جزئیات ثبت می‌شود؛ کلیک روی زنگوله یا رد کردن آن، اعلان را «دیده شد» نمی‌کند.',
        'نقطهٔ منوی «اعلانات» تا وقتی حداقل یک اعلان خوانده‌نشدهٔ تازه هست روشن می‌ماند؛ خاموش شدن آن یعنی همهٔ اعلان‌های تازه را باز کرده‌اید.',
    ];
@endphp

<div x-data="{ tab: 'pins' }">
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($tabs as $tab)
            <button
                type="button"
                @click="tab = '{{ $tab['id'] }}'"
                :class="tab === '{{ $tab['id'] }}'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                class="flex-1 flex flex-col items-center justify-center gap-0.5 px-1.5 py-2 rounded-xl text-[11px] font-bold transition-all duration-200"
            >
                <span class="material-symbols-rounded text-[18px]">{{ $tab['icon'] }}</span>
                <span class="leading-tight text-center">{{ $tab['label'] }}</span>
            </button>
        @endforeach
    </div>

    <div x-show="tab === 'pins'" x-cloak class="space-y-3">
        @foreach($pinRows as $s)
            @php
                $chipClasses = match ($s['color']) {
                    'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                    'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                    'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                    'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                };
            @endphp
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $s['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'read'" x-cloak class="space-y-3">
        @foreach($readRows as $s)
            @php
                $chipClasses = match ($s['color']) {
                    'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                    'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                    'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                    'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                };
            @endphp
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $s['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @foreach($notes as $note)
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
            </div>
        @endforeach
    </div>
</div>