@php
    $tabs = [
        ['id' => 'routing', 'icon' => 'alt_route', 'label' => 'مسیریابی هوشمند'],
        ['id' => 'recent', 'icon' => 'history', 'label' => 'اخیراً باز شده'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $routingRows = [
        ['icon' => 'alt_route', 'color' => 'primary', 'label' => 'یک لینک، دو مقصد', 'text' => 'لینک‌های داخلی با آی‌پیِ شما مقایسه می‌شوند: اگر داخل شبکهٔ سازمان باشید، به آدرس درون‌سازمانی می‌روید؛ اگر بیرون باشید، به آدرس اینترنتی. همان لینک، دو مقصد مختلف.'],
        ['icon' => 'apartment', 'color' => 'tertiary', 'label' => 'همان زبانه یا زبانهٔ جدید', 'text' => 'وقتی لینک به آدرس داخلی برود، در همین زبانه باز می‌شود؛ وقتی به آدرس خارجی برود، در زبانهٔ جدید باز می‌شود. لینک‌های بخش «خارجی» همیشه زبانهٔ جدید هستند.'],
        ['icon' => 'public_off', 'color' => 'secondary', 'label' => 'بخش‌های خالی', 'text' => 'بخش «داخلی» یا «خارجی» فقط اگر مدیر حداقل یک لینک از آن نوع تعریف کرده باشد ظاهر می‌شود؛ وگرنه پیام «تعریف نشده» می‌نشیند. این یعنی لینکی حذف یا اضافه شده، نه اینکه صفحه خراب است.'],
    ];

    $recentRows = [
        ['icon' => 'history', 'color' => 'tertiary', 'label' => 'فقط روی همین مرورگر', 'text' => 'فهرست «اخیراً باز شده» در همان مرورگر و روی همان دستگاه ذخیره می‌شود — با حساب کاربری یا دستگاه‌های دیگر هماهنگ نمی‌شود. روی مرورگر دیگر، فهرست دیگری دارید.'],
        ['icon' => 'delete_sweep', 'color' => 'primary', 'label' => 'پاک کردن', 'text' => 'دکمهٔ «پاک کردن» فقط همین فهرستِ محلی را پاک می‌کند؛ لینک‌ها خودشان حذف نمی‌شوند و دفعهٔ بعد که روی آن‌ها کلیک کنید دوباره در فهرست می‌آیند.'],
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