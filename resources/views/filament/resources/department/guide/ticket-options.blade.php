@php
    $parts = [
        [
            'icon' => 'category',
            'label' => 'نوع درخواست',
            'hint' => 'یکی از انواع پیش‌فرض تیکت: پشتیبانی (support)، دسترسی (access) یا توسعه (development). هنگام نوشتن، گزینه‌های معتبر پیشنهاد می‌شوند و باید دقیقاً یکی از آن‌ها را بنویسید.',
        ],
        [
            'icon' => 'key',
            'label' => 'کد حوزه',
            'hint' => 'کد انگلیسیِ حوزهٔ تیکت؛ فقط حروف انگلیسی، عدد، خط تیره و زیرخط مجاز است. سیستم از این کد برای تطبیق با آیکون و عنوان استفاده می‌کند و کاربران آن را نمی‌بینند.',
        ],
        [
            'icon' => 'label',
            'label' => 'عنوان حوزه',
            'hint' => 'عنوان فارسیِ همان حوزه که در فرم تیکتِ کاربر به‌عنوان یک گزینه نمایش داده می‌شود.',
        ],
        [
            'icon' => 'star',
            'label' => 'آیکون',
            'hint' => 'اختیاری — از فهرست کشویی، آیکون را با دیدنِ شکل واقعی آن انتخاب کنید (نیازی به تایپ نام آیکون نیست). اگر تنظیم شود، آن حوزه در فرم تیکت با همین آیکون ظاهر می‌شود؛ در غیر این صورت آیکون پیش‌فرضِ آن حوزه به‌کار می‌رود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">confirmation_number</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">هر واحد می‌تواند گزینه‌های سفارشیِ فرمِ تیکت را بازنویس کند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        بخش «گزینه‌های تیکت» به شما اجازه می‌دهد برای هر واحد سازمانی، حوزه‌های تیکتِ اختصاصی تعریف کنید. وقتی کاربر در پنل کاربری تیکت ثبت می‌کند، اگر واحدِ او گزینه‌های سفارشی داشته باشد، فهرستِ «نوع درخواست» و «حوزه» به‌جای مقادیر پیش‌فرض، از همین تنظیمات خوانده می‌شود. اگر واحدی گزینه‌ای تعریف نکرده باشد، همان فهرست پیش‌فرضِ تیکت برایش به‌کار می‌رود.
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
                می‌توانید گزینه‌ها را با دکمه‌های بالا/پایین بچینید؛ ترتیب در فرم تیکت کاربر حفظ می‌شود. هر گزینه را می‌توان جمع و باز کرد.
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
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">اگر واحد گزینه‌ای تعریف نکرده باشد (بخش خالی باشد)، فرم تیکت کاربر همان فهرست پیش‌فرض (سه نوع) را نمایش می‌دهد. زبانهٔ «بدون گزینهٔ تیکت» در فهرست همین واحدها را جدا می‌کند.</p>
                </div>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                برچسبِ نوع و حوزهٔ هر تیکت از گزینه‌های واحد هدفِ همان تیکت خوانده می‌شود و در صورت نبودن، به گزینه‌های پیش‌فرض و سپس واحدهای دیگر می‌رسد. تیکت‌ها فقط کلید را نگه می‌دارند؛ اگر کلید یک گزینه را تغییر دهید، تیکت‌های قدیمی دیگر با آن جور نیستند و کلید خام را نشان می‌دهند — کدها را ثابت نگه دارید.
            </p>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                بعد از هر ویرایش، تنظیمات گزینه‌های تیکت خودکار به‌روز می‌شوند تا تغییر بلافاصله در فرم تیکت کاربر اثر کند.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">group_add</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">تخصیص آزاد تیکت</p>
        </div>
        <div class="px-5 py-4 text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium flex flex-col gap-3">
            <p>کلید «تخصیص آزاد تیکت» در بخش «گزینه‌های تیکت» وضعیت واحد را در برابر تیکت تغییر می‌دهد: وقتی روشن باشد، این واحد حتی بدون مدیر هم می‌تواند تیکت بگیرد و همهٔ اعضای فعالِ آن (به‌جز خودِ درخواست‌دهنده) می‌توانند تیکت‌های بازِ بدون‌مسئولِ واحد را بردارند، به همکار تخصیص دهند یا به واحد دیگری ارجاع بزنند؛ بستن، بازگشت به صف و ثبت اثربخشی همچنان فقط با مدیر واحد یا مسئول رسیدگی است و مدیر واحد هم همهٔ اختیارات خود را نگه می‌دارد.</p>
            <p>برای برداشتن کلید شرطی وجود دارد: اگر واحد مدیر نداشته باشد و تیکتی (باز یا در دست بررسی) هنوز روی آن باز باشد، خاموش‌کردن با پیام خطا روی خود کلید رد می‌شود — اول تیکت‌ها را ببندید یا ارجاع دهید. خاموش‌کردنِ مجاز، همه‌چیز را به حالت عادی (فقط مدیر واحد) برمی‌گرداند و اعلان‌های اعضا خودکار پاک‌سازی می‌شوند.</p>
        </div>
    </div>
</div>