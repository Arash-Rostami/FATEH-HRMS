<?php

namespace App\Models\Concerns;

use App\Services\Cache\ModelCacheVersion;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasResourceMetadata
{
    private const FIELD_CONFIG = [
        'floor' => ['icon' => 'layers', 'label' => 'طبقه ', 'class' => ''],
        'extension' => ['icon' => 'call', 'label' => '', 'class' => 'font-mono tracking-wider'],
        'unit' => ['icon' => 'meeting_room', 'label' => 'واحد ', 'class' => ''],
        'card' => ['icon' => 'credit_card', 'label' => '', 'class' => 'font-mono tracking-wider'],
    ];

    private const FACET_SKIP = ['notes'];
    private const FACET_MIN_PRESENCE = 0.4;
    private const FACET_MAX_OPTIONS = 15;

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public static function metadataFacets(string $type): array
    {
        return ModelCacheVersion::remember(static::class, "facets:{$type}", now()->addHour(), function () use ($type) {
            $rows = static::where('type', $type)->where('status', 'active')->pluck('metadata');
            $total = $rows->count();

            if ($total === 0) {
                return [];
            }

            $candidates = [];

            foreach ($rows as $metadata) {
                if (!is_array($metadata)) {
                    continue;
                }

                foreach ($metadata as $key => $value) {
                    if ($key === 'custom' && is_array($value)) {
                        foreach ($value as $customKey => $customValue) {
                            if (is_scalar($customValue) && $customValue !== '') {
                                $candidates["custom->{$customKey}"][] = $customValue;
                            }
                        }
                        continue;
                    }

                    if (in_array($key, self::FACET_SKIP, true) || !is_scalar($value) || $value === '') {
                        continue;
                    }

                    $candidates[$key][] = $value;
                }
            }

            $facets = [];

            foreach ($candidates as $path => $values) {
                $presence = count($values) / $total;

                $distinct = array_unique(array_map('strval', $values));
                sort($distinct);
                $distinctCount = count($distinct);

                if ($presence < self::FACET_MIN_PRESENCE || $distinctCount < 2 || $distinctCount > self::FACET_MAX_OPTIONS) {
                    continue;
                }

                $key = str_contains($path, '->') ? substr($path, strpos($path, '->') + 2) : $path;
                $config = self::FIELD_CONFIG[$key] ?? null;

                $facets[] = [
                    'key' => $key,
                    'path' => $path,
                    'label' => $config ? trim($config['label']) : (string) str($key)->headline(),
                    'icon' => $config['icon'] ?? 'filter_alt',
                    'options' => array_map(fn($v) => ['value' => $v, 'label' => self::facetValueLabel($key, $v, $config)], $distinct),
                ];
            }

            return $facets;
        });
    }

    private static function facetValueLabel(string $key, string $value, ?array $config): string
    {
        $n = (int) $value;

        return match (true) {
            $key === 'floor' && $n < 0 => 'طبقه منفی ' . (convertToPersian(abs($n)) ?? abs($n)),
            $key === 'floor' && $n === 0 => 'همکف',
            default => trim($config['label'] ?? (string) str($key)->headline() . ' ') . ' ' . (convertToPersian($value) ?? $value),
        };
    }

    protected function formattedMetadata(): Attribute
    {
        return Attribute::make(
            get: function () {
                $metadata = $this->metadata;
                if (!is_array($metadata) || $metadata === []) {
                    return [];
                }

                $items = [];
                foreach ($metadata as $key => $value) {
                    if ($key === 'notes') {
                        continue;
                    }
                    if ($key === 'custom' && is_array($value)) {
                        array_push($items, ...$this->customMetadataItems($value));
                        continue;
                    }
                    if ($key === 'available_days' && is_array($value)) {
                        $items[] = $this->availableDaysMetadataItem($value);
                        continue;
                    }
                    if ($key === 'time_slots' && is_array($value)) {
                        $items[] = $this->timeSlotsMetadataItem($value);
                        continue;
                    }
                    $items[] = $this->genericMetadataItem($key, $value);
                }

                return array_reverse($items);
            }
        )->shouldCache();
    }

    protected function statusSummary(): Attribute
    {
        return Attribute::make(
            get: function () {
                $meta = is_array($this->metadata) ? $this->metadata : [];
                $format = fn(mixed $v) => convertToPersian($v) ?? $v;

                $parts = match ($this->type) {
                    'seat' => array_filter([
                        isset($meta['floor']) ? 'طبقه ' . $format($meta['floor']) : null,
                        isset($meta['unit']) ? 'واحد ' . $format($meta['unit']) : null,
                        isset($meta['extension']) ? 'داخلی ' . $format($meta['extension']) : null,
                    ]),
                    'spot' => array_filter([
                        'جای پارک ' . $this->name,
                        isset($meta['floor']) ? 'طبقه ' . $format($meta['floor']) : null,
                        isset($meta['card']) ? 'کارت ' . $format($meta['card']) : null,
                    ]),
                    'meeting', 'car' => array_filter([
                        isset($meta['capacity']) ? 'ظرفیت ' . $format($meta['capacity']) . ' نفر' : null,
                        !empty($meta['available_days']) ? $this->joinDayLabels($meta['available_days']) : null,
                        isset($meta['time_slots']['start'], $meta['time_slots']['end'])
                            ? $format($meta['time_slots']['start']) . ' - ' . $format($meta['time_slots']['end'])
                            : null,
                    ]),
                    default => [],
                };

                return $parts !== [] ? implode(' • ', $parts) : $this->labeled_name;
            }
        );
    }

    private function joinDayLabels(array $days): string
    {
        return implode('، ', array_map(fn($d) => __("resources/policy/strings.days.{$d}"), $days));
    }

    private function customMetadataItems(array $custom): array
    {
        $items = [];

        foreach ($custom as $key => $value) {
            $items[] = (object) [
                'icon' => null,
                'label' => str($key)->headline()->finish(' : '),
                'class' => '',
                'value' => convertToPersian($value) ?? $value,
            ];
        }

        return $items;
    }

    private function availableDaysMetadataItem(array $days): object
    {
        return (object)[
            'icon' => 'event_repeat',
            'label' => 'روزهای در دسترس: ',
            'class' => '',
            'value' => $this->joinDayLabels($days),
        ];
    }

    private function timeSlotsMetadataItem(array $slot): object
    {
        return (object)[
            'icon' => 'schedule',
            'label' => '',
            'class' => 'font-mono tracking-wider',
            'value' => convertToPersian($slot['start'] ?? '') . ' - ' . convertToPersian($slot['end'] ?? ''),
        ];
    }

    private function genericMetadataItem(string $key, mixed $value): object
    {
        $base = self::FIELD_CONFIG[$key] ?? [
            'icon' => null,
            'label' => str($key)->headline()->finish(' : '),
            'class' => '',
        ];

        return (object)array_merge($base, ['value' => convertToPersian($value) ?? $value]);
    }
}
