@php
    $scopeChip = [
        'contacts' => 'bg-[var(--tool-sapphire-bg)] text-[var(--tool-sapphire-color)]',
        'channels' => 'bg-[var(--tool-sage-bg)] text-[var(--tool-sage-color)]',
        'shared'   => 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]',
    ];
    $scopeLabel = ['contacts' => 'پیام‌رسان', 'channels' => 'کانال', 'shared' => 'مشترک'];

    $tabs = [
        ['id' => 'edit',     'icon' => 'edit_note',  'label' => 'ویرایش و حذف'],
        ['id' => 'write',    'icon' => 'edit',       'label' => 'نوشتن و ضمیمه'],
        ['id' => 'search',   'icon' => 'search',     'label' => 'جستجو و فیلتر'],
        ['id' => 'notify',   'icon' => 'volume_up',  'label' => 'صدا و اعلان'],
        ['id' => 'roles',    'icon' => 'shield_person', 'label' => 'نقش‌های کانال'],
    ];

    $sections = [
        'edit' => [
            ['icon' => 'edit', 'tone' => 'tertiary', 'scope' => 'shared', 'label' => 'ویرایش محدود به ۱۰ دقیقه', 'text' => 'ویرایش فقط برای پیام‌های خودتان و تا ۱۰ دقیقه (۶۰۰ ثانیه) پس از ارسال مجاز است.'],
            ['icon' => 'delete', 'tone' => 'primary', 'scope' => 'shared', 'label' => 'حذف فقط آخرین پیام', 'text' => 'حذف مثل ویرایش فقط روی آخرین پیام خودتان و تا ۱۰ دقیقه (۶۰۰ ثانیه) پس از ارسال کار می‌کند؛ با کلیک روی سطل، پیام حذف و دکمهٔ «بازگشت» ۴ ثانیه‌ای ظاهر می‌شود.'],
            ['icon' => 'shield_person', 'tone' => 'secondary', 'scope' => 'channels', 'label' => 'مالک کانال دائمی', 'text' => 'مالک کانال نمی‌تواند از کانال خودش خارج شود؛ نقش مالک قابل‌لغو نیست.'],
        ],
        'write' => [
            ['icon' => 'keyboard', 'tone' => 'primary', 'scope' => 'shared', 'label' => 'ارسال با Enter', 'text' => 'Enter یا Ctrl+Enter ارسال می‌کند؛ Shift+Enter خط جدید می‌سازد.'],
            ['icon' => 'content_paste', 'tone' => 'tertiary', 'scope' => 'shared', 'label' => 'جای‌گذاری مستقیم تصویر', 'text' => 'تصویر را مستقیم در باکس پیام paste کنید تا خودکار ضمیمه شود؛ نیازی به دکمهٔ گیره نیست.'],
            ['icon' => 'counter_1', 'tone' => 'secondary', 'scope' => 'shared', 'label' => 'شمارنده و سقف کاراکتر', 'text' => 'شمارنده از ۱۸۰۰ به بالا نمایش داده می‌شود؛ سقف پیام‌رسان ۲۰۰۰ و کانال ۴۰۰۰ کاراکتر است.'],
            ['icon' => 'attach_file', 'tone' => 'tertiary', 'scope' => 'shared', 'label' => 'حد فایل ضمیمه', 'text' => 'حداکثر ۵ فایل، هر کدام تا ۱۰ مگابایت (تصویر/PDF/Office/zip).'],
            ['icon' => 'format_quote', 'tone' => 'primary', 'scope' => 'shared', 'label' => 'پاسخ با نقل‌قول', 'text' => 'با انتخاب متن هر پیام، دکمهٔ «پاسخ با نقل‌قول» کنار آن ظاهر می‌شود.'],
        ],
        'search' => [
            ['icon' => 'filter_alt', 'tone' => 'primary', 'scope' => 'contacts', 'label' => 'فیلتر پیام‌رسان', 'text' => 'فیلتر همه/خوانده‌نشده/آنلاین + جستجوی نام در نوار کناری.'],
            ['icon' => 'keyboard', 'tone' => 'tertiary', 'scope' => 'contacts', 'label' => 'جستجوی داخل گفتگو', 'text' => 'کلید / (اسلش) جستجوی داخل گفتگو را باز می‌کند.'],
            ['icon' => 'search', 'tone' => 'secondary', 'scope' => 'channels', 'label' => 'جستجوی کانال', 'text' => 'جستجوی نام کانال در نوار کناری + جستجوی پیام داخل کانال.'],
            ['icon' => 'my_location', 'tone' => 'primary', 'scope' => 'shared', 'label' => 'پرش به نتیجه', 'text' => 'کلیک روی نتیجهٔ جستجو شما را به همان پیام می‌برد و آن را هایلایت می‌کند.'],
        ],
        'notify' => [
            ['icon' => 'volume_up', 'tone' => 'tertiary', 'scope' => 'shared', 'label' => 'صدای ارسال و میوت', 'text' => 'صدای ارسال هنگام فرستادن پیام پخش می‌شود؛ با «میوت هر گفتگو/کانال»، «میوت‌همه» یا تنظیمات پوش قطع می‌شود.'],
            ['icon' => 'fullscreen', 'tone' => 'primary', 'scope' => 'shared', 'label' => 'نمایش تمام‌صفحه', 'text' => 'دکمهٔ تمام‌صفحه پنل را جدا می‌کند؛ Esc آن را برمی‌گرداند.'],
            ['icon' => 'vertical_align_bottom', 'tone' => 'secondary', 'scope' => 'shared', 'label' => 'اسکرول روان به انتها', 'text' => 'هنگام بازکردن یک گفتگو/کانال، نمایش به‌صورت روان به آخرین پیام اسکرول می‌شود.'],
            ['icon' => 'notifications_active', 'tone' => 'tertiary', 'scope' => 'shared', 'label' => 'پوش مرورگر', 'text' => 'وقتی پیام جدید می‌رسد و تب باز نیست، پوش مرورگر می‌آید (اگر پوش روشن باشد).'],
        ],
    ];
@endphp

<div x-data="{ tab: 'edit' }">
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

    @foreach($sections as $tabId => $rows)
        <div x-show="tab === '{{ $tabId }}'" x-cloak class="space-y-2">
            @foreach($rows as $row)
                @php
                    $chipClasses = match ($row['tone']) {
                        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                    };
                @endphp
                <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
                        <span class="material-symbols-rounded text-[16px]">{{ $row['icon'] }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-0.5">
                            <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $row['label'] }}</p>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $scopeChip[$row['scope']] }}">{{ $scopeLabel[$row['scope']] }}</span>
                        </div>
                        <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

    <div x-show="tab === 'roles'" x-cloak>
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-3">نقش‌ها و سطح دسترسی در کانال‌ها.</p>
        @include('livewire.dashboard.channel.legend')
    </div>
</div>