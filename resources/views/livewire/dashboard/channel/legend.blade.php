@php
    $pills = [
        ['id' => 'owner', 'icon' => 'shield_person', 'label' => 'مالک کانال'],
        ['id' => 'member', 'icon' => 'person', 'label' => 'عضو عادی'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $rows = [
        'owner' => [
            'note' => 'شما این کانال را ساخته‌اید یا مدیریت آن به شما محول شده است.',
            ['icon' => 'group', 'color' => 'primary', 'label' => 'مدیریت اعضا', 'text' => 'از «اطلاعات کانال» → «مدیریت اعضا» می‌توانید عضو اضافه یا حذف کنید. اعضای اضافه‌شده تا وقتی وارد کانال نشوند، یک نقطهٔ «جدید» در نوار کناری می‌گیرند. خودتان همیشه عضو هستید و نمی‌توانید خودتان را حذف کنید.'],
            ['icon' => 'logout', 'color' => 'error', 'label' => 'خروج ممکن نیست', 'text' => 'دکمهٔ «خروج از کانال» برای شما نمایش داده نمی‌شود؛ مالک نمی‌تواند از کانال خودش خارج شود. فقط ادمین می‌تواند با حذف کانال یا تغییر مالک، این وضعیت را برطرف کند.'],
        ],
        'member' => [
            'note' => 'شما عضو این کانال هستید ولی مالک آن نیستید.',
            ['icon' => 'logout', 'color' => 'tertiary', 'label' => 'خروج آزاد', 'text' => 'هر زمان بخواهید می‌توانید از دکمهٔ «خروج از کانال» استفاده کنید و از کانال خارج شوید. بعد از خروج، کانال از نوار کناری شما می‌رود و پیام‌های آن برایتان قابل‌مشاهده نیست.'],
            ['icon' => 'person_add', 'color' => 'secondary', 'label' => 'دعوت توسط مالک', 'text' => 'در کانال خصوصی فقط مالک می‌تواند عضو اضافه کند؛ شما نمی‌توانید کسی را دعوت کنید. در کانال عمومی، دیگران خودشان از «کاوش» عضو می‌شوند.'],
        ],
    ];

    $notes = [
        'کانال عمومی با آیکون بلندگو و کانال خصوصی با آیکون قفل نمایش داده می‌شود. فقط کانال‌های عمومی در «کاوش» برای پیوستن ظاهر می‌شوند؛ کانال خصوصی فقط با دعوتِ مالک قابل‌عضویت است.',
        'نشان خوانده‌نشدهٔ هر کانال بر اساس آخرین پیامی است که خوانده‌اید؛ با باز کردن کانال، همهٔ پیام‌ها تا آخرین مورد به‌صورت خوانده‌شده ثبت می‌شوند و نشان صفر می‌شود.',
        'وقتی در کانالی که باز نیست با @ به شما اشاره شود، اعلانی پایین صفحه ظاهر می‌شود؛ با «رفتن به پیام» به همان پیام می‌روید. باز کردن کانال، اعلان را خودکار پاک می‌کند.',
        'پیام‌های قدیمی‌تر در دسته‌های ۱۰تایی بارگذاری می‌شوند؛ با دکمهٔ «موارد قدیمی‌تر» بقیه را ببینید.',
        'ویرایش و حذف پیام فقط برای پیام‌های خودتان و تا ۱۰ دقیقه پس از ارسال ممکن است؛ بعد از آن فقط ادمین می‌تواند پیام را حذف کند.',
    ];
@endphp

<div x-data="{ tab: 'owner' }">
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($pills as $pill)
            <button
                type="button"
                @click="tab = '{{ $pill['id'] }}'"
                :class="tab === '{{ $pill['id'] }}'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200"
            >
                <span class="material-symbols-rounded text-[17px]">{{ $pill['icon'] }}</span>
                {{ $pill['label'] }}
            </button>
        @endforeach
    </div>

    @foreach($rows as $roleId => $sections)
        <div x-show="tab === '{{ $roleId }}'" x-cloak class="space-y-3">
            <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">{{ $sections['note'] }}</p>
            @foreach(array_slice($sections, 1) as $s)
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
    @endforeach

    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @foreach($notes as $note)
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
            </div>
        @endforeach
    </div>
</div>