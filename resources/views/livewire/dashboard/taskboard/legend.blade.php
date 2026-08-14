@php
    $tabs = [
        ['id' => 'roles', 'icon' => 'groups', 'label' => 'نقش‌ها'],
        ['id' => 'done', 'icon' => 'task_alt', 'label' => 'ستون انجام‌شده'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $roleRows = [
        'creator' => [
            'note' => 'شما این وظیفه را ساخته‌اید؛ به خودتان یا به فرد دیگری محول شده باشد.',
            ['icon' => 'visibility', 'color' => 'primary', 'label' => 'می‌بینید', 'text' => 'تا وقتی وظیفه محول نشده، در تب «وظایف من» می‌مانَد؛ به‌محض این‌که آن را به فرد دیگری محول کنید، برای پیگیری به تب «محول شده» منتقل می‌شود.'],
            ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'اقدام شما', 'text' => 'اگر وظیفه را به فرد دیگری محول کرده‌اید، تغییر وضعیت با اوست و شما فقط می‌توانید در گفتگوی وظیفه پیام بدهید؛ اگر هنوز محول نشده، خودتان هم می‌توانید وضعیت را تغییر دهید.'],
            ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان', 'text' => 'تا وقتی وظیفه محول نشده و در ستون «انجام نشده» است، نشان کارتابل شما روشن می‌ماند؛ پس از محول‌کردن، نشان و زنگولهٔ تغییرات به مسئول انجام منتقل می‌شود و برای شما فقط پاسخ‌های تازهٔ او زنگوله می‌زند.'],
        ],
        'assignee' => [
            'note' => 'وظیفه‌ای به شما محول شده است.',
            ['icon' => 'visibility', 'color' => 'primary', 'label' => 'می‌بینید', 'text' => 'وظیفه‌ای که به شما محول شده، در تب «وظایف من» شما دیده می‌شود؛ تب «محول شده» فقط برای کسی است که خودش وظیفه را به دیگری واگذار کرده.'],
            ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'اقدام شما', 'text' => 'وضعیت وظیفه (در حال انجام / انجام‌شده) را خودتان تغییر می‌دهید و می‌توانید در گفتگوی وظیفه با ایجادکننده پیام رد و بدل کنید.'],
            ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان', 'text' => 'نشان کارتابل تا وقتی وظیفهٔ محول‌شده به شما در ستون «انجام نشده» باشد روشن می‌ماند؛ زنگوله با هر پیام تازهٔ ایجادکننده.'],
        ],
    ];

    $doneRows = [
        ['icon' => 'history', 'color' => 'primary', 'label' => 'پنجرهٔ ۴۵ روزه', 'text' => 'وظایف انجام‌شده‌ای که بیش از ۴۵ روز از آخرین تغییرشان می‌گذرد، به‌طور پیش‌فرض از کارتابل پنهان می‌شوند تا ستون شلوغ نشود؛ با دکمهٔ «تاریخ» در سرستون، همهٔ موارد قدیمی‌تر را ببینید. این فقط یک فیلتر نمایش است و چیزی حذف نمی‌شود.'],
        ['icon' => 'archive', 'color' => 'tertiary', 'label' => 'آرشیو', 'text' => 'با دکمهٔ آرشیو روی هر کارت انجام‌شده، آن را به‌اختیار از فهرست فعال پنهان کنید؛ با دکمهٔ آرشیو در سرستون، فقط موارد آرشیو‌شده را ببینید و با «خروج از آرشیو» آن‌ها را برگردانید. باز کردن دوباره یا جابه‌جایی یک وظیفهٔ آرشیو‌شده، خودکار آن را از آرشیو خارج می‌کند. آرشیو چیزی را حذف نمی‌کند.'],
        ['icon' => 'delete', 'color' => 'error', 'label' => 'حذف و پاک‌سازی خودکار', 'text' => 'دکمهٔ سطل زباله (فقط برای ایجادکننده) یک حذف نرم است؛ ردیف ۳۰ روز در سطل زباله می‌ماند و سپس برای همیشه پاک می‌شود. هیچ وظیفهٔ انجام‌شده‌ای که خودتان حذف نکرده‌اید، به‌مرور زمان حذف نمی‌شود.'],
    ];

    $notes = [
        'گفتگوی هر وظیفه فقط بین ایجادکننده و مسئول انجام آن است؛ فرد دیگری در آن مشارکت نمی‌کند.',
        'همین قابلیت گفتگوی دوطرفه در تیکتینگ هم وجود دارد؛ برای جزئیات، راهنمای بخش تیکتینگ را ببینید.',
        'کارت‌هایی که برچسب «از تیکت» دارند، به‌صورت خودکار از یک تیکت ساخته شده‌اند و فقط برای پیگیری‌اند؛ ویرایش و گفتگو دربارهٔ آن‌ها فقط از طریق خودِ تیکت انجام می‌شود.',
        'وقتی وظیفه‌ای را به فرد دیگری محول می‌کنید، نمای شما خودکار به زبانهٔ «محول شده» می‌رود. واگردانیِ محول‌کردن فقط توسط ایجادکننده و فقط وقتی وظیفه انجام‌شده نیست و از تیکت نیست ممکن است.',
        'جستجو در عنوان و توضیحات، موقتاً هم پنجرهٔ ' . convertToPersian('45') . ' روزه و هم فیلتر آرشیو را غیرفعال می‌کند؛ بنابراین نتایج شامل وظایف آرشیو‌شده و انجام‌شده‌های قدیمی هم می‌شود.',
    ];
@endphp

<div x-data="{ tab: 'roles', role: 'creator' }">
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

    <div x-show="tab === 'roles'" x-cloak>
        <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
            @foreach([
                ['id' => 'creator', 'icon' => 'person', 'label' => 'ایجادکننده'],
                ['id' => 'assignee', 'icon' => 'assignment_ind', 'label' => 'مسئول انجام'],
            ] as $role)
                <button
                    type="button"
                    @click="role = '{{ $role['id'] }}'"
                    :class="role === '{{ $role['id'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200"
                >
                    <span class="material-symbols-rounded text-[17px]">{{ $role['icon'] }}</span>
                    {{ $role['label'] }}
                </button>
            @endforeach
        </div>

        @foreach($roleRows as $roleId => $sections)
            <div x-show="role === '{{ $roleId }}'" x-cloak class="space-y-3">
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
    </div>

    <div x-show="tab === 'done'" x-cloak class="space-y-3">
        @foreach($doneRows as $s)
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