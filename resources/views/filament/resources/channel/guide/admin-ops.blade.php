@php
    $ops = [
        [
            'icon' => 'view_carousel',
            'label' => 'مشاهده و ویرایش گروه',
            'hint' => 'دکمهٔ «مشاهده» اینفولیست گروه را باز می‌کند (نام، شناسه، نوع، مالک، شمارش اعضا/پیام‌ها، توضیحات و وضعیت حذف خودکار). دکمهٔ «ویرایش» فرم ویرایش را باز می‌کند — می‌توانید نام، شناسه (slug)، نوع و مالک را تغییر دهید. تغییر نوع خصوصی→عمومی، گروه را در فهرست «کاوش» کاربران قرار می‌دهد.',
        ],
        [
            'icon' => 'group',
            'label' => 'مدیریت اعضا (RelationManager)',
            'hint' => 'روی صفحهٔ ویرایش گروه، دو مدیریت ارتباط ظاهر می‌شود. «اعضا» به شما اجازه می‌دهد کاربرانی را attach (افزودن) یا detach (حذف) کنید. مالکِ گروه از حذف‌شدن محافظت می‌شود — دکمهٔ «حذف عضو» برای ردیفِ مالک نمایش داده نمی‌شود. ستون «آخرین پیام خوانده‌شده» نشان می‌دهد هر عضو تا کجای گفتگو را دیده است.',
        ],
        [
            'icon' => 'chat',
            'label' => 'بازبینی پیام‌ها (RelationManager)',
            'hint' => 'مدیریت ارتباط «پیام‌ها» همهٔ پیام‌های گروه (حتی حذف‌شده‌های نرم) را نشان می‌دهد — فرستنده، متن، وضعیت ویرایش، تاریخ و وضعیت حذف خودکار. می‌توانید پیام را مشاهده، ویرایش، حذف نرم یا بازگردانی کنید. پیام‌های حذف‌شده پس از ۳۰ روز به‌صورت دائمی پاک می‌شوند و همراهشان فایل‌های ضمیمه هم حذف می‌شود.',
        ],
        [
            'icon' => 'auto_delete',
            'label' => 'حذف نرم، بازگردانی و حذف دائمی',
            'hint' => 'دکمهٔ «حذف» یک حذف نرم است (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">deleted_at</code> پر می‌شود). گروه‌های حذف‌شده با دکمهٔ «بازگرداندن» قابل بازیابی‌اند. پس از ۳۰ روز، Prunable گروه را به‌صورت دائمی حذف می‌کند و دایرکتوری ضمیمه‌های آن در Storage هم پاک می‌شود. ستون «وضعیت حذف خودکار» و فیلتر «در آستانه حذف» زمان حذف دائمی را نشان می‌دهند.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'با اکشن گروهی «خروجی Excel» می‌توانید گروه‌های انتخاب‌شده را به‌صورت فایل اکسل صادر کنید — شامل نام، شناسه، نوع، مالک، شمارش اعضا/پیام‌ها و تاریخ‌ها.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">عملیات مدیریتی شما روی گروه‌ها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        شما به‌عنوان ادمین می‌توانید گروه‌ها را مشاهده و ویرایش کنید، اعضا و پیام‌ها را بازبینی کنید، گروه‌های نامناسب را حذف نرم کنید و در صورت نیاز بازگردانید. ساخت گروهِ جدید از این صفحه ممکن نیست — کاربران خودشان گروه می‌سازند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اکشن‌های ردیف و مدیریت ارتباط</p>
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
                حذفِ یک گروه از طرف ادمین، اعضا را از پنل کاربری‌شان خارج نمی‌کند — گروه ناپدید می‌شود ولی ردیف‌های عضویت تا حذف دائمی در پایگاه داده باقی می‌مانند.
            </p>
        </div>
    </div>
</div>