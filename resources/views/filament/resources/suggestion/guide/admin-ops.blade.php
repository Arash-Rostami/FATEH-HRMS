@php
    $ops = [
        [
            'icon' => 'rate_review',
            'label' => 'ثبت بازخورد (Header Action)',
            'hint' => 'روی صفحهٔ «مشاهده»، دکمهٔ «بازخورد» فقط برای سرپرست واحدی ظاهر می‌شود که واحدش در مرحلهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">team_remarks</code> (واحد ثبت‌کننده = اولین واحد ذی‌نفع) یا <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">dept_remarks</code> (واحد او در فهرست ذی‌نفعان) است و هنوز بازخورد موافق/نیمه‌موافق/مخالف نداده. مدیریت ارشد این دکمه را نمی‌بیند.',
        ],
        [
            'icon' => 'gavel',
            'label' => 'تصمیم نهایی (Header Action)',
            'hint' => 'دکمهٔ «تصمیم» فقط برای «تصمیم‌گیرندهٔ ارشد» (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">isSeniorDecisionMaker</code>) و فقط در مرحلهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">awaiting_decision</code> ظاهر می‌شود. سه گزینه: پذیرش (با ارجاع اختیاری واحدها + دستورالعمل)، رد، یا درخواست تکمیل مجدد. ثبت تصمیم، بازخورد واحد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">MA</code> را در جدول بررسی‌ها می‌نویسد.',
        ],
        [
            'icon' => 'task_alt',
            'label' => 'تکمیل اقدام واحد (Header Action)',
            'hint' => 'دکمهٔ «تکمیل اقدام» فقط برای سرپرست واحدی ظاهر می‌شود که توسط مدیریت ارشد برای اجرای اقدام ارجاع شده باشد و ردیف بررسیِ واحدش هنوز <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">complete</code> نشده. تکمیل همهٔ واحدهای ارجاع‌شده، مرحله را به <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">closed</code> می‌برد.',
        ],
        [
            'icon' => 'forum',
            'label' => 'مدیریت ارتباط «بررسی‌ها»',
            'hint' => 'روی صفحهٔ ویرایش، مدیریت ارتباط «بررسی‌ها» همهٔ ردیف‌های <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">Review</code> را نشان می‌دهد: بررسی‌کننده، واحد، نوع بازخورد، وضعیت تکمیل، توضیحات، اقدامات و تاریخ. این ردیف‌ها توسط اکشن‌های بازخورد/تصمیم/تکمیل نوشته می‌شوند — ساخت دستی بررسی از این جدول متداول نیست.',
        ],
        [
            'icon' => 'summarize',
            'label' => 'اینفولیست دو‌زبانه‌ای',
            'hint' => 'صفحهٔ «مشاهده» دو زبانه دارد: «بررسی کلی» (تایم‌لاین روند + شناسه، مرحله، ثبت‌کننده، واحد ثبت‌کننده، تکمیل شخصی، ارسال به مدیریت ارشد، اهداف، قواعد، واحدها، مهلت و تاریخ‌ها + شرح و پیوست) و «بررسی‌ها» (شمارش موافق/نیمه‌موافق/مخالف + تایم‌لاین بازخوردها + بخش تصمیم شامل یادداشت، واحدهای ارجاع‌شده و دستورالعمل).',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'با اکشن گروهی «خروجی Excel» می‌توانید پیشنهادهای انتخاب‌شده را صادر کنید. ستون‌ها و قالب از <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">SuggestionExporter</code> می‌آید.',
        ],
        [
            'icon' => 'search',
            'label' => 'جستجوی سراسری',
            'hint' => 'جستجوی سراسری پنل در عنوان، شرح و نام ثبت‌کننده می‌گردد و در نتیجه نام ثبت‌کننده و مرحله را نشان می‌دهد. کلیک روی نتیجه به صفحهٔ «مشاهده» می‌رود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">عملیات مدیریتی شما روی پیشنهادها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        سه اکشن اصلی روی صفحهٔ «مشاهده» — بازخورد، تصمیم و تکمیل اقدام — بر اساس نقش کاربر و مرحلهٔ پیشنهاد ظاهر می‌شوند. این اکشن‌ها از همان <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">SuggestionAccessPolicy</code>ای استفاده می‌کنند که پنل کاربری استفاده می‌کند، پس رفتار ادمین و کاربر یکسان است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اکشن‌های صفحهٔ مشاهده و مدیریت ارتباط</p>
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
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">tips_and_updates</span>
                ویرایش مستقیم مرحله از فرم ادمین توصیه نمی‌شود — <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">syncStage</code> در اولین بازخورد بعدی آن را بازنویسی می‌کند.
            </p>
        </div>
    </div>
</div>