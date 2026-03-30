<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'metadata',
        'status',
        'image',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public static function getTabs(): array
    {
        return [
            ['id' => 'seat', 'icon' => 'desk', 'label' => 'میز کار'],
            ['id' => 'spot', 'icon' => 'local_parking', 'label' => 'پارکینگ'],
            ['id' => 'car', 'icon' => 'directions_car', 'label' => 'خودرو'],
            ['id' => 'meeting', 'icon' => 'person', 'label' => 'ملاقات']
        ];
    }

    public function isType(string $type): bool
    {
        return $this->type === $type;
    }

    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'name', 'name');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    protected function displayImage(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->image) return $this->image;

                return $this->isType('meeting') ? $this->relatedUser?->profile?->image : null;
            }
        );
    }

    protected function displayImageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->display_image) return null;

                return Storage::url($this->display_image);
            }
        );
    }

    protected function formattedMetadata(): Attribute
    {
        return Attribute::make(
            get: function () {
                $config = [
                    'floor' => [
                        'icon' => 'layers',
                        'label' => 'طبقه ',
                        'class' => '',
                    ],
                    'extension' => [
                        'icon' => 'call',
                        'label' => '',
                        'class' => 'font-mono tracking-wider',
                    ]
                ];

                $items = [];
                if (!$this->metadata || !is_array($this->metadata)) return $items;

                foreach ($this->metadata as $key => $value) {
                    $base = $config[$key] ?? [
                        'label' => str($key)->headline()->finish(' : '),
                        'class' => '',
                    ];

                    $items[] = (object)array_merge($base, [
                        'value' => convertToPersian($value) ?? $value
                    ]);
                }

                return array_reverse($items);
            }
        );
    }

    protected function icon(): Attribute
    {
        return Attribute::make(
            get: fn() => collect(self::getTabs())->firstWhere('id', $this->type)['icon'] ?? 'chair'
        );
    }
}
