<?php

namespace App\Models\Concerns;

use App\Enums\ResourceTypeIcon;
use App\Models\Department;
use App\Services\Cache\ModelCacheVersion;

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
        $svg = svg($icon, 'w-5 h-5 text-gray-500 dark:text-gray-400')->toHtml();

        return "<div class='flex items-center gap-3'>{$svg} <span class='font-medium text-gray-700 dark:text-gray-200'>{$label}</span></div>";
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

    public static function getCustomRequestAreaLabel(?string $requestType, ?string $requestArea, ?string $departmentCode = null): string
    {
        $area = self::normalizeOptionKey($requestArea);

        if ($area === '') return '';

        $type = self::normalizeOptionKey($requestType) ?: null;
        $own = $departmentCode ? self::customOptionRows($departmentCode) : [];
        $defaults = self::defaultAreaRows();

        return self::firstAreaLabel($own, $area, $type)
            ?? self::firstAreaLabel($own, $area)
            ?? self::firstAreaLabel($defaults, $area, $type)
            ?? self::firstAreaLabel($defaults, $area)
            ?? self::firstAreaLabel(self::customOptionRows(), $area)
            ?? trim((string)$requestArea);
    }

    public static function searchRequestKeys(string $column, string $term): array
    {
        $needle = self::normalizeOptionKey($term);

        if ($needle === '') return [];

        $labels = self::labelsByKey($column);

        return array_values(array_filter(
            self::knownRequestKeys($column),
            fn($key) => collect([$key, ...($labels[self::normalizeOptionKey($key)] ?? [])])
                ->contains(fn($label) => str_contains(self::normalizeOptionKey($label), $needle)),
        ));
    }

    public static function requestKeyLabelMap(string $column): array
    {
        $map = [];

        foreach (self::knownRequestKeys($column) as $key) {
            $map[$key] = $column === 'request_type'
                ? self::getCustomRequestTypeLabel($key)
                : self::getCustomRequestAreaLabel(null, $key);
        }

        return $map;
    }

    private static function labelsByKey(string $column): array
    {
        $labels = [];

        if ($column === 'request_type') {
            foreach (self::$requestTypeOptions as $key => $label) {
                $labels[self::normalizeOptionKey($key)][] = $label;
            }

            foreach (self::customOptionRows() as $row) {
                $labels[$row['type']][] = $row['rawType'];
            }

            return $labels;
        }

        foreach ([...self::defaultAreaRows(), ...self::customOptionRows()] as $row) {
            if (filled($row['label'])) $labels[$row['area']][] = $row['label'];
        }

        return $labels;
    }

    public static function requestKeyVariants(string $column, array $keys): array
    {
        $wanted = array_map(fn($key) => self::normalizeOptionKey($key), $keys);

        return array_values(array_unique([
            ...$keys,
            ...array_filter(self::knownRequestKeys($column), fn($key) => in_array(self::normalizeOptionKey($key), $wanted, true)),
        ]));
    }

    public static function requestTypeFilterOptions(): array
    {
        return self::filterOptions('request_type', fn($key) => self::getCustomRequestTypeLabel($key));
    }

    public static function requestAreaFilterOptions(): array
    {
        return self::filterOptions('request_area', fn($key) => self::getCustomRequestAreaLabel(null, $key));
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

    public static function getCustomRequestTypeLabel(?string $requestType, ?string $departmentCode = null): string
    {
        $type = self::normalizeOptionKey($requestType);

        if ($type === '') return '';

        $custom = fn(?string $code) => collect(self::customOptionRows($code))->firstWhere('type', $type)['rawType'] ?? null;

        return self::systemTypeLabel($type)
            ?? $custom($departmentCode)
            ?? $custom(null)
            ?? trim((string)$requestType);
    }

    public static function allRequestTypeOptions(): array
    {
        $types = self::$requestTypeOptions;

        foreach (Department::getCachedModels() as $department) {
            foreach ($department->ticket_options ?? [] as $opt) {
                if (!empty($opt['request_type'])) {
                    $types[$opt['request_type']] ??= $opt['request_type'];
                }
            }
        }

        return $types;
    }

    public static function allRequestAreaOptions(): array
    {
        $areas = [];

        foreach (self::$requestAreaOptions as $options) {
            $areas += array_filter($options, fn($label, $key) => $key !== '', ARRAY_FILTER_USE_BOTH);
        }

        foreach (Department::getCachedModels() as $department) {
            foreach ($department->ticket_options ?? [] as $opt) {
                if (!empty($opt['area_key'])) {
                    $areas[$opt['area_key']] ??= $opt['area_label'] ?? $opt['area_key'];
                }
            }
        }

        asort($areas);

        return $areas;
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

        return $icons[self::normalizeOptionKey($area)] ?? 'heroicon-o-question-mark-circle';
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

        return $icons[self::normalizeOptionKey($area)] ?? 'help_outline';
    }

    private static function extractCustomIcon(?string $area, ?string $departmentCode): ?string
    {
        $key = self::normalizeOptionKey($area);

        if ($key === '') return null;

        $icon = fn(array $rows) => collect($rows)->first(fn($row) => $row['area'] === $key && !empty($row['icon']))['icon'] ?? null;

        if ($departmentCode && ($own = $icon(self::customOptionRows($departmentCode)))) return $own;

        if (collect(self::defaultAreaRows())->contains('area', $key)) return null;

        return $icon(self::customOptionRows());
    }

    private static function normalizeOptionKey(?string $value): string
    {
        $value = strtr(trim((string)$value), ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک']);

        return mb_strtolower(preg_replace('/[\s\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{0640}\x{FEFF}]+/u', '', $value) ?? $value);
    }

    private static function customOptionRows(?string $departmentCode = null): array
    {
        return Department::getCachedModels()
            ->when($departmentCode, fn($departments) => $departments->only([$departmentCode]))
            ->flatMap(fn($department) => $department->ticket_options ?? [])
            ->filter(fn($option) => is_array($option))
            ->map(fn($option) => [
                'type' => self::normalizeOptionKey($option['request_type'] ?? null),
                'rawType' => trim((string)($option['request_type'] ?? '')),
                'area' => self::normalizeOptionKey($option['area_key'] ?? null),
                'label' => $option['area_label'] ?? '',
                'icon' => $option['icon'] ?? null,
            ])
            ->values()
            ->all();
    }

    private static function defaultAreaRows(): array
    {
        return collect(self::$requestAreaOptions)
            ->flatMap(fn($options, $type) => collect($options)
                ->reject(fn($label, $key) => $key === '')
                ->map(fn($label, $key) => ['type' => self::normalizeOptionKey($type), 'area' => self::normalizeOptionKey($key), 'label' => $label])
                ->values())
            ->all();
    }

    private static function firstAreaLabel(array $rows, string $area, ?string $type = null): ?string
    {
        foreach ($rows as $row) {
            if ($row['area'] === $area && ($type === null || $row['type'] === $type) && filled($row['label'])) return $row['label'];
        }

        return null;
    }

    private static function systemTypeLabel(string $type): ?string
    {
        foreach (self::$requestTypeOptions as $key => $label) {
            if (self::normalizeOptionKey($key) === $type) return $label;
        }

        return null;
    }

    private static function distinctTicketValues(string $column): array
    {
        return ModelCacheVersion::remember(
            static::class,
            "distinct:{$column}",
            now()->addSeconds(ModelCacheVersion::defaultSeconds()),
            fn() => static::query()->whereNotNull($column)->where($column, '!=', '')->distinct()->pluck($column)->all(),
        );
    }

    private static function knownRequestKeys(string $column): array
    {
        $configured = array_keys($column === 'request_type' ? self::allRequestTypeOptions() : self::allRequestAreaOptions());

        return array_values(array_unique([...array_map('strval', $configured), ...self::distinctTicketValues($column)]));
    }

    private static function filterOptions(string $column, callable $label): array
    {
        $keys = [];

        foreach (self::knownRequestKeys($column) as $key) {
            $keys[self::normalizeOptionKey($key)] ??= $key;
        }

        unset($keys['']);

        $options = [];

        foreach ($keys as $key) {
            $options[$key] = $label($key);
        }

        asort($options);

        return $options;
    }

    private static function fetchDepartmentTicketOptions(?string $departmentCode): ?array
    {
        if (!$departmentCode) return null;

        $department = Department::getCachedModels()->get($departmentCode);

        return ($department && !empty($department->ticket_options))
            ? $department->ticket_options
            : null;
    }
}
