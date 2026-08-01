<?php

namespace App\Services\Menu;

class BadgeLegendCatalog
{
    public static function groups(): array
    {
        return [
            'notifications' => ['label' => 'اطلاعیه و پیام', 'icon' => 'forum'],
            'content' => ['label' => 'اخبار و تقویم', 'icon' => 'event_note'],
            'opportunities' => ['label' => 'فرصت‌ها و پیشنهادها', 'icon' => 'lightbulb'],
            'tasks' => ['label' => 'وظایف و تیکت', 'icon' => 'checklist'],
            'compliance' => ['label' => 'اسناد و ارزیابی', 'icon' => 'fact_check'],
        ];
    }

    public static function get(string $key): ?array
    {
        return static::all()[$key] ?? null;
    }

    public static function grouped(): array
    {
        $items = static::all();

        return collect(static::groups())
            ->map(fn($group, $key) => [
                'id' => $key,
                'label' => $group['label'],
                'icon' => $group['icon'],
                'items' => collect($items)->where('group', $key)->values()->all(),
            ])
            ->values()
            ->all();
    }

    protected static function all(): array
    {
        return [
            'posts-controller' => [
                'group' => 'notifications', 'tone' => 'sapphire', 'icon' => 'campaign',
                'label' => 'اطلاعیهٔ خوانده‌نشده',
                'lights' => 'با انتشار اطلاعیهٔ تازه.',
                'clears' => 'با بازکردن همان اطلاعیه در تب «اطلاعات».',
                'surface' => 'نقطهٔ کنار تب «اطلاعات» در نوار کناری/پایین.',
            ],
            'contacts-controller' => [
                'group' => 'notifications', 'tone' => 'sapphire', 'icon' => 'chat',
                'label' => 'پیام خوانده‌نشده',
                'lights' => 'با دریافت پیام تازه در پیام‌رسان.',
                'clears' => 'با بازکردن همان گفتگو.',
                'surface' => 'نقطهٔ کنار آیتم «مخاطبین» در منوی همبرگری.',
            ],
            'feeds' => [
                'group' => 'content', 'tone' => 'sapphire', 'icon' => 'rss_feed',
                'label' => 'خبر تازه',
                'lights' => 'با انتشار خبر تازه در بخش اخبار.',
                'clears' => 'با بازکردن تب «اخبار»؛ خبر تازه‌تر دوباره روشنش می‌کند.',
                'surface' => 'نقطهٔ کنار تب «اخبار» در نوار کناری/پایین.',
            ],
            'shared-events' => [
                'group' => 'content', 'tone' => 'sapphire', 'icon' => 'event',
                'label' => 'رویداد مشترک نزدیک',
                'lights' => 'وقتی رویدادی مشترک در ۲۴ ساعت آینده باشد.',
                'clears' => 'با بازکردن تب «تقویم» در همان روز.',
                'surface' => 'نقطهٔ کنار تب «تقویم» در نوار کناری/پایین.',
            ],
            'special-days' => [
                'group' => 'content', 'tone' => 'sapphire', 'icon' => 'cake',
                'label' => 'مناسبت امروز',
                'lights' => 'وقتی امروز تولد یا سالگرد همکاریِ یکی از همکاران باشد.',
                'clears' => 'با بازکردن تب «تقویم» در همان روز.',
                'surface' => 'نقطهٔ کنار تب «تقویم» در نوار کناری/پایین.',
            ],
            'ads-controller' => [
                'group' => 'opportunities', 'tone' => 'amethyst', 'icon' => 'campaign',
                'label' => 'آگهی فعال',
                'lights' => 'وقتی در بخش آگهی‌ها موردی فعال باشد.',
                'clears' => 'با غیرفعال یا حذف‌شدن آگهی؛ برای همهٔ کاربران یکسان است و با مشاهده خاموش نمی‌شود.',
                'surface' => 'نقطهٔ کنار آیتم «فرصت‌های شغلی» در منوی همبرگری.',
            ],
            'suggestion-controller' => [
                'group' => 'opportunities', 'tone' => 'gold', 'icon' => 'lightbulb',
                'label' => 'پیشنهاد نیازمند تصمیم',
                'lights' => 'برای مدیرعامل: وقتی پیشنهادی منتظر تصمیم باشد. برای مدیر واحد: وقتی پیشنهادی نیازمند اعلام‌نظر واحد شما باشد یا پیشنهادی ارجاعی به واحد شما هنوز تکمیل نشده باشد. برای کارمند عادی روشن نمی‌شود.',
                'clears' => 'با ثبت تصمیم یا اعلام‌نظر لازم؛ صرفاً مشاهدهٔ صفحهٔ پیشنهادها خاموشش نمی‌کند.',
                'surface' => 'نقطهٔ کنار آیتم «پیشنهادات» در منوی همبرگری.',
            ],
            'tasks-controller' => [
                'group' => 'tasks', 'tone' => 'gold', 'icon' => 'checklist',
                'label' => 'وظیفهٔ انجام‌نشده',
                'lights' => 'وقتی وظیفه‌ای در ستون «انجام‌نشده» برد وظایف شما باشد.',
                'clears' => 'با انجام یا جابه‌جاکردن وظیفه؛ صرفاً مشاهده خاموشش نمی‌کند.',
                'surface' => 'نقطهٔ کنار آیتم «برد وظایف» در منوی همبرگری.',
            ],
            'ths-controller' => [
                'group' => 'tasks', 'tone' => 'gold', 'icon' => 'confirmation_number',
                'label' => 'تیکت نیازمند اقدام',
                'lights' => 'وقتی تیکت باز یا در حال بررسیِ مربوط به شما وجود داشته باشد.',
                'clears' => 'با اقدام روی تیکت (پاسخ‌دادن، تعیین مسئول، یا بستن آن)؛ صرفاً مشاهدهٔ صفحهٔ تیکتینگ خاموشش نمی‌کند.',
                'surface' => 'نقطهٔ کنار آیتم «تیکتینگ» در منوی همبرگری.',
            ],
            'dms-controller' => [
                'group' => 'compliance', 'tone' => 'gold', 'icon' => 'description',
                'label' => 'سند نیازمند تأیید/مطالعه',
                'lights' => 'وقتی سندی در مدیریت اسناد نیازمند تأیید یا مطالعهٔ شما باشد.',
                'clears' => 'با تأیید یا مطالعهٔ همان سند.',
                'surface' => 'نقطهٔ کنار آیتم «مدیریت اسناد» در منوی همبرگری.',
            ],
            'energy-controller' => [
                'group' => 'compliance', 'tone' => 'gold', 'icon' => 'bolt',
                'label' => 'ارزیابی انرژی ماهانه',
                'lights' => 'وقتی پرسشنامهٔ انرژی این ماه را تکمیل نکرده باشید.',
                'clears' => 'با تکمیل پرسشنامهٔ همان ماه.',
                'surface' => 'نقطهٔ کنار آیتم «پرسشنامه انرژی» در منوی همبرگری.',
            ],
            'channels-controller' => [
                'group' => 'notifications', 'tone' => 'sage', 'icon' => 'campaign',
                'label' => 'دعوت یا پیام تازهٔ کانال',
                'lights' => 'با دعوت‌شدن به کانالی تازه یا رسیدن پیام تازه در کانالی که عضو آن هستید. این مورد دات ندارد، فقط زنگوله.',
                'clears' => 'با بستن زنگوله؛ دکمهٔ «مشاهده» مستقیماً شما را به همان کانال می‌برد.',
            ],
            'gallery-controller' => [
                'group' => 'content', 'tone' => 'sage', 'icon' => 'photo_library',
                'label' => 'تصویر تازه در گالری',
                'lights' => 'با افزودن تصویر تازه به گالری. این مورد دات ندارد، فقط زنگوله.',
                'clears' => 'با بستن زنگوله؛ دکمهٔ «مشاهده» مستقیماً شما را به همان تصویر می‌برد.',
            ],
            'reports-controller' => [
                'group' => 'compliance', 'tone' => 'sage', 'icon' => 'show_chart',
                'label' => 'گزارش تازه',
                'lights' => 'با انتشار گزارش تازه. این مورد دات ندارد، فقط زنگوله.',
                'clears' => 'با بستن زنگوله؛ دکمهٔ «مشاهده» مستقیماً شما را به همان گزارش می‌برد.',
            ],
        ];
    }
}
