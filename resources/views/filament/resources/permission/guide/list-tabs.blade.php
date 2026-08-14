@php
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام ردیف‌های دسترسی — بدون فیلتر اضافه. این زبانهٔ پیش‌فرض است.'],
        ['label' => 'مدیران کل', 'hint' => 'فقط ردیف‌هایی با is_super_admin = روشن. شمارشِ مدیران ارشد به‌صورت نشانِ هشدار روی زبانه می‌نشیند.'],
        ['label' => 'کاربران عادی', 'hint' => 'فقط ردیف‌هایی با is_super_admin = خاموش. شمارشِ مدیران عادی به‌صورت نشانِ خاکستری روی زبانه می‌نشیند.'],
    ];
    $filters = [
        ['label' => 'فقط مدیر ارشد', 'hint' => 'فیلتر سه‌حالته: فقط مدیران ارشد / فقط مدیران عادی.'],
        ['label' => 'بازه تاریخ ایجاد', 'hint' => 'فیلتر بازهٔ تاریخ ایجاد ردیف دسترسی.'],
    ];
    $groups = [
        ['label' => 'بر اساس ماژول', 'hint' => 'ردیف‌ها را بر اساس ماژول گروه‌بندی می‌کند: مدیران ارشد در یک گروه «مدیر ارشد»، مدیران عادی بر اساس اولین ماژولِ abilitiesشان، و ردیف‌های بدون ماژول در گروه «—» می‌نشینند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">filter_alt</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">زبانه‌ها، فیلترها و گروه‌بندی برای پیمایشِ سریعِ ردیف‌ها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فهرست سه زبانه دارد که ردیف‌ها را بر اساس سطح دسترسی جدا می‌کند و هر کدام شمارشِ زنده نشان می‌دهد. ستون «تعداد ماژول‌ها» در جدول، شمارشِ ماژول‌های مجاز را به‌صورت نشان نمایش می‌دهد — برای مدیر ارشد، کل ماژول‌ها منهای استثناها؛ برای مدیر عادی، طولِ abilities.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tab</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">زبانه‌های فهرست</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tabs as $t)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">tab</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_list</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلترها و گروه‌بندی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($filters as $f)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">filter_list</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
            @foreach($groups as $g)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">folder</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $g['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $g['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                زبانه‌ها فقط وقتی نمایش داده می‌شوند که تنظیم show_list_tabs کاربر روشن باشد — اگر زبانه‌ها را نمی‌بینید، این تنظیم را در ترجیحات حساب خود بررسی کنید.
            </p>
        </div>
    </div>
</div>