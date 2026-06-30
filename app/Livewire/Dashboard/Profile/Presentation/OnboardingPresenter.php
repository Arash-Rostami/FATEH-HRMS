<?php

namespace App\Livewire\Dashboard\Profile\Presentation;

use App\Models\Traits\HasExtraCatalog;
use App\Models\Traits\HasPublicAssetUrl;
use ArrayObject;
use Illuminate\Support\Collection;

class OnboardingPresenter
{
    use HasExtraCatalog, HasPublicAssetUrl;

    public function formatExtras(ArrayObject|array|null $extras): array
    {
        if (blank($extras)) return [];

        $items = ($extras instanceof ArrayObject)
            ? $extras->getArrayCopy()
            : (array)$extras;

        $isNew = array_key_exists('key', reset($items) ?: []);

        $out = [];

        foreach ($items as $key => $item) {
            if ($isNew) {
                $key = trim((string)($item['key'] ?? ''));

                if ($key === '') continue;

                $content = is_array($item['value'] ?? null)
                    ? ($item['value']['content'] ?? '')
                    : ($item['value'] ?? '');

                $title = $item['display_title'] ?? null;
            } else {
                $content = is_array($item)
                    ? ($item['content'] ?? $item)
                    : $item;

                $title = is_array($item)
                    ? ($item['display_title'] ?? null)
                    : null;
            }

            $isPredefined = isset(self::$extraIcons[$key]) && $key !== 'default';

            $out[] = [
                'key' => $key,
                'icon' => self::$extraIcons[$key] ?? self::$extraIcons['default'],
                'title' => $title ?? self::$extraLabels[$key] ?? self::autoTitle($key),
                'content' => $content,
                'is_predefined' => $isPredefined,
                'container_class' => $isPredefined
                    ? 'border-[var(--md-sys-color-outline-variant)]'
                    : 'border-dashed border-[var(--md-sys-color-outline)] bg-[var(--md-sys-color-surface-variant)]/30',
                'icon_bg_class' => $isPredefined
                    ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]'
                    : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]',
            ];
        }

        return $out;
    }

    public function guides(Collection|array|null $guides): array
    {
        $guides = collect($guides);
        if ($guides->isEmpty()) return [];

        return $guides
            ->filter(fn($g) => !empty($g['url']))
            ->map(fn($guide) => $this->normalizeGuide($guide))
            ->values()
            ->toArray();
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

    public function videos(Collection|array|null $videos): array
    {
        $videos = collect($videos);
        if ($videos->isEmpty()) return [];

        return $videos->map(fn($video, $index) => [
            'url' => $video['url'] ? self::resolvePublicAssetUrl($video['url']) : '#',
            'title' => $video['title'] ?? 'ویدیوی آموزشی ' . ($index + 1),
            'duration' => $video['duration'] ?? null,
            'thumbnail' => $video['thumbnail'] ? self::resolvePublicAssetUrl($video['thumbnail']) : null,
        ])->toArray();
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
        $url = isset($guide['url']) && $guide['url'] !== '#' ? self::resolvePublicAssetUrl($guide['url']) : '#';
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
