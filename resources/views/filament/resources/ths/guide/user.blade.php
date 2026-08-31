@php
    $panels = [
        [
            'icon' => 'view_carousel',
            'label' => 'سه زبانهٔ اصلی صفحه',
            'hint' => 'صفحهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">/ths</code> سه زبانه دارد: «تیکت جدید» (فرم ثبت)، «تاریخچه» (فهرست تیکت‌ها) و «ارزیابی». زبانهٔ «ارزیابی» فقط برای درخواست‌دهندهٔ یک تیکتِ بسته‌شده که هنوز امتیاز رضایت نداده ظاهر می‌شود و پس از ثبت امتیاز ناپدید می‌شود.',
        ],
        [
            'icon' => 'add_box',
            'label' => 'ثبت تیکت',
            'hint' => 'کاربر واحد هدف (اختیاری)، نوع درخواست، حوزه، اولویت، موضوع و شرح را وارد می‌کند و تا سه فایل ضمیمه می‌گذارد. گزینهٔ «پشتیبانی عمومی» تنها وقتی دیده می‌شود که هیچ واحدی حوزه‌های اختصاصی تعریف نکرده باشد؛ به‌محض اینکه حداقل یک واحد چنین کند، این گزینه خودکار حذف می‌شود.',
        ],
        [
            'icon' => 'history',
            'label' => 'تاریخچه و فیلتر «نیاز به اقدام من»',
            'hint' => 'در زبانهٔ «تاریخچه»، فهرست پیش‌فرض «تیکت‌های من» (تیکت‌هایی که کاربر خودش ثبت کرده) است. با روشن کردنِ «نیاز به اقدام من»، فهرست به تیکت‌های محول‌شده به کاربر (در حال بررسی) و — اگر مدیر واحد باشد — تیکت‌های بازِ بدون‌مسئولِ واحدش محدود می‌شود. جستجوی شناسه/موضوع/شرح در «تیکت‌های من» فعال است.',
        ],
        [
            'icon' => 'forum',
            'label' => 'مودال جزئیات — دو زبانه',
            'hint' => 'با کلیک روی یک تیکت، مودال با دو زبانه باز می‌شود: «جزئیات» (حوزه، وضعیت، اولویت، تاریخ، موضوع، شرح و فایل‌های ضمیمه) و «پاسخ و پیگیری» (گفتگو، تخصیص، اثربخشی و بستن). این مودال همان مؤلفهٔ «گفتگو و اقدامات» است که در صفحهٔ ویرایشِ ادمین هم کار می‌کند.',
        ],
        [
            'icon' => 'rate_review',
            'label' => 'امتیاز رضایت — پس از بستن',
            'hint' => 'درخواست‌دهنده پس از بسته‌شدن تیکت می‌تواند یک امتیاز ۱ تا ۵ ستاره و یک یادداشت اختیاری ثبت کند. امتیاز در <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">satisfaction_score</code> و یادداشت در <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">extra.satisfaction_comment</code> ذخیره می‌شوند.',
        ],
        [
            'icon' => 'trending_up',
            'label' => 'انتقال اولویت به وظیفهٔ پیگیری',
            'hint' => 'اولویتی که هنگام ثبت تیکت انتخاب شده، به وظیفهٔ خودکارِ متناظرش در «وظیفه‌ها» هم منتقل می‌شود و همین اولویت جایگاه کارت در ستون و نوار فوریت کناری آن را در تخته وظایف تعیین می‌کند (به الگوی «اولویت به‌عنوان یک سیگنال واقعی» در راهنمای وظیفه‌ها مراجعه کنید). کاربر پس از ثبت نمی‌تواند اولویت تیکت خودش را تغییر دهد؛ فقط ادمین از فرم ویرایش می‌تواند، و این تغییر بلافاصله اولویت و جایگاه وظیفهٔ پیوندی را هم به‌روزرسانی می‌کند.',
        ],
    ];

    $roles = [
        [
            'icon' => 'person',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'درخواست‌دهنده',
            'hint' => 'تیکت را ثبت کرده و پیگیر آن است. تا وقتی تیکت باز است می‌تواند در گفتگو پیام بدهد؛ پس از بستن، امتیاز رضایت می‌دهد. زنگوله با هر پاسخ تازه یا بسته‌شدن فعال می‌شود.',
        ],
        [
            'icon' => 'shield_person',
            'chip' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
            'label' => 'مدیر واحد',
            'hint' => 'تیکت به واحدش ارجاع شده. می‌تواند مسئول تعیین کند، خودش پاسخ دهد، اثربخشی ثبت کند و تیکت را ببندد. نشان کارتابل تا وقتی تیکتی از واحدش بدون مسئول باز باشد روشن می‌ماند.',
        ],
        [
            'icon' => 'engineering',
            'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            'label' => 'مسئول رسیدگی',
            'hint' => 'تیکت به او محول شده. به درخواست‌دهنده پاسخ می‌دهد، اثربخشی را ثبت می‌کند و تیکت را می‌بندد. نشان کارتابل تا وقتی تیکت محول‌شده‌اش بسته نشده روشن می‌ماند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ تیکتینگ چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/ths</code> کاربر یک مرکز پشتیبانی است — ثبت تیکت، پیگیریِ تیکت‌های قبلی و ارزیابیِ تیکت‌های بسته‌شده. وقتی کاربری از وضعیت تیکت یا دسترسی‌اش شکایت می‌کند، این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">widgets</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">قابلیت‌های پنل کاربر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($panels as $p)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $p['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $p['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $p['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">shield_person</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">سه نقش و دسترسی‌ها</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($roles as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $r['chip'] }}">
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
                گفتگوی هر تیکت پس از بسته‌شدن فقط قابل‌مطالعه است؛ پیام تازه‌ای ثبت نمی‌شود. جزئیاتِ کاملِ نقش‌ها در راهنمای گردش‌کارِ داخلِ پنل کاربری آمده است.
            </p>
        </div>
    </div>
</div>