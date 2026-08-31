@php
    $statuses = [
        [
            'icon' => 'check_circle',
            'label' => 'فعال',
            'code' => 'active',
            'chip' => 'bg-success-100 text-success-700',
            'hint' => 'رزرو پابرجاست و منبع برای آن بازه در دسترس کاربر است. این تنها وضعیتی است که کاربر خودش می‌تواند لغو کند (دکمهٔ لغو در صفحهٔ کاربر فقط برای رزرو فعال ظاهر می‌شود).',
        ],
        [
            'icon' => 'autorenew',
            'label' => 'آزادشده',
            'code' => 'released',
            'chip' => 'bg-info-100 text-info-700',
            'hint' => 'ادمین با دکمهٔ «آزادسازی» رزرو را پیش از موعد پایان از حالت فعال خارج کرده. رزرو در سوابق می‌ماند، علیه سقف ماهانه می‌شمارد و کاربر دیگر نمی‌تواند آن را لغو کند. برای رزروهای بلندمدتِ در‌حال‌انجام، پایان به همین لحظه کوتاه می‌شود و باقی‌ماندهٔ بازه برای دیگران آزاد می‌گردد. برای رزروهای ساعتی، منبع تنها در صورت فعال‌بودنِ «مجوز رزرو در زمان آزادشده» (در قوانین رزرو) آزاد می‌شود؛ در غیر این صورت وضعیت آزادشده می‌ماند ولی منبع هنوز اشغال است.',
        ],
        [
            'icon' => 'cancel',
            'label' => 'لغو توسط کاربر',
            'code' => 'cancelled_user',
            'chip' => 'bg-warning-100 text-warning-700',
            'hint' => 'خودِ کاربر رزرو را لغو کرده است. دلیل لغو (در صورت ثبت) در فیلد «دلیل لغو» ثبت می‌شود و «لغوکننده» همان کاربر است.',
        ],
        [
            'icon' => 'shield_person',
            'label' => 'لغو توسط ادمین',
            'code' => 'cancelled_admin',
            'chip' => 'bg-danger-100 text-danger-700',
            'hint' => 'ادمین از این صفحهٔ مدیریت رزرو را لغو کرده است (دکمهٔ «لغو رزرو» + انتخاب دلیل). «لغوکننده» ادمینِ اقدامکننده ثبت می‌شود. این لغو دیگر به سقف لغوی ماهانهٔ کاربر نمی‌افزاید (آن سقف فقط لغوهای خودِ کاربر را می‌شمارد).',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">چهار وضعیت — رنگ و آیکون هرکدام در جدول همین است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر رزرو یکی از این چهار حالت را دارد. رنگ و آیکون ستون «وضعیت» در جدول/اینفولیست مستقیماً از همین تعریف می‌آید: <span class="font-black text-[var(--md-sys-color-primary)]">فعال</span> (سبز/تیک)، <span class="font-black text-[var(--md-sys-color-primary)]">آزادشده</span> (آبی/چرخش)، <span class="font-black text-[var(--md-sys-color-primary)]">لغو کاربر</span> (زرد/ضربدر) و <span class="font-black text-[var(--md-sys-color-primary)]">لغو ادمین</span> (قرمز/سپر). لغوها (کاربر یا ادمین) هر دو در دامنهٔ اسکوپ «لغو شده» قرار می‌گیرند و در زبانهٔ «لغو شده» تاریخچهٔ کاربر ظاهر می‌شوند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">flag</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">چهار وضعیت رزرو</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($statuses as $s)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $s['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                            <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $s['code'] }}</code>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $s['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">balance</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">قانون سقف ماهانه — کدام وضعیت‌ها می‌شمارند؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">add</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">فعال و آزادشده هر دو می‌شمارند</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">سقف ماهانه <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">max_per_user</code> مجموعِ رزروهای «فعال» و «آزادشده» در همان ماه را محدود می‌کند. پس آزادسازی یک رزرو، آن را از جای منبع بیرون می‌کند ولی جای سقف ماهانه را آزاد نمی‌کند — کاربر برای رزرو بعدی همان ماه باید یکی را لغو کند.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">remove</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">لغوها می‌شمارند ولی در سقفِ دیگری</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">رزروهای لغو‌شده از سقف <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">max_per_user</code> کسر نمی‌شوند (جایشان آزاد می‌شود)، ولی لغوِ کاربر به سقف جداگانهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">max_cancel_count</code> در پنجرهٔ سی‌روزه می‌افزاید. لغوی ادمین به این سقف اضافه نمی‌شود.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">lock</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">فقط «فعال» قابل‌لغو توسط کاربر است</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">دکمهٔ لغو در صفحهٔ کاربر و دکمهٔ «لغو رزرو» در این صفحه فقط روی رزرو با وضعیت فعال ظاهر می‌شوند. رزرو «آزادشده» دیگر فعال نیست و کاربر نمی‌تواند آن را لغو کند — فقط ادمین می‌تواند از این صفحه حذفش کند.</p>
                </div>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                وقتی کاربر می‌گوید «رزرو را لغو کردم ولی هنوز به سقف رسیده‌ام»، علت معمولاً رزروهای «آزادشده» است — آن‌ها فعال نیستند ولی جای سقف ماهانه را نگه می‌دارند.
            </p>
        </div>
    </div>
</div>