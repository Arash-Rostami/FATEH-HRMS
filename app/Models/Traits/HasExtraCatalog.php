<?php

namespace App\Models\Traits;

trait HasExtraCatalog
{
    protected static array $extraIcons = [
        // Logistics & Access
        'parking' => 'local_parking',
        'location' => 'location_on',
        'transport' => 'directions_bus',
        'access' => 'door_front',
        'security_access' => 'verified_user',

        // Workplace Environment
        'dress_code' => 'checkroom',
        'workspace' => 'desktop_windows',
        'facilities' => 'meeting_room',
        'wifi' => 'wifi',
        'cleaning' => 'cleaning_services',

        // IT & Digital Tools
        'it_setup' => 'computer',
        'software' => 'install_desktop',
        'slack' => 'forum',
        'github' => 'code',
        'gitlab' => 'code_blocks',
        'security' => 'security',
        'password_policy' => 'password',
        'vpn' => 'vpn_key',

        // Time & Attendance
        'working_hours' => 'schedule',
        'vacation' => 'beach_access',
        'remote_work' => 'home_work',
        'overtime' => 'timer',
        'attendance' => 'how_to_reg',
        'holidays' => 'celebration',

        // People & Culture
        'buddy_system' => 'diversity_3',
        'team_structure' => 'account_tree',
        'culture' => 'diversity_2',
        'values' => 'star',
        'mentorship' => 'supervisor_account',

        // Benefits & Welfare
        'benefits' => 'card_giftcard',
        'insurance' => 'health_and_safety',
        'lunch' => 'restaurant',
        'cafeteria' => 'local_cafe',
        'gym' => 'fitness_center',
        'welfare' => 'volunteer_activism',
        'loan' => 'account_balance',

        // Communication & Support
        'emergency' => 'emergency',
        'contact' => 'contact_phone',
        'support' => 'support_agent',
        'hr_contact' => 'manage_accounts',

        // Learning & Growth
        'training' => 'school',
        'handbook' => 'menu_book',
        'performance' => 'assessment',
        'goals' => 'outlined_flag',
        'library' => 'local_library',

        // Feedback & Surveys
        'survey' => 'poll',
        'feedback' => 'rate_review',
        'suggestions' => 'lightbulb',

        // Fallback
        'default' => 'info',
    ];

