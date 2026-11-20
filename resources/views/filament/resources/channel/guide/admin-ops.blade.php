@php
    $ops = [
        [
            'icon' => 'view_carousel',
            'label' => 'مشاهده و ویرایش گروه',
            'hint' => 'دکمهٔ «مشاهده» صفحهٔ جزئیات گروه را باز می‌کند (نام، کد گروه، نوع، مالک، تعداد اعضا و پیام‌ها، توضیحات و وضعیت حذف خودکار). دکمهٔ «ویرایش» فرم ویرایش را باز می‌کند — می‌توانید نام، کد گروه، نوع و مالک را تغییر دهید. تغییر نوع از خصوصی به عمومی، گروه را در فهرست «کاوش» کاربران قرار می‌دهد.',
        ],
        [
            'icon' => 'group',
            'label' => 'مدیریت اعضا',
            'hint' => 'در ویرایش گروه، بخش «اعضا» را باز کنید. می‌توانید اعضا را اضافه یا حذف کنید. برای مدیر کردن یک نفر، او باید فعال باشد و دست‌کم یک بار وارد گروه شده باشد. نقش مالک و مدیران گروه پروژه از اینجا تغییر نمی‌کند.',
        ],
        [
            'icon' => 'chat',
            'label' => 'بازبینی پیام‌ها',
            'hint' => 'بخش «پیام‌ها» همهٔ پیام‌های گروه را نشان می‌دهد — فرستنده، متن، وضعیت ویرایش، تاریخ و وضعیت حذف خودکار. می‌توانید پیام را مشاهده، ویرایش، حذف یا بازگردانی کنید. پیام‌های حذف‌شده پس از ۳۰ روز به‌صورت دائمی پاک می‌شوند و همراهشان فایل‌های ضمیمه هم حذف می‌شود.',
        ],
        [
            'icon' => 'lock',
            'label' => 'گروه دارای کلید مالک',
            'hint' => 'گروه در فهرست دیده می‌شود، اما برای دیدن جزئیات، اعضا و پیام‌ها باید کلید را وارد کنید. کلید را از مالک بگیرید. پس از ورود کلید درست، دسترسی تا پایان نشست برقرار می‌ماند.',
        ],
        [
            'icon' => 'auto_delete',
            'label' => 'حذف نرم، بازگردانی و حذف دائمی',
            'hint' => 'گروه حذف‌شده را تا پیش از پاک شدن دائمی می‌توانید بازگردانید. پس از ۳۰ روز، پاک‌سازی خودکار می‌تواند گروه و پیوست‌های آن را برای همیشه حذف کند.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'با عملیات گروهی «خروجی اکسل» می‌توانید گروه‌های انتخاب‌شده را به‌صورت فایل اکسل صادر کنید — شامل نام، شناسه، نوع، مالک، شمارش اعضا/پیام‌ها و تاریخ‌ها.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">عملیات مدیریتی شما روی گروه‌ها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        شما به‌عنوان ادمین می‌توانید گروه‌ها را مشاهده و ویرایش کنید، اعضا وپیام‌ها را بازبینی کنید، گروه‌های نامناسب را حذف موقت کنید و در صورت نیاز بازگردانید. ساخت گروهِ جدید از این صفحه ممکن نیست — کاربران خودشان گروه می‌سازند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اکشن‌های ردیف و بخش</p>
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
                حذفِ یک گروه از طرف ادمین، اعضا را از پنل کاربری‌شان خارج نمی‌کند — گروه دیده نمی‌شود ولی اطلاعات عضویت تا زمان حذف دائمی نگه داشته می‌شود.
            </p>
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2 mt-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                بخش «پیام‌ها» مجوز جداگانه ندارد و برای هر کسی که صفحهٔ گروه را باز کند در دسترس است؛ اما بخش «اعضا» به مجوز «مشاهدهٔ» ماژول کاربر نیاز دارد.
            </p>
        </div>
    </div>
</div>
