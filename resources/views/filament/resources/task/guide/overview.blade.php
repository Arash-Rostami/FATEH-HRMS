@php
    $rows = [
        [
            'icon' => 'view_column',
            'label' => 'یک وظیفهٔ فعال',
            'tag' => 'اصلی',
            'hint' => 'هر ردیف یک وظیفه است با عنوان، توضیحات، وضعیت (انجام‌نشده / در حال انجام / در انتظار / انجام‌شده)، ضرب‌الاجل و دو نفرِ کلیدی: <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">user_id</code> (ایجادکننده) و <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">assigned_to</code> (مسئول انجام). وقتی <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">assigned_to</code> پر باشد و با <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">user_id</code> متفاوت باشد، وظیفه «محول‌شده» است (ستون <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">is_delegated</code> روشن می‌شود).',
        ],
        [
            'icon' => 'bar_chart',
            'label' => 'زبانهٔ اطلاعات سازمانی (detail)',
            'tag' => 'HasOne',
            'hint' => 'متادیتای سازمانیِ وظیفه (واحد، بخش، پروژه، طرح، مبدأ اقدام، همکاران، مسئول ردگیری، وضعیتِ فرآیندی و پیوست‌ها) در یک رابطهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">detail</code> از نوع HasOne ذخیره می‌شود — روی همان ردیفِ وظیفه، نه در جدول اصلی. ستون‌های <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">detail.*</code> و زبانهٔ «اطلاعات سازمانی» فرم/اینفولیست همین رابطه را می‌خوانند.',
        ],
        [
            'icon' => 'archive',
            'label' => 'وظیفهٔ آرشیو‌شده',
            'tag' => 'archived_at',
            'hint' => 'وقتی یک وظیفهٔ انجام‌شده آرشیو می‌شود، <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">archived_at</code> پر می‌شود (بدون حذف). ستون <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">is_archived</code> و فیلتر سه‌حالتهٔ «آرشیو» همین فیلد را نمایش/فیلتر می‌کنند. باز کردن دوبارهٔ یک وظیفهٔ آرشیو‌شده (تغییر وضعیت به غیر از انجام) خودکار <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">archived_at</code> را پاک می‌کند.',
        ],
        [
            'icon' => 'delete',
            'label' => 'وظیفهٔ حذف‌شدهٔ نرم',
            'tag' => 'deleted_at',
            'hint' => 'حذف، رکورد را برنمی‌دارد؛ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">deleted_at</code> را پر می‌کند. این صفحه بدون اسکوپ SoftDelete کوئری می‌زند، پس ردیف‌های حذف‌شده را هم می‌بینید و دکمهٔ «بازیابی» برایشان ظاهر می‌شود. پس از ' . convertToPersian('30') . ' روز، Prunable رکورد و فایل‌های پیوستش را برای همیشه پاک می‌کند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«وظیفه» یک رکورد گردش‌کار است؛ کلیدهای اصلی‌اش دو نفر و یک ضرب‌الاجل است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در این صفحه یک وظیفه است که یک ایجادکننده و یک مسئول انجام دارد و در یکی از چهار وضعیت قرار می‌گیرد. متادیتای سازمانی در رابطهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">detail</code> می‌نشیند. این ماژول دو طرف دارد: پنل ادمین (همین صفحه، نظارت کل‌سازمان) و پنل کاربری (برد وظایف در <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/taskboard</code>) — زبانهٔ «تجربهٔ کاربر» را ببینید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">هر ردیف چیست؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rows as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">{{ $r['tag'] }}</span>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $r['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                وضعیت یک منبع واحد است: <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">TaskStatus</code> رنگ/آیکون/برچسب جدول، اینفولیست و خروجی اکسل را هم‌زمان تغذیه می‌کند.
            </p>
        </div>
    </div>
</div>