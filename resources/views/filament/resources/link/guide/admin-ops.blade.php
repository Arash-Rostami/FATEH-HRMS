@php
    $ops = [
        ['icon' => 'visibility', 'label' => 'مشاهده', 'hint' => 'همهٔ اطلاعات لینک را فقط برای دیدن نشان می‌دهد: عنوان، توضیحات، نوع، ترتیب، آدرس خارجی، آدرس داخلی، آی‌پی‌ها، تصویر و آیکون و تاریخ‌های ایجاد و به‌روزرسانی (شمسی). آدرس داخلی و آی‌پی‌ها فقط وقتی پر باشند نمایش داده می‌شوند.'],
        ['icon' => 'edit', 'label' => 'ویرایش', 'hint' => 'فرم لینک را باز می‌کند: نوع (داخلی یا خارجی)، ترتیب، آدرس خارجی، آدرس داخلی، آی‌پی‌ها، عنوان، توضیحات، تصویر و آیکون. کادر «آی‌پی‌های شبکه داخلی» فقط وقتی «آدرس داخلی» پر شده نمایش داده می‌شود.'],
        ['icon' => 'delete', 'label' => 'حذف', 'hint' => 'لینک را برای همیشه پاک می‌کند و کاربران دیگر آن را در پنل خود نمی‌بینند. می‌توانید چند لینک انتخاب‌شده را هم یکجا حذف کنید.'],
        ['icon' => 'download', 'label' => 'خروجی اکسل', 'hint' => 'لینک‌های انتخاب‌شده را از منوی عملیات گروهی به‌صورت فایل اکسل دریافت می‌کنید. ستون‌ها: شناسه، ترتیب، عنوان، آدرس خارجی، آدرس داخلی، نوع، مسیریابی هوشمند (بله یا خیر)، توضیحات و تاریخ ایجاد (شمسی). می‌توانید خروجی همهٔ لینک‌های فیلترشده را هم بگیرید.'],
    ];
    $tabs = [
        ['label' => 'همه', 'hint' => 'همهٔ لینک‌ها بدون هیچ فیلتری.'],
        ['label' => 'داخلی', 'hint' => 'فقط لینک‌های نوع داخلی. تعداد آن‌ها با نشان سبز روی زبانه دیده می‌شود.'],
        ['label' => 'خارجی', 'hint' => 'فقط لینک‌های نوع خارجی. تعداد آن‌ها با نشان نارنجی روی زبانه دیده می‌شود.'],
    ];
    $filters = [
        ['label' => 'نوع لینک', 'hint' => 'لینک‌ها را بر اساس نوع (داخلی یا خارجی) نشان می‌دهد.'],
        ['label' => 'مسیریابی هوشمند', 'hint' => 'یا فقط لینک‌هایی را نشان می‌دهد که مسیریابی هوشمند دارند، یا فقط لینک‌هایی که ندارند. مسیریابی هوشمند یعنی «آدرس داخلی» پر شده است.'],
    ];
    $groups = [
        ['label' => 'گروه‌بندی بر اساس نوع', 'hint' => 'لینک‌ها را بر اساس نوع (داخلی یا خارجی) دسته‌بندی می‌کند؛ هر دسته را می‌توانید ببندید یا باز کنید.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: تعریف لینک‌ها، تنظیم مسیریابی و کنترل ترتیب نمایش</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف جدول یک لینک است. در انتهای هر ردیف سه دکمه می‌بینید: مشاهده، ویرایش و حذف. دکمهٔ ایجاد لینک جدید بالای صفحه است. ترتیب نمایش را با کشیدن ردیف‌ها یا با تغییر «ترتیب نمایش» در فرم عوض می‌کنید. جستجوی سراسری پنل، لینک‌ها را با عنوان، آدرس یا توضیحات پیدا می‌کند و شما را مستقیم به صفحهٔ ویرایش می‌برد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">عملیات روی هر ردیف</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($ops as $op)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $op['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $op['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $op['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

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
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_alt</span>
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
                تعداد لینک‌های زبانه‌های «داخلی» و «خارجی» یک‌بار محاسبه می‌شود؛ پس عوض کردن زبانه صفحه را کند نمی‌کند.
            </p>
        </div>
    </div>
</div>