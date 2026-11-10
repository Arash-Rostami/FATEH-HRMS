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
        برای تعیین مخاطبان گالری، یک واحد یا چند واحد انتخاب کنید. با پر کردن هرکدام، گزینهٔ دیگر خودکار غیرفعال و خالی می‌شود. اگر هیچ‌کدام را انتخاب نکنید، گالری عمومی است.
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
                ستون «واحد سازمانی» همهٔ واحدهای ثبت‌شده را بدون تکرار نشان می‌دهد، حتی اگر هر دو بخش واحد پر باشند. اگر هیچ واحد فعالی با اطلاعات ثبت‌شده مطابقت نداشته باشد، «واحد نامشخص» دیده می‌شود؛ گالری همچنان خصوصی است.
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
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">«عمومی» گالری‌های بدون واحد مخاطب را نشان می‌دهد؛ «خصوصی» گالری‌هایی را که حداقل یک واحد مخاطب دارند.</p>
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
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">«چند واحدی» گالری‌هایی را نشان می‌دهد که فهرست واحدهای چندگانه دارند. «تک‌واحدی» گالری‌هایی را نشان می‌دهد که بخش تک‌واحدی آن‌ها پر و فهرست واحدهای چندگانه‌شان خالی است.</p>
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
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">با انتخاب یک واحد، گالری‌هایی نمایش داده می‌شوند که آن واحد در بخش تک‌واحدی یا در فهرست واحدهای چندگانهٔ آن‌ها ثبت شده باشد.</p>
                </div>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر گالری را نمی‌بیند، واحد مخاطب را بررسی کنید. کاربران گالری‌های عمومی و گالری‌های مجاز برای واحد خود را می‌بینند.
            </p>
        </div>
    </div>
</div>