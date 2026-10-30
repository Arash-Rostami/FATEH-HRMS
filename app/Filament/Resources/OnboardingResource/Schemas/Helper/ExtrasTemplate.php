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
            $count === 0 => 'برای شروع سریع، نمونه‌های پرکاربرد را انتخاب کنید؛ خودکار به فهرست زیر اضافه می‌شوند.',
            $count <= 5 => "{$count} بخش اضافه شد. حالا می‌توانید متن هر بخش را در فهرست زیر ویرایش کنید.",
            default => 'بخش‌های زیاد ممکن است کاربران را خسته کند. بهتر است متن هر بخش را کوتاه و جذاب بنویسید.',
        };
    }

    public static function options(array $extras = []): array
    {
        $grouped = once(function () {
            $catalog = [];
            foreach (Onboarding::adminOptions() as $data) {
                foreach ($data['items'] as $item) {
                    $catalog[$data['title']][$item['key']] = $item['label'];
                }
            }
            return $catalog;
        });

        $known = collect($grouped)->flatMap(fn($items) => array_keys($items));
        $custom = collect($extras)
            ->filter(fn($row) => filled($row['key'] ?? null) && ! $known->contains($row['key']));

        if ($custom->isNotEmpty()) {
            $grouped['سفارشی'] = $custom
                ->mapWithKeys(fn($row) => [$row['key'] => $row['display_title'] ?? Onboarding::autoTitle($row['key'])])
                ->all();
        }

        return $grouped;
    }

    public static function sync($state, Get $get, Set $set): void
    {
        $extras = $get('extras') ?? [];
        $set('_extras_template', collect($extras)->pluck('key')->toArray());
    }
}
