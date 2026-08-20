@php
    $pills = [
        ['id' => 'roles', 'icon' => 'group', 'label' => 'نقش‌ها'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $rows = [
        'requester' => [
            'note' => 'شما این تیکت را ثبت کرده‌اید و پیگیر آن هستید.',
            ['icon' => 'visibility', 'color' => 'primary', 'label' => 'می‌بینید', 'text' => 'در تب «تاریخچه»، حالت پیش‌فرض («تیکت‌های من») فقط تیکت‌های خودتان را نشان می‌دهد.'],
            ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'اقدام شما', 'text' => 'تا زمانی که تیکت باز است می‌توانید در «پاسخ و پیگیری» پیام بدهید؛ پس از بسته‌شدن، در تب «ارزیابی» به آن امتیاز رضایت بدهید.'],
            ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان', 'text' => 'زنگوله با هر پاسخ تازه یا بسته‌شدن تیکت روشن می‌شود؛ نشان کارتابل برای شما فعال نمی‌شود چون اقدامی از سمت شما لازم نیست.'],
        ],
        'head' => [
            'note' => 'تیکت به واحد شما ارجاع شده یا در حال بررسیِ زیرمجموعه شماست.',
            ['icon' => 'visibility', 'color' => 'primary', 'label' => 'می‌بینید', 'text' => 'در تب «تاریخچه»، دکمهٔ «نیاز به اقدام من» را روشن کنید تا فهرست به تیکت‌های بازِ بدون‌مسئولِ واحدتان و تیکت‌های در حال بررسیِ محول‌شده به شما محدود شود.'],
            ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'اقدام شما', 'text' => 'تیکت را باز کنید و از «پاسخ و پیگیری» یک مسئول رسیدگی تعیین کنید یا خودتان پاسخ دهید؛ به‌عنوان مدیر واحد می‌توانید امتیاز «اثربخشی» را هم شما ثبت کنید (پیش‌نیاز بستن تیکت).'],
            ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان', 'text' => 'نشان کارتابل تا وقتی تیکتی از واحدتان بدون مسئول باز بماند یا خودتان مسئول رسیدگیِ تیکتی در حال بررسی باشید، روشن می‌ماند؛ زنگوله وقتی خودتان درخواست‌دهنده یا مسئول رسیدگی باشید با پاسخ‌های تازه فعال می‌شود.'],
        ],
        'assignee' => [
            'note' => 'تیکت به شما محول شده و در حال بررسی است.',
            ['icon' => 'visibility', 'color' => 'primary', 'label' => 'می‌بینید', 'text' => 'همان دکمهٔ «نیاز به اقدام من» در تب «تاریخچه»، تیکت‌های محول‌شده به شما را نشان می‌دهد.'],
            ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'اقدام شما', 'text' => 'در «پاسخ و پیگیری» به درخواست‌دهنده پاسخ دهید و امتیاز «اثربخشی» را ثبت کنید؛ بدون این امتیاز تیکت بسته نمی‌شود، و حتی پس از بسته‌شدن هم می‌توانید آن را اصلاح کنید.'],
            ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان', 'text' => 'نشان کارتابل تا وقتی تیکتِ محول‌شده به شما بسته نشده روشن می‌ماند؛ زنگوله با هر پاسخ تازهٔ درخواست‌دهنده.'],
        ],
    ];

    $notes = [
        'گفتگوی هر تیکت پس از بسته‌شدن فقط قابل‌مطالعه است؛ پیام تازه‌ای ثبت نمی‌شود.',
        'وظیفه‌های تب «وظیفه‌ها» هم همین گفتگوی دوطرفه را دارند: فقط ایجادکننده و مسئول وظیفه می‌توانند در آن پیام بدهند.',
        'به‌محض تعیین مسئول رسیدگی، یک وظیفهٔ پیگیری متناظر هم در «وظیفه‌ها» برای درخواست‌دهنده و مسئول رسیدگی ساخته می‌شود؛ گفتگو همچنان فقط از طریق همین تیکت انجام می‌شود.',
        'گزینهٔ «پشتیبانی عمومی» در فرم ثبت تیکت به‌محض این‌که حداقل یک واحد حوزه‌های درخواست اختصاصی خودش را تنظیم کند، خودکار حذف می‌شود.',
        'شناسهٔ هر تیکت به‌صورت «پیشوند-سال‌ماه-شماره» است؛ پیشوند برابر کدِ واحد هدف است (مثلاً TN-۲۶۰۸-۰۰۰۱) و با انتخاب «پشتیبانی عمومی» پیشوند T می‌شود.',
        'هنگام بسته‌شدن تیکت، فیلد «نتیجه اقدام» به‌صورت خودکار از روی گفتگو ساخته و فقط‌خواندنی می‌شود؛ در پنل کاربر این فیلد دیده نمی‌شود ولی در خروجی اکسلِ ادمین هست.',
    ];
@endphp

<div x-data="{ tab: 'roles', role: 'requester' }">
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

    <div x-show="tab === 'roles'" x-cloak>
        <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
            @foreach([
                ['id' => 'requester', 'icon' => 'person', 'label' => 'درخواست‌دهنده'],
                ['id' => 'head', 'icon' => 'shield_person', 'label' => 'مدیر واحد'],
                ['id' => 'assignee', 'icon' => 'engineering', 'label' => 'مسئول رسیدگی'],
            ] as $r)
                <button
                    type="button"
                    @click="role = '{{ $r['id'] }}'"
                    :class="role === '{{ $r['id'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex items-center justify-center gap-1.5 px-2 py-2 rounded-xl text-[12px] font-bold transition-all duration-200"
                >
                    <span class="material-symbols-rounded text-[17px]">{{ $r['icon'] }}</span>
                    {{ $r['label'] }}
                </button>
            @endforeach
        </div>

        @foreach($rows as $roleId => $sections)
            <div x-show="role === '{{ $roleId }}'" x-cloak class="space-y-3">
                <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">{{ $sections['note'] }}</p>
                @foreach(array_slice($sections, 1) as $s)
                    @php
                        $chipClasses = match ($s['color']) {
                            'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                            'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                            'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
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

    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @foreach($notes as $note)
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
            </div>
        @endforeach
    </div>
</div>
