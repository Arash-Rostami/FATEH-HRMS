@php
    $ops = [
        ['icon' => 'view_carousel', 'label' => 'مشاهدهٔ پاسخ‌نامه', 'hint' => 'دکمهٔ «مشاهده» اینفولیست را باز می‌کند: کاربر، تاریخ تکمیل و ثبت، امتیاز کلی (به‌صورت <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">X / 16</code>)، امتیاز چهار بعد با رنگ‌بندی، بازپخشِ پرسش‌ها و پاسخ‌های کاربر، و میانگین‌های کش‌شدهٔ آن کاربر. صفحهٔ ویرایش وجود ندارد — منبع، فقط خواندنی است.'],
        ['icon' => 'psychology', 'label' => 'بازپخش پرسش‌ها و پاسخ‌ها', 'hint' => 'در صفحهٔ مشاهده، بخش «جزئیات پرسش‌ها» پرسش‌های همان ماه را از بانک چرخشی بازسازی می‌کند و پاسخ‌های ثبت‌شدهٔ کاربر را کنار آن نشان می‌دهد. اگر کاربر پاسخی ثبت نکرده باشد، آن بخش خالی می‌ماند.'],
        ['icon' => 'monitoring', 'label' => 'میانگین‌های کش‌شدهٔ کاربر', 'hint' => 'بخش «خلاصه پاسخ‌ها» میانگین امتیازهای چهار بعد و کلیِ آن کاربر را از کش می‌خواند (یک ساعت اعتبار). این کش با ثبت یا حذفِ پاسخ‌نامهٔ جدید برای همان کاربر خودکار باطل می‌شود.'],
        ['icon' => 'auto_delete', 'label' => 'حذف ردیف', 'hint' => 'دکمهٔ «حذف» در هر ردیف، پاسخ‌نامه را حذف می‌کند. حذف، کشِ میانگینِ آن کاربر را هم باطل می‌کند. از آنجا که قفل ' . convertToPersian('25') . ' روزه بر اساس تاریخ تکمیل کار می‌کند، حذفِ آخرین پاسخ‌نامه ممکن است به کاربر اجازهٔ ثبتِ دوباره بدهد.'],
        ['icon' => 'download', 'label' => 'خروجی اکسل', 'hint' => 'با اکشن گروهی «خروجی Excel» می‌توانید پاسخ‌نامه‌های انتخاب‌شده را صادر کنید — شامل شناسه، کاربر، چهار امتیاز بعد، امتیاز کلی، اندیس ماه و تاریخ‌ها. کوئریِ خروجی رابطهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">user</code> را eager-load می‌کند.'],
        ['icon' => 'person_search', 'label' => 'جستجوی سراسری', 'hint' => 'جستجوی سراسری بر اساس نام کاربر (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">user.name</code>) انجام می‌شود و در نتیجه امتیاز کلی و تاریخ تکمیل نشان داده می‌شود. کوئریِ اصلی منبع، <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">user</code> را eager-load می‌کند تا ستون کاربر بدون پرسش N+1 رندر شود.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">عملیات مدیریتی شما روی پاسخ‌نامه‌ها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        این منبع فقط خواندنی است — دکمهٔ «ساخت» وجود ندارد و صفحهٔ ویرایش هم تعریف نشده. شما فقط پاسخ‌نامه‌ها را مشاهده و در صورت نیاز حذف می‌کنید و خروجی اکسل می‌گیرید. ثبت پاسخ از طرف ادمین ممکن نیست.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اکشن‌های ردیف و صفحه</p>
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
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر می‌گوید «پرسشنامه باز نمی‌شود»، احتمالاً در دورهٔ {{ convertToPersian('25') }} روزه است؛ آخرین پاسخ‌نامهٔ او را در این صفحه بیابید و تاریخ تکمیل را بازبینی کنید.
            </p>
        </div>
    </div>
</div>