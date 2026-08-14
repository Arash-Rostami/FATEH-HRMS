@php
    $parts = [
        [
            'icon' => 'category',
            'label' => 'نوع درخواست (request_type)',
            'hint' => 'یکی از انواع پیش‌فرض تیکت — پشتیبانی (support)، دسترسی (access) یا توسعه (development). این فیلد با یک datalist از کلیدهای معتبر پر می‌شود و باید دقیقاً یکی از آن‌ها باشد.',
        ],
        [
            'icon' => 'key',
            'label' => 'کلید حوزه (area_key)',
            'hint' => 'کلید انگلیسیِ حوزهٔ تیکت — فقط حروف، اعداد، خط تیره و زیرخط (regex). این کلید در سیستم ذخیره و برای تطبیق با آیکون/برچسب استفاده می‌شود؛ کاربر آن را نمی‌بیند.',
        ],
        [
            'icon' => 'label',
            'label' => 'عنوان حوزه (area_label)',
            'hint' => 'برچسب فارسیِ همان حوزه که در فرم تیکتِ کاربر به‌عنوان گزینهٔ قابل‌انتخاب نمایش داده می‌شود.',
        ],
        [
            'icon' => 'star',
            'label' => 'آیکون (icon)',
            'hint' => 'اختیاری — یک آیکون Heroicons (مثلاً heroicon-o-server). اگر تنظیم شود، آن حوزه در فرم تیکت با همین آیکون ظاهر می‌شود؛ در غیر این صورت آیکون پیش‌فرضِ آن حوزه به‌کار می‌رود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">confirmation_number</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">هر واحد می‌تواند گزینه‌های سفارشیِ فرمِ تیکت را بازنویس کند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فیلد «گزینه‌های تیکت» یک Repeater است که به شما اجازه می‌دهد برای هر واحد سازمانی، حوزه‌های تیکتِ اختصاصی تعریف کنید. وقتی کاربر در پنل کاربری تیکت ثبت می‌کند، اگر واحدِ او گزینه‌های سفارشی داشته باشد، فهرستِ «نوع درخواست» و «حوزه» به‌جای مقادیر پیش‌فرض، از همین تنظیمات خوانده می‌شود. اگر واحدی گزینه‌ای تعریف نکرده باشد، همان فهرست پیش‌فرضِ تیکت برایش به‌کار می‌رود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">view_list</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اجزای هر گزینه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($parts as $p)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $p['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $p['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $p['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                می‌توانید گزینه‌ها را با دکمه‌های بالا/پایین بچینید؛ ترتیب در فرم تیکت کاربر حفظ می‌شود. فیلد قابلِ جمع شدن است (collapsible).
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">swap_horiz</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">سفارشی در برابر پیش‌فرض</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">check_circle</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">واحد با گزینهٔ سفارشی</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">اگر واحد حداقل یک گزینه تعریف کرده باشد، فرم تیکتِ کاربرِ آن واحد فقط همان نوع‌ها و حوزه‌های سفارشی را نشان می‌دهد — فهرست پیش‌فرض به‌طور کامل جایگزین می‌شود.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">remove_circle</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">واحد بدون گزینه</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">اگر واحد گزینه‌ای تعریف نکرده باشد (فیلد خالی یا بدون آیتم)، فرم تیکت کاربر همان فهرست پیش‌فرضِ سه‌نوعی را نمایش می‌دهد. زبانهٔ «بدون گزینه تیکت» در فهرست همین واحدها را جدا می‌کند.</p>
                </div>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                تنظیمات گزینه‌های تیکت در کش سیستم نگه داشته می‌شود؛ بعد از ویرایش، کش به‌صورت خودکار پاک می‌شود تا تغییر بلافاصله در فرم تیکت کاربر اثر کند.
            </p>
        </div>
    </div>
</div>