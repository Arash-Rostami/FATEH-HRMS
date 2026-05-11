<?php

namespace App\Filament\Resources\OnboardingResource\Schemas\Helper;

use App\Models\Onboarding;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class ExtrasTemplate
{
    public static function add($state, Set $set, Get $get): void
    {
        if (blank($state)) return;

        $extras = $get('extras') ?? [];
        $existingKeys = collect($extras)->pluck('key')->toArray();

        $allItems = collect(Onboarding::adminOptions())
            ->flatMap(fn($cat) => $cat['items']);

        foreach ($state as $key) {
            if (in_array($key, $existingKeys)) continue;

            $item = $allItems->firstWhere('key', $key);

            $extras[] = [
                'key' => $key,
                'display_title' => $item['label'] ?? Onboarding::autoTitle($key),
                'value' => $item['description'] ?? '',
            ];
        }

        $set('extras', $extras);
    }

    public static function hint(Get $get): string
    {
        $extras = $get('extras') ?? [];
        $count = count($extras);

        return match (true) {
            $count === 0 => '🚀 برای شروع سریع، الگوهای پرکاربرد را انتخاب کنید؛ به‌صورت خودکار به لیست زیر اضافه می‌شوند.',
            $count <= 5 => "✍️ عالی! {$count} آیتم اضافه شد. حالا می‌توانید توضیحات و جزئیات هر کدام را در لیست زیر شخصی‌سازی کنید.",
            default => '💡 افزودن آیتم‌های زیاد ممکن است کاربران را خسته کند. پیشنهاد می‌کنیم حداقل توضیحات کارت‌های را مختصر و جذاب نگه دارید.',
        };
    }

    public static function options(): array
    {
        return once(function () {
            $grouped = [];
            foreach (Onboarding::adminOptions() as $category => $data) {
                foreach ($data['items'] as $item) {
                    $grouped[$data['title']][$item['key']] = $item['label'];
                }
            }
            return $grouped;
        });
    }

    public static function sync($state, Get $get, Set $set): void
    {
        $extras = $get('extras') ?? [];
        $set('_extras_template', collect($extras)->pluck('key')->toArray());
    }
}
