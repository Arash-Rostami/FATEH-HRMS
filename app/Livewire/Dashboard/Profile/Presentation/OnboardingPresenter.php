<?php

namespace App\Livewire\Dashboard\Profile\Presentation;

use Illuminate\Support\Collection;

class OnboardingPresenter
{
    private array $extraIcons = [
        // Logistics & Access
        'parking' => 'local_parking',
        'location' => 'location_on',
        'transport' => 'directions_bus',
        'access' => 'key',
        'security_access' => 'badge',

        // Workplace Environment
        'dress_code' => 'checkroom',
        'workspace' => 'desk',
        'facilities' => 'apartment',
        'wifi' => 'wifi',
        'cleaning' => 'cleaning_services',

        // IT & Digital Tools
        'it_setup' => 'computer',
        'software' => 'apps',
        'slack' => 'chat',
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
        'attendance' => 'event_available',
        'holidays' => 'celebration',

        // People & Culture
        'buddy_system' => 'diversity_3',
        'team_structure' => 'account_tree',
        'culture' => 'interests',
        'values' => 'compost',
        'mentorship' => 'supervisor_account',

        // Benefits & Welfare
        'benefits' => 'card_giftcard',
        'insurance' => 'health_and_safety',
        'lunch' => 'restaurant',
        'cafeteria' => 'fastfood',
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
        'performance' => 'trending_up',
        'goals' => 'track_changes',
        'library' => 'local_library',

        // Feedback & Surveys
        'survey' => 'bar_chart',
        'feedback' => 'rate_review',
        'suggestions' => 'lightbulb',

        // Fallback for unpredicted keys
        'default' => 'info',
    ];

    private array $extraLabels = [
        'parking' => 'پارکینگ و رفت‌وآمد',
        'location' => 'آدرس و موقعیت مکانی',
        'transport' => 'حمل و نقل عمومی',
        'dress_code' => 'پوشش و لباس',
        'workspace' => 'میز کار و تجهیزات',
        'wifi' => 'شبکه WiFi',
        'it_setup' => 'راه‌اندازی سیستم و IT',
        'software' => 'نرم‌افزارهای مورد نیاز',
        'slack' => 'اتاق‌های Slack',
        'github' => 'دسترسی GitHub/GitLab',
        'working_hours' => 'ساعات کاری',
        'remote_work' => 'سیاست دورکاری',
        'vacation' => 'مرخصی و تعطیلات',
        'overtime' => 'اضافه‌کاری',
        'buddy_system' => 'سیستم دوست راهنما',
        'team_structure' => 'ساختار تیم',
        'culture' => 'فرهنگ سازمانی',
        'benefits' => 'بسته مزایا',
        'insurance' => 'بیمه تکمیلی',
        'lunch' => 'ناهار و کافه‌تریا',
        'gym' => 'باشگاه ورزشی',
        'emergency' => 'مخاطبین اضطراری',
        'contact' => 'اطلاعات تماس کلیدی',
        'training' => 'آموزش و توسعه',
        'performance' => 'ارزیابی عملکرد',
        'survey' => 'نظرسنجی روز اول',
        'security' => 'امنیت اطلاعات',
    ];

    private array $extraDescriptions = [
        'parking' => 'جایگاه پارکینگ، دسترسی با مترو/اتوبوس، و هزینه‌های رفت‌وآمد',
        'dress_code' => 'کد لباس روزهای عادی، جلسات رسمی، و پنج‌شنبه‌ها',
        'it_setup' => 'شماره داخلی IT، تحویل لپ‌تاپ، نصب نرم‌افزارها، و رمز عبور اولیه',
        'buddy_system' => 'معرفی دوست راهنما (Buddy)، نحوه ارتباط، و انتظارات از این سیستم',
        'working_hours' => 'ساعت کاری رسمی، فلکس‌تایم، و نحوه ثبت حضور',
        'remote_work' => 'تعداد روزهای مجاز دورکاری، شرایط و نحوه درخواست',
        'benefits' => 'بیمه تکمیلی، وام، یارانه‌ها، و مزایای غیرنقدی',
        'emergency' => 'شماره‌های اورژانس، مخاطبین ۲۴ ساعته، و آدرس بیمارستان نزدیک',
        'survey' => 'لینک نظرسنجی روز اول و بازخورد سه‌ماهه',
        'security' => 'سیاست رمز عبور، استفاده از USB، و محرمانگی داده‌ها',
    ];

    private array $extraCategories = [
        'logistics' => ['parking', 'location', 'transport', 'access'],
        'workplace' => ['dress_code', 'workspace', 'facilities', 'wifi'],
        'technology' => ['it_setup', 'software', 'slack', 'github', 'security', 'vpn'],
        'time_policy' => ['working_hours', 'vacation', 'remote_work', 'overtime', 'holidays'],
        'people' => ['buddy_system', 'team_structure', 'culture', 'values', 'mentorship'],
        'benefits' => ['benefits', 'insurance', 'lunch', 'gym', 'welfare', 'loan'],
        'support' => ['emergency', 'contact', 'support', 'hr_contact'],
        'growth' => ['training', 'handbook', 'performance', 'goals', 'library'],
        'feedback' => ['survey', 'feedback', 'suggestions'],
    ];

