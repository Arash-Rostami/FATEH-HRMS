<?php

namespace App\Models\Traits;

use App\Enums\ResourceTypeIcon;
use App\Models\Department;
use Illuminate\Support\Facades\Blade;

trait HasTicketOptions
{
    public static array $requestTypeOptions = [
        'support' => 'پشتیبانی',
        'access' => 'دسترسی',
        'development' => 'توسعه',
    ];
    public static array $requestAreaOptions = [
        'support' => [
            '' => 'انتخاب نمایید',
            'windows_office' => 'ویندوز و نرم‌افزار',
            'vpn' => 'اینترنت، وای‌فای و VPN',
            'voip_telephone' => 'تلفن، VoIP و سیم‌کارت',
            'printer_scanner' => 'پرینتر، اسکنر و دستگاه‌های کپی',
            'remote_working' => 'دورکاری و FortiClient',
            'host_domain' => 'هاست، دامنه و وب‌سایت',
            'backups_restore' => 'پشتیبان‌گیری، بازیابی و بایگانی',
            'purchase_requests' => 'درخواست اقلام، خرید یا تعمیرات',
            'generate_bi_reports' => 'تولید یا ویرایش گزارش‌های سازمانی',
            'hardware_equipment' => 'سخت‌افزار و تجهیزات جانبی',
            'software_license' => 'درخواست نصب نرم‌افزار و لایسنس',
            'security_antivirus' => 'امنیت، فایروال و آنتی‌ویروس',
            'cctv_security' => 'دوربین مداربسته و امنیت فیزیکی',
            'training_guidance' => 'آموزش و مشاوره فنی',
            'other' => 'سایر',
        ],
        'access' => [
            '' => 'انتخاب نمایید',
            'bi' => 'BI (هوش تجاری)',
            'rahkaran' => 'راهکاران',
            'file_server' => 'فایل سرور',
            'mizito' => 'میزیتو',
            'chargoon' => 'چارگون',
            'hrms' => 'HRMS (منابع انسانی)',
            'bms' => 'BMS (مدیریت عملیاتی)',
            'sarv_crm' => 'CRM',
            'email' => 'ایمیل',
            'active_directory' => 'اکتیو دایرکتوری و اکانت ویندوز',
            'password_reset' => 'بازیابی رمز عبور و قفل‌گشایی حساب',
            'internet_proxy' => 'سطح دسترسی اینترنت و پروکسی',
            'database' => 'پایگاه داده (دیتابیس)',
            'project_management' => 'ابزارهای مدیریت پروژه ',
            'physical_access' => 'اکسس کنترل و گیت‌های تردد',
            'other' => 'سایر',
        ],
        'development' => [
            '' => 'انتخاب نمایید',
            'microservice' => 'میکروسرویس',
            'application' => 'اپلیکیشن',
            'automation' => 'اتوماسیون',
            'feature_request' => 'توسعه و ایجاد امکانات جدید',
            'api_integration' => 'توسعه API و یکپارچه‌سازی',
            'db_optimization' => 'طراحی و بهینه‌سازی ساختار دیتابیس',
            'other' => 'سایر',
        ],
    ];

    public static function getCustomAdminAreaLabelHtml(string $requestType, string $areaKey, ?string $departmentCode = null): string
    {
        $label = self::getCustomRequestAreaLabel($requestType, $areaKey, $departmentCode);

        if ($areaKey === '') return $label;

        $icon = self::getCustomHeroiconForArea($areaKey, $departmentCode);

        return Blade::render(
            "<div class='flex items-center gap-3'><x-icon name='{$icon}' class='w-5 h-5 text-gray-500 dark:text-gray-400' /> <span class='font-medium text-gray-700 dark:text-gray-200'>{$label}</span></div>"
        );
    }

    public static function getCustomHeroiconForArea(?string $area, ?string $departmentCode = null): string
    {
        return self::extractCustomIcon($area, $departmentCode) ?? self::getHeroiconForArea($area);
    }

    public static function getCustomMaterialIconForArea(?string $area, ?string $departmentCode = null): string
    {
        $custom = self::extractCustomIcon($area, $departmentCode);

        if ($custom !== null) {
            $materialIcon = ResourceTypeIcon::tryFrom($custom)?->getMaterialIcon();

            if ($materialIcon !== null) {
                return $materialIcon;
            }
        }

        return self::getMaterialIconForArea($area);
    }

    public static function getCustomRequestAreaLabel(string $requestType, string $requestArea, ?string $departmentCode = null): string
    {
        $options = self::getCustomRequestAreaOptions($departmentCode, $requestType);

        return $options[$requestArea] ?? self::getRequestAreaOptions($requestType, $requestArea);
    }

    public static function getCustomRequestAreaOptions(?string $departmentCode, string $requestType): array
    {
        $defaults = self::$requestAreaOptions[$requestType] ?? [];
        $options = self::fetchDepartmentTicketOptions($departmentCode);

        if (!$options) return $defaults;

        $customOptions = [];

        foreach ($options as $opt) {
            if (($opt['request_type'] ?? null) === $requestType && !empty($opt['area_key'])) {
                $customOptions[$opt['area_key']] = $opt['area_label'] ?? '';
            }
        }

        if (empty($customOptions)) return $defaults;

        return ['' => 'انتخاب نمایید'] + $customOptions;
    }

