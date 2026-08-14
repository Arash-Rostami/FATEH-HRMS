@php
    $ops = [
        ['icon' => 'visibility', 'label' => 'مشاهده', 'hint' => 'صفحهٔ اینفولیست لینک را باز می‌کند: عنوان، توضیحات، نوع، ترتیب، آدرس خارجی، آدرس داخلی، آی‌پی‌ها، تصویر/آیکون و تاریخ‌های ایجاد/بروزرسانی — همگی شمسی و راست‌چین. آی‌پی‌ها و آدرس داخلی فقط اگر پر باشند نشان داده می‌شوند.'],
        ['icon' => 'edit', 'label' => 'ویرایش', 'hint' => 'فرم لینک را باز می‌کند: نوع (داخلی/خارجی)، ترتیب، آدرس خارجی، آدرس داخلی، آی‌پی‌ها، عنوان، توضیحات، تصویر و آیکون. «آی‌پی‌ها» فقط وقتی «آدرس داخلی» پر شده نمایان می‌شود.'],
        ['icon' => 'delete', 'label' => 'حذف', 'hint' => 'رکورد لینک را کاملاً برمی‌دارد. کاربران دیگر این لینک را در پنل خود نمی‌بینند. حذف روی ردیف‌های انتخاب‌شده به‌صورت گروهی هم ممکن است.'],
        ['icon' => 'download', 'label' => 'خروجی اکسل', 'hint' => 'از منوی bulk actions روی ردیف‌های انتخاب‌شده خروجی می‌گیرید. ستون‌ها: شناسه، ترتیب، عنوان، آدرس خارجی، آدرس داخلی، نوع، مسیریابی هوشمند (بله/خیر)، توضیحات و تاریخ ایجاد (شمسی). اعمال روی کل فهرست فیلترشده نیز ممکن است.'],
    ];
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام لینک‌ها — بدون فیلتر اضافه.'],
        ['label' => 'داخلی', 'hint' => 'فقط لینک‌های نوع داخلی. شمارشان به‌صورت نشانِ سبز روی زبانه می‌نشیند.'],
        ['label' => 'خارجی', 'hint' => 'فقط لینک‌های نوع خارجی. شمارشان به‌صورت نشانِ هشدار روی زبانه می‌نشیند.'],
    ];
    $filters = [
        ['label' => 'نوع لینک', 'hint' => 'فیلتر بر اساس نوع (داخلی/خارجی).'],
        ['label' => 'مسیریابی هوشمند', 'hint' => 'فیلتر سه‌حالته: فقط لینک‌های دارای مسیریابی هوشمند / فقط بدون مسیریابی. مسیریابی هوشمند یعنی «آدرس داخلی» پر شده.'],
    ];
    $groups = [
        ['label' => 'گروه‌بندی بر اساس نوع', 'hint' => 'لینک‌ها را به‌ازای نوع (داخلی/خارجی) گروه‌بندی می‌کند — گروه‌های تاشو.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: تعریف لینک‌ها، تنظیم مسیریابی و کنترل ترتیب نمایش</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در جدول یک لینک است. سه دکمهٔ عملیات روی هر ردیف موجود است (پس از سلول‌ها): مشاهده، ویرایش، حذف. دکمهٔ «ساخت لینک» در هدر صفحه قرار دارد. ترتیب نمایش با کشیدن ردیف‌ها (reorderable) یا ویرایش فیلد «ترتیب» عوض می‌شود. جستجوی سراسری پنل لینک‌ها را با عنوان، آدرس یا توضیحات پیدا می‌کند و مستقیم به ویرایش می‌رود.
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
                شمارِ زبانه‌های «داخلی» و «خارجی» در یک پرسش واحد و کش‌شده (once) محاسبه می‌شود — تعویض زبانه پرسش اضافه نمی‌زند.
            </p>
        </div>
    </div>
</div>