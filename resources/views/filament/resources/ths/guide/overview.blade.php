@php
    $rows = [
        [
            'icon' => 'confirmation_number',
            'label' => 'شناسهٔ تیکت — پیشوند + سال‌ماه + شماره',
            'hint' => 'شناسهٔ نمایشی هر تیکت به‌صورت <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">PREFIX-YYMM-NNNN</code> ساخته می‌شود. پیشوند برابرِ کدِ «واحد هدف» است (مثلاً <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">TN</code>) و اگر واحد هدف انتخاب نشود، پیشوندِ پیش‌فرض <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">T</code> است. ستون شناسه قابل کپی است و اگر تکمیل دیرتر از مهلت باشد، قرمز می‌شود.',
        ],
        [
            'icon' => 'category',
            'label' => 'نوع و حوزهٔ درخواست — قابل سفارشی‌سازی توسط واحد',
            'hint' => 'هر تیکت یکی از سه نوع «پشتیبانی»، «دسترسی» یا «توسعه» است و هر نوع فهرست حوزه‌های مخصوص خودش را دارد (ویندوز/نرم‌افزار، ایمیل، دیتابیس، …). هر واحد سازمانی می‌تواند با <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">ticket_options</code> حوزه‌ها و نوع‌های اختصاصی خودش را تعریف کند؛ آن‌گاه برای تیکت‌های ارجاع‌شده به آن واحد، فقط همان گزینه‌ها نمایش داده می‌شود.',
        ],
        [
            'icon' => 'low_priority',
            'label' => 'اولویت — فقط در زمان ثبت',
            'hint' => 'اولویت سه‌پایه‌ای است: «عادی»، «فوری» و «خیلی فوری». اولویت در زمان ثبت تعیین می‌شود و پس از آن در فرم ویرایش غیرفعال است؛ تغییر آن در طول گردش‌کار ممکن نیست.',
        ],
        [
            'icon' => 'sync',
            'label' => 'وضعیت و چرخهٔ خودکار',
            'hint' => 'تیکت یکی از وضعیت‌های «باز»، «در حال بررسی» یا «بسته‌شده» را دارد. به‌محض تعیینِ مسئول رسیدگی، وضعیت از «باز» به «در حال بررسی» تغییر می‌کند و با خالی کردن مسئول، برمی‌گردد. برای بستن تیکت، ثبتِ «اثربخشی» الزامی است؛ در هنگام بستن، فیلد «نتیجه اقدام» خودکار از روی گفتگو ساخته می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">support_agent</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«تیکت» یک درخواست رسمی پشتیبانی، دسترسی یا توسعه است که کاربر ثبت می‌کند و شما نظارت کل‌سازمان دارید</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربران تیکت را از صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/ths</code> در پنل کاربری ثبت می‌کنند. شما در این صفحه همهٔ تیکت‌ها را می‌بینید، مسئول تعیین می‌کنید، مهلت می‌گذارید، اثربخشی را بازبینی می‌کنید و خروجی اکسل می‌گیرید. ساخت تیکت از طرف ادمین هم ممکن است ولی گردش‌کار اصلی همان است: درخواست‌دهنده ثبت می‌کند → مدیر واحد مسئول تعیین می‌کند → مسئول پاسخ می‌دهد و اثربخشی را ثبت می‌کند → تیکت بسته می‌شود → درخواست‌دهنده امتیاز رضایت می‌دهد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">list_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">هر تیکت چه چیزی دارد؟</p>
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
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $r['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                فیلدهای غیرثابت تیکت (واحد درخواست‌دهنده، واحد هدف، یادداشت رضایت) درون یک کیسهٔ JSON به‌نام <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">extra</code> نگهداری می‌شوند — واحد هدف همان چیزی است که پیشوند شناسه و فیلتر «واحد هدف» را تغذیه می‌کند.
            </p>
        </div>
    </div>
</div>