    public static function getCustomRequestTypeOptions(?string $departmentCode): array
    {
        $defaults = self::$requestTypeOptions;
        $options = self::fetchDepartmentTicketOptions($departmentCode);

        if (!$options) return $defaults;

        $types = [];
        foreach ($options as $opt) {
            if (isset($opt['request_type'])) {
                $type = $opt['request_type'];
                $types[$type] = $defaults[$type] ?? $type;
            }
        }
        return $types ?: $defaults;
    }

    public static function getHeroiconForArea(?string $area): string
    {
        static $icons = [
            'windows_office' => 'heroicon-o-computer-desktop',
            'vpn' => 'heroicon-o-globe-alt',
            'voip_telephone' => 'heroicon-o-phone',
            'printer_scanner' => 'heroicon-o-printer',
            'remote_working' => 'heroicon-o-home-modern',
            'host_domain' => 'heroicon-o-server',
            'backups_restore' => 'heroicon-o-archive-box',
            'purchase_requests' => 'heroicon-o-shopping-cart',
            'generate_bi_reports' => 'heroicon-o-chart-bar',
            'hardware_equipment' => 'heroicon-o-cpu-chip',
            'software_license' => 'heroicon-o-key',
            'security_antivirus' => 'heroicon-o-shield-check',
            'cctv_security' => 'heroicon-o-video-camera',
            'training_guidance' => 'heroicon-o-academic-cap',
            'bi' => 'heroicon-o-presentation-chart-line',
            'rahkaran' => 'heroicon-o-building-office-2',
            'file_server' => 'heroicon-o-folder-open',
            'mizito' => 'heroicon-o-puzzle-piece',
            'chargoon' => 'heroicon-o-cog-8-tooth',
            'hrms' => 'heroicon-o-users',
            'bms' => 'heroicon-o-building-library',
            'sarv_crm' => 'heroicon-o-briefcase',
            'email' => 'heroicon-o-envelope',
            'active_directory' => 'heroicon-o-identification',
            'password_reset' => 'heroicon-o-lock-open',
            'internet_proxy' => 'heroicon-o-signal',
            'database' => 'heroicon-o-circle-stack',
            'project_management' => 'heroicon-o-clipboard-document-list',
            'physical_access' => 'heroicon-o-finger-print',
            'microservice' => 'heroicon-o-rectangle-group',
            'application' => 'heroicon-o-device-phone-mobile',
            'automation' => 'heroicon-o-sparkles',
            'feature_request' => 'heroicon-o-plus-circle',
            'api_integration' => 'heroicon-o-arrows-right-left',
            'db_optimization' => 'heroicon-o-bolt',
            'other' => 'heroicon-o-ellipsis-horizontal-circle',
        ];

        return $icons[$area] ?? 'heroicon-o-question-mark-circle';
    }

    public static function getMaterialIconForArea(?string $area): string
    {
        static $icons = [
            'windows_office' => 'desktop_windows',
            'vpn' => 'vpn_key',
            'voip_telephone' => 'phone_in_talk',
            'printer_scanner' => 'print',
            'remote_working' => 'home_work',
            'host_domain' => 'dns',
            'backups_restore' => 'settings_backup_restore',
            'purchase_requests' => 'shopping_cart',
            'generate_bi_reports' => 'monitoring',
            'hardware_equipment' => 'devices',
            'software_license' => 'key',
            'security_antivirus' => 'security',
            'cctv_security' => 'videocam',
            'training_guidance' => 'school',
            'bi' => 'query_stats',
            'rahkaran' => 'business',
            'file_server' => 'folder_shared',
            'mizito' => 'extension',
            'chargoon' => 'settings_applications',
            'hrms' => 'groups',
            'bms' => 'factory',
            'sarv_crm' => 'handshake',
            'email' => 'email',
            'active_directory' => 'badge',
            'password_reset' => 'lock_reset',
            'internet_proxy' => 'router',
            'database' => 'database',
            'project_management' => 'checklist',
            'physical_access' => 'meeting_room',
            'microservice' => 'grid_view',
            'application' => 'app_shortcut',
            'automation' => 'smart_toy',
            'feature_request' => 'add_task',
            'api_integration' => 'api',
            'db_optimization' => 'speed',
            'other' => 'more_horiz',
        ];

        return $icons[$area] ?? 'help_outline';
    }

    public static function getRequestAreaOptions(string $requestType, ?string $requestArea): string
    {
        return self::$requestAreaOptions[$requestType][$requestArea] ?? 'یافت نشد';
    }

    private static function extractCustomIcon(?string $area, ?string $departmentCode): ?string
    {
        $options = self::fetchDepartmentTicketOptions($departmentCode);

        if ($options) {
            foreach ($options as $opt) {
                if (($opt['area_key'] ?? null) === $area && !empty($opt['icon'])) {
                    return $opt['icon'];
                }
            }
        }

        return null;
    }

    private static function fetchDepartmentTicketOptions(?string $departmentCode): ?array
    {
        if (!$departmentCode) return null;

        static $cache = [];

        if (array_key_exists($departmentCode, $cache)) return $cache[$departmentCode];

        $department = Department::getCachedModels()->get($departmentCode);
        return $cache[$departmentCode] = ($department && !empty($department->ticket_options))
            ? $department->ticket_options
            : null;
    }
}
