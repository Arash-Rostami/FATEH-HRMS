<?php

namespace App\Models;

use App\Enums\ResourceType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    protected $appends = [
        'display_image',
        'display_image_url',
        'formatted_metadata',
        'icon',
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

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopeAvailable(Builder $query, string $type, Carbon $start, Carbon $end, ?string $floor = null, bool $allowOverlap = false): Builder
    {
        $statuses = $allowOverlap ? ['active'] : ['active', 'released'];

        return $query->where('type', $type)
            ->where('status', 'active')
            ->when($floor, fn($q) => $q->where('metadata->floor', $floor))
            ->whereDoesntHave('reservations', function ($q) use ($start, $end, $statuses) {
                $q->whereIn('status', $statuses)
                    ->where(fn($sub) => $sub
                        ->where(fn($inner) => $inner->where('start_time', '<', $end)->where('end_time', '>', $start))
                        ->orWhere(fn($inner) => $inner->whereNull('start_time')
                            ->where('created_at', '>=', $start->copy()->startOfDay())
                            ->where('created_at', '<=', $end->copy()->endOfDay()))
                    );
            });
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    protected function displayImage(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->image ?: (
            $this->type === 'meeting'
                ? $this->relatedUser?->profile?->image
                : null
            )
        );
    }

    protected function displayImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->display_image
                ? Storage::url($this->display_image)
                : null
        );
    }

    protected function formattedMetadata(): Attribute
    {
        return Attribute::make(
            get: function () {
                static $config = [
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
                $metadata = $this->metadata;
                if (!is_array($metadata) || $metadata === []) return [];


                foreach ($metadata as $key => $value) {
                    $base = $config[$key] ?? [
                        'icon' => null,
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

    protected function labeledName(): Attribute
    {
        return Attribute::make(get: fn() => sprintf('%s ⇄ %s',
            ($this->type instanceof ResourceType ? $this->type : ResourceType::tryFrom($this->type))?->getLabel() ?? $this->type,
            $this->name
        )
        );
    }
}
