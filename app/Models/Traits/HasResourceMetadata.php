<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasResourceMetadata
{
    private const FIELD_CONFIG = [
        'floor' => ['icon' => 'layers', 'label' => 'طبقه ', 'class' => ''],
        'extension' => ['icon' => 'call', 'label' => '', 'class' => 'font-mono tracking-wider'],
        'unit' => ['icon' => 'meeting_room', 'label' => 'واحد ', 'class' => ''],
        'card' => ['icon' => 'credit_card', 'label' => '', 'class' => 'font-mono tracking-wider'],
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
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
                        !empty($meta['available_days'])
                            ? collect($meta['available_days'])->map(fn($d) => __("resources/policy/strings.days.{$d}"))->implode('، ')
                            : null,
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

    private function customMetadataItems(array $custom): array
    {
        return collect($custom)->map(fn($value, $key) => (object)[
            'icon' => null,
            'label' => str($key)->headline()->finish(' : '),
            'class' => '',
            'value' => convertToPersian($value) ?? $value,
        ])->values()->all();
    }

    private function availableDaysMetadataItem(array $days): object
    {
        return (object)[
            'icon' => 'event_repeat',
            'label' => 'روزهای در دسترس: ',
            'class' => '',
            'value' => collect($days)->map(fn($d) => __("resources/policy/strings.days.{$d}"))->implode('، '),
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
