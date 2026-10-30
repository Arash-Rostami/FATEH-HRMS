@php
    $rows = [
        [
            'icon' => 'alt_route',
            'label' => 'مسیریابی هوشمند چگونه کار می‌کند؟',
            'hint' => 'وقتی «آدرس داخلی» را پر کنید، در ستون «مسیریابی هوشمند» جدول یک نشان روشن می‌شود. هر بار کاربر روی لینک کلیک کند، آی‌پی او با فهرست «آی‌پی‌های شبکه داخلی» مقایسه می‌شود. اگر آی‌پی او در فهرست باشد به آدرس داخلی می‌رود، وگرنه به آدرس خارجی.',
        ],
        [
            'icon' => 'apartment',
            'label' => 'بدون آی‌پی، همه داخلی حساب می‌شوند',
            'hint' => 'اگر «آدرس داخلی» پر باشد ولی فهرست آی‌پی خالی بماند، همهٔ کاربران به آدرس داخلی می‌روند. برای اینکه فقط کاربران داخل شبکه به آن بروند، دست‌کم یک آی‌پی وارد کنید.',
        ],
        [
            'icon' => 'link',
            'label' => 'پر کردن همزمان دو کادر',
            'hint' => '«آدرس داخلی» و «آی‌پی‌های شبکه داخلی» باید با هم پر شوند یا با هم خالی بمانند. اگر فقط یکی را پر کنید، فرم ذخیره نمی‌شود و پیامی برای اصلاح می‌بینید.',
        ],
        [
            'icon' => 'swap_horiz',
            'label' => 'باز شدن لینک برای کاربر',
            'hint' => 'اگر لینک به آدرس داخلی برود، در همان زبانه باز می‌شود؛ اگر به آدرس خارجی برود، در زبانهٔ جدید باز می‌شود. لینک «خارجی» همیشه در زبانهٔ جدید باز می‌شود. این رفتار به مقصد نهایی بستگی دارد، نه به نوع لینک.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">alt_route</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">مسیریابی هوشمند: یک لینک، دو مقصد</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        مسیریابی هوشمند یعنی یک لینک برای کاربری که داخل شبکهٔ سازمان است به یک آدرس و برای کاربری که بیرون از شبکه است به آدرس دیگری برود. این کار با دو کادر تنظیم می‌شود: «آدرس داخلی» (مقصد کاربران داخل شبکه) و «آی‌پی‌های شبکه داخلی» (آی‌پی‌هایی که داخل شبکه حساب می‌شوند). اگر «آدرس داخلی» خالی باشد، مسیریابی هوشمند خاموش است و همه به «آدرس خارجی» می‌روند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">route</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">نکات مسیریابی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rows as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $r['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                برای دیدن اینکه کدام لینک‌ها مسیریابی هوشمند دارند، از فیلتر «مسیریابی هوشمند» در جدول استفاده کنید. با نگه‌داشتن نشانگر موس روی نشان ستون، تعداد آی‌پی‌های ثبت‌شده را می‌بینید.
            </p>
        </div>
    </div>
</div>