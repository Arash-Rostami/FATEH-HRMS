@php
    $keys = [
        [
            'key'   => 'window_days',
            'icon'  => 'calendar_month',
            'title' => 'بازه مجاز پیش‌رزرو (روز)',
            'ctrl'  => 'تا چند روز آینده امکان رزرو هست؛ مهره‌ی تقویم جلالی کاربر را به همین بازه محدود می‌کند.',
            'bite'  => 'اگر کاربر تاریخی دورتر از این تعداد روز انتخاب کند، خطای ERR-003 می‌بیند.',
            'chip'  => '۰ تا ۳۶۵',
        ],
        [
            'key'   => 'window_hours',
            'icon'  => 'schedule',
            'title' => 'حداقل زمان قبل از رزرو (ساعت)',
            'ctrl'  => 'چند ساعت قبل از شروع تایمِ امروز، ثبت رزرو بسته می‌شود (اعلام قبلی حداقل).',
            'bite'  => 'اگر کاربر تایم نزدیک‌تر از این ساعت‌ها را رزرو کند، خطای ERR-004 می‌بیند.',
            'chip'  => '۰ تا ۷۲',
        ],
        [
            'key'   => 'allowed_days',
            'icon'  => 'date_range',
            'title' => 'روزهای مجاز هفته',
            'ctrl'  => 'لیست چک‌باکس از شنبه تا جمعه؛ فقط در این روزها رزرو باز است.',
            'bite'  => 'رزرو در روزی که تیک نخورده باشد، خطای ERR-005 می‌دهد. خالی گذاشتن = همه روزها مجاز.',
            'chip'  => 'آرایه روزها',
        ],
        [
            'key'   => 'allowed_hours_start',
            'icon'  => 'play_circle',
            'title' => 'ساعت شروع فعالیت',
            'ctrl'  => 'مرز پایین شبکه‌ی تایم ۳۰ دقیقه‌ای؛ قبل از این ساعت رزرو باز نیست. با فیلد پایان در کلید allowed_hours ذخیره می‌شود.',
            'bite'  => 'رزرو قبل از این ساعت → خطای ERR-009. پشتیبانی از شیفت شبانه (شروع > پایان).',
            'chip'  => 'HH:MM',
        ],
        [
            'key'   => 'allowed_hours_end',
            'icon'  => 'stop_circle',
            'title' => 'ساعت پایان فعالیت',
            'ctrl'  => 'مرز بالای شبکه‌ی تایم؛ بعد از این ساعت رزرو باز نیست. با فیلد شروع در کلید allowed_hours ذخیره می‌شود.',
            'bite'  => 'رزرو بعد از این ساعت → خطای ERR-009. در رزرو تمام‌روز این قانون نادیده گرفته می‌شود.',
            'chip'  => 'HH:MM',
        ],
        [
            'key'   => 'min_duration_minutes',
            'icon'  => 'compress',
            'title' => 'حداقل مدت رزرو (دقیقه)',
            'ctrl'  => 'کف زمان یک جلسه؛ رزرو کوتاه‌تر از این رد می‌شود.',
            'bite'  => 'مدت کمتر از این مقدار → خطای ERR-007. در رزرو تمام‌روز بررسی نمی‌شود.',
            'chip'  => 'دقیقه',
        ],
        [
            'key'   => 'max_duration_minutes',
            'icon'  => 'expand',
            'title' => 'حداکثر مدت رزرو (دقیقه)',
            'ctrl'  => 'سقف زمان یک جلسه؛ رزرو بلندتر از این رد می‌شود.',
            'bite'  => 'مدت بیشتر از این مقدار → خطای ERR-008. در رزرو تمام‌روز بررسی نمی‌شود.',
            'chip'  => 'دقیقه',
        ],
        [
            'key'   => 'max_per_user',
            'icon'  => 'counter_4',
            'title' => 'سقف رزرو همزمان',
            'ctrl'  => 'هر کاربر در یک ماه چند رزرو فعال می‌تواند داشته باشد. شمارش شامل رزروهای «فعال» و «آزادشده» در همان ماه/سال است.',
            'bite'  => 'رسیدن به این سقف → خطای ERR-012 و مسدود شدن ثبت رزرو جدید تا پایان ماه یا لغو یکی.',
            'chip'  => 'ماهانه',
        ],
        [
            'key'   => 'max_cancel_count',
            'icon'  => 'block',
            'title' => 'سقف مجاز لغو',
            'ctrl'  => 'حداکثر تعداد لغو در ۳۰ روز اخیر. وقتی کاربر به این تعداد برسد، هم لغو جدید بسته می‌شود و هم ثبت رزرو جدید.',
            'bite'  => 'لغو بیش از حد → خطای ERR-017 در زمان لغو و ERR-011 در زمان ثبت رزرو جدید.',
            'chip'  => '۳۰ روز',
        ],
        [
            'key'   => 'allow_repeat',
            'icon'  => 'repeat',
            'title' => 'مجوز رزرو دوره‌ای',
            'ctrl'  => 'فعال = کاربر می‌تواند رزرو را به‌صورت تکرارشونده ثبت کند. خاموش = فقط رزرو یکباره.',
            'bite'  => 'خاموش بودن و درخواست تکرار → خطای ERR-010. پیش‌فرض روشن.',
            'chip'  => 'بله/خیر',
        ],
        [
            'key'   => 'allow_partial_cancel',
            'icon'  => 'content_cut',
            'title' => 'مجوز لغو جزئی',
            'ctrl'  => 'فعال = لغو فقط همان occurrence انتخاب‌شده. خاموش = لغو کل سری رزروهای دوره‌ای فعال.',
            'bite'  => 'کاربر زمانی که کل سری لغو می‌شود را در تب رزرو می‌بیند؛ کد خطا ندارد بلکه رفتار لغو را تغییر می‌دهد. پیش‌فرض روشن.',
            'chip'  => 'بله/خیر',
        ],
        [
            'key'   => 'allow_overlap_release',
            'icon'  => 'layers',
            'title' => 'مجوز رزرو در زمان آزادشده',
            'ctrl'  => 'فعال = روی زمان رزروهای «آزادشده» (Released) هم می‌توان رزرو زد. خاموش = فقط زمان کاملاً خالی.',
            'bite'  => 'وقتی خاموش باشد و زمان قبلاً آزاد شده باشد، خطای ERR-013. پیش‌فرض خاموش.',
            'chip'  => 'بله/خیر',
        ],
        [
            'key'   => 'allow_full_day',
            'icon'  => 'calendar_today',
            'title' => 'مجوز رزرو تمام‌روز',
            'ctrl'  => 'فعال = کاربر می‌تواند کل روز را رزرو کند. خاموش = فقط بازه‌ی ساعتی. نوع «ملاقات» ذاتاً غیر تمام‌روز است.',
            'bite'  => 'درخواست تمام‌روز وقتی خاموش باشد → خطای ERR-006. پیش‌فرض روشن.',
            'chip'  => 'بله/خیر',
        ],
        [
            'key'   => 'requires_approval',
            'icon'  => 'verified_user',
            'title' => 'تاییدیه ادمین',
            'ctrl'  => 'فعلاً نمایشی و غیرفعال است؛ هنوز در موتور اعتبارسنجی اعمال نمی‌شود.',
            'bite'  => 'بدون اثر در جریان کاربر؛ برای نسخه‌ی آینده آماده شده است.',
            'chip'  => 'غیرفعال',
        ],
    ];
@endphp

<div class="flex flex-col gap-4" dir="rtl">
    @foreach($keys as $k)
        <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-surface)] p-5 shadow-md shadow-[var(--md-sys-color-shadow)]/5">
            <span class="shrink-0 material-symbols-rounded text-[26px] text-[var(--md-sys-color-primary)] mt-0.5">{{ $k['icon'] }}</span>
            <div class="flex-1 flex flex-col gap-2.5">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <h3 class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">{{ $k['title'] }}</h3>
                    <code class="px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $k['key'] }}</code>
                </div>
                <div class="flex flex-col gap-1.5">
                    <p class="text-[12.5px] leading-relaxed font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="font-black text-[var(--md-sys-color-primary)]">کنترل:</span> {{ $k['ctrl'] }}
                    </p>
                    <p class="text-[12.5px] leading-relaxed font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="font-black text-[var(--md-sys-color-error)]">در جریان کاربر:</span> {{ $k['bite'] }}
                    </p>
                </div>
                <span class="self-start text-[10px] font-bold px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]">{{ convertToPersian($k['chip']) }}</span>
            </div>
        </div>
    @endforeach
</div>