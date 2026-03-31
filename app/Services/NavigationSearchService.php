<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NavigationSearchService
{
    /**
     * Get all searchable items.
     * Extend this method to add more items.
     */
    protected function getSearchableItems(): Collection
    {
        return collect([
            [
                'id' => 'home',
                'title' => 'مروری',
                'subtitle' => 'داشبورد اصلی و وضعیت کلی',
                'icon' => 'home',
                'action' => 'tab:home',
                'keywords' => ['home', 'dashboard', 'main', 'start', 'home', 'خانه', 'داشبورد', 'اصلی', 'شروع', 'نمای کلی'],
            ],
            [
                'id' => 'posts',
                'title' => 'پست‌ها',
                'subtitle' => 'مدیریت و مشاهده مقالات',
                'icon' => 'newspaper',
                'action' => 'tab:post',
                'keywords' => ['post', 'article', 'blog', 'write', 'content', 'پست', 'مقاله', 'بلاگ', 'نوشتن', 'محتوا', 'اخبار'],
            ],
            [
                'id' => 'feeds',
                'title' => 'اخبار و رویدادها',
                'subtitle' => 'آخرین خبرها و اطلاعیه‌ها',
                'icon' => 'rss_feed',
                'action' => 'tab:feed',
                'keywords' => ['feed', 'news', 'update', 'timeline', 'stream', 'اخبار', 'خبر', 'تازه', 'رویداد', 'اطلاعیه', 'فید'],
            ],
            [
                'id' => 'calendar',
                'title' => 'تقویم کاری',
                'subtitle' => 'مدیریت جلسات و برنامه‌ها',
                'icon' => 'calendar_month',
                'action' => 'tab:calendar',
                'keywords' => ['calendar', 'schedule', 'event', 'date', 'meeting', 'plan', 'time', 'تقویم', 'برنامه', 'رویداد', 'جلسه', 'زمان', 'تاریخ', 'سررسید'],
            ],
            [
                'id' => 'gallery',
                'title' => 'گالری تصاویر',
                'subtitle' => 'آرشیو عکس‌ها و رسانه‌ها',
                'icon' => 'image',
                'action' => 'tab:gallery',
                'keywords' => ['gallery', 'photo', 'image', 'picture', 'media', 'album', 'گالری', 'عکس', 'تصویر', 'رسانه', 'آلبوم'],
            ],
            [
                'id' => 'reports',
                'title' => 'گزارش‌ها',
                'subtitle' => 'آمار و تحلیل عملکرد',
                'icon' => 'description',
                'action' => 'tab:reports',
                'keywords' => ['report', 'analytic', 'statistic', 'data', 'chart', 'metric', 'گزارش', 'آمار', 'تحلیل', 'داده', 'نمودار', 'عملکرد'],
            ],
            [
                'id' => 'links',
                'title' => 'لینک‌های سریع',
                'subtitle' => 'دسترسی به سامانه‌های دیگر',
                'icon' => 'open_in_new',
                'action' => 'tab:links',
                'keywords' => ['link', 'shortcut', 'url', 'bookmark', 'external', 'site', 'لینک', 'پیوند', 'میانبر', 'سایت', 'سامانه'],
            ],
            [
                'id' => 'status',
                'title' => 'وضعیت حضور',
                'subtitle' => 'ثبت و مشاهده وضعیت کاری',
                'icon' => 'hub',
                'action' => 'tab:status',
                'keywords' => ['status', 'presence', 'online', 'busy', 'work', 'availability', 'وضعیت', 'حضور', 'آنلاین', 'مشغول', 'کاری', 'تردد'],
            ],
            [
                'id' => 'faqs',
                'title' => 'پرسش‌های متداول',
                'subtitle' => 'راهنما و سوالات پرتکرار',
                'icon' => 'help',
                'action' => 'tab:faqs',
                'keywords' => ['faq', 'help', 'question', 'support', 'answer', 'guide', 'manual', 'سوال', 'پرسش', 'پاسخ', 'راهنما', 'کمک', 'پشتیبانی'],
            ],
            [
                'id' => 'dms',
                'title' => 'مدیریت اسناد',
                'subtitle' => 'مخزن متمرکز اسناد رسمی',
                'icon' => 'folder_managed',
                'action' => 'route:dms.index',
                'keywords' => ['dms', 'document', 'file', 'archive', 'signature', 'اسناد', 'سند', 'فایل', 'آرشیو', 'امضا', 'مخزن', 'نامه'],
            ],
            [
                'id' => 'ths',
                'title' => 'سیستم تیکت',
                'subtitle' => 'درخواست‌های پشتیبانی و دسترسی',
                'icon' => 'support_agent',
                'action' => 'route:ths.index',
                'keywords' => ['ths', 'ticket', 'support', 'helpdesk', 'issue', 'تیکت', 'پشتیبانی', 'درخواست', 'مشکل', 'گزارش', 'آی تی', 'سیستم'],
            ],
            [
                'id' => 'reservation',
                'title' => 'رزرو فضا و منابع',
                'subtitle' => 'رزرو میز، پارکینگ، خودرو و جلسات',
                'icon' => 'meeting_room',
                'action' => 'route:reservation.index',
                'keywords' => ['reservation', 'booking', 'desk', 'parking', 'car', 'meeting', 'room', 'رزرو', 'میز', 'پارکینگ', 'خودرو', 'ماشین', 'جلسه', 'اتاق', 'فضا'],
            ],
            [
                'id' => 'taskboard',
                'title' => 'تسک بورد',
                'subtitle' => 'مدیریت وظایف و پروژه‌ها',
                'icon' => 'view_kanban',
                'action' => 'route:taskboard.index',
                'keywords' => ['task', 'board', 'kanban', 'project', 'todo', 'agile', 'تسک', 'وظیفه', 'پروژه', 'بورد', 'کانبان', 'کار', 'انجام', 'مدیریت'],
            ],
            // Global Actions
            [
                'id' => 'profile',
                'title' => 'پروفایل کاربری',
                'subtitle' => 'ویرایش اطلاعات حساب',
                'icon' => 'person',
                'action' => 'route:profile',
                'keywords' => ['profile', 'account', 'user', 'me', 'setting', 'avatar', 'پروفایل', 'حساب', 'کاربر', 'من', 'تنظیمات', 'عکس'],
            ],
            [
                'id' => 'logout',
                'title' => 'خروج از حساب',
                'subtitle' => 'پایان نشست کاربری',
                'icon' => 'logout',
                'action' => 'event:logout',
                'keywords' => ['logout', 'exit', 'signout', 'leave', 'خروج', 'بستن', 'پایان'],
            ],
        ]);
    }

    /**
     * Search for items based on a query string.
     * Uses comprehensive fuzzy matching.
     */
    public function search(string $query): array
    {
        $query = Str::lower(trim($query));

        if (strlen($query) < 2) return [];

        return $this->getSearchableItems()
            ->map(function ($item) use ($query) {
                $score = 0;
                $exactMatch = false;

                // 1. Check Title & Subtitle (High Priority)
                if (Str::contains(Str::lower($item['title']), $query)) {
                    $score += 50;
                }
                if (Str::contains(Str::lower($item['subtitle']), $query)) {
                    $score += 30;
                }

                // 2. Check Keywords (Exact & Fuzzy)
                foreach ($item['keywords'] as $keyword) {
                    $keyword = Str::lower($keyword);

                    // Exact match
                    if ($keyword === $query) {
                        $score += 100;
                        $exactMatch = true;
                        break;
                    }

                    // Partial match (contains)
                    if (Str::contains($keyword, $query)) {
                        $score += 40;
                    }

                    // Near word match (Levenshtein) - for typos (if query is at least 3 chars to avoid noise)
                    if (strlen($query) >= 3) {
                        $distance = levenshtein($query, $keyword);
                        $length = max(strlen($query), strlen($keyword));
                        $similarity = 1 - ($distance / $length);

                        if ($similarity > 0.7) {
                            $score += 20 * $similarity;
                        }
                    }
                }

                $item['score'] = $score;
                return $item;
            })
            ->filter(fn($item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->values()
            ->toArray();
    }
}