    protected static array $extraLabels = [
        'parking' => '🚗 پارکینگ و خودرو',
        'location' => '📍 آدرس و موقعیت مکانی',
        'transport' => '🚌 حمل و نقل عمومی',
        'access' => '🚪 دسترسی و ورود به ساختمان',
        'security_access' => '🛡️ دسترسی امنیتی و کارت پرسنلی',

        'dress_code' => '👔 پوشش و لباس',
        'workspace' => '🖥️ میز کار و فضای شخصی',
        'facilities' => '🏢 اتاق جلسات و امکانات عمومی',
        'wifi' => '📶 شبکه WiFi',
        'cleaning' => '🧹 نظافت و بهداشت محیط کار',

        'it_setup' => '💻 راه‌اندازی سیستم و تجهیزات IT',
        'software' => '🧩 نرم‌افزارهای مورد نیاز',
        'slack' => '💬 فضای کاری Slack',
        'github' => '🐙 دسترسی GitHub',
        'gitlab' => '🦊 دسترسی GitLab',
        'security' => '🔒 امنیت سایبری و حریم خصوصی',
        'password_policy' => '🔑 سیاست رمز عبور و احراز هویت',
        'vpn' => '🔐 شبکه خصوصی مجازی (VPN)',

        'working_hours' => '⏰ ساعات کاری',
        'vacation' => '🏖️ مرخصی و تعطیلات',
        'remote_work' => '🏠 سیاست دورکاری',
        'overtime' => '⏱️ اضافه‌کاری',
        'attendance' => '✅ حضور و غیاب',
        'holidays' => '🎉 تعطیلات رسمی',

        'buddy_system' => '🤝 سیستم دوست راهنما',
        'team_structure' => '🏗️ ساختار تیم و چارت سازمانی',
        'culture' => '🎭 فرهنگ سازمانی',
        'values' => '⭐ ارزش‌های بنیادین سازمان',
        'mentorship' => '🧑‍🏫 منتورشیپ و راهنمایی حرفه‌ای',

        'benefits' => '🎁 بسته کامل مزایا و پاداش‌ها',
        'insurance' => '🏥 بیمه تکمیلی و درمانی',
        'lunch' => '🍽️ ناهار و زمان استراحت',
        'cafeteria' => '☕ کافه‌تریا و منوی خوراک',
        'gym' => '🏋️ باشگاه ورزشی',
        'welfare' => '❤️ خدمات رفاهی و حمایتی',
        'loan' => '💰 وام و تسهیلات مالی',

        'emergency' => '🚨 مخاطبین اضطراری',
        'contact' => '📞 اطلاعات تماس کلیدی',
        'support' => '💻 پشتیبانی فنی و اداری',
        'hr_contact' => '👤 ارتباط با منابع انسانی',

        'training' => '📚 آموزش و توسعه حرفه‌ای',
        'handbook' => '📖 راهنمای داخلی کارکنان',
        'performance' => '📈 ارزیابی عملکرد و شایستگی',
        'goals' => '🎯 اهداف فردی و تیمی',
        'library' => '🏛️ کتابخانه و منابع آموزشی',

        'survey' => '📊 نظرسنجی و بازخورد دوره‌ای',
        'feedback' => '💬 بازخورد و نظرات کارکنان',
        'suggestions' => '💡 پیشنهادات و ایده‌های بهبود',
    ];
    protected static array $extraDescriptions = [
        'parking' => 'جایگاه پارکینگ اختصاصی، نحوه رزرو جا، و هزینه‌های رفت‌وآمد با خودرو',
        'location' => 'آدرس دقیق دفتر، نقشه دسترسی، نزدیک‌ترین ایستگاه مترو و اتوبوس',
        'transport' => 'خطوط حمل و نقل عمومی در دسترس، سرویس ایاب و ذهاب، و بلیت‌های شرکتی',
        'access' => 'ساعت کاری ساختمان، نحوه دریافت کارت ورود، و قوانین تردد',
        'security_access' => 'صدور کارت پرسنلی، مجوزهای دسترسی طبقاتی، و قوانین امنیتی ساختمان',

        'dress_code' => 'کد لباس روزهای عادی، پوشش جلسات رسمی، و آداب پنج‌شنبه‌ها',
        'workspace' => 'میز کار اختصاصی، صندلی ارگونومیک، لوازم اداری، و نحوه شخصی‌سازی فضا',
        'facilities' => 'اتاق‌های جلسات، فضای استراحت، آبدارخانه، و نحوه رزرو فضا',
        'wifi' => 'نام شبکه بی‌سیم، رمز عبور، قوانین استفاده، و محدودیت‌های پهنای باند',
        'cleaning' => 'زمان‌بندی نظافت روزانه، نحوه درخواست خدمات ویژه، و قوانین بهداشت',

        'it_setup' => 'تحویل لپ‌تاپ، شماره داخلی IT، نصب اولیه نرم‌افزار، و تنظیمات ایمیل',
        'software' => 'فهرست نرم‌افزارهای تأییدشده، لایسنس‌ها، و فرآیند درخواست نصب از تیم IT',
        'slack' => 'نام فضای کاری، کانال‌های ضروری تیم، قوانین ارتباطی، و وضعیت آنلاین',
        'github' => 'دسترسی به سازمان GitHub، تیم‌های توسعه، مخازن پروژه، و قوانین کنترل نسخه',
        'gitlab' => 'دسترسی به سرور GitLab داخلی، گروه‌های تیم، و تنظیمات CI/CD',
        'security' => 'سیاست‌های امنیت سایبری، رمزنگاری داده‌ها، و قوانین محرمانگی اطلاعات',
        'password_policy' => 'الزامات رمز عبور قوی، راه‌اندازی احراز هویت دو مرحله‌ای، و تغییر دوره‌ای',
        'vpn' => 'راهنمای نصب کلاینت VPN، تنظیمات اتصال امن، و عیب‌یابی از راه دور',

        'working_hours' => 'ساعت کاری رسمی، بازه فلکس‌تایم، و نحوه ثبت ورود و خروج',
        'vacation' => 'تعداد روزهای مرخصی سالانه، فرآیند درخواست، تأییدیه مدیر، و محاسبه مانده',
        'remote_work' => 'تعداد روزهای مجاز دورکاری در هفته، شرایط فنی، و نحوه درخواست',
        'overtime' => 'قوانین اضافه‌کاری، سقف ساعات مجاز، نحوه ثبت، و نرخ محاسبه',
        'attendance' => 'سیستم حضور و غیاب، نحوه ثبت ورود/خروج، و گزارش‌گیری ماهانه',
        'holidays' => 'تقویم تعطیلات رسمی سال جاری، تعطیلات شرکتی، و نحوه اطلاع‌رسانی',

        'buddy_system' => 'معرفی دوست راهنما (Buddy)، نحوه ارتباط اولیه، و انتظارات از این برنامه',
        'team_structure' => 'چارت سازمانی تیم، نقش هر عضو، مسئولیت‌ها، و گزارش‌دهی خطی',
        'culture' => 'ارزش‌ها، هنجارهای رفتاری، سبک کاری، و آداب ارتباطی در سازمان',
        'values' => 'ارزش‌های بنیادین سازمان، انتظارات رفتاری، و چارچوب تصمیم‌گیری',
        'mentorship' => 'برنامه منتورشیپ، نحوه انتخاب منتور، جلسات منظم، و اهداف توسعه',

        'benefits' => 'بیمه تکمیلی، وام، یارانه‌ها، مزایای غیرنقدی، و جدول مقایسه سطوح',
        'insurance' => 'پوشش بیمه درمانی تکمیلی، نحوه ثبت‌نام، فرانشیز، و لیست مراکز طرف قرارداد',
        'lunch' => 'ساعت ناهار، رستوران‌های پیشنهادی اطراف، سفارش غذا، و یارانه تغذیه',
        'cafeteria' => 'منوی روزانه کافه‌تریا، ساعت کار، نحوه شارژ کارت، و قیمت‌ها',
        'gym' => 'باشگاه ورزشی شرکت، ساعت کار، کلاس‌های گروهی، و نحوه ثبت‌نام',
        'welfare' => 'برنامه‌های رفاهی فصلی، کمک‌هزینه‌ها، و خدمات حمایتی کارکنان',
        'loan' => 'شرایط دریافت وام قرض‌الحسنه، تسهیلات مسکن، مدارک، و جدول بازپرداخت',

        'emergency' => 'شماره‌های اورژانس، مخاطبین ۲۴ ساعته، آدرس نزدیک‌ترین بیمارستان، و نقشه',
        'contact' => 'شماره‌های داخلی کلیدی، ایمیل‌های تیم‌های اصلی، و ساعت پاسخگویی',
        'support' => 'کانال‌های پشتیبانی فنی و اداری، اولویت‌بندی تیکت‌ها، و زمان حل',
        'hr_contact' => 'اطلاعات تماس کارشناس HR، ساعت ملاقات حضوری، و نحوه تنظیم قرار',

        'training' => 'برنامه‌های آموزشی سالانه، بودجه توسعه فردی، و پلتفرم‌های یادگیری',
        'handbook' => 'کتابچه راهنمای کارکنان، قوانین داخلی، رویه‌های اداری، و حقوق و تکالیف',
        'performance' => 'چرخه ارزیابی عملکرد فصلی، معیارهای شایستگی، و فرآیند بازخورد',
        'goals' => 'تنظیم اهداف فردی و تیمی، OKRها، پیگیری پیشرفت، و بازنگری فصلی',
        'library' => 'دسترسی به کتابخانه دیجیتال، منابع آموزشی، اشتراک‌های سازمانی، و رزرو',

        'survey' => 'لینک نظرسنجی روز اول، چک‌لیست ارزیابی سه‌ماهه، و نتایج دوره‌ای',
        'feedback' => 'نحوه ارائه بازخورد سازنده، گیرنده‌های اصلی، و فرآیند پیگیری و پاسخگویی',
        'suggestions' => 'صندوق پیشنهادات، فرآیند ارزیابی ایده‌ها، و جوایز بهبودهای اجراشده',
    ];

