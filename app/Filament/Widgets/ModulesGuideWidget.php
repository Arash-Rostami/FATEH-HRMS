<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class ModulesGuideWidget extends Widget
{
    protected static ?int $sort = -1;

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
            ['schema', 'filter_alt', 'layers', 'manage_search', 'tune', 'bolt', 'edit_note', 'description', 'table_chart', 'account_tree', 'calculate', 'speed'],
            ['relation_managers', 'filters', 'groups', 'search', 'toggle_sort', 'actions', 'form', 'infolist', 'table', 'nested_resources', 'table_summaries', 'deferred_analytics'],
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
            'profile' => ['filament_icon' => 'heroicon-o-identification'],
            'skills' => ['filament_icon' => 'heroicon-o-bolt'],
            'onboarding' => ['filament_icon' => 'heroicon-o-building-office-2'],
            'documents' => ['filament_icon' => 'heroicon-o-cloud-arrow-up'],
            'credentials' => ['filament_icon' => 'heroicon-o-key'],
            'ads' => ['filament_icon' => 'heroicon-o-briefcase'],
            'suggestion' => ['filament_icon' => 'heroicon-o-light-bulb'],
            'taskboard' => ['filament_icon' => 'heroicon-o-view-columns'],
            'dms' => ['filament_icon' => 'heroicon-o-folder-open'],
            'ths' => ['filament_icon' => 'heroicon-o-lifebuoy'],
            'reservation' => ['filament_icon' => 'heroicon-o-building-office'],
            'contact' => ['filament_icon' => 'heroicon-o-chat-bubble-left-right'],
            'channel' => ['filament_icon' => 'heroicon-o-hashtag'],
            'energy' => ['filament_icon' => 'heroicon-o-bolt'],
            'auth' => ['filament_icon' => 'heroicon-o-shield-check'],
            'radio' => ['filament_icon' => 'heroicon-o-signal'],
            'others' => ['filament_icon' => 'heroicon-o-sparkles'],
        ];
    }

    protected function moduleMeta(): array
    {
        return [
            'announce' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'از طریق فرم‌های غنی (Rich Text) محتوای اطلاعیه‌ها را قالب‌بندی کنید. از قابلیت انتشار هدفمند بهره ببرید و پیام‌ها را بر اساس اولویت‌بندی در جداول پایش کنید.'],
            'feed' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'برای مدیریت اخبار سازمانی از امکانات Relation Managers جهت پایش نظرات و واکنش‌ها استفاده نمایید. ستون‌های جدول را برای بررسی سریع تیترها شخصی‌سازی کنید.'],
            'calendar' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'رویدادها را با استفاده از تقویم تعاملی برنامه‌ریزی کنید. فیلترهای زمانی (Date Filters) در لیست رویدادها، بازیابی مناسبت‌های پیش‌رو را تسهیل می‌بخشد.'],
            'gallery' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'از کامپوننت‌های آپلود فایل با قابلیت پیش‌نمایش تصویر (Image Previews) استفاده کنید. امکان Bulk Actions برای حذف یا تغییر وضعیت گروهی تصاویر بسیار کاربردی است.'],
            'links' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'لینک‌های دسترسی سریع را با آیکون‌های متناسب ثبت نمایید. از قابلیت مرتب‌سازی (Sorting) در جداول برای اولویت‌بندی نمایش ابزارها بهره بگیرید.'],
            'faq' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'پایگاه دانش را با دسته‌بندی‌های اصولی مدیریت کنید. فیلدهای Infolist به شما اجازه می‌دهد پیش‌نمایش دقیقی از نحوه نمایش پرسش‌ها به کاربران داشته باشید.'],
            'ads' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'فرصت‌های شغلی را با جزئیات دقیق منتشر کنید. از طریق سوئیچ‌های Toggle وضعیت فعال یا بایگانی بودن فرصت‌ها را به‌سرعت در نمای جدول کنترل نمایید.'],
            'suggestion' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'جریان پیشنهادات را از طریق فیلترهای وضعیت (Status Filters) رهگیری کنید. امکان ثبت بازخورد و تصمیم‌گیری مدیریتی مستقیماً از بخش Infolist یا Action های سفارشی فراهم است.'],
            'contact' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'روند ارتباطات داخلی را پایش کنید. با استفاده از قابلیت جستجوی جامع (Global Search)، تاریخچه تعاملات و پیام‌های سیستمی را با دقت بررسی نمایید.'],
            'channel' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'وضعیت کانال‌ها و پیام‌ها را از طریق Relation Managers مدیریت کنید. مالکیت و اعضای هر کانال با استفاده از Attach/Detach Actions به‌سرعت قابل تنظیم است.'],
            'reservation' => ['major_category' => 'عملیات و منابع', 'admin_tip' => 'فضاها و منابع را به‌دقت پیکربندی کنید. از ابزارهای فیلترینگ برای مدیریت ظرفیت‌ها و از Action های تعبیه‌شده برای تعلیق یا لغو رزروهای تداخل‌دار استفاده نمایید.'],
            'reports' => ['major_category' => 'عملیات و منابع', 'admin_tip' => 'گزارش‌های رسمی را با کنترل نسخه دقیق ثبت کنید. از قابلیت Export در Bulk Actions برای دریافت خروجی‌های استاندارد مدیریتی (CSV/Excel) بهره بگیرید.'],
            'taskboard' => ['major_category' => 'عملیات و منابع', 'admin_tip' => 'جریان کاری تسک‌ها را با ابزار کانبان رهگیری کنید. از طریق Relation Managers پیشرفت وظایف زیرمجموعه را بررسی و وضعیت‌ها را به‌روزرسانی نمایید.'],
            'dms' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'مخزن اسناد را با ساختاردهی استاندارد مدیریت کنید. کنترل نسخه‌ها و دسترسی‌ها را از طریق فرم‌های پیچیده و قابلیت اعتبارسنجی یکپارچه تضمین نمایید.'],
            'ths' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'صف تیکت‌ها را بر اساس اولویت و دپارتمان فیلتر کنید. قابلیت گروه‌بندی (Grouping) در جداول، نمای شفافی از حجم درخواست‌های در حال بررسی ارائه می‌دهد.'],
            'energy' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'نتایج ارزیابی انرژی را تحلیل کنید. استفاده از ویجت‌های نموداری (Chart Widgets) و فیلترهای بازه زمانی، رهگیری الگوهای رفتاری سازمان را ساده می‌سازد.'],
            'radio' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'ایستگاه‌های رادیویی زنده را تنظیم کنید. با استفاده از ستون‌های Toggle امکان فعال یا غیرفعال‌سازی سریع کانال‌های در حال پخش فراهم است.'],
            'others' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'تنظیمات امکانات رفاهی و ابزارهای کوچک سازمان را یکپارچه مدیریت کنید. از قابلیت شخصی‌سازی ستون‌ها (Column Toggling) برای تمرکز بر داده‌های کلیدی استفاده کنید.'],
            'profile' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'پروفایل‌های پرسنلی را با جزئیات کامل سازمانی یکپارچه کنید. از Relation Managers برای مدیریت اسناد هویتی و سوابق فعالیت‌های کاربر در سیستم بهره‌مند شوید.'],
            'skills' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'صف درخواست‌های مهارت را با فیلترهای وضعیت و دپارتمان پیگیری کنید و از Bulk Actions برای تأیید یا رد گروهی استفاده نمایید. ویجت «تقاضای پنهان» در داشبورد، پرتکرارترین مهارت‌های غایب از کاتالوگ را برای افزودن سریع نمایش می‌دهد.'],
            'onboarding' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'مسیرهای شروع‌به‌کار را به‌صورت گام‌به‌گام طراحی کنید. با استفاده از ابزار مرتب‌سازی سطرها (Reorder Actions)، اولویت و توالی مراحل را به‌راحتی تنظیم نمایید.'],
            'documents' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'مدارک الزامی کاربران را کنترل کنید. از قابلیت‌های نمایش وضعیت با استفاده از آیکون‌ها و رنگ‌های مختلف (Badge Columns) برای رهگیری سریع تاییدیه اسناد استفاده کنید.'],
            'credentials' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'اطلاعات کاربری و دسترسی‌های سازمانی را با امنیت بالا نگهداری کنید. فیلترهای پیشرفته برای رهگیری تاریخ انقضا و وضعیت اکانت‌های اختصاص‌یافته در دسترس است.'],
            'auth' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'اختیارات تفویض‌شده را شفاف‌سازی کنید. مدیریت تاریخ‌های اعتبار از طریق فیلترهای زمانی و امکان تمدید گروهی با Bulk Actions تسهیل شده است.'],
        ];
    }
}
