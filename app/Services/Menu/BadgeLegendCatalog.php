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

    public static function subgroups(): array
    {
        return [
            'notifications' => [
                'direct' => ['label' => 'پیام مستقیم', 'icon' => 'campaign'],
                'channels' => ['label' => 'کانال‌ها', 'icon' => 'groups'],
            ],
            'content' => [
                'calendar' => ['label' => 'تقویم', 'icon' => 'event'],
                'media' => ['label' => 'اخبار و گالری', 'icon' => 'rss_feed'],
            ],
            'compliance' => [
                'tracking' => ['label' => 'پیگیری', 'icon' => 'fact_check'],
                'reports' => ['label' => 'گزارش', 'icon' => 'show_chart'],
                'tasksheet' => ['label' => 'تسک‌شیت', 'icon' => 'assignment_turned_in'],
            ],
            'tasks' => [
                'list' => ['label' => 'فهرست', 'icon' => 'checklist'],
                'deadline' => ['label' => 'سررسید', 'icon' => 'schedule'],
                'approval' => ['label' => 'تأیید', 'icon' => 'verified_user'],
                'projects' => ['label' => 'پروژه', 'icon' => 'workspaces'],
                'tickets' => ['label' => 'تیکت', 'icon' => 'confirmation_number'],
            ],
        ];
    }

    public static function grouped(): array
    {
        $items = static::all();
        $subgroupDefs = static::subgroups();

        return collect(static::groups())
            ->map(function ($group, $key) use ($items, $subgroupDefs) {
                $groupItems = collect($items)->where('group', $key);
                $defs = $subgroupDefs[$key] ?? null;

                return [
                    'id' => $key,
                    'label' => $group['label'],
                    'icon' => $group['icon'],
                    'items' => $groupItems->values()->all(),
                    'subgroups' => $defs ? collect($defs)
                        ->map(fn($def, $subId) => [
                            'id' => $subId,
                            'label' => $def['label'],
                            'icon' => $def['icon'],
                            'items' => $groupItems->where('subgroup', $subId)->values()->all(),
                        ])
                        ->values()
                        ->all() : [],
                ];
            })
            ->values()
            ->all();
    }

    protected static function all(): array
    {
        return [
            'posts-controller' => [
                'group' => 'notifications', 'subgroup' => 'direct', 'tone' => 'sapphire', 'icon' => 'campaign',
                'label' => 'اطلاعیهٔ خوانده‌نشده',
                'lights' => 'با انتشار اطلاعیهٔ تازه.',
                'clears' => 'با بازکردن همان اطلاعیه در تب «اعلانات».',
                'surface' => 'نقطهٔ کنار تب «اعلانات» در نوار کناری/پایین.',
            ],
            'contacts-controller' => [
                'group' => 'notifications', 'subgroup' => 'direct', 'tone' => 'sapphire', 'icon' => 'chat',
                'label' => 'پیام خوانده‌نشده',
                'lights' => 'با دریافت پیام تازه در پیام‌رسان.',
                'clears' => 'با بازکردن همان گفتگو.',
                'surface' => 'نقطهٔ کنار آیتم «مخاطبین» در منوی همبرگری.',
            ],
            'feeds' => [
                'group' => 'content', 'subgroup' => 'media', 'tone' => 'sapphire', 'icon' => 'rss_feed',
                'label' => 'خبر تازه',
                'lights' => 'با انتشار خبر تازه در بخش اخبار.',
                'clears' => 'با بازکردن تب «اخبار»؛ خبر تازه‌تر دوباره روشنش می‌کند.',
                'surface' => 'نقطهٔ کنار تب «اخبار» در نوار کناری/پایین.',
            ],
            'shared-events' => [
                'group' => 'content', 'subgroup' => 'calendar', 'tone' => 'sapphire', 'icon' => 'event',
                'label' => 'رویداد مشترک نزدیک',
                'lights' => 'وقتی رویدادی مشترک در ۲۴ ساعت آینده باشد.',
                'clears' => 'با بازکردن تب «تقویم» در همان روز.',
                'surface' => 'نقطهٔ کنار تب «تقویم» در نوار کناری/پایین.',
            ],
            'special-days' => [
                'group' => 'content', 'subgroup' => 'calendar', 'tone' => 'sapphire', 'icon' => 'cake',
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
                'group' => 'tasks', 'subgroup' => 'list', 'tone' => 'gold', 'icon' => 'checklist',
                'label' => 'وظیفهٔ انجام‌نشده',
                'lights' => 'وقتی وظیفه‌ای در ستون «انجام‌نشده» برد وظایف شما باشد.',
                'clears' => 'با انجام یا جابه‌جاکردن وظیفه؛ صرفاً مشاهده خاموشش نمی‌کند.',
                'surface' => 'نقطهٔ کنار آیتم «برد وظایف» در منوی همبرگری.',
            ],
            'tasks-deadline' => [
                'group' => 'tasks', 'subgroup' => 'list', 'tone' => 'gold', 'icon' => 'schedule',
                'label' => 'وظیفهٔ نزدیک به سررسید',
                'lights' => 'وقتی یکی از وظایف شما سررسید گذشته یا ظرف ۳ روز آینده باشد.',
                'clears' => 'با تغییر وضعیت یا مهلت همان وظیفه؛ صرفاً مشاهده خاموشش نمی‌کند.',
                'surface' => 'بدون نقطهٔ اختصاصی؛ فقط از طریق زنگولهٔ اعلان‌های بالای صفحه.',
            ],
            'tasks-controller:due-soon-edge' => [
                'group' => 'tasks', 'subgroup' => 'deadline', 'tone' => 'sage', 'icon' => 'schedule',
                'label' => 'کارت اعلان نزدیک‌شدن سررسید',
                'lights' => 'وقتی وظیفهٔ محول‌شده به شما کمتر از ۲۴ ساعت تا سررسید داشته باشد، کارت اعلانی پایین صفحه ظاهر می‌شود. این مورد دات ندارد، فقط کارت.',
                'clears' => 'با بستن کارت (×) پنهان می‌شود؛ دکمهٔ «مشاهده» مستقیماً شما را به همان وظیفه می‌برد.',
            ],
            'tasks-overdue-nudge' => [
                'group' => 'tasks', 'subgroup' => 'deadline', 'tone' => 'sage', 'icon' => 'event_busy',
                'label' => 'وظیفهٔ سررسید گذشته',
                'lights' => 'یک‌بار، وقتی وظیفهٔ محول‌شده به شما از سررسید خود بگذرد. این مورد دات ندارد، فقط زنگوله.',
                'clears' => 'با بستن زنگوله، یا خودکار وقتی وظیفه تکمیل/آرشیو شود یا مهلتش تغییر کند؛ هرگز دوباره ظاهر نمی‌شود مگر وظیفه دوباره سررسید بگذراند.',
            ],
            'tasks-approval-nudge' => [
                'group' => 'tasks', 'subgroup' => 'approval', 'tone' => 'sage', 'icon' => 'verified_user',
                'label' => 'وظیفهٔ منتظر تأیید',
                'lights' => 'وقتی در پروژه‌ای که «تأیید مدیر» آن فعال است، وظیفه‌ای به ستون انجام برود و شما مدیر آن پروژه باشید. پس از ۲۴ ساعت عبارت «یادآوری» و پس از ۴۸ ساعت «فوری» به عنوان اعلان اضافه می‌شود. این مورد دات ندارد، فقط زنگوله.',
                'clears' => 'با تأییدکردن وظیفه از طرف شما؛ با بستن زنگوله فقط پنهان می‌شود.',
            ],
            'tasks-pending-approval' => [
                'group' => 'tasks', 'subgroup' => 'approval', 'tone' => 'gold', 'icon' => 'task_alt',
                'label' => 'وظیفهٔ در انتظار تأیید (نشانگر کلی)',
                'lights' => 'وقتی در پروژه‌های تحت مدیریت شما حداقل یک وظیفهٔ انجام‌شدهٔ تأییدنشده وجود داشته باشد. این مورد دات ندارد، فقط زنگوله.',
                'clears' => 'با تأیید یا بازگشت وضعیت همان وظیفه؛ صرفاً مشاهدهٔ پروژه خاموشش نمی‌کند.',
            ],
            'projects-controller' => [
                'group' => 'tasks', 'subgroup' => 'projects', 'tone' => 'gold', 'icon' => 'workspaces',
                'label' => 'دعوت به پروژه',
                'lights' => 'با افزوده‌شدن شما به یک پروژهٔ تازه یا موجود. این مورد دات ندارد، فقط زنگوله.',
                'clears' => 'با بستن زنگوله؛ دکمهٔ «مشاهده» مستقیماً شما را به همان پروژه می‌برد.',
            ],
            'projects-controller:edge' => [
                'group' => 'tasks', 'subgroup' => 'projects', 'tone' => 'sage', 'icon' => 'group_add',
                'label' => 'کارت اعلان پروژه (دعوت/کانال بازنشده)',
                'lights' => 'با افزوده‌شدن شما به پروژه‌ای یا دعوت‌شدن به کانال پروژه‌ای که هنوز باز نکرده‌اید، کارت اعلانی پایین صفحه ظاهر می‌شود. این مورد دات ندارد، فقط کارت.',
                'clears' => 'با بستن کارت (×) پنهان می‌شود؛ روی همهٔ صفحات نمایش می‌یابد.',
            ],
            'ths-controller' => [
                'group' => 'tasks', 'subgroup' => 'tickets', 'tone' => 'gold', 'icon' => 'confirmation_number',
                'label' => 'تیکت نیازمند اقدام',
                'lights' => 'وقتی تیکت باز یا در حال بررسیِ مربوط به شما وجود داشته باشد.',
                'clears' => 'با اقدام روی تیکت (پاسخ‌دادن، تعیین مسئول، یا بستن آن)؛ صرفاً مشاهدهٔ صفحهٔ تیکتینگ خاموشش نمی‌کند.',
                'surface' => 'نقطهٔ کنار آیتم «تیکتینگ» در منوی همبرگری.',
            ],
            'dms-controller' => [
                'group' => 'compliance', 'subgroup' => 'tracking', 'tone' => 'gold', 'icon' => 'description',
                'label' => 'سند نیازمند تأیید/مطالعه',
                'lights' => 'وقتی سندی در مدیریت اسناد نیازمند تأیید یا مطالعهٔ شما باشد.',
                'clears' => 'با تأیید یا مطالعهٔ همان سند.',
                'surface' => 'نقطهٔ کنار آیتم «مدیریت اسناد» در منوی همبرگری.',
            ],
            'energy-controller' => [
                'group' => 'compliance', 'subgroup' => 'tracking', 'tone' => 'gold', 'icon' => 'bolt',
                'label' => 'ارزیابی انرژی ماهانه',
                'lights' => 'وقتی پرسشنامهٔ انرژی را طی ۲۵ روز اخیر تکمیل نکرده باشید.',
                'clears' => 'با تکمیل پرسشنامه؛ ۲۵ روز پس از آن دوباره روشن می‌شود.',
                'surface' => 'نقطهٔ کنار آیتم «پرسشنامه انرژی» در منوی همبرگری.',
            ],
            'channels-controller' => [
                'group' => 'notifications', 'subgroup' => 'channels', 'tone' => 'sage', 'icon' => 'campaign',
                'label' => 'دعوت یا پیام تازهٔ کانال',
                'lights' => 'با دعوت‌شدن به کانالی تازه یا رسیدن پیام تازه در کانالی که عضو آن هستید. این مورد دات ندارد، فقط زنگوله.',
                'clears' => 'با بستن زنگوله؛ دکمهٔ «مشاهده» مستقیماً شما را به همان کانال می‌برد.',
            ],
            'channels-controller:edge' => [
                'group' => 'notifications', 'subgroup' => 'channels', 'tone' => 'sage', 'icon' => 'alternate_email',
                'label' => 'کارت اعلان کانال (دعوت/منشن)',
                'lights' => 'وقتی به کانالی دعوت شوید یا کسی شما را در پیام کانال منشن کند، کارت اعلانی پایین صفحه ظاهر می‌شود. این مورد دات ندارد، فقط کارت.',
                'clears' => 'با بستن کارت (×) پنهان می‌شود؛ روی همهٔ صفحات نمایش می‌یابد.',
            ],
            'gallery-controller' => [
                'group' => 'content', 'subgroup' => 'media', 'tone' => 'sage', 'icon' => 'photo_library',
                'label' => 'تصویر تازه در گالری',
                'lights' => 'با افزودن تصویر تازه به گالری. این مورد دات ندارد، فقط زنگوله.',
                'clears' => 'با بستن زنگوله؛ دکمهٔ «مشاهده» مستقیماً شما را به همان تصویر می‌برد.',
            ],
            'reports-controller' => [
                'group' => 'compliance', 'subgroup' => 'reports', 'tone' => 'sage', 'icon' => 'show_chart',
                'label' => 'گزارش تازه',
                'lights' => 'با انتشار گزارش تازه. این مورد دات ندارد، فقط زنگوله.',
                'clears' => 'با بستن زنگوله؛ دکمهٔ «مشاهده» مستقیماً شما را به همان گزارش می‌برد.',
            ],
            'tasksheet-controller' => [
                'group' => 'compliance', 'subgroup' => 'tasksheet', 'tone' => 'sage', 'icon' => 'assignment_turned_in',
                'label' => 'گزارش تسک‌شیت به اشتراک گذاشته شد',
                'lights' => 'وقتی مدیرتان یا خودتان یک گزارش تسک‌شیت را برای کسی ارسال کنید. این مورد دات ندارد، فقط زنگوله.',
                'clears' => 'با بستن زنگوله؛ دکمهٔ «مشاهده» همان گزارش را در تب جدید باز می‌کند — لینک تا ۱۴ روز معتبر است.',
            ],
        ];
    }
}
