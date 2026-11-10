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
                'channels' => ['label' => 'گروه‌ها', 'icon' => 'groups'],
            ],
            'content' => [
                'calendar' => ['label' => 'تقویم', 'icon' => 'event'],
                'media' => ['label' => 'اخبار و گالری', 'icon' => 'rss_feed'],
            ],
            'compliance' => [
                'tracking' => ['label' => 'پیگیری', 'icon' => 'fact_check'],
                'reports' => ['label' => 'گزارش', 'icon' => 'show_chart'],
                'tasksheet' => ['label' => 'تسک‌شیت', 'icon' => 'assignment_turned_in'],
                'ai-report' => ['label' => 'گزارش ساز هوشمند', 'icon' => 'article'],
                'correspondence' => ['label' => 'مکاتبات', 'icon' => 'mail'],
            ],
            'tasks' => [
                'list' => ['label' => 'فهرست', 'icon' => 'checklist'],
                'deadline' => ['label' => 'سررسید', 'icon' => 'schedule'],
                'approval' => ['label' => 'تأیید', 'icon' => 'verified_user'],
                'projects' => ['label' => 'پروژه', 'icon' => 'workspaces'],
                'workflow' => ['label' => 'چرخهٔ کاری', 'icon' => 'conversion_path'],
                'tickets' => ['label' => 'تیکت', 'icon' => 'confirmation_number'],
                'reminders' => ['label' => 'یادآوری', 'icon' => 'alarm'],
                'reminders-badge' => ['label' => 'زنگ یادآوری', 'icon' => 'notifications_active'],
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
        return array_filter([
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
                'surface' => 'نقطهٔ کنار گزینهٔ «مخاطبین» در منوی اصلی.',
            ],
            'feeds' => [
                'group' => 'content', 'subgroup' => 'media', 'tone' => 'sapphire', 'icon' => 'rss_feed',
                'label' => 'خبر تازه',
                'lights' => 'با انتشار خبر تازه در بخش اخبار.',
                'clears' => 'با بازکردن تب «اخبار»؛ با انتشار خبر بعدی، نشانگر دوباره روشن می‌شود.',
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
                'surface' => 'نقطهٔ کنار گزینهٔ «فرصت‌های شغلی» در منوی اصلی.',
            ],
            'suggestion-controller' => [
                'group' => 'opportunities', 'tone' => 'gold', 'icon' => 'lightbulb',
                'label' => 'پیشنهاد نیازمند تصمیم',
                'lights' => 'برای مدیرعامل، هنگام انتظار برای تصمیم نهایی؛ برای مدیر واحد، هنگام نیاز به نظر واحد، درخواست «تکمیل مجدد» از سوی مدیرعامل یا تکمیل‌نشدن پیشنهاد ارجاعی. درخواست تکمیل مجدد حتی پس از ثبت نظر قبلی نیز نمایش داده می‌شود. این نشانگر برای سایر کارکنان فعال نیست.',
                'clears' => 'با ثبت تصمیم یا نظر لازم؛ مشاهدهٔ صفحهٔ پیشنهادها به‌تنهایی کافی نیست.',
                'surface' => 'نقطهٔ کنار گزینهٔ «پیشنهادات» در منوی اصلی.',
            ],
            'tasks-controller' => [
                'group' => 'tasks', 'subgroup' => 'list', 'tone' => 'gold', 'icon' => 'checklist',
                'label' => 'وظیفهٔ انجام‌نشده',
                'lights' => 'وقتی وظیفه‌ای در ستون «انجام‌نشده» برد وظایف شما باشد.',
                'clears' => 'با انجام یا جابه‌جاکردن وظیفه؛ صرفاً مشاهده خاموشش نمی‌کند.',
                'surface' => 'نقطهٔ کنار گزینهٔ «برد وظایف» در منوی اصلی.',
            ],
            'tasks-deadline' => [
                'group' => 'tasks', 'subgroup' => 'list', 'tone' => 'gold', 'icon' => 'schedule',
                'label' => 'وظیفهٔ نزدیک به سررسید',
                'lights' => 'وقتی مهلت یکی از وظایف شما گذشته باشد یا تا ۳ روز آینده به پایان برسد.',
                'clears' => 'با تغییر وضعیت یا مهلت همان وظیفه؛ صرفاً مشاهده خاموشش نمی‌کند.',
                'surface' => 'بدون نقطهٔ اختصاصی؛ فقط از طریق زنگولهٔ اعلان‌های بالای صفحه.',
            ],
            'tasks-controller:due-soon-edge' => [
                'group' => 'tasks', 'subgroup' => 'deadline', 'tone' => 'sage', 'icon' => 'schedule',
                'label' => 'کارت اعلان نزدیک‌شدن سررسید',
                'lights' => 'وقتی وظیفهٔ محول‌شده به شما کمتر از ۲۴ ساعت تا سررسید داشته باشد، کارت اعلانی پایین صفحه ظاهر می‌شود. این کارت نشانگر جداگانه‌ای در منو ندارد.',
                'clears' => 'با بستن کارت (×) پنهان می‌شود؛ دکمهٔ «مشاهده» مستقیماً شما را به همان وظیفه می‌برد.',
            ],
            'tasks-overdue-nudge' => [
                'group' => 'tasks', 'subgroup' => 'deadline', 'tone' => 'sage', 'icon' => 'event_busy',
                'label' => 'وظیفهٔ سررسید گذشته',
                'lights' => 'پس از پایان مهلت وظیفهٔ محول‌شده به شما. اگر تأخیر به مدت تعیین‌شده در پروژه برسد (پیش‌فرض ۳ روز)، برای «جوابگو»ی وظیفه نیز اعلان ارسال می‌شود. وظایف در وضعیت «توقف» اعلان تأخیر ندارند. این مورد فقط در اعلان‌های زنگوله نمایش داده می‌شود.',
                'clears' => 'با بستن اعلان، تکمیل یا بایگانی وظیفه، یا تغییر مهلت آن. اگر وظیفه دوباره وارد تأخیر شود، ممکن است اعلان تازه‌ای دریافت کنید.',
            ],
            'tasks-approval-nudge' => [
                'group' => 'tasks', 'subgroup' => 'approval', 'tone' => 'sage', 'icon' => 'verified_user',
                'label' => 'وظیفهٔ منتظر تأیید',
                'lights' => 'وقتی وظیفه‌ای در پروژهٔ دارای «تأیید مدیر» انجام شود و تأیید آن بر عهدهٔ شما باشد: مدیر پروژه، یا در نبود مدیر فعال، «جوابگو»ی وظیفه. پس از ۲۴ ساعت، عنوان اعلان «یادآوری» و پس از ۴۸ ساعت «فوری» می‌شود. این مورد فقط در اعلان‌های زنگوله نمایش داده می‌شود.',
                'clears' => 'با تأیید وظیفه؛ بستن اعلان فقط آن را پنهان می‌کند و به‌معنای تأیید نیست.',
            ],
            'tasks-pending-approval' => [
                'group' => 'tasks', 'subgroup' => 'approval', 'tone' => 'gold', 'icon' => 'task_alt',
                'label' => 'وظیفهٔ در انتظار تأیید (نشانگر کلی)',
                'lights' => 'وقتی در پروژه‌های تحت مدیریت شما حداقل یک وظیفهٔ انجام‌شدهٔ تأییدنشده وجود داشته باشد. فقط در فهرست اعلان‌های زنگوله نمایش داده می‌شود و نشانگر جداگانه‌ای در منو ندارد.',
                'clears' => 'با تأیید یا بازگشت وضعیت همان وظیفه؛ صرفاً مشاهدهٔ پروژه خاموشش نمی‌کند.',
            ],
            'projects-controller' => [
                'group' => 'tasks', 'subgroup' => 'projects', 'tone' => 'gold', 'icon' => 'workspaces',
                'label' => 'دعوت به پروژه',
                'lights' => 'با افزوده‌شدن شما به یک پروژهٔ تازه یا موجود (مستقیم یا از راه دپارتمان)، یا وقتی مالک پروژه شوید. فقط در فهرست اعلان‌های زنگوله نمایش داده می‌شود و نشانگر جداگانه‌ای در منو ندارد.',
                'clears' => 'با بستن اعلان؛ دکمهٔ «مشاهده» مستقیماً شما را به همان پروژه می‌برد.',
            ],
            'workflow:nudge' => [
                'group' => 'tasks', 'subgroup' => 'workflow', 'tone' => 'gold', 'icon' => 'conversion_path',
                'label' => 'تغییرات چرخهٔ کاری',
                'lights' => 'هنگام رسیدن نوبت شما، بازگشت مرحله، حذف شما از مرحله، لغو چرخه یا تکمیل چرخه‌ای که مالک آن هستید. این مورد فقط در اعلان‌های زنگوله نمایش داده می‌شود.',
                'clears' => 'با بستن اعلان؛ اعلان نوبت شما با تکمیل مرحله نیز برطرف می‌شود. بازکردن تب «چرخهٔ کاری» به‌تنهایی کافی نیست.',
            ],
            'projects-controller:edge' => [
                'group' => 'tasks', 'subgroup' => 'projects', 'tone' => 'sage', 'icon' => 'group_add',
                'label' => 'گروه پروژهٔ بازنشده',
                'lights' => 'وقتی به گروه پروژه دعوت شده‌اید و هنوز آن را باز نکرده‌اید، کارت اعلان در پایین صفحه نمایش داده می‌شود. این کارت نشانگر جداگانه‌ای در منو ندارد.',
                'clears' => 'با بستن کارت (×) پنهان می‌شود؛ روی همهٔ صفحات نمایش می‌یابد.',
            ],
            'ths-controller' => [
                'group' => 'tasks', 'subgroup' => 'tickets', 'tone' => 'gold', 'icon' => 'confirmation_number',
                'label' => 'تیکت نیازمند اقدام',
                'lights' => 'وقتی تیکت باز در واحد شما (برای همهٔ مدیران واحد؛ در واحدِ «تخصیص آزاد» برای همهٔ اعضای واحد)، تیکت در حال بررسیِ محول‌شده به شما، یا تیکت بسته‌شدهٔ منتظر امتیاز شما وجود داشته باشد.',
                'clears' => 'برای مدیر واحد، با تعیین مسئول تیکت باز؛ برای مسئول رسیدگی، با بستن تیکت یا بازگشت آن به صف؛ برای درخواست‌دهنده، با ثبت امتیاز رضایت. پاسخ‌دادن یا مشاهدهٔ صفحه به‌تنهایی کافی نیست.',
                'surface' => 'نقطهٔ کنار گزینهٔ «تیکتینگ» در منوی اصلی.',
            ],
            'ths-controller:nudge' => [
                'group' => 'tasks', 'subgroup' => 'tickets', 'tone' => 'sage', 'icon' => 'confirmation_number',
                'label' => 'تیکت: ارجاع، پاسخ یا نتیجه',
                'lights' => 'وقتی تیکتی به واحد یا به شما ارجاع شود، پاسخ تازه‌ای برای تیکتی که درگیر آن هستید ثبت شود، یا تیکت شما بسته شود و منتظر امتیاز شما باشد. پاسخ‌دهنده برای پاسخ خودش اعلان نمی‌گیرد مگر اینکه اقدام بعدی با او باشد.',
                'clears' => 'با بستن اعلان؛ برای هر پاسخ اعلانی جداگانه است. با تغییر مسئول یا وضعیت تیکت، اعلان کسانی که دیگر طرف اقدام نیستند، و پس از بسته‌شدن و ثبت امتیاز، اعلان‌های تیکت خودکار حذف می‌شوند. دکمهٔ «مشاهده» مستقیماً شما را به همان تیکت می‌برد. این مورد نشانگر جداگانه‌ای در منو ندارد.',
            ],
            'dms-controller' => [
                'group' => 'compliance', 'subgroup' => 'tracking', 'tone' => 'gold', 'icon' => 'description',
                'label' => 'سند نیازمند تأیید/مطالعه',
                'lights' => 'وقتی سندی در مدیریت اسناد نیازمند تأیید یا مطالعهٔ شما باشد.',
                'clears' => 'با تأیید یا مطالعهٔ همان سند.',
                'surface' => 'نقطهٔ کنار گزینهٔ «مدیریت اسناد» در منوی اصلی.',
            ],
            'energy-controller' => [
                'group' => 'compliance', 'subgroup' => 'tracking', 'tone' => 'gold', 'icon' => 'bolt',
                'label' => 'ارزیابی انرژی ماهانه',
                'lights' => 'وقتی پرسشنامهٔ انرژی را طی ۲۵ روز اخیر تکمیل نکرده باشید.',
                'clears' => 'با تکمیل پرسشنامه؛ ۲۵ روز پس از آن دوباره روشن می‌شود.',
                'surface' => 'نقطهٔ کنار گزینهٔ «پرسشنامه انرژی» در منوی اصلی.',
            ],
            'channels-controller' => [
                'group' => 'notifications', 'subgroup' => 'channels', 'tone' => 'sage', 'icon' => 'campaign',
                'label' => 'دعوت یا پیام تازهٔ گروه',
                'lights' => 'با دعوت‌شدن به گروهی تازه یا رسیدن پیام تازه در گروهی که عضو آن هستید. فقط در فهرست اعلان‌های زنگوله نمایش داده می‌شود و نشانگر جداگانه‌ای در منو ندارد.',
                'clears' => 'با بستن اعلان؛ دکمهٔ «مشاهده» مستقیماً شما را به همان گروه می‌برد.',
            ],
            'channels-controller:edge' => [
                'group' => 'notifications', 'subgroup' => 'channels', 'tone' => 'sage', 'icon' => 'alternate_email',
                'label' => 'دعوت به گروه یا اشاره به نام شما',
                'lights' => 'وقتی به گروهی دعوت شوید یا در پیامی به نام شما اشاره شود، کارت اعلان در پایین صفحه نمایش داده می‌شود. این کارت نشانگر جداگانه‌ای در منو ندارد.',
                'clears' => 'با بستن کارت (×) پنهان می‌شود؛ روی همهٔ صفحات نمایش می‌یابد.',
            ],
            'gallery-controller' => [
                'group' => 'content', 'subgroup' => 'media', 'tone' => 'sage', 'icon' => 'photo_library',
                'label' => 'تصویر تازه در گالری',
                'lights' => 'با افزودن تصویر تازه به گالری. فقط در فهرست اعلان‌های زنگوله نمایش داده می‌شود و نشانگر جداگانه‌ای در منو ندارد.',
                'clears' => 'با بستن اعلان؛ دکمهٔ «مشاهده» مستقیماً شما را به همان تصویر می‌برد.',
            ],
            'reports-controller' => [
                'group' => 'compliance', 'subgroup' => 'reports', 'tone' => 'sage', 'icon' => 'show_chart',
                'label' => 'گزارش تازه',
                'lights' => 'با انتشار گزارش تازه. فقط در فهرست اعلان‌های زنگوله نمایش داده می‌شود و نشانگر جداگانه‌ای در منو ندارد.',
                'clears' => 'با بستن اعلان؛ دکمهٔ «مشاهده» مستقیماً شما را به همان گزارش می‌برد.',
            ],
            'tasksheet-controller' => [
                'group' => 'compliance', 'subgroup' => 'tasksheet', 'tone' => 'sage', 'icon' => 'assignment_turned_in',
                'label' => 'اشتراک‌گذاری گزارش تسک‌شیت',
                'lights' => 'هنگام ارسال گزارش تسک‌شیت توسط شما یا مدیرتان. این مورد فقط در اعلان‌های زنگوله نمایش داده می‌شود.',
                'clears' => 'با بستن اعلان؛ دکمهٔ «مشاهده» گزارش را در زبانهٔ جدید مرورگر باز می‌کند. پیوند گزارش تا ۷ روز معتبر است.',
            ],
            'ai-report-controller' => [
                'group' => 'compliance', 'subgroup' => 'ai-report', 'tone' => 'sapphire', 'icon' => 'article',
                'label' => 'دریافت گزارش از همکاران',
                'lights' => 'وقتی همکاری گزارشی آماده را با شما به اشتراک بگذارد و شما هنوز آن را باز نکرده باشید.',
                'clears' => 'با بازکردن گزارش، لغو اشتراک، یا غیرفعال‌شدن و حذف آن.',
                'surface' => 'نقطهٔ کنار گزینهٔ «گزارش ساز هوشمند» در منوی اصلی.',
            ],
            'ai-report-controller:nudge' => [
                'group' => 'compliance', 'subgroup' => 'ai-report', 'tone' => 'sage', 'icon' => 'article',
                'label' => 'گزارش به اشتراک گذاشته‌شده با شما',
                'lights' => 'وقتی همکاری گزارشی آماده را با شما به اشتراک بگذارد. برای هر گزارش یک اعلان دریافت می‌کنید. این مورد نشانگر جداگانه‌ای در منو ندارد.',
                'clears' => 'با بازکردن گزارش یا بستن اعلان؛ با لغو اشتراک، غیرفعال‌شدن یا حذف گزارش، اعلان خودکار حذف می‌شود. پیوند دکمهٔ «مشاهده» تا ' . convertToPersian(config('ai.report.share_link_days')) . ' روز معتبر است و پس از آن، گزارش از فهرست «دریافتی» در صفحهٔ گزارش ساز هوشمند در دسترس می‌ماند.',
            ],
            'reminders-controller' => [
                'group' => 'tasks', 'subgroup' => 'reminders-badge', 'tone' => 'gold', 'icon' => 'notifications_active',
                'label' => 'یادآوری نیازمند توجه',
                'lights' => 'با رسیدن موعد یادآوریِ متصل به وظیفه، تیکت، سند یا موارد مشابه، در صورت انتخاب روش «نشانگر منو». این روش برای یادآوری‌های مستقل در دسترس نیست.',
                'clears' => 'با تکمیل یا حذف یادآوری، یا انتقال مورد مرتبط به سطل زباله؛ بازکردن پنجرهٔ یادآوری‌ها به‌تنهایی کافی نیست.',
                'surface' => 'نقطهٔ کنار آیکن زنگ یادآوری‌ها در نوار بالای صفحه.',
            ],
            'reminders-controller:due-nudge' => [
                'group' => 'tasks', 'subgroup' => 'reminders', 'tone' => 'sage', 'icon' => 'alarm',
                'label' => 'یادآوری سررسید‌شده',
                'lights' => 'پس از رسیدن موعد یادآوری فعال شما، در صورت انتخاب این روش اطلاع‌رسانی. این مورد فقط در اعلان‌های زنگوله نمایش داده می‌شود.',
                'clears' => 'با بستن اعلان؛ همچنین با تکمیل یا حذف یادآوری، تغییر موعد آن یا انتقال مورد مرتبط به سطل زباله، خودکار حذف می‌شود.',
            ],
            'reminders-controller:due-today-edge' => [
                'group' => 'tasks', 'subgroup' => 'reminders', 'tone' => 'sage', 'icon' => 'notifications',
                'label' => 'یادآوری امروز',
                'lights' => 'با رسیدن موعد یادآوری امروز، در صورت انتخاب این روش اطلاع‌رسانی، کارت اعلان در پایین صفحه نمایش داده می‌شود. این کارت نشانگر جداگانه‌ای در منو ندارد.',
                'clears' => 'بستن کارت با دکمهٔ ×، یادآوری را «انجام‌شده» ثبت می‌کند. کارت با تکمیل یا حذف یادآوری یا انتقال مورد مرتبط به سطل زباله نیز خودکار حذف می‌شود. این کارت در همهٔ صفحات قابل نمایش است.',
            ],
            'correspondence-controller' => [
                'group' => 'compliance', 'subgroup' => 'correspondence', 'tone' => 'gold', 'icon' => 'mail',
                'label' => 'نامه نیازمند توجه',
                'lights' => 'وقتی نامه‌ای برای امضای شما ارسال شده باشد یا نامه‌ای که در ۳۰ روز اخیر به شما رسیده و هنوز باز نشده یا امضا نشده باشد.',
                'clears' => 'بخش «در انتظار امضا» با امضای شما؛ بخش «باز نکرده» تا باز کردن نامه یا پس از حدود یک ماه خودکار. صرفاً مشاهدهٔ صفحهٔ مکاتبات خاموشش نمی‌کند.',
                'surface' => 'نقطهٔ کنار گزینهٔ «مکاتبات» در منوی اصلی.',
            ],
            'correspondence-controller:nudge' => [
                'group' => 'compliance', 'subgroup' => 'correspondence', 'tone' => 'sage', 'icon' => 'mail',
                'label' => 'نامه: امضا، تأیید یا رد',
                'lights' => 'وقتی نوبت امضای شما باشد یا نامه‌ای که نوشته‌اید تأیید یا رد شود. گیرندهٔ رونوشت فقط پس از تأیید نامهٔ فوری یا آنی اعلان دریافت می‌کند. این مورد نشانگر جداگانه‌ای در منو ندارد.',
                'clears' => 'با امضای نامه یا بستن اعلان؛ با پس‌گرفتن یا باطل‌شدن نامه، اعلان‌های آن خودکار حذف می‌شوند. نامهٔ محرمانه فقط با عنوان «نامه محرمانه» در اعلان نمایش داده می‌شود.',
            ],
        ], fn (string $key) => !tenantOffName($key), ARRAY_FILTER_USE_KEY);
    }
}
