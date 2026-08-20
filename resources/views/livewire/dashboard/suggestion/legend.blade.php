@php
    $pills = [
        ['id' => 'stages', 'icon' => 'route', 'label' => 'مراحل'],
        ['id' => 'auto', 'icon' => 'auto_mode', 'label' => 'خودکارسازی'],
        ['id' => 'roles', 'icon' => 'group', 'label' => 'نقش‌ها'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $flowStages = [
        ['icon' => 'hourglass_empty', 'label' => 'ارسال شده', 'code' => 'pending', 'hint' => 'ثبت اولیه؛ هنوز هیچ واحدی بازخورد نداده.'],
        ['icon' => 'group', 'label' => 'منتظر هم‌تیمی‌ها', 'code' => 'team_remarks', 'hint' => 'واحد ثبت‌کننده هنوز بازخورد نداده.'],
        ['icon' => 'corporate_fare', 'label' => 'منتظر ذینفعان', 'code' => 'dept_remarks', 'hint' => 'بازخورد ثبت‌کننده ثبت شده ولی یکی از واحدهای ذی‌نفع هنوز پاسخ نداده.'],
        ['icon' => 'gavel', 'label' => 'منتظر تصمیم', 'code' => 'awaiting_decision', 'hint' => 'همه پاسخ داده‌اند؛ منتظر تصمیم مدیریت ارشد.'],
    ];

    $outcomeStages = [
        ['icon' => 'check_circle', 'label' => 'پذیرفته شده', 'code' => 'accepted', 'hint' => 'مدیریت ارشد پذیرفت. اگر واحدی ارجاع شده باشد، تا تکمیل همه در این مرحله می‌ماند.'],
        ['icon' => 'cancel', 'label' => 'پذیرفته نشده', 'code' => 'rejected', 'hint' => 'مدیریت ارشد رد کرد. پایان مسیر.'],
        ['icon' => 'find_in_page', 'label' => 'نیازمند تکمیل', 'code' => 'under_review', 'hint' => 'درخواست تکمیل مجدد — نه رد شدن. با ثبت بازخورد جدید، مرحله دوباره محاسبه می‌شود.'],
        ['icon' => 'lock', 'label' => 'پایان‌یافته', 'code' => 'closed', 'hint' => 'پذیرفته‌شده و همهٔ واحدهای ارجاع‌شده اقدام را تکمیل کرده‌اند.'],
    ];

    $auto = [
        ['icon' => 'hourglass_empty', 'label' => 'سکوت = نیمه‌موافق پس از ۴۸ ساعت', 'hint' => 'اگر واحدی در بازهٔ ۴۸ ساعته پاسخ ندهد، بازخورد او خودکار «نیمه‌موافق» ثبت می‌شود و برچسب «تولید خودکار» می‌گیرد. مهلت بررسی با علامت ⚠ نشان داده می‌شود وقتی گذشته باشد.'],
        ['icon' => 'inventory_2', 'label' => 'پذیرش با ارجاع تا «closed» می‌ماند', 'hint' => 'پذیرش با ارجاع واحدها، روی «accepted» می‌مانَد تا همهٔ واحدهای ارجاع‌شده اقدام را تکمیل‌شده اعلام کنند — آن‌گاه «closed» می‌شود.'],
        ['icon' => 'bolt', 'label' => 'رد فقط با تصمیم مدیریت ارشد', 'hint' => '«rejected» فقط وقتی مدیریت ارشد مخالف باشد رخ می‌دهد. «under_review» یعنی نیازمند تکمیل مجدد، نه رد شدن.'],
    ];

    $roles = [
        'submitter' => [
            'note' => 'شما پیشنهاد را ثبت کرده‌اید (غیر از واحد مدیریت ارشد).',
            ['icon' => 'edit_note', 'label' => 'ثبت و تکمیل شخصی', 'text' => 'فرم ثبت شامل عنوان، شرح، اهداف، قواعد، واحدهای ذی‌نفع و پیوست است. واحد ثبت‌کننده خودکار به فهرست ذی‌نفعان اضافه می‌شود. با پرچم «تکمیل شخصی» می‌توانید پیش‌فرض بازخورد همهٔ واحدها را هم پر کنید.'],
            ['icon' => 'visibility', 'label' => 'پایش', 'text' => 'شما روند بررسی را در صفحهٔ جزئیات می‌بینید — تایم‌لاین، وضعیت هر واحد و بازخوردها. مگر آنکه سرپرست واحد باشید، اقدام فعالی از شما انتظار نمی‌رود.'],
        ],
        'head' => [
            'note' => 'شما سرپرست واحد ذی‌نفع هستید (نه مدیریت ارشد).',
            ['icon' => 'rate_review', 'label' => 'ثبت بازخورد', 'text' => 'وقتی واحد شما در مرحلهٔ بازخورد است، کارت «ثبت بازخورد» با گزینه‌های موافق/نیمه‌موافق/مخالف ظاهر می‌شود. کارتِ پیشنهادِ نیازمند پاسخ با یک نشان اعلان و حاشیهٔ قرمز در فهرست مشخص می‌شود.'],
            ['icon' => 'task_alt', 'label' => 'تکمیل اقدام ارجاع‌شده', 'text' => 'اگر پیشنهاد پذیرفته شود و واحد شما برای اجرای اقدام ارجاع داده شود، کارت «تکمیل اقدام واحد» ظاهر می‌شود. با «ثبت تکمیل اقدام» آن را کامل می‌کنید.'],
        ],
        'ceo' => [
            'note' => 'شما تصمیم‌گیرندهٔ ارشد هستید (واحد MA).',
            ['icon' => 'gavel', 'label' => 'تصمیم نهایی', 'text' => 'در مرحلهٔ «منتظر تصمیم» کارت «تصمیم نهایی» را می‌بینید: پذیرش (با ارجاع اختیاری واحدها و دستورالعمل)، رد یا درخواست تکمیل مجدد.'],
            ['icon' => 'block', 'label' => 'ثبت پیشنهاد ممکن نیست', 'text' => 'کاربران واحد MA مجاز به ثبت پیشنهاد نیستند — دکمهٔ «ایجاد» برای شما ظاهر نمی‌شود.'],
        ],
    ];

    $notes = [
        'شناسهٔ هر پیشنهاد به‌صورت SN-YYYYMMDD-NNNNNN است — با جستجوی «SN-» هم در پنل کاربری و هم ادمین یافت می‌شود.',
        'واحد ثبت‌کننده خودکار به فهرست ذی‌نفعان اضافه و واحد MA از آن حذف می‌شود؛ در زمان ساخت یک ردیف بررسی برای واحد ثبت‌کننده ساخته می‌شود.',
        'نشان اعلان روی کارت فهرست یعنی واحد شما از شما انتظار پاسخ دارد (requiresMyAction) — نه اعلان عمومی.',
        'مرحلهٔ پیشنهاد دستی عوض نمی‌شود؛ syncStage با هر بازخورد جدید آن را از روی وضعیت همهٔ بازخوردها محاسبه می‌کند.',
        'جدول برترین‌ها بالای صفحه، سه کاربر برتر را بر اساس تعداد پیشنهادهای پذیرفته‌شده نشان می‌دهد.',
    ];
@endphp

<div x-data="{ tab: 'stages', stageSub: 'flow', role: 'submitter' }">
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
                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]" dir="ltr">{{ $s['code'] }}</span>
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
                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]" dir="ltr">{{ $s['code'] }}</span>
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

    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @foreach($notes as $note)
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
            </div>
        @endforeach
    </div>
</div>
