<?php

namespace App\Models;

use App\Enums\ProfileDetailGroup;
use App\Filament\Resources\ProfileResource\Enums\Position;
use App\Models\Traits\HasAvatar as HasImage;
use App\Models\Traits\HasDateHelpers;
use App\Models\Traits\HasMenuState;
use App\Models\Traits\HasOccasions;
use App\Services\ProfileDetailCatalog;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Profile extends Model
{
    use HasFactory, HasDateHelpers, HasImage, HasMenuState, HasOccasions, CleansAttachedFiles;

    protected $fillable = [
        'personnel_id',
        'image',
        'attachments',
        'gender',
        'employment_type',
        'marital_status',
        'number_of_children',
        'employment_status',
        'id_card_number',
        'id_booklet_number',
        'degree',
        'field',
        'birthdate',
        'landline',
        'cellphone',
        'license_plate',
        'zip_code',
        'address',
        'accessibility',
        'department_id',
        'position',
        'insurance',
        'emergency_phone',
        'emergency_relationship',
        'start_date',
        'end_date',
        'work_experience',
        'interests',
        'favorite_colors',
        'about_me',
        'user_id',
    ];

    public function age(): ?int
    {
        return $this->birthdate?->age;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'code');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ProfileDetail::class);
    }

    public function detailsMap(): Collection
    {
        return $this->details->pluck('value', 'key');
    }


    public function syncDetails(array $values): void
    {
        $keysToDelete = [];
        $recordsToUpsert = [];

        foreach ($values as $key => $value) {
            if (blank($value)) {
                $keysToDelete[] = $key;
                continue;
            }
            $recordsToUpsert[] = [
                'profile_id' => $this->id,
                'key'        => $key,
                'section'    => ProfileDetailCatalog::definition($key)['section'] ?? ProfileDetailGroup::Other->value,
                'value'      => (string) $value,
            ];
        }

        if (!empty($keysToDelete))   $this->details()->whereIn('key', $keysToDelete)->delete();
        if (!empty($recordsToUpsert)) $this->details()->upsert($recordsToUpsert, ['profile_id', 'key'], ['section', 'value']);
    }

    public function getImageUrl(): ?string
    {
        return $this->resolveImageUrl($this->image);
    }

    public function getDocsPathToken(): string
    {
        return hash_hmac('sha256', (string) $this->id, config('app.key'));
    }

    public function getInitialsAvatarUrl(): string
    {
        return $this->generateInitialsAvatar($this->user?->name);
    }

    public function isProbational(): bool
    {
        return $this->employment_status === 'probational';
    }

    public function isTerminated(): bool
    {
        return $this->employment_status === 'terminated';
    }

    public function isWorking(): bool
    {
        return $this->employment_status === 'working';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPositionLabelAttribute(): string
    {
        return Position::tryFrom($this->position)?->getLabel()
            ?? ($this->position ?: 'کارشناس');
    }

    public function getDisplayPositionAttribute(): string
    {
        $displayTitle = $this->detailsMap()->get('display_title');

        return filled($displayTitle) ? (string) $displayTitle : $this->position_label;
    }

    public function getHasDisplayPositionAttribute(): bool
    {
        return filled($this->position) || filled($this->detailsMap()->get('display_title'));
    }

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'favorite_colors' => 'array',
            'birthdate' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'number_of_children' => 'integer',
            'about_me' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $profile) {
            static::deleteStoredFiles($profile->image);
            static::deleteStoredFiles($profile->attachments);
        });
    }
}
