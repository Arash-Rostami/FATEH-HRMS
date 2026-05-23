<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class ModulesGuideWidget extends Widget
{
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

        return collect(config('modules', []))
            ->map(fn (array $module) => [...$module, ...($this->moduleMeta()[$module['id']] ?? []), ...($this->moduleFilamentIcons()[$module['id']] ?? [])])
            ->groupBy(fn (array $module) => $module['major_category'] ?? 'محتوا و ارتباطات')
            ->sortBy(fn ($_, $key) => array_search($key, $categoriesOrder, true));
    }

    protected function moduleMeta(): array
    {
        return [
            'announce' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'مدیریت اطلاعیه‌ها با اولویت نمایش، انتشار هدفمند، و بروزرسانی مستمر پیام‌ها.'],
            'feed' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'مدیریت فید خبری با دسته‌بندی محتوا و بهینه‌سازی تجربه مصرف خبر.'],
            'calendar' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'ثبت و بروزرسانی رویدادها، مناسبت‌ها، و برنامه‌ریزی تقویمی سازمان.'],
            'gallery' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'انتخاب محتوای رسانه‌ای، مدیریت کیفیت نمایش، و حفظ هویت بصری سازمان.'],
            'links' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'ساماندهی لینک‌های سازمانی، کنترل سلامت آدرس‌ها، و دسته‌بندی دسترسی ابزارها.'],
            'faq' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'مدیریت پایگاه دانش سوالات پرتکرار با بازنویسی پاسخ‌های دقیق و کاربردی.'],
            'ads' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'انتشار فرصت‌ها، کنترل وضعیت فعال/بایگانی، و گزارش‌گیری سریع از نیازهای جذب.'],
            'suggestion' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'هدایت جریان پیشنهادات با فیلتر مرحله، ثبت بازخورد، و پایش نرخ مشارکت.'],
            'contact' => ['major_category' => 'محتوا و ارتباطات', 'admin_tip' => 'کنترل ارتباطات داخلی، پایش کیفیت تعاملات، و نگهداری تجربه پیام‌رسانی سازمانی.'],

            'reservation' => ['major_category' => 'عملیات و منابع', 'admin_tip' => 'تعریف منابع و قوانین رزرو، مدیریت تعلیق یا لغو رزرو، و پایش ظرفیت‌ها.'],
            'reports' => ['major_category' => 'عملیات و منابع', 'admin_tip' => 'ثبت گزارش‌های رسمی، کنترل نسخه و فایل، و بازیابی سریع اطلاعات مدیریتی.'],
            'taskboard' => ['major_category' => 'عملیات و منابع', 'admin_tip' => 'مدیریت فرآیند کانبان با CRUD وظایف، جابجایی وضعیت، و رصد پیشرفت تیم‌ها.'],

            'dms' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'مدیریت چرخه سند، امضا و مشاهده، همراه با ساختاردهی استاندارد رویه‌ها.'],
            'ths' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'مدیریت صف درخواست‌ها با فیلتر اولویت، دسته‌بندی موضوع، و پیگیری پاسخگویی.'],
            'energy' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'تحلیل نتایج ارزیابی انرژی، فیلتر بر اساس بازه زمانی، و رصد روندهای تیمی.'],
            'radio' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'تنظیم ایستگاه‌ها، مدیریت تنوع محتوای صوتی، و بهبود تجربه محیط کار.'],
            'others' => ['major_category' => 'سیستم‌ها و ابزارها', 'admin_tip' => 'هدایت و نگهداری امکانات تکمیلی مانند ابزارهای سبک و قابلیت‌های شخصی‌سازی.'],

            'profile' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'CRUD کامل پروفایل، مدیریت وضعیت حضور، و اتصال داده کاربر با واحد سازمانی.'],
            'onboarding' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'ایجاد مسیر شروع به کار، ویرایش محتوای آموزشی، و مدیریت نسخه‌های فعال آنبوردینگ.'],
            'documents' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'تعریف مدارک الزامی، بررسی وضعیت تایید، و کنترل چرخه دریافت و نگهداری.'],
            'credentials' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'مدیریت دسترسی‌های سازمانی با ثبت امن، بروزرسانی موردی، و بایگانی دسترسی‌های منسوخ.'],
            'auth' => ['major_category' => 'کاربران و سازمان', 'admin_tip' => 'ثبت و شفاف‌سازی اختیارات تفویض‌شده با تاریخ اعتبار و توضیح دامنه اجرا.'],
        ];
    }

    protected function moduleFilamentIcons(): array
    {
        return [
            'announce' => ['filament_icon' => 'heroicon-o-megaphone'],
            'feed' => ['filament_icon' => 'heroicon-o-rss'],
            'calendar' => ['filament_icon' => 'heroicon-o-calendar-days'],
            'gallery' => ['filament_icon' => 'heroicon-o-photo'],
            'reports' => ['filament_icon' => 'heroicon-o-chart-bar'],
            'links' => ['filament_icon' => 'heroicon-o-link'],
            'faq' => ['filament_icon' => 'heroicon-o-question-mark-circle'],
            'profile' => ['filament_icon' => 'heroicon-o-identification'],
            'onboarding' => ['filament_icon' => 'heroicon-o-building-office-2'],
            'documents' => ['filament_icon' => 'heroicon-o-cloud-arrow-up'],
            'credentials' => ['filament_icon' => 'heroicon-o-key'],
            'ads' => ['filament_icon' => 'heroicon-o-briefcase'],
            'suggestion' => ['filament_icon' => 'heroicon-o-light-bulb'],
            'taskboard' => ['filament_icon' => 'heroicon-o-clipboard-document-list'],
            'dms' => ['filament_icon' => 'heroicon-o-folder'],
            'ths' => ['filament_icon' => 'heroicon-o-ticket'],
            'reservation' => ['filament_icon' => 'heroicon-o-calendar-days'],
            'contact' => ['filament_icon' => 'heroicon-o-chat-bubble-left-ellipsis'],
            'energy' => ['filament_icon' => 'heroicon-o-bolt'],
            'auth' => ['filament_icon' => 'heroicon-o-shield-check'],
            'radio' => ['filament_icon' => 'heroicon-o-signal'],
            'others' => ['filament_icon' => 'heroicon-o-sparkles'],
        ];
    }
}
