<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class ModulesGuideWidget extends Widget
{
    protected static ?int $sort = 0;

    protected string $view = 'filament.widgets.guide';

    protected int|string|array $columnSpan = 'full';

    public function getGroupedModules(): Collection
    {
        $categoriesOrder = [
            'محتوا و ارتباطات',
            'عملیات و منابع',
            'سیستم‌ها و ابزارها',
            'کاربران و سازمان',
        ];

        $meta = $this->moduleMeta();
        $icons = $this->moduleFilamentIcons();

        return collect(config('modules', []))
            ->reject(fn (array $module) => tenantOff($module['id'] ?? ''))
            ->map(function (array $module) use ($meta, $icons) {
                $id = $module['id'] ?? '';
                return array_merge(
                    $module,
                    $meta[$id] ?? [],
                    $icons[$id] ?? []
                );
            })
            ->groupBy(fn(array $module) => $module['major_category'] ?? 'محتوا و ارتباطات')
            ->sortBy(fn($_, $key) => array_search($key, $categoriesOrder, true))
            ->mapWithKeys(function ($modules, $category) {
                return [md5($category) => [
                    'name' => $category,
                    'modules' => $modules,
                ]];
            });
    }

    public function getTools(): array
    {
        return array_map(
            fn (string $icon, string $key): array => [
                'icon' => $icon,
                'label' => __("resources/dashboard/strings.guide.tools.{$key}.label"),
                'desc' => __("resources/dashboard/strings.guide.tools.{$key}.desc"),
            ],
            ['schema', 'filter_alt', 'layers', 'manage_search', 'tune', 'bolt', 'edit_note', 'description', 'table_chart', 'account_tree', 'query_stats', 'speed'],
            ['relation_managers', 'filters', 'groups', 'search', 'toggle_sort', 'actions', 'form', 'infolist', 'table', 'nested_resources', 'stats', 'deferred_analytics'],
        );
    }

    protected function moduleFilamentIcons(): array
    {
        return [
            'announce' => ['filament_icon' => 'heroicon-o-megaphone'],
            'feed' => ['filament_icon' => 'heroicon-o-rss'],
            'calendar' => ['filament_icon' => 'heroicon-o-calendar-days'],
            'gallery' => ['filament_icon' => 'heroicon-o-photo'],
            'reports' => ['filament_icon' => 'heroicon-o-presentation-chart-line'],
            'links' => ['filament_icon' => 'heroicon-o-arrow-top-right-on-square'],
            'faq' => ['filament_icon' => 'heroicon-o-question-mark-circle'],
            'notification-guide' => ['filament_icon' => 'heroicon-o-bell'],
            'analytics' => ['filament_icon' => 'heroicon-o-chart-bar'],
            'profile' => ['filament_icon' => 'heroicon-o-identification'],
            'status' => ['filament_icon' => 'heroicon-o-user-group'],
            'skills' => ['filament_icon' => 'heroicon-o-bolt'],
            'onboarding' => ['filament_icon' => 'heroicon-o-building-office-2'],
            'documents' => ['filament_icon' => 'heroicon-o-cloud-arrow-up'],
            'credentials' => ['filament_icon' => 'heroicon-o-key'],
            'ads' => ['filament_icon' => 'heroicon-o-briefcase'],
            'suggestion' => ['filament_icon' => 'heroicon-o-light-bulb'],
            'taskboard' => ['filament_icon' => 'heroicon-o-view-columns'],
            'projects' => ['filament_icon' => 'heroicon-o-rectangle-stack'],
            'tasksheet' => ['filament_icon' => 'heroicon-o-document-chart-bar'],
            'ai-report' => ['filament_icon' => 'heroicon-o-document-chart-bar'],
            'correspondence' => ['filament_icon' => 'heroicon-o-envelope-open'],
            'dms' => ['filament_icon' => 'heroicon-o-folder-open'],
            'launchpad' => ['filament_icon' => 'heroicon-o-rocket-launch'],
            'media' => ['filament_icon' => 'heroicon-o-play-circle'],
            'ths' => ['filament_icon' => 'heroicon-o-lifebuoy'],
            'reservation' => ['filament_icon' => 'heroicon-o-building-office'],
            'contact' => ['filament_icon' => 'heroicon-o-chat-bubble-left-right'],
            'channel' => ['filament_icon' => 'heroicon-o-hashtag'],
            'energy' => ['filament_icon' => 'heroicon-o-bolt'],
            'authority' => ['filament_icon' => 'heroicon-o-clipboard-document-check'],
            'reminder' => ['filament_icon' => 'heroicon-o-clock'],
            'notify' => ['filament_icon' => 'heroicon-o-envelope'],
            'ai-assistant' => ['filament_icon' => 'heroicon-o-cpu-chip'],
            'others' => ['filament_icon' => 'heroicon-o-sparkles'],
        ];
    }

    protected function moduleMeta(): array
    {
        return [
            'announce' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'مخاطبان اطلاعیه را مشخص کنید و برای نمایش بالای فهرست، آن را سنجاق کنید.'],
            'feed' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'برای هر خبر، عنوان، منبع و توضیح کوتاه را ثبت کنید. پیش از انتشار، مخاطبان خبر را بررسی کنید.'],
            'calendar' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'برای هر رویداد، تاریخ و ساعت شروع و پایان را بررسی کنید. از فیلتر تاریخ برای پیدا کردن رویدادهای یک بازه استفاده کنید.'],
            'gallery' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'پیش از انتشار، پیش‌نمایش تصاویر را ببینید و مخاطبان مجموعه را مشخص کنید.'],
            'links' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'برای هر سامانه، نام و نشانی روشن بنویسید. اگر نشانی داخل شرکت با نشانی اینترنتی فرق دارد، هر دو را بررسی کنید.'],
            'faq' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'پرسش را با همان واژه‌هایی بنویسید که کارکنان به کار می‌برند. اگر پاسخ چند مرحله دارد، آن‌ها را به‌ترتیب بنویسید.'],
            'notification-guide' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'محتوای این راهنما از طریق پنل ادمین قابل ویرایش نیست — برای افزودن یا اصلاح متن یک نشانگر، ردیف مربوطه را مستقیماً در کاتالوگ اختصاصی نشانگرها (App\\Services\\Menu\\BadgeLegendCatalog) ویرایش کنید.'],
            'ads' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'شرح شغل و شرایط استخدام را روشن بنویسید. پس از پایان جذب، آگهی را غیرفعال کنید.'],
            'suggestion' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'پیشنهادها را بر اساس وضعیت بررسی پیدا کنید. در جزئیات هر پیشنهاد، نظر واحدهای مرتبط و تصمیم نهایی را ببینید.'],
            'contact' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'برای پیدا کردن یک گفتگو، ابتدا نام یکی از طرف‌های گفتگو را در فهرست جست‌وجو کنید.'],
            'channel' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'پیش از عمومی‌کردن گروه، پیام‌های قبلی آن را بررسی کنید. اعضای تازهٔ گروه عمومی به پیام‌های قبلی دسترسی دارند. گروه‌های پروژه خصوصی می‌مانند.'],
            'reservation' => ['major_category' => 'عملیات و منابع', 'admin_tip' => 'منابع و قوانین رزرو را در بخش‌های مربوط تنظیم کنید. در جدول رزروها می‌توانید موارد را بر اساس منبع یا وضعیت پیدا کنید. برای پایان‌دادن به رزروِ در حال استفاده از «آزادسازی» استفاده کنید.'],
            'reports' => ['major_category' => 'عملیات و منابع', 'admin_tip' => 'مخاطبان و تاریخ انقضا را پیش از انتشار مشخص کنید. گزارش‌های مهم را برای نمایش بالای فهرست سنجاق کنید.'],
            'taskboard' => ['major_category' => 'عملیات و منابع', 'admin_tip' => 'برای پیدا کردن کارهای نیازمند پیگیری، جدول را بر اساس مسئول، وضعیت یا مهلت انجام بررسی کنید.'],
            'projects' => ['major_category' => 'عملیات و منابع', 'admin_tip' => 'مالک، اعضا و واحدهای دارای دسترسی را مشخص کنید. پروژه‌های تمام‌شده را بایگانی کنید تا از پروژه‌های جاری جدا شوند.'],
            'tasksheet' => ['major_category' => 'عملیات و منابع', 'admin_tip' => 'تسک‌شیت از وظایف و پروژه‌ها ساخته می‌شود. برای دیدن گزارش یک نفر، از گزینهٔ تسک‌شیت در صفحهٔ مرتبط استفاده کنید.'],
            'ai-report' => ['major_category' => 'عملیات و منابع', 'admin_tip' => 'ساخت و ویرایش گزارش در پنل کاربر انجام می‌شود. در پنل مدیریت می‌توانید گزارش را بررسی یا غیرفعال کنید. فیلتر پیش‌نویس‌های قدیمی به پیدا کردن گزارش‌های نیمه‌تمام کمک می‌کند.'],
            'correspondence' => ['major_category' => 'عملیات و منابع', 'admin_tip' => 'نوشتن، امضا و ارسال نامه در پنل کاربر انجام می‌شود. سربرگ رسمی را در «سربرگ» تنظیم کنید و دسترسی «مکاتبات» و «سربرگ» را به نقش‌های مربوط بدهید.'],
            'dms' =>['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'هنگام انتشار سند، نسخه و مخاطبان آن را بررسی کنید. پس از انتشار، تأیید دریافت و مطالعهٔ سند را پیگیری کنید.'],
            'launchpad' => ['major_category' => 'عملیات و منابع', 'admin_tip' => 'برنامهٔ تازه از پنل کاربر ثبت می‌شود. برای توقف دسترسی، برنامه را غیرفعال کنید. جدول را بر اساس دپارتمان گروه‌بندی کنید تا ظرفیت مصرف‌شده را ببینید.'],
            'media' => ['major_category' => 'عملیات و منابع', 'admin_tip' => 'ابتدا رسانهٔ اصلی یا سری را بسازید، سپس جلسه‌ها را از زبانهٔ «جلسه‌ها» اضافه و با کشیدن مرتب کنید. جلسهٔ تاریخ‌دار تا زمان انتشار قفل می‌ماند و پیشرفت کاربران در منبع «پیشرفت دانش» دیده می‌شود.'],
            'ths' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'برای پیدا کردن درخواست‌های نیازمند رسیدگی، تیکت‌ها را بر اساس واحد و وضعیت بررسی کنید. نتیجهٔ پاسخ‌گویی واحدها در بخش آمار قابل پیگیری است.'],
            'authority' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'در هر ردیف مشخص کنید مسئول کار کیست و چه اختیاری دارد. در حالت تمرکز می‌توانید همان ردیف را در دید نگه دارید.'],
            'energy' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'برای مقایسهٔ نتایج دوره‌ها، بازهٔ زمانی را مشخص کنید و امتیاز هر زمینه را جداگانه ببینید.'],
            'reminder' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'هر کاربر یادآوری‌های خودش را می‌سازد و تنظیم می‌کند. اعلان سررسید از سامانه فرستاده می‌شود.'],
            'notify' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'پیش از فعال‌کردن قانون، با «ارسال آزمایشی» پیام را در ایمیل خودتان ببینید. اگر پیام‌های زیادی ارسال می‌شود، از «فقط با تغییر این اطلاعات» یا بازهٔ اعتبار استفاده کنید.'],
            'others' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'تنظیم ظاهر و استفاده از ابزارهای جانبی، مانند ماشین‌حساب و زمان‌سنج، از پنل کاربر انجام می‌شود.'],
            'ai-assistant' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'دسترسی کارمندان به دستیار برای هر سازمان جداگانه تعیین می‌شود. برای تغییر آن، از مدیر سامانه بخواهید تنظیم دستیار را بررسی کند.'],
            'profile' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'برای اصلاح اطلاعات پرسنلی، پروندهٔ کاربر را باز کنید. اطلاعات ثبت‌شده در آمار منابع انسانی هم به کار می‌رود.'],
            'skills' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'درخواست‌ها را بر اساس وضعیت و واحد بررسی کنید. «تقاضای پنهان» نشان می‌دهد کارکنان کدام مهارت‌های خارج از فهرست را بیشتر جست‌وجو کرده‌اند.'],
            'onboarding' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'راهنما، ویدیوها و دستورالعمل‌ها را به ترتیب استفاده در روزهای اول همکاری قرار دهید.'],
            'documents' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'مدارک بارگذاری‌شده را بررسی کنید و نتیجه را در وضعیت مدرک ثبت کنید تا کاربر بتواند آن را پیگیری کند.'],
            'credentials' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'پیش از ثبت اطلاعات ورود، کاربر و سامانهٔ مربوط را بررسی کنید. رمز را فقط در اختیار همان کاربر قرار دهید.'],
            'analytics' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'آمار از اطلاعات حساب و پروفایل کارکنان ساخته می‌شود. برای اصلاح آمار، ابتدا اطلاعات ثبت‌شدهٔ کاربر را بررسی کنید.'],
            'status' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'هر کاربر وضعیت حضور خودش را تعیین می‌کند. اطلاعات تماس و جایگاه سازمانی از پروندهٔ کارکنان نمایش داده می‌شود.'],
        ];
    }
}