    public function formatExtras(\ArrayObject|array|null $extras): array
    {
        if (empty($extras)) return [];

        $items = [];
        foreach ((array)$extras as $key => $content) {
            $isPredefined = isset($this->extraIcons[$key]) && $key !== 'default';

            $items[] = [
                'key' => $key,
                'icon' => $this->extraIcons[$key] ?? $this->extraIcons['default'],
                'title' => $this->extraLabels[$key] ?? $this->autoTitle($key),
                'content' => $content,
                'is_predefined' => $isPredefined,
                // Styling hints for Blade
                'container_class' => $isPredefined
                    ? 'border-[var(--md-sys-color-outline-variant)]'
                    : 'border-dashed border-[var(--md-sys-color-outline)] bg-[var(--md-sys-color-surface-variant)]/30',
                'icon_bg_class' => $isPredefined
                    ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]'
                    : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]',
            ];
        }

        return $items;
    }

    public function getAdminOptions(): array
    {
        $options = [];

        foreach ($this->extraCategories as $category => $keys) {
            $items = [];
            foreach ($keys as $key) {
                if (!isset($this->extraIcons[$key])) continue;

                $items[] = [
                    'key' => $key,
                    'icon' => $this->extraIcons[$key],
                    'label' => $this->extraLabels[$key] ?? $this->autoTitle($key),
                    'description' => $this->extraDescriptions[$key] ?? null,
                ];
            }

            if (!empty($items)) {
                $options[$category] = [
                    'title' => $this->categoryTitle($category),
                    'items' => $items,
                ];
            }
        }

        return $options;
    }

    public function guides(?Collection $guides): array
    {
        if (!$guides?->count()) return [];

        return $guides->map(fn($guide) => $this->normalizeGuide($guide))->toArray();
    }

    public function hasContent($onboarding): bool
    {
        if (!$onboarding) return false;

        return $onboarding->welcome
            || $onboarding->videos?->isNotEmpty()
            || $onboarding->mission
            || $onboarding->vision
            || $onboarding->guides?->isNotEmpty()
            || $onboarding->schedule
            || count((array)$onboarding->extras) > 0;
    }

    public function videos(?Collection $videos): array
    {
        if (!$videos?->count()) return [];

        return $videos->map(fn($video, $index) => [
            'url' => $video['url'] ?? $video['src'] ?? $video['link'] ?? '#',
            'title' => $video['title'] ?? $video['name'] ?? 'ویدیوی آموزشی ' . ($index + 1),
            'duration' => $video['duration'] ?? $video['length'] ?? null,
            'thumbnail' => $video['thumbnail'] ?? $video['poster'] ?? $video['image'] ?? null,
            'thumb_play' => ($video['thumbnail'] ?? '') . '-play', // For hover swap
        ])->toArray();
    }

    private function autoTitle(string $key): string
    {
        return $this->extraLabels[$key] ?? str($key)->replace('_', ' ')->title()->value();
    }

    private function categoryTitle(string $category): string
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
            default => 'سایر موارد',
        };
    }

    private function fileBadgeClass(string $ext): string
    {
        return match (strtolower($ext)) {
            'pdf' => 'bg-red-500 text-white',
            'doc', 'docx' => 'bg-blue-600 text-white',
            'xls', 'xlsx' => 'bg-green-600 text-white',
            default => 'bg-[var(--md-sys-color-outline)] text-[var(--md-sys-color-surface)]',
        };
    }

    private function fileColorClass(string $ext): string
    {
        return match (strtolower($ext)) {
            'pdf' => 'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400',
            'doc', 'docx' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300',
            'xls', 'xlsx' => 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300',
            default => 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]',
        };
    }

    private function fileIcon(string $ext): string
    {
        return match ($ext) {
            'pdf' => 'picture_as_pdf',
            'doc', 'docx' => 'description',
            'xls', 'xlsx' => 'table_chart',
            'ppt', 'pptx' => 'slideshow',
            'zip', 'rar' => 'folder_zip',
            'jpg', 'jpeg', 'png', 'gif' => 'image',
            'mp4', 'mov', 'avi' => 'videocam',
            default => 'insert_drive_file',
        };
    }

    private function normalizeGuide(array $guide): array
    {
        $url = $guide['url'] ?? $guide['link'] ?? '#';
        $title = $guide['title'] ?? $guide['name'] ?? $guide['filename'] ?? 'سند بدون نام';
        $ext = strtolower($guide['ext'] ?? $guide['type'] ?? $guide['extension'] ?? pathinfo($url, PATHINFO_EXTENSION) ?: 'file');

        return [
            'url' => $url,
            'title' => $title,
            'ext' => $ext,
            'size' => $guide['size'] ?? null,
            'icon' => $this->fileIcon($ext),
            'icon_class' => $this->fileColorClass($ext),
            'badge_class' => $this->fileBadgeClass($ext),
            'is_pdf' => $ext === 'pdf',
        ];
    }
}
