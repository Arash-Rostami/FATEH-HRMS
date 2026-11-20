@php
    $pills = [
        ['id' => 'stages', 'icon' => 'route', 'label' => 'مراحل'],
        ['id' => 'auto', 'icon' => 'auto_mode', 'label' => 'مهلت و نتیجه'],
        ['id' => 'roles', 'icon' => 'group', 'label' => 'نقش‌ها'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $flowStages = [
        ['icon' => 'hourglass_empty', 'label' => 'ثبت‌شده', 'code' => 'pending', 'hint' => 'پیشنهاد ثبت شده و هنوز نظر واحدها کامل نیست.'],
        ['icon' => 'group', 'label' => 'منتظر نظر واحد پیشنهاددهنده', 'code' => 'team_remarks', 'hint' => 'مدیر واحد پیشنهاددهنده باید نظر واحد را ثبت کند؛ ثبت‌کنندهٔ عادی لازم نیست از طرف مدیر پاسخ بدهد.'],
        ['icon' => 'corporate_fare', 'label' => 'منتظر نظر واحدهای مرتبط', 'code' => 'dept_remarks', 'hint' => 'نظر واحد پیشنهاددهنده ثبت شده است. مدیران واحدهای دیگر باید نظر واحد خود را بنویسند.'],
        ['icon' => 'gavel', 'label' => 'منتظر تصمیم نهایی', 'code' => 'awaiting_decision', 'hint' => 'نظر واحدها کامل شده است. تصمیم‌گیرندهٔ ارشد باید پذیرش، رد یا نیاز به تکمیل را مشخص کند.'],
    ];

    $outcomeStages = [
        ['icon' => 'check_circle', 'label' => 'پذیرفته‌شده', 'code' => 'accepted', 'hint' => 'پیشنهاد پذیرفته شده است. اگر برای اجرا به واحدها سپرده شده، هنوز باید انجام کارشان را ثبت کنند. پذیرش بدون ارجاع، در همین مرحله می‌ماند.'],
        ['icon' => 'cancel', 'label' => 'ردشده', 'code' => 'rejected', 'hint' => 'پیشنهاد پذیرفته نشده و بررسی آن پایان یافته است.'],
        ['icon' => 'find_in_page', 'label' => 'نیازمند تکمیل', 'code' => 'under_review', 'hint' => 'مدیر واحد پیشنهاددهنده در همین پیشنهاد، نظر و توضیح تازه ثبت می‌کند؛ سپس مدیریت ارشد دوباره تصمیم می‌گیرد. لازم نیست پیشنهاد تازه‌ای بسازید.'],
        ['icon' => 'lock', 'label' => 'پایان‌یافته', 'code' => 'closed', 'hint' => 'همهٔ واحدهایی که مسئول اجرا بوده‌اند، انجام کار خود را ثبت کرده‌اند.'],
    ];

    $auto = [
        ['icon' => 'hourglass_empty', 'label' => 'ثبت نظر خودکار پس از ۴۸ ساعت', 'hint' => 'فقط در دو مرحلهٔ نظر واحدها، اگر پاسخ در مهلت ۴۸ساعته نرسد، سامانه در بررسی ساعتی «بدون نظر قطعی» ثبت می‌کند. این نظر موافقت نیست. نظرهای ثبت‌شده جایگزین نمی‌شوند و مرحلهٔ «نیازمند تکمیل» شامل این کار خودکار نیست.'],
        ['icon' => 'inventory_2', 'label' => 'پایان پیشنهاد پس از تکمیل اقدامات', 'hint' => 'برای پیشنهاد دارای ارجاع، مدیر هر واحد پس از انجام واقعی کار، «ثبت تکمیل اقدام» را می‌زند. با ثبت همهٔ واحدهای مسئول، مرحله خودکار «پایان‌یافته» می‌شود.'],
        ['icon' => 'bolt', 'label' => 'تفاوت رد و درخواست تکمیل', 'hint' => '«ردشده» یعنی پیشنهاد پذیرفته نشده است. «نیازمند تکمیل» یعنی توضیح بیشتری لازم است و بعد دوباره تصمیم‌گیری می‌شود.'],
    ];

    $roles = [
        'submitter' => [
            'note' => 'شما پیشنهاددهنده هستید.',
            ['icon' => 'edit_note', 'label' => 'ثبت نظر واحدها توسط شما', 'text' => 'واحد خودتان خودکار اضافه می‌شود. اگر نظر واحدها را از قبل گرفته‌اید، «ثبت نظر واحدها توسط من» را روشن کنید و همان نظرها را بنویسید. این نظرها به نام شما ثبت می‌شوند و در روند بررسی، پاسخ همان واحدها حساب می‌شوند. ممکن است پیشنهاد مستقیم به تصمیم نهایی برسد.'],
            ['icon' => 'visibility', 'label' => 'پیگیری پیشنهاد', 'text' => 'پیشنهاد را از فهرست باز کنید تا مرحله، نظر واحدها و نتیجه را ببینید. معمولاً پیگیری بعدی با مدیر واحد یا تصمیم‌گیرنده است.'],
        ],
        'head' => [
            'note' => 'شما سرپرست واحد مرتبط هستید (نه مدیریت ارشد).',
            ['icon' => 'rate_review', 'label' => 'ثبت نظر', 'text' => 'وقتی نوبت واحد شما باشد، بخش «ثبت نظر» ظاهر می‌شود. موافق، مخالف یا بدون نظر قطعی را انتخاب کنید و دلیل را بنویسید. این کار، پذیرش نهایی پیشنهاد نیست.'],
            ['icon' => 'task_alt', 'label' => 'تکمیل اقدام ارجاع‌شده', 'text' => 'اگر اجرای کار به واحد شما سپرده شده، پس از انجام آن «ثبت تکمیل اقدام» را بزنید. این دکمه فقط انجام کار واحد شما را ثبت می‌کند.'],
        ],
        'ceo' => [
            'note' => 'سامانه به شما اختیار تصمیم نهایی داده است.',
            ['icon' => 'gavel', 'label' => 'تصمیم نهایی', 'text' => 'در مرحلهٔ «منتظر تصمیم نهایی»، تصمیم را ثبت کنید. با پذیرش می‌توانید هم‌زمان واحدهای مسئول اجرا و کار هرکدام را مشخص کنید. با نیاز به تکمیل، مدیر واحد پیشنهاددهنده دوباره پاسخ می‌دهد.'],
            ['icon' => 'block', 'label' => 'ثبت پیشنهاد ممکن نیست', 'text' => 'کاربران واحدهای دارای کد MA و MG نمی‌توانند پیشنهاد ثبت کنند. اختیار تصمیم نهایی بر اساس سمت و ساختار سازمان تعیین می‌شود؛ فقط نام واحد ملاک نیست.'],
        ],
    ];

    $noteGroups = [
        'list' => [
            'label' => 'شناسه و فهرست',
            'icon' => 'tag',
            'notes' => [
                'شناسه با SN شروع می‌شود و تاریخ میلادی ثبت و شمارهٔ پیشنهاد را دارد. نام یا شناسه را در جستجو وارد کنید.',
                'فهرست اول پیشنهادهای با اولویت زیاد را نشان می‌دهد؛ در هر اولویت، تازه‌ترین مورد بالاتر است. اولویت، جای پیشنهاد در فهرست را عوض می‌کند و وعدهٔ زمان رسیدگی نیست.',
                'جدول برترین‌ها بالای صفحه، سه کاربر برتر را بر اساس تعداد پیشنهادهای پذیرفته‌شده نشان می‌دهد.',
                'از دکمهٔ زنگ روی کارت برای خودتان یادآور بسازید. اگر یادآور داشته باشید، همان دکمه آن را برای ویرایش باز می‌کند.',
            ],
        ],
        'action' => [
            'label' => 'اقدام و نقش‌ها',
            'icon' => 'pending_actions',
            'notes' => [
                'فقط واحدهایی را انتخاب کنید که پیشنهاد به کارشان مربوط است. واحد خودتان خودکار در فهرست قرار می‌گیرد.',
                'نشان اعلان روی کارت یعنی پیشنهادی در انتظار اقدام شماست؛ بسته به نقش شما، ثبت نظر، تکمیل اقدام یا تصمیم نهایی لازم است.',
                'با انتخاب «فقط نیازمند اقدام من»، فقط پیشنهادهای منتظر اقدام شما نمایش داده می‌شوند. اولویت هر پیشنهاد (کم، متوسط یا زیاد) نیز با رنگ مشخص است.',
            ],
        ],
        'flow' => [
            'label' => 'جریان و نمایش',
            'icon' => 'visibility',
            'notes' => [
                'با انتخاب هر پیشنهاد، جزئیات آن در کنار فهرست باز می‌شود. تا آماده‌شدن اطلاعات، نشان بارگذاری در همان بخش دیده می‌شود.',
                'برای تغییر مرحله، کار لازم را انجام دهید: ثبت نظر، تصمیم یا تکمیل اجرا. مرحله با همین کارها خودکار تغییر می‌کند.',
            ],
        ],
    ];
@endphp

<div x-data="{ tab: 'stages', stageSub: 'flow', role: 'submitter', noteSub: 'list' }">
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($pills as $pill)
            <button
                type="button"
                @click="tab = '{{ $pill['id'] }}'"
                :class="tab === '{{ $pill['id'] }}'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                class="flex-1 flex flex-col items-center justify-center gap-0.5 px-1.5 py-2 rounded-xl text-[11px] font-bold transition-all duration-200"
            >
                <span class="material-symbols-rounded text-[18px]">{{ $pill['icon'] }}</span>
                <span class="leading-tight text-center">{{ $pill['label'] }}</span>
            </button>
        @endforeach
    </div>

    <div x-show="tab === 'stages'" x-cloak>
        <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
            @foreach([
                ['id' => 'flow', 'icon' => 'route', 'label' => 'در جریان'],
                ['id' => 'outcome', 'icon' => 'flag', 'label' => 'نتیجه'],
            ] as $sub)
                <button
                    type="button"
                    @click="stageSub = '{{ $sub['id'] }}'"
                    :class="stageSub === '{{ $sub['id'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex items-center justify-center gap-1.5 px-2 py-2 rounded-xl text-[12px] font-bold transition-all duration-200"
                >
                    <span class="material-symbols-rounded text-[17px]">{{ $sub['icon'] }}</span>
                    {{ $sub['label'] }}
                </button>
            @endforeach
        </div>

        <div x-show="stageSub === 'flow'" x-cloak class="space-y-3">
            @foreach($flowStages as $s)
                <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                        <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-0.5">
                            <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                        </div>
                        <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div x-show="stageSub === 'outcome'" x-cloak class="space-y-3">
            @foreach($outcomeStages as $s)
                <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                        <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-0.5">
                            <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                        </div>
                        <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div x-show="tab === 'auto'" x-cloak class="space-y-3">
        @foreach($auto as $a)
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-tertiary-container)]/30 px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                    <span class="material-symbols-rounded text-[16px]">{{ $a['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $a['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $a['hint'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'roles'" x-cloak>
        <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
            @foreach([
                ['id' => 'submitter', 'icon' => 'person', 'label' => 'ثبت‌کننده'],
                ['id' => 'head', 'icon' => 'supervisor_account', 'label' => 'سرپرست واحد'],
                ['id' => 'ceo', 'icon' => 'shield_person', 'label' => 'مدیریت ارشد'],
            ] as $role)
                <button
                    type="button"
                    @click="role = '{{ $role['id'] }}'"
                    :class="role === '{{ $role['id'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex items-center justify-center gap-1.5 px-2 py-2 rounded-xl text-[12px] font-bold transition-all duration-200"
                >
                    <span class="material-symbols-rounded text-[17px]">{{ $role['icon'] }}</span>
                    {{ $role['label'] }}
                </button>
            @endforeach
        </div>

        @foreach($roles as $roleId => $sections)
            <div x-show="role === '{{ $roleId }}'" x-cloak class="space-y-3">
                <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">{{ $sections['note'] }}</p>
                @foreach(array_slice($sections, 1) as $s)
                    <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
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

    <div x-show="tab === 'notes'" x-cloak>
        <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
            @foreach($noteGroups as $key => $group)
                <button
                    type="button"
                    @click="noteSub = '{{ $key }}'"
                    :class="noteSub === '{{ $key }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex items-center justify-center gap-1.5 px-2 py-2 rounded-xl text-[12px] font-bold transition-all duration-200"
                >
                    <span class="material-symbols-rounded text-[17px]">{{ $group['icon'] }}</span>
                    {{ $group['label'] }}
                </button>
            @endforeach
        </div>

        @foreach($noteGroups as $key => $group)
            <div x-show="noteSub === '{{ $key }}'" x-cloak class="space-y-2">
                @foreach($group['notes'] as $note)
                    <div class="flex items-start gap-2 px-1">
                        <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                        <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
