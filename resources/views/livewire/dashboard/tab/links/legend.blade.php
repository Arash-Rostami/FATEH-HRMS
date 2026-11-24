@php
    $tabs = [
        ['id' => 'routing', 'icon' => 'alt_route', 'label' => 'باز کردن پیوندها'],
        ['id' => 'recent', 'icon' => 'history', 'label' => 'اخیراً باز شده'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $routingRows = [
        ['icon' => 'alt_route', 'color' => 'primary', 'label' => 'انتخاب خودکار نشانی', 'text' => 'پیوندهای داخلی متناسب با محل اتصال شما باز می‌شوند: در شبکهٔ سازمان، نشانی درون‌سازمانی و خارج از آن، نشانی اینترنتی استفاده می‌شود. لازم نیست پیوند جداگانه‌ای انتخاب کنید.'],
        ['icon' => 'apartment', 'color' => 'tertiary', 'label' => 'همان زبانه یا زبانهٔ جدید', 'text' => 'وقتی لینک به آدرس داخلی برود، در همین زبانه باز می‌شود؛ وقتی به آدرس خارجی برود، در زبانهٔ جدید باز می‌شود. لینک‌های بخش «خارجی» همیشه زبانهٔ جدید هستند.'],
        ['icon' => 'public_off', 'color' => 'secondary', 'label' => 'بخش‌های خالی', 'text' => 'پیام «تعریف نشده» یعنی هنوز پیوندی برای آن بخش ثبت نشده است.'],
        ['icon' => 'search', 'color' => 'primary', 'label' => 'جستجو و فیلتر نوع', 'text' => 'دکمهٔ فیلتر پایین صفحه جستجو در عنوان و توضیح لینک را باز می‌کند؛ سه دکمهٔ «همه/داخلی/خارجی» فقط یک بخش را نمایش می‌دهند. اگر جستجو یا فیلتر نتیجه‌ای نداشته باشد، پیام «نتیجه‌ای یافت نشد» به‌جای «تعریف نشده» می‌نشیند.'],
        ['icon' => 'subject', 'color' => 'tertiary', 'label' => 'توضیح روی نگه‌داشتن اشاره‌گر', 'text' => 'اگر مدیر برای لینک توضیحی ثبت کرده باشد، با نگه‌داشتن نشانگر روی کارت (یا لمس طولانی در موبایل) همان توضیح ظاهر می‌شود.'],
        ['icon' => 'content_copy', 'color' => 'secondary', 'label' => 'کپی سریع آدرس', 'text' => 'روی هر کارت، با نگه‌داشتن نشانگر یک دکمهٔ کپی در گوشهٔ راست بالا ظاهر می‌شود که آدرس همان لینک را در کلیپ‌بورد کپی می‌کند — بدون باز شدن لینک.'],
        ['icon' => 'apps', 'color' => 'tertiary', 'label' => 'نمای شبکه‌ای', 'text' => '«نمای شبکه‌ای» پیوندهای داخلی و خارجی را در دو بخش نشان می‌دهد. کنار ۹ پیوند اول عدد می‌بینید. برای باز کردن هرکدام، Alt و عدد همان پیوند را بزنید.'],
        ['icon' => 'swap_horiz', 'color' => 'secondary', 'label' => 'در موبایل: تب داخلی/خارجی', 'text' => 'در نمای ریلی روی موبایل، یک دکمهٔ دوتایی «داخلی/خارجی» بالای فهرست می‌آید و هر بار فقط کارت‌های همان بخش (به‌صورت شبکهٔ فشرده، نه اسکرول افقی) نمایش داده می‌شود؛ در دسکتاپ هر دو بخش مثل قبل زیر هم و به‌صورت ریلِ افقی می‌آیند.'],
    ];

    $recentRows = [
        ['icon' => 'history', 'color' => 'tertiary', 'label' => 'فقط روی همین مرورگر', 'text' => 'فهرست «اخیراً باز شده» در همان مرورگر و روی همان دستگاه ذخیره می‌شود — با حساب کاربری یا دستگاه‌های دیگر هماهنگ نمی‌شود. روی مرورگر دیگر، فهرست دیگری دارید.'],
        ['icon' => 'delete_sweep', 'color' => 'primary', 'label' => 'پاک کردن', 'text' => '«پاک کردن» فهرست پیوندهای اخیر را خالی می‌کند. وقتی دوباره پیوندی را باز کنید، در این فهرست ظاهر می‌شود.'],
        ['icon' => 'star', 'color' => 'secondary', 'label' => 'حداکثر ' . convertToPersian('6') . ' مورد', 'text' => 'این فهرست نهایتاً ' . convertToPersian('6') . ' لینکِ اخیر را نگه می‌دارد؛ با کلیک روی لینکِ جدید، قدیمی‌ترین از فهرست خارج می‌شود.'],
    ];

    $notes = [
        'لینکی که برای همکارِ داخل شبکه به آدرس داخلی می‌رود، ممکن است برای شما (بیرون از شبکه) به آدرس خارجی برود — این رفتار عمدی است، نه خطا.',
        'فهرست «اخیراً باز شده» روی هر دستگاه جدا است؛ پاک کردن آن روی یک دستگاه فهرستِ دستگاه دیگر را پاک نمی‌کند.',
        'ترتیب لینک‌ها در هر بخش توسط مدیر تعیین می‌شود؛ شما نمی‌توانید این ترتیب را تغییر دهید.',
    ];
@endphp

<div x-data="{ tab: 'routing' }">
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

    <div x-show="tab === 'routing'" x-cloak class="space-y-3">
        @foreach($routingRows as $s)
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

    <div x-show="tab === 'recent'" x-cloak class="space-y-3">
        @foreach($recentRows as $s)
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
