@php
    $panels = [
        [
            'icon' => 'view_carousel',
            'label' => 'سه زبانهٔ اصلی صفحه',
            'hint' => '«تیکت جدید» برای ثبت درخواست و «تاریخچه» برای پیگیری است. «ارزیابی» زمانی ظاهر می‌شود که درخواست‌دهنده تیکت بسته‌شده‌ای بدون امتیاز رضایت داشته باشد؛ پس از ثبت امتیاز ناپدید می‌شود. تیکت لغوشده ارزیابی نمی‌خواهد.',
        ],
        [
            'icon' => 'add_box',
            'label' => 'ثبت تیکت',
            'hint' => 'کاربر واحد رسیدگی‌کننده، نوع، حوزه، اولویت، موضوع و شرح را مشخص می‌کند و می‌تواند حداکثر ۳ فایل پیوست کند. «نوشتن هوشمند» موضوع و شرح را پیشنهاد می‌دهد؛ کاربر باید متن را بررسی و جاهای خالی «____» را پر یا حذف کند. پس از بررسی پیشنهاد، کاربر باید جداگانه «ثبت درخواست» را بزند. متن و انتخاب‌های فرم به سرویس هوش مصنوعی فرستاده می‌شود.',
        ],
        [
            'icon' => 'history',
            'label' => 'تاریخچه و فیلتر «نیاز به اقدام من»',
            'hint' => 'فهرست عادی، تیکت‌های ثبت‌شدهٔ خود کاربر است. «نیاز به اقدام من» تیکت‌های در حال رسیدگی او و تیکت‌های خودش که منتظر امتیاز هستند را نشان می‌دهد. مدیر واحد، تیکت‌های باز واحد را هم می‌بیند. در واحد با تخصیص آزاد، اعضای فعال هم تیکت‌های بازِ دیگران را می‌بینند. «تیکت‌های واحد من» فقط برای مدیر واحد است.',
        ],
        [
            'icon' => 'forum',
            'label' => 'پنجرهٔ جزئیات — دو زبانه',
            'hint' => '«جزئیات» موضوع، شرح، پیوست‌ها و مرحلهٔ رسیدگی را نشان می‌دهد. در «پاسخ و پیگیری» می‌توان پیام فرستاد و اقدام‌های مجاز را انجام داد. «توضیحات» کنار زبانه‌ها، مشخصات کامل، مهلت، مسئول و فایل‌ها را باز می‌کند. همین گفتگو در صفحهٔ ویرایش مدیریت هم در دسترس است.',
        ],
        [
            'icon' => 'rate_review',
            'label' => 'امتیاز رضایت — پس از بستن',
            'hint' => 'درخواست‌دهنده پس از بسته‌شدن، از ۱ تا ۵ ستاره به نحوهٔ رسیدگی امتیاز می‌دهد؛ ۱ کمترین و ۵ بیشترین رضایت است. نوشتن توضیح اختیاری است. «رضایت» نظر درخواست‌دهنده دربارهٔ نحوهٔ رسیدگی است. «اثربخشی» نشان می‌دهد نتیجه چقدر مفید بوده است.',
        ],
        [
            'icon' => 'trending_up',
            'label' => 'انتقال اولویت به وظیفهٔ پیگیری',
            'hint' => 'اولویت تیکت به وظیفهٔ پیگیری آن هم منتقل می‌شود. کاربر پس از ثبت نمی‌تواند اولویت را عوض کند؛ مدیر دارای دسترسی ویرایش می‌تواند آن را تغییر دهد و وظیفه هم به‌روز می‌شود.',
        ],
    ];

    $roles = [
        [
            'icon' => 'person',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'درخواست‌دهنده',
            'hint' => 'تیکت را ثبت و پیگیری می‌کند. تا پیش از بسته‌شدن می‌تواند پیام بدهد یا درخواست را لغو کند. لغو، تیکت را برای همیشه می‌بندد. پس از پایان رسیدگی، امتیاز رضایت می‌دهد. پاسخ تازه، تعیین مسئول و بسته‌شدن به او اطلاع داده می‌شود.',
        ],
        [
            'icon' => 'shield_person',
            'chip' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
            'label' => 'مدیر واحد',
            'hint' => 'می‌تواند مسئول تعیین کند، پاسخ بدهد، تیکت را ببندد یا با نوشتن دلیل به واحد دیگری بفرستد. «بازگشت به صف» مسئول را برمی‌دارد و تیکت را به صف همان واحد برمی‌گرداند. دلیل این اقدام‌ها برای درخواست‌دهنده هم دیده می‌شود. اثربخشی اختیاری است.',
        ],
        [
            'icon' => 'engineering',
            'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            'label' => 'مسئول رسیدگی',
            'hint' => 'به درخواست پاسخ می‌دهد و پس از پایان رسیدگی تیکت را می‌بندد. ارسال پاسخ به‌تنهایی تیکت را نمی‌بندد. اگر نتواند ادامه دهد، دلیل را در کادر پاسخ می‌نویسد و «بازگشت به صف» را می‌زند. اثربخشی اختیاری است.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ تیکت چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربر از این صفحه درخواست ثبت می‌کند، پاسخ‌ها را می‌خواند و پس از پایان رسیدگی امتیاز می‌دهد. این راهنما کمک می‌کند بدانید هر نقش چه کاری می‌تواند انجام دهد.
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
