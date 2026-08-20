<?php

namespace App\Enums;

use App\Models\ReservationPolicy;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class ResourceType
{
    public const CACHE_KEY = 'resource_types_registry';

    public const DISPLAY_KEYS = ['display_label', 'display_color', 'display_icon', 'display_material_icon', 'is_full_day'];

    private const DEFAULT_EMOJI = '📦';

    private const LEGACY_FALLBACK = [
        'seat' => ['label' => 'میز کار', 'color' => 'primary', 'icon' => 'heroicon-o-computer-desktop', 'material_icon' => 'desk', 'is_full_day' => true, 'emoji' => '🖥️'],
        'spot' => ['label' => 'پارکینگ', 'color' => 'success', 'icon' => 'heroicon-o-map-pin', 'material_icon' => 'local_parking', 'is_full_day' => true, 'emoji' => '🅿️'],
        'car' => ['label' => 'خودرو', 'color' => 'warning', 'icon' => 'heroicon-o-truck', 'material_icon' => 'directions_car', 'is_full_day' => true, 'emoji' => '🚗'],
        'meeting' => ['label' => 'ملاقات', 'color' => 'info', 'icon' => 'heroicon-o-users', 'material_icon' => 'person', 'is_full_day' => false, 'emoji' => '🤝🏽'],
    ];

    private function __construct(
        public readonly string $value,
        private readonly string $label,
        private readonly string $color,
        private readonly string $icon,
        private readonly string $materialIcon,
        private readonly bool $fullDay,
        private readonly string $emoji,
    ) {
    }

    public static function tryFrom(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $row = static::registry()->get($value);

        return $row ? static::fromRow($value, $row) : null;
    }

    /** @return self[] */
    public static function cases(): array
    {
        return static::registry()
            ->map(fn(array $row, string $value) => static::fromRow($value, $row))
            ->values()
            ->all();
    }

    public static function pluck(): array
    {
        return collect(self::cases())->mapWithKeys(fn(self $type) => [$type->value => $type->label])->all();
    }

    public static function tabs(): array
    {
        return array_map(
            fn(self $type) => ['id' => $type->value, 'icon' => $type->materialIcon, 'label' => $type->label],
            self::cases(),
        );
    }

    public static function forgetCache(): void
    {
        ModelCacheVersion::bump(ReservationPolicy::class);
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getEmoji(): string
    {
        return $this->emoji;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getMaterialIcon(): string
    {
        return $this->materialIcon;
    }

    public function isFullDay(): bool
    {
        return $this->fullDay;
    }

    private static function fromRow(string $value, array $row): self
    {
        return new self(
            value: $value,
            label: $row['label'],
            color: $row['color'],
            icon: $row['icon'],
            materialIcon: $row['material_icon'],
            fullDay: (bool) $row['is_full_day'],
            emoji: $row['emoji'] ?? self::DEFAULT_EMOJI,
        );
    }

    /** @return Collection<string, array{label: string, color: string, icon: string, material_icon: string, is_full_day: bool, emoji?: string}> */
    private static function registry(): Collection
    {
        return Cache::rememberForever(ModelCacheVersion::key(ReservationPolicy::class, self::CACHE_KEY), fn() => ReservationPolicy::query()
            ->whereIn('key', self::DISPLAY_KEYS)
            ->orderBy('id')
            ->get(['id', 'resource_type', 'key', 'value'])
            ->groupBy('resource_type')
            ->map(fn(Collection $rows) => $rows->pluck('value', 'key'))
            ->filter(fn(Collection $byKey) => $byKey->has('display_label'))
            ->map(fn(Collection $byKey, string $type) => [
                'label' => $byKey['display_label'],
                'color' => $byKey['display_color'] ?? 'gray',
                'icon' => $byKey['display_icon'] ?? 'heroicon-o-cube',
                'material_icon' => $byKey['display_material_icon'] ?? 'category',
                'is_full_day' => (bool) ($byKey['is_full_day'] ?? true),
                'emoji' => self::LEGACY_FALLBACK[$type]['emoji'] ?? self::DEFAULT_EMOJI,
            ])
            ->union(self::LEGACY_FALLBACK));
    }
}
