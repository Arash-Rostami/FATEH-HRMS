@php
    $ops = [
        [
            'icon' => 'person_add',
            'label' => 'ثبت تیکت — واحد درخواست‌دهنده خودکار پر می‌شود',
            'hint' => 'با انتخاب «درخواست‌دهنده»، فیلد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">extra.department</code> از روی پروفایلِ او خودکار پر می‌شود. انتخاب «واحد هدف» فهرست «نوع درخواست» و «حوزه درخواست» را فیلتر و مسئولِ قبلی را پاک می‌کند. فایل‌های ضمیمه تا سقف ' . convertToPersian('3') . ' فایل و ' . convertToPersian('10') . ' مگابایت با پیشوند <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">TENANT-THS-</code> (پیشوند = کلید مستأجر، مثلاً PERSOL) ذخیره می‌شوند و فقط در ثبت اولیه قابل ارسال‌اند.',
        ],
        [
            'icon' => 'sync',
            'label' => 'تخصیص مسئول — تغییر خودکار وضعیت',
            'hint' => 'فیلد «مسئول رسیدگی» زنده است: خالی‌کردنِ مسئول در وضعیت «در حال بررسی»، تیکت را به «باز» برمی‌گرداند و تعیین مسئول در وضعیت «باز»، تیکت را به «در حال بررسی» می‌برد. گزینه‌های مسئول به کاربرانِ فعالِ «واحد هدف» محدود است؛ مسئولِ فعلیِ قدیمی در فهرست می‌ماند تا ذخیره‌سازی خطا ندهد. در ویرایش، تغییرِ مسئول یک وظیفهٔ پیگیریِ متناظر را هم همگام می‌کند. وضعیت «در حال بررسی» بدون مسئول پذیرفته نمی‌شود.',
        ],
        [
            'icon' => 'insights',
            'label' => 'بستن تیکت — اثربخشی الزامی + نتیجهٔ خودکار',
            'hint' => 'برای گذاشتن وضعیت روی «بسته‌شده»، ثبتِ «اثربخشی» الزامی است؛ بدون آن ذخیره‌سازی با خطا رد می‌شود. در هنگام بستن، فیلد «نتیجه اقدام» خودکار از روی گفتگو (نام، تاریخ و متنِ هر پاسخ) ساخته و فقط‌خواندنی می‌شود. «اثربخشی» را مسئول رسیدگی یا مدیر واحد ثبت می‌کند. تیکتِ بسته‌شده قطعی است: دوباره باز نمی‌شود، مسئول و درخواست‌دهنده‌اش عوض نمی‌شود و تاریخ تکمیل خودکار ثبت می‌شود؛ تیکتی که درخواست‌دهنده لغو کرده امتیاز رضایت نمی‌خواهد.',
        ],
        [
            'icon' => 'assignment',
            'label' => 'وظیفهٔ پیگیری همگام (TaskBoard)',
            'hint' => 'به‌محض تعیین مسئول، یک وظیفه (با وضعیت «انجام‌نشده» و مهلت تیکت) با <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">ticket_id</code> پیوندی در «وظیفه‌ها» ساخته می‌شود که مسئولِ همان تیکت را می‌گیرد. تغییر مسئول از طرف ادمین، مسئولِ آن وظیفه را هم به‌روزرسانی می‌کند. برخلاف کاربر، ادمین می‌تواند اولویتِ تیکت را از فرم ویرایش هم تغییر دهد؛ این تغییر اولویت و جایگاهِ وظیفهٔ پیوندی را نیز خودکار همگام می‌کند. اگر مسئول برداشته شود (رها کردن، ارجاع به واحد دیگر یا بازگرداندن وضعیت به «باز») وظیفهٔ پیگیری برداشته می‌شود و با تعیین مسئول تازه دوباره می‌آید؛ بستن تیکت آن را انجام‌شده می‌کند و حذف تیکت، وظیفه و پاسخ‌ها را هم حذف می‌کند. وظیفهٔ پیوندی در پنل ادمین قابل ویرایش/حذف مستقیم نیست. گفتگوی تیکت همچنان فقط از طریق همین تیکت انجام می‌شود، نه از طریق وظیفه.',
        ],
        [
            'icon' => 'calendar_today',
            'label' => 'مهلت رسیدگی — تاریخ جلالی + ساعت',
            'hint' => 'مهلت از دو فیلد تاریخ (جلالی) و ساعت جدا ساخته می‌شود و در ذخیره با هم ترکیب می‌شود (ساعت پیش‌فرض ' . convertToPersian('08:00') . '). رنگ ستون مهلت: قرمز برای گذشته یا دیر‌کرد، سبز برای در‌مهلت، آبی برای نرسیده.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'دکمهٔ «خروجی Excel» بالای فهرست همهٔ تیکت‌های فهرستِ فعلی (با فیلترها و زبانهٔ انتخابی، همهٔ واحدها) را کامل صادر می‌کند و اکشن گروهی «خروجی Excel» فقط تیکت‌های انتخاب‌شده را؛ حوزه و نوع اختصاصی با برچسب واقعی، واحدها، شرح، گفتگو و پیوست‌ها هم در خروجی هستند. ستون‌ها: شناسه، وضعیت، اولویت، نوع، حوزه، درخواست‌دهنده، واحد، واحد هدف، موضوع، شرح، یادداشت‌ها، مسئول، مهلت، تکمیل، اثربخشی، رضایت و توضیح آن، نتیجهٔ اقدام، گفتگو، پیوست‌ها و تاریخ ثبت/بروزرسانی. فرمت‌های Excel و CSV هر دو در دسترس‌اند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">عملیات مدیریتی شما روی تیکت‌ها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        شما می‌توانید تیکت بسازید، مسئول تعیین کنید، مهلت بگذارید، اثربخشی را بازبینی کنید، تیکت را ببندید و خروجی اکسل بگیرید. چرخهٔ وضعیت با تخصیص مسئول و بستن تیکت خودکار می‌چرخد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اکشن‌های فرم و ویرایش</p>
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
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $op['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-error)]">warning</span>
                «گفتگو و اقدامات» در صفحهٔ ویرایش یک مؤلفهٔ زندهٔ پنل کاربری است که دسترسی‌پذیری‌اش را از <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">TicketAccessPolicy::canView</code> می‌گیرد — ادمینی که درخواست‌دهنده، مسئول یا مدیرِ واحدِ هدفِ تیکت نباشد، هنگام باز کردنِ صفحهٔ ویرایش با خطای ۴۰۳ مواجه می‌شود.
            </p>
        </div>
    </div>
</div>