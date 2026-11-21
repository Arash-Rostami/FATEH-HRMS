<?php

namespace App\Traits\Filament;

use App\Enums\ResourceTypeIcon;
use BackedEnum;

trait FilamentIconOptions
{
    protected static function curatedIconOptions(string $enumClass = ResourceTypeIcon::class): array
    {
        return collect($enumClass::cases())
            ->mapWithKeys(fn(BackedEnum $icon) => [$icon->value => static::renderCuratedIconOption($icon->value, $icon->getLabel())])
            ->all();
    }

    protected static function curatedIconOptionLabel(string $value, string $enumClass = ResourceTypeIcon::class): string
    {
        $icon = $enumClass::tryFrom($value);

        if ($icon) {
            return static::renderCuratedIconOption($icon->value, $icon->getLabel());
        }

        $name = preg_replace('/[^a-z0-9\-]/', '', str_replace('heroicon-', '', $value));
        $label = ucwords(str_replace('-', ' ', preg_replace('/^[a-z]-/', '', $name)));

        return static::renderCuratedIconOption($value, $label);
    }

    private static function renderCuratedIconOption(string $value, string $label): string
    {
        $file = base_path('vendor/blade-ui-kit/blade-heroicons/resources/svg/' . str_replace('heroicon-', '', $value) . '.svg');
        $svg = file_exists($file) && is_file($file) ? static::formatIconSvg(file_get_contents($file)) : '';

        return '<div class="flex items-center gap-2">' . $svg . '<span>' . e($label) . '</span></div>';
    }

    private static function formatIconSvg(string $svg): string
    {
        $svg = preg_replace('/\s+(width|height)="[^"]*"/', '', $svg);

        return str_replace('<svg', '<svg class="w-4 h-4"', $svg);
    }
}
