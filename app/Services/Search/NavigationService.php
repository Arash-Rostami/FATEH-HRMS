<?php

namespace App\Services\Search;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NavigationService
{
    protected static ?Collection $items = null;

    /**
     * Search for items based on a query string.
     */
    public function search(string $query): array
    {
        $query = $this->normalize($query);

        if (strlen($query) < 2) return [];

        // Break query into words (e.g., "ticket support" -> ['ticket', 'support'])
        $tokens = array_values(array_filter(explode(' ', $query)));

        return $this->getSearchableItems()
            ->map(function ($item) use ($query, $tokens) {
                $score = 0;

                $title = $this->normalize($item['title']);
                $subtitle = $this->normalize($item['subtitle']);
                $keywords = collect($item['keywords'])->map(fn($k) => $this->normalize($k));

                // 1. Exact Match Priority (Highest Score)
                if ($title === $query || $keywords->contains($query)) $score += 100;

                // 2. Token Matching (Smart Multi-word search)
                foreach ($tokens as $token) {
                    if (Str::contains($title, $token)) $score += 40;
                    if (Str::contains($subtitle, $token)) $score += 20;
                    if ($keywords->contains(fn($k) => Str::contains($k, $token))) $score += 30;
                }

                $item['score'] = $score;
                return $item;
            })
            ->filter(fn($item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->values()
            ->toArray();
    }

    /**
     * Normalizes text to handle Persian/Arabic inconsistencies and casing.
     */
    protected function normalize(string $text): string
    {
        $text = Str::lower(trim($text));
        $map = [
            'ي' => 'ی',
            'ك' => 'ک',
        ];

        $text = strtr($text, $map);

        return preg_replace('/\s+/u', ' ', $text);
    }

    /**
     * Get all searchable items.
     */

    protected function getSearchableItems(): Collection
    {
        return static::$items ??= collect([
            [
                'id' => 'home',
                'title' => 'خانه',
                'subtitle' => 'داشبورد اصلی و وضعیت کلی',
                'icon' => 'home',
                'action' => 'tab:home',
                'keywords' => [
                    'home', 'dashboard', 'main', 'start', 'landing', 'overview', 'summary',
                    'خانه', 'داشبورد', 'اصلی', 'شروع', 'نمای کلی', 'خلاصه', 'صفحه اصلی', 'ورود',
                    'صفحه اول', 'مرکز', 'home page', 'panel', 'control panel',
                ],
            ],
            [
                'id' => 'posts',
                'title' => 'پست و اعلانات',
                'subtitle' => 'اطلاعیه‌های منابع انسانی و پیام‌های مرکزی',
                'icon' => 'campaign',
                'action' => 'tab:post',
                'keywords' => [
                    'announce', 'announcement', 'post', 'notice', 'news', 'bulletin', 'memo', 'update', 'message', 'hr',
                    'پست', 'اعلانات', 'اطلاعیه', 'اطلاعیه‌ها', 'اعلامیه', 'خبر', 'اخبار داخلی', 'پیام', 'پیام مرکزی',
                    'منابع انسانی', 'اچ آر', 'ایمپلوی', 'بخشنامه', 'ابلاغ', 'روابط عمومی', 'درون سازمانی',
                ],
            ],
            [
                'id' => 'feed',
                'title' => 'اخبار و فیدها',
                'subtitle' => 'آخرین خبرها و اطلاعیه‌های صنعت و شرکت',
                'icon' => 'rss_feed',
                'action' => 'tab:feed',
                'keywords' => [
                    'feed', 'news', 'update', 'timeline', 'stream', 'rss', 'latest', 'fresh', 'breaking',
                    'اخبار', 'خبر', 'تازه', 'جدید', 'به‌روز', 'بروزرسانی', 'رویداد', 'فید', 'فید خبری',
                    'خبرها', 'اطلاعیه', 'صنعت', 'شرکت', 'داغ', 'آخرین',
                ],
            ],
            [
                'id' => 'calendar',
                'title' => 'تقویم',
                'subtitle' => 'مدیریت جلسات، تولدها و رویدادهای شرکت',
                'icon' => 'calendar_month',
                'action' => 'tab:calendar',
                'keywords' => [
                    'calendar', 'schedule', 'event', 'date', 'meeting', 'plan', 'time', 'agenda', 'reminder',
                    'تقویم', 'برنامه', 'برنامه زمانی', 'رویداد', 'جلسه', 'جلسات', 'زمان', 'تاریخ', 'تولد', 'سالگرد',
                    'یادآور', 'نوبت', 'زمان‌بندی', 'زمانبندی', 'agenda', 'رزرو جلسه', 'کالندر',
                ],
            ],
            [
                'id' => 'gallery',
                'title' => 'گالری تصاویر',
                'subtitle' => 'آرشیو عکس‌ها و ویدیوهای رویدادهای شرکت',
                'icon' => 'photo_library',
                'action' => 'tab:gallery',
                'keywords' => [
                    'gallery', 'photo', 'image', 'picture', 'media', 'album', 'photos', 'videos',
                    'گالری', 'عکس', 'عکس‌ها', 'تصویر', 'تصاویر', 'رسانه', 'آلبوم', 'ویدیو', 'ویدئو',
                    'خاطره', 'آرشیو عکس', 'آرشیو تصاویر', 'media library', 'تصاویر رویداد',
                ],
            ],
            [
                'id' => 'reports',
                'title' => 'گزارشات',
                'subtitle' => 'آمار، تحلیل عملکرد و اسناد بخش‌ها',
                'icon' => 'show_chart',
                'action' => 'tab:reports',
                'keywords' => [
                    'report', 'reports', 'analytic', 'analysis', 'statistic', 'data', 'chart', 'metric', 'kpi', 'dashboard',
                    'گزارش', 'گزارشات', 'آمار', 'تحلیل', 'تحلیل داده', 'داده', 'نمودار', 'عملکرد', 'شاخص',
                    'سند', 'سنجش', 'آمار عملکرد', 'پرفورمنس', 'عملیاتی', 'مالی', 'مدیریتی',
                ],
            ],
            [
                'id' => 'links',
                'title' => 'لینک‌ها و مسیرهای دیجیتال سازمان',
                'subtitle' => 'دسترسی متمرکز به سامانه‌های داخلی و خارجی',
                'icon' => 'open_in_new',
                'action' => 'tab:links',
                'keywords' => [
                    'link', 'links', 'shortcut', 'url', 'bookmark', 'external', 'site', 'tool', 'resource',
                    'لینک', 'لینک‌ها', 'پیوند', 'میانبر', 'آدرس', 'نشانی', 'سایت', 'سامانه', 'ابزار',
                    'دسترسی', 'منابع', 'ابزارها', 'صفحه', 'مخزن لینک', 'لینک مفید',
                ],
            ],
            [
                'id' => 'status',
                'title' => 'وضعیت حضور',
                'subtitle' => 'ثبت و مشاهده وضعیت کاری و آنلاین بودن',
                'icon' => 'hub',
                'action' => 'tab:status',
                'keywords' => [
                    'status', 'presence', 'online', 'busy', 'work', 'availability', 'active', 'away', 'offline',
                    'وضعیت', 'حضور', 'آنلاین', 'آفلاین', 'مشغول', 'آزاد', 'کاری', 'تردد', 'دورکار', 'دورکاری',
                    'در دسترس', 'در جلسه', 'مرخصی', 'غایب', 'فعال', 'حاضر',
                ],
            ],
            [
                'id' => 'faq',
                'title' => 'پرسش‌های متداول (FAQ)',
                'subtitle' => 'راهنما و سوالات پرتکرار برای کارکنان',
                'icon' => 'help',
                'action' => 'tab:faqs',
                'keywords' => [
                    'faq', 'help', 'question', 'questions', 'support', 'answer', 'guide', 'manual', 'how to',
                    'سوال', 'سوالات', 'پرسش', 'پرسش‌ها', 'پاسخ', 'راهنما', 'کمک', 'پشتیبانی', 'متداول',
                    'آموزش', 'نحوه استفاده', 'پرسش و پاسخ', 'مستند راهنما', 'راهنمای سریع',
                ],
            ],

            [
                'id' => 'dms',
                'title' => 'مدیریت اسناد',
                'subtitle' => 'مخزن متمرکز اسناد رسمی با امضا و پیگیری',
                'icon' => 'folder_managed',
                'action' => 'route:dms',
                'keywords' => [
                    'dms', 'document', 'documents', 'file', 'files', 'archive', 'record', 'records', 'signature', 'workflow',
                    'اسناد', 'سند', 'اسناد رسمی', 'فایل', 'فایل‌ها', 'آرشیو', 'مخزن', 'نامه', 'نامه رسمی', 'امضا',
                    'ثبت سند', 'گردش کار', 'بایگانی', 'مدرک', 'مدارک', 'پیگیری سند', 'آرشیو اسناد',
                ],
            ],
            [
                'id' => 'ths',
                'title' => 'سیستم تیکت',
                'subtitle' => 'درخواست‌های پشتیبانی و دسترسی به واحد فنی',
                'icon' => 'support_agent',
                'action' => 'route:ths',
                'keywords' => [
                    'ths', 'ticket', 'tickets', 'support', 'helpdesk', 'issue', 'incident', 'request', 'service desk',
                    'تیکت', 'پشتیبانی', 'درخواست', 'درخواست پشتیبانی', 'مشکل', 'خطا', 'گزارش مشکل', 'واحد فنی',
                    'آی تی', 'فنی', 'help desk', 'service desk', 'رفع اشکال', 'پشتیبان', 'اعلام خرابی',
                ],
            ],
            [
                'id' => 'reservation',
                'title' => 'رزرو فضا و منابع',
                'subtitle' => 'رزرو میز، پارکینگ، خودرو و جلسات کاری',
                'icon' => 'meeting_room',
                'action' => 'route:reservation',
                'keywords' => [
                    'reservation', 'booking', 'book', 'reserve', 'desk', 'parking', 'car', 'meeting', 'room', 'space',
                    'رزرو', 'رزرو میز', 'رزرو پارکینگ', 'رزرو خودرو', 'رزرو ماشین', 'رزرو جلسه', 'اتاق',
                    'فضا', 'منابع', 'نوبت', 'میز کار', 'اتاق جلسه', 'سالن', 'booking room', 'booking desk',
                ],
            ],
            [
                'id' => 'taskboard',
                'title' => 'تسک بورد',
                'subtitle' => 'مدیریت وظایف و پروژه‌ها با متد کانبان',
                'icon' => 'view_kanban',
                'action' => 'route:tasks',
                'keywords' => [
                    'task', 'tasks', 'board', 'kanban', 'project', 'todo', 'to do', 'workflow', 'sprint', 'agile',
                    'تسک', 'وظیفه', 'وظایف', 'پروژه', 'بورد', 'کانبان', 'کار', 'کارها', 'انجام', 'پیگیری',
                    'مدیریت کار', 'مدیریت پروژه', 'لیست کارها', 'برد', 'تسک بورد', 'برنامه‌ریزی', 'تیمی',
                ],
            ],
            [
                'id' => 'ads',
                'title' => 'فرصت‌های شغلی',
                'subtitle' => 'مشاهده موقعیت‌های استخدامی و شغلی شرکت',
                'icon' => 'work',
                'action' => 'route:ads',
                'keywords' => [
                    'ads', 'job', 'jobs', 'career', 'careers', 'hiring', 'hire', 'employment', 'vacancy', 'vacancies',
                    'فرصت', 'فرصت شغلی', 'شغل', 'شغل‌ها', 'استخدام', 'استخدامی', 'همکاری', 'کار', 'نیرو',
                    'جذب', 'استخدام نیرو', 'آگهی', 'آگهی شغلی', 'موقعیت شغلی', 'درخواست همکاری',
                ],
            ],
            [
                'id' => 'suggestion',
                'title' => 'پیشنهادات',
                'subtitle' => 'ارسال نظر و ایده به مدیریت با فرآیند ساختاریافته',
                'icon' => 'lightbulb',
                'action' => 'route:suggestion',
                'keywords' => [
                    'suggestion', 'suggestions', 'idea', 'feedback', 'improvement', 'proposal', 'opinion', 'comment',
                    'پیشنهاد', 'پیشنهادات', 'نظر', 'نظرات', 'بازخورد', 'ایده', 'بهبود', 'پیشنهاد بهبود',
                    'اعلام نظر', 'توصیه', 'درخواست بهبود', 'پیام به مدیریت', 'مدیرعامل', 'امتیاز', 'ثبت نظر',
                ],
            ],
            [
                'id' => 'contact',
                'title' => 'پیام‌رسان داخلی',
                'subtitle' => 'گفتگوی زنده و خصوصی با همکاران',
                'icon' => 'perm_contact_calendar',
                'action' => 'route:contact',
                'keywords' => [
                    'contact', 'contacts', 'chat', 'message', 'messages', 'internal', 'messenger', 'dm', 'conversation',
                    'پیام', 'پیام‌رسان', 'چت', 'گفتگو', 'گفت‌وگو', 'مخاطب', 'داخلی', 'همکار', 'همکاران',
                    'خصوصی', 'فوری', 'ارسال پیام', 'دایرکت', 'ارتباط', 'مکالمه', 'پیام خصوصی',
                ],
            ],
            [
                'id' => 'channels',
                'title' => 'کانال‌ها',
                'subtitle' => 'کانال‌های موضوعی و گروهی',
                'icon' => 'campaign',
                'action' => 'route:channels',
                'keywords' => [
                    'channel', 'channels', 'room', 'topic', 'broadcast', 'group', 'announce', 'feed',
                    'کانال', 'کانال‌ها', 'گروه', 'موضوعی', 'گروهی', 'پخش', 'اعلام', 'موضوع', 'کانال اطلاع‌رسانی',
                ],
            ],
            [
                'id' => 'energy',
                'title' => 'پرسش‌نامه انرژی',
                'subtitle' => 'خودارزیابی در چهار بُعد بدن، احساسات، ذهن، روح',
                'icon' => 'energy',
                'action' => 'route:energy',
                'keywords' => [
                    'energy', 'questionnaire', 'survey', 'self assessment', 'self-assessment', 'wellbeing', 'well-being',
                    'انرژی', 'پرسشنامه', 'پرسش‌نامه', 'خودارزیابی', 'ارزیابی', 'سلامت', 'سلامت روان', 'روحیه',
                    'بهره‌وری', 'تمرکز', 'توان', 'چهار بُعد', 'بدن', 'احساسات', 'ذهن', 'روح',
                    'تونی شوارتز', 'خودشناسی', 'پرسشنامه انرژی', 'energy audit',
                ],
            ],
            [
                'id' => 'auth',
                'title' => 'اختیارات',
                'subtitle' => 'مجوزهای تفویض‌شده توسط مدیرعامل به صورت شفاف',
                'icon' => 'verified_user',
                'action' => 'route:authority',
                'keywords' => [
                    'authority', 'authorities', 'permission', 'permissions', 'delegation', 'access', 'role', 'roles', 'privilege',
                    'اختیارات', 'مجوز', 'مجوزها', 'تفویض', 'دسترسی', 'سطح دسترسی', 'نقش', 'نقش‌ها', 'صلاحیت',
                    'مدیرعامل', 'سازمانی', 'شفاف', 'حکم', 'اختیار', 'اختیارات سازمانی', 'دسترسی‌ها',
                ],
            ],
            [
                'id' => 'analytics',
                'title' => 'تحلیل‌های منابع انسانی',
                'subtitle' => 'نمودارهای ترکیب جمعیتی، صلاحیت، سلامت واحد و مشارکت',
                'icon' => 'monitoring',
                'action' => 'route:analytics',
                'keywords' => [
                    'analytics', 'chart', 'charts', 'hr analytics', 'demographics', 'engagement', 'dashboard', 'statistics',
                    'تحلیل', 'تحلیل‌ها', 'آمار', 'نمودار', 'نمودارها', 'منابع انسانی', 'جمعیت‌شناسی', 'مشارکت',
                    'سلامت واحد', 'صلاحیت', 'گزارش تحلیلی', 'داشبورد تحلیلی',
                ],
            ],

            [
                'id' => 'profile',
                'title' => 'پروفایل کاربری',
                'subtitle' => 'ویرایش اطلاعات حساب و مشاهده وضعیت همکاران',
                'icon' => 'badge',
                'action' => 'url:/profile',
                'keywords' => [
                    'profile', 'account', 'user', 'me', 'settings', 'setting', 'avatar', 'my profile', 'account settings',
                    'پروفایل', 'حساب', 'کاربر', 'من', 'تنظیمات', 'تنظیمات حساب', 'عکس', 'آواتار', 'وضعیت', 'همکار',
                    'اطلاعات شخصی', 'مشخصات', 'ویرایش پروفایل', 'پروفایل من', 'حساب کاربری',
                ],
            ],
            [
                'id' => 'onboarding',
                'title' => 'آنبوردینگ',
                'subtitle' => 'آشنایی استخدام‌شدگان جدید با شرکت و راهنماها',
                'icon' => 'apartment',
                'action' => 'url:/profile?tab=onboarding',
                'keywords' => [
                    'onboarding', 'new hire', 'orientation', 'welcome', 'guide', 'first day', 'boarding',
                    'آنبوردینگ', 'استخدام', 'استخدام جدید', 'جدید', 'ورود', 'ورود به شرکت', 'آشنایی', 'معارفه',
                    'راهنما', 'شرکت', 'سیاست', 'مستندات', 'پرسنل جدید', 'آموزش اولیه', 'شروع کار',
                ],
            ],
            [
                'id' => 'documents',
                'title' => 'مدارک و اسناد',
                'subtitle' => 'بارگذاری و مدیریت مدارک هویتی و پرسنلی',
                'icon' => 'cloud_upload',
                'action' => 'url:/profile?tab=documents',
                'keywords' => [
                    'documents', 'document', 'upload', 'download', 'identity', 'id', 'personnel', 'attachments',
                    'مدارک', 'اسناد', 'بارگذاری', 'آپلود', 'دانلود', 'پرسنلی', 'هویت', 'شناسنامه', 'کارت ملی',
                    'تأیید', 'تایید', 'مدرک', 'مدارک هویتی', 'فایل مدارک', 'پیوست', 'ضمیمه', 'پرونده',
                ],
            ],
            [
                'id' => 'credentials',
                'title' => 'دسترسی‌های سازمانی',
                'subtitle' => 'نام کاربری و رمز عبور سامانه‌ها در کیف پول امن',
                'icon' => 'vpn_key',
                'action' => 'url:/profile?tab=credentials',
                'keywords' => [
                    'credentials', 'password', 'username', 'login', 'access', 'secret', 'vault', 'credential vault',
                    'سامانه', 'رمز', 'رمز عبور', 'نام کاربری', 'دسترسی', 'امن', 'کیف پول', 'کیف پول رمز', 'ورود',
                    'جستجو', 'کپی', 'اعتبارنامه', 'credential', 'login info', 'اطلاعات ورود', 'اطلاعات دسترسی',
                ],
            ],
            [
                'id' => 'skills',
                'title' => 'مهارت‌ها',
                'subtitle' => 'کاتالوگ مهارت‌ها، تأییدیه همکاران و درخواست مهارت جدید',
                'icon' => 'bolt',
                'action' => 'url:/profile?tab=skills',
                'keywords' => [
                    'skills', 'skill', 'talent', 'talent pool', 'endorsement', 'endorse', 'catalog', 'expertise',
                    'مهارت', 'مهارت‌ها', 'استعداد', 'تأییدیه', 'تایید مهارت', 'کاتالوگ', 'تخصص', 'توانمندی',
                    'درخواست مهارت', 'منتورینگ', 'راهنمایی',
                ],
            ],

            [
                'id' => 'radio',
                'title' => 'رادیو زنده',
                'subtitle' => 'موسیقی و برنامه‌های زنده برای افزایش تمرکز',
                'icon' => 'radio',
                'action' => 'event:radio',
                'keywords' => [
                    'radio', 'music', 'live', 'stream', 'audio', 'station', 'broadcast', 'playlist',
                    'رادیو', 'موسیقی', 'زنده', 'تمرکز', 'ایستگاه', 'پخش', 'آهنگ', 'صدا', 'موزیک',
                    'رادیو زنده', 'رادیو آنلاین', 'پخش زنده', 'موسیقی پس‌زمینه', 'آهنگ زنده',
                ],
            ],
            [
                'id' => 'calculator',
                'title' => 'ماشین حساب',
                'subtitle' => 'محاسبات سریع و شخصی',
                'icon' => 'computer',
                'action' => 'event:calculate',
                'keywords' => [
                    'calculator', 'math', 'compute', 'calculation', 'sum', 'subtract', 'multiply', 'divide',
                    'ماشین حساب', 'محاسبه', 'ریاضی', 'عدد', 'جمع', 'تفریق', 'ضرب', 'تقسیم', 'محاسبات',
                    'درصد', 'جمع و تفریق', 'محاسبه سریع', 'حساب', 'فرمول',
                ],
            ],
            [
                'id' => 'stopwatch',
                'title' => 'تایمر و آلارم',
                'subtitle' => 'شمارش معکوس و زمان‌بندی دستی',
                'icon' => 'timer',
                'action' => 'event:stopwatch',
                'keywords' => [
                    'stopwatch', 'timer', 'alarm', 'countdown', 'clock', 'reminder', 'timebox',
                    'تایمر', 'آلارم', 'شمارش', 'معکوس', 'زمان', 'ساعت', 'یادآور', 'هشدار',
                    'کرنومتر', 'زمان‌بندی', 'زمانبندی', 'زنگ', 'توقف‌سنج', 'شمارنده',
                ],
            ],
            [
                'id' => 'logout',
                'title' => 'خروج از حساب',
                'subtitle' => 'پایان نشست کاربری',
                'icon' => 'logout',
                'action' => 'event:logout',
                'keywords' => [
                    'logout', 'sign out', 'signout', 'exit', 'leave', 'disconnect',
                    'خروج', 'خروج از حساب', 'بستن', 'پایان', 'قطع', 'نشست', 'خارج شدن',
                    'خروج از سیستم', 'خروج امن', 'ترک حساب', 'log out',
                ],
            ],
        ]);
    }
}