    protected static array $extraCategories = [
        'logistics' => ['parking', 'location', 'transport', 'access', 'security_access'],
        'workplace' => ['dress_code', 'workspace', 'facilities', 'wifi', 'cleaning'],
        'technology' => ['it_setup', 'software', 'slack', 'github', 'gitlab', 'security', 'password_policy', 'vpn'],
        'time_policy' => ['working_hours', 'vacation', 'remote_work', 'overtime', 'attendance', 'holidays'],
        'people' => ['buddy_system', 'team_structure', 'culture', 'values', 'mentorship'],
        'benefits' => ['benefits', 'insurance', 'lunch', 'cafeteria', 'gym', 'welfare', 'loan'],
        'support' => ['emergency', 'contact', 'support', 'hr_contact'],
        'growth' => ['training', 'handbook', 'performance', 'goals', 'library'],
        'feedback' => ['survey', 'feedback', 'suggestions'],
    ];

    public static function adminOptions(): array
    {
        $options = [];

        foreach (static::$extraCategories as $category => $keys) {
            $items = [];
            foreach ($keys as $key) {
                $items[] = [
                    'key' => $key,
                    'icon' => static::extraIcon($key),
                    'label' => static::extraLabel($key),
                    'description' => static::extraDescription($key),
                ];
            }

            $options[$category] = [
                'title' => static::categoryTitle($category),
                'items' => $items,
            ];
        }

        return $options;
    }

    public static function autoTitle(string $key): string
    {
        return static::$extraLabels[$key] ?? static::fallbackLabel($key);
    }

    public static function categoryTitle(string $category): string
    {
        return match ($category) {
            'logistics' => 'رفت و آمد و تردد',
            'workplace' => 'محیط کار',
            'technology' => 'فناوری و ابزار',
            'time_policy' => 'زمان و مقررات',
            'people' => 'همکاران و فرهنگ',
            'benefits' => 'مزایا و رفاه',
            'support' => 'پشتیبانی و تماس',
            'growth' => 'رشد و یادگیری',
            'feedback' => 'بازخورد',
            default => static::fallbackLabel($category),
        };
    }

    public static function extraDescription(string $key): ?string
    {
        return static::$extraDescriptions[$key] ?? null;
    }

    public static function extraIcon(string $key): string
    {
        return static::$extraIcons[$key] ?? static::$extraIcons['default'];
    }

    public static function extraIcons(): array
    {
        return static::$extraIcons;
    }

    public static function extraLabel(string $key): string
    {
        return static::$extraLabels[$key] ?? static::fallbackLabel($key);
    }

    public static function getAllKeys(): array
    {
        return collect(static::$extraCategories)
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public static function isValidKey(string $key): bool
    {
        return isset(static::$extraIcons[$key]) && $key !== 'default';
    }

    private static function fallbackLabel(string $key): string
    {
        return "آیتم بدون عنوان ({$key})";
    }
}
