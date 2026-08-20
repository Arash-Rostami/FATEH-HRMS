<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use App\Enums\ResourceType;
use App\Models\Traits\HasPublicAssetUrl;
use App\Models\Traits\HasResourceMetadata;
use App\Traits\CleansAttachedFiles;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resource extends Model
{
    use HasFactory, HasPublicAssetUrl, CleansAttachedFiles, HasResourceMetadata;

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
        return ResourceType::tabs();
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
        $isFullDay = ResourceType::tryFrom($type)?->isFullDay() ?? false;
        $day = strtolower($start->englishDayOfWeek);

        return $query
            ->where('type', $type)
            ->where('status', 'active')
            ->when($floor, fn($q) => $q->where('metadata->floor', $floor))
            ->where(fn($q) => $q
                ->whereNull('metadata->available_days')
                ->orWhereJsonContains('metadata->available_days', $day)
            )
            ->when(!$isFullDay, fn($q) => $q->where(fn($q2) => $q2
                ->whereNull('metadata->time_slots->start')
                ->orWhere(fn($q3) => $q3
                    ->where('metadata->time_slots->start', '<=', $start->format('H:i'))
                    ->where('metadata->time_slots->end', '>=', $end->format('H:i'))
                )
            ))
            ->whereDoesntHave('reservations', fn($q) => $q
                ->whereIn('status', $allowOverlap ? [ReservationStatus::Active->value] : [ReservationStatus::Active->value, ReservationStatus::Released->value])
                ->where(fn($q) => $q
                    ->where(fn($q) => $q
                        ->where('start_time', '<', $end)
                        ->where('end_time', '>', $start)
                    )
                    ->orWhere(fn($q) => $q
                        ->whereNull('start_time')
                        ->whereBetween('created_at', [
                            $start->copy()->startOfDay(),
                            $end->copy()->endOfDay(),
                        ])
                    )
                )
            );
    }

    protected function displayImage(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->image ?: (
            $this->type === 'meeting'
                ? $this->relatedUser?->profile?->image
                : null
            )
        )->shouldCache();
    }

    protected function displayImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->display_image
                ? self::resolvePublicAssetUrl($this->display_image)
                : null
        )->shouldCache();
    }

    protected function icon(): Attribute
    {
        return Attribute::make(
            get: fn() => ResourceType::tryFrom($this->type)?->getMaterialIcon() ?? 'chair'
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

    protected static function booted(): void
    {
        static::deleting(fn(self $resource) => static::deleteStoredFiles($resource->getRawOriginal('image')));
        static::deleted(function (self $resource): void {
            if (! static::where('type', $resource->type)->exists()) {
                ReservationPolicy::where('resource_type', $resource->type)->delete();
                ResourceType::forgetCache();
            }
        });
    }
}
