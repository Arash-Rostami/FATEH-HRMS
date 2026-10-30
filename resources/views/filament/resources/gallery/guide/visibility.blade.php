@php
    $modes = [
        [
            'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            'icon' => 'public',
            'label' => 'عمومی',
            'hint' => 'وقتی «واحد سازمانی» و «واحدهای سازمانی (چندگانه)» هر دو خالی باشند، گالری برای همهٔ کاربران قابل دید است. در جدول با نماد کره و رنگ سبز نشان داده می‌شود.',
            'rule' => 'هر دو فیلد واحد خالی‌اند',
        ],
        [
            'chip' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]',
            'icon' => 'lock',
            'label' => 'تک‌واحدی (خصوصی)',
            'hint' => 'وقتی فقط «واحد سازمانی» (department_id) پر باشد، گالری فقط برای کاربران همان واحد در پنل کاربری قابل دید است. در جدول با نماد قفل و رنگ زرد نشان داده می‌شود.',
            'rule' => 'فقط «واحد سازمانی» پر است',
        ],
        [
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'icon' => 'groups',
            'label' => 'چندواحدی (مشترک)',
            'hint' => 'وقتی «واحدهای سازمانی (چندگانه)» (departments) پر باشد، گالری برای کاربران همهٔ آن واحدها قابل دید است. در جدول با نماد گروه و رنگ آبی نشان داده می‌شود.',
            'rule' => '«واحدهای سازمانی (چندگانه)» پر است («واحد سازمانی» نادیده گرفته می‌شود)',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">سه حالتِ دید: عمومی، تک‌واحدی، چندواحدی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        دیدِ یک گالری با دو فیلد مجزا تعیین می‌شود: <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">department_id</code> (تک‌واحدی) و <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">departments</code> (آرایهٔ JSON چندواحدی). در فرم، به‌محض پر کردن یکی، دیگری خودکار غیرفعال و خالی می‌شود (afterStateUpdated)؛ پس فقط یکی از سه حالتِ زیر ذخیره می‌شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tune</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">سه حالت و قاعدهٔ ذخیره‌شدن</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($modes as $m)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $m['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $m['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $m['label'] }}</p>
                            <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $m['rule'] }}</code>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $m['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                ستون «واحد سازمانی» جدول، مقادیر را از Accessor یکپارچهٔ <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">all_departments</code> می‌خواند: اگر <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">department_id</code> پر باشد، آن را به اولِ آرایهٔ <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">departments</code> اضافه می‌کند و یونیک‌سازی می‌نماید — پس حتی اگر هر دو فیلد پر باشند، نمایش جدول همهٔ واحدها را با هم می‌بیند. اگر کدهای ذخیره‌شده با هیچ واحد فعالی مطابقت نداشته باشند (مثلاً واحد حذف‌شده)، ستون به‌جای «عمومی» عبارت «واحد نامشخص» را نشان می‌دهد — چون رکورد همچنان خصوصی است، فقط واحدش دیگر قابل شناسایی نیست.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">filter_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">فیلترهای مرتبط با دسترسی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">visibility</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">فیلتر دسترسی</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">سه‌حالته: «عمومی» فقط رکوردهایی که هم <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">department_id</code> و هم <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">departments</code> خالی‌اند؛ «خصوصی» فقط رکوردهای دارای حداقل یکی از این دو.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">share</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">فیلتر نوع اشتراک</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">«چند واحدی» فقط رکوردهایی که <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">departments</code> غیرخالی است (با <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">JSON_LENGTH &gt; 0</code>)؛ «تک‌واحدی» فقط رکوردهایی که <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">department_id</code> دارند و <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">departments</code> خالی است.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">groups</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">فیلتر واحد سازمانی</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">یک واحد انتخاب می‌کنید و رکوردهایی که یا <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">department_id</code> آن‌ها برابر آن است یا در آرایهٔ <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">departments</code> آن را دارند (<code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">orWhereJsonContains</code>) فهرست می‌شوند.</p>
                </div>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                کاربری که گالری را نمی‌بیند: در پنل کاربری، کوئریِ پایه فقط رکوردهای عمومی یا آن‌هایی که واحد کاربر در <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">department_id</code>/<code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">departments</code> دارند می‌آورد — هیچ گالری خصوصیِ واحدِ دیگری برایش قابل دیدن نیست.
            </p>
        </div>
    </div>
</div>