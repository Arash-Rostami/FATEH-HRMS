@php
    $rows = [
        [
            'icon' => 'confirmation_number',
            'label' => 'شناسهٔ تیکت — پیشوند + سال‌ماه + شماره',
            'hint' => 'شناسه از کد واحد، سال و ماه میلادی و شمارهٔ تیکت ساخته می‌شود؛ مانند TN-۲۶۰۸-۰۰۰۱. اگر واحد مشخص نباشد، کد T به کار می‌رود. شناسه را می‌توانید کپی کنید. اگر تیکت پس از مهلت تمام شده باشد، شناسه در فهرست قرمز است.',
        ],
        [
            'icon' => 'category',
            'label' => 'نوع و حوزهٔ درخواست — قابل سفارشی‌سازی توسط واحد',
            'hint' => '«نوع درخواست» دستهٔ کلی و «حوزهٔ درخواست» موضوع دقیق‌تر است. گزینه‌های پیش‌فرض پشتیبانی، دسترسی و توسعه هستند. اگر واحد گزینه‌های اختصاصی داشته باشد، نوع و حوزهٔ همان واحد نمایش داده می‌شوند.',
        ],
        [
            'icon' => 'low_priority',
            'label' => 'اولویت — هنگام ثبت',
            'hint' => 'اولویت سه سطح «عادی»، «فوری» و «خیلی فوری» دارد. کاربر آن را هنگام ثبت انتخاب می‌کند و پس از ثبت نمی‌تواند تغییر دهد. مدیر دارای دسترسی ویرایش می‌تواند آن را عوض کند.',
        ],
        [
            'icon' => 'sync',
            'label' => 'وضعیت و چرخهٔ خودکار',
            'hint' => 'تیکت ابتدا «باز» است. با تعیین مسئول، «در حال بررسی» می‌شود و با برداشتن مسئول به «باز» برمی‌گردد. بستن، پایان قطعی رسیدگی است و تیکت دوباره باز نمی‌شود. اثربخشی اختیاری است؛ نتیجهٔ اقدام از روی گفتگو ساخته می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">support_agent</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">ثبت و پیگیری درخواست‌های پشتیبانی، دسترسی و توسعه در قالب تیکت</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        تیکت یعنی یک درخواست قابل پیگیری. کاربر آن را ثبت می‌کند، مسئول رسیدگی تعیین می‌شود، گفتگو و پیگیری انجام می‌شود و در پایان تیکت بسته می‌شود. درخواست‌دهنده پس از بسته‌شدن امتیاز رضایت می‌دهد. شما با توجه به دسترسی خود می‌توانید تیکت بسازید، مسئول و مهلت تعیین کنید، آن را ببندید یا خروجی بگیرید.
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
                واحد درخواست‌دهنده، واحد رسیدگی‌کننده و یادداشت رضایت در اطلاعات تیکت نگهداری می‌شوند. پیشوند شناسه و فیلتر «واحد رسیدگی‌کننده» بر اساس واحد رسیدگی‌کننده هستند.
            </p>
        </div>
    </div>
</div>
