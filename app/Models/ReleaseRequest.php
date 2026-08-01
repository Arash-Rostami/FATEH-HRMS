<?php

namespace App\Models;

use App\Enums\ReleaseRequestStatus;
use App\Enums\ReleaseRequestType;
use App\Services\ContentSanitizerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReleaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForType(Builder $query, ReleaseRequestType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    public function scopeForStatus(Builder $query, ReleaseRequestStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ReleaseRequestStatus::Open->value,
            ReleaseRequestStatus::InReview->value,
        ]);
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value): ?string => $value === null ? null : trim(strip_tags($value)),
        );
    }

    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value): ?string => $value === null ? null : ContentSanitizerService::clean(strip_tags($value)),
        );
    }
}