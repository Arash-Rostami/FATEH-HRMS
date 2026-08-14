@php
    $fields = [
        [
            'icon' => 'person',
            'label' => 'کاربر اختصاصی (user_id)',
            'tag' => 'مخاطب',
            'hint' => 'اگر خالی بگذارید، این آنبوردینگ برای «همه کاربران» نمایش داده می‌شود (مخاطب عمومی). اگر یک کاربر خاص را انتخاب کنید، نسخهٔ اختصاصی همان کاربر می‌شود. کلیدِ تصمیم‌گیری در پنل کاربری همین فیلد است.',
        ],
        [
            'icon' => 'toggle_on',
            'label' => 'فعال (is_active)',
            'tag' => 'کلید',
            'hint' => 'فقط یک آنبوردینگ فعال برای هر مخاطب مجاز است (عمومی یا کاربر خاص). وقتی رکوردی را فعال ذخیره می‌کنید، بقیهٔ رکوردهای فعالِ همان مخاطب به‌صورت خودکار غیرفعال می‌شوند. این اعمال فقط هنگام ذخیره از فرم انجام می‌شود — تغییر سریع ستون در جدول این بررسی را اجرا نمی‌کند.',
        ],
        [
            'icon' => 'waving_hand',
            'label' => 'خوش‌آمدگویی (welcome)',
            'tag' => 'محتوا',
            'hint' => 'پیام خوش‌آمدگویی که جدیدالاستخدامان هنگام اولین ورود می‌بینند. ویرایشگر غنی با رنگِ متن، جدول و تیتر است؛ تنها فیلدِ اجباریِ محتواست.',
        ],
        [
            'icon' => 'flag',
            'label' => 'مأموریت / چشم‌انداز / برنامه زمانی',
            'tag' => 'محتوا',
            'hint' => 'سه فیلد متنیِ غنی و اختیاری: مأموریت سازمان، چشم‌انداز بلندمدت، و برنامهٔ زمانی دورهٔ آشنایی. هر کدام که خالی باشد، کارتِ مربوطه در پنل کاربر نمایش داده نمی‌شود.',
        ],
        [
            'icon' => 'videocam',
            'label' => 'ویدیوها (videos)',
            'tag' => 'آرایه‌ای',
            'hint' => 'Repeater از ویدیوها: عنوان، فایل ویدیو (دیسک public، مسیر onboarding/video، حداکثر ۹۵ مگابایت)، تصویر بندانگشتی (حداکثر ۱ مگابایت) و مدت زمان دلخواه. ترتیب قابل تغییر است.',
        ],
        [
            'icon' => 'menu_book',
            'label' => 'راهنماها (guides)',
            'tag' => 'آرایه‌ای',
            'hint' => 'Repeater از مستندات قابل دانلود: عنوان و فایل (PDF/Word/Excel، مسیر onboarding/guides، حداکثر ۴۹ مگابایت). پسوند و حجم فایل هنگام ذخیره از دیسک خوانده و خودکار پر می‌شود.',
        ],
        [
            'icon' => 'dashboard_customize',
            'label' => 'بخش‌های اضافی (extras)',
            'tag' => 'آرایه‌ای',
            'hint' => 'Repeater از کارت‌های متغیر: کلید (مثل parking)، عنوان نمایشی و محتوای غنی. ۴۵ کلید از پیش‌تعریف‌شده در ۹ دسته موجود است؛ با «افزودن سریع از الگوها» می‌توانید چند مورد را یکجا اضافه کنید.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«آنبوردینگ» صفحهٔ آشنایی کارکنان جدید با سازمان است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف یک پکیج محتوایی است که در زبانهٔ «آنبوردینگ» صفحهٔ پروفایل کاربر به نمایش درمی‌آید: پیام خوش‌آمد، ویدیوها، مأموریت/چشم‌انداز، مستندات، برنامهٔ روز اول و کارت‌های اضافی. مخاطبِ هر رکورد یا «همه کاربران» است (user_id خالی) یا یک کاربر خاص. کاربر فقط یک نسخه می‌بیند: نسخهٔ اختصاصیِ خودش (اگر باشد) وگرنه نسخهٔ عمومی.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای هر رکورد آنبوردینگ</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($fields as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                                {{ $f['tag'] }}
                            </span>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                مدل ذخیره‌سازی: videos و guides به‌صورت Collection و extras به‌صورت ArrayObject در پایگاه‌داده می‌نشینند — بدون مهاجرت، فقط با همین Repeater‌ها اداره می‌شوند.
            </p>
        </div>
    </div>
</div>