<?php

namespace App\Models;

use App\Enums\ProfileDetailGroup;
use App\Filament\Resources\ProfileResource\Enums\Position;
use App\Models\Concerns\HasAvatar as HasImage;
use App\Models\Concerns\HasDateHelpers;
use App\Models\Concerns\HasMenuState;
use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\HasOccasions;
use App\Jobs\ReconcileNudge;
use App\Jobs\SyncProjectChannelMembershipJob;
use App\Services\Cache\ModelCacheVersion;
use App\Services\ProfileDetailCatalog;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use RuntimeException;

class Profile extends Model
{
    use HasFactory, HasDateHelpers, HasImage, HasMenuState, HasModelCache, HasOccasions, CleansAttachedFiles;

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

    public static function cachedWithDates(): Collection
    {
        return static::cached(
            'with_dates:u' . ModelCacheVersion::version(User::class),
            fn() => static::query()
                ->select('id', 'user_id', 'birthdate', 'start_date', 'image')
                ->with('user:id,name')
                ->where(fn($q) => $q->whereNotNull('birthdate')->orWhereNotNull('start_date'))
                ->get()
        );
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
            ModelCacheVersion::bump(User::class);
        });

        static::creating(function (self $profile) {
            if (static::where('user_id', $profile->user_id)->exists()) {
                throw new RuntimeException(__('resources/profile/strings.validation.duplicate'));
            }
        });

        static::saved(function (self $profile) {
            $moved = $profile->wasChanged('department_id');

            if (!$moved && !$profile->wasChanged('position')) {
                return;
            }

            ModelCacheVersion::bump(User::class);

            $codes = array_filter([$profile->getOriginal('department_id'), $profile->department_id]);

            if (!$codes) {
                return;
            }

            if ($moved) {
                Project::whereNotNull('channel_id')
                    ->where(fn($q) => collect($codes)->each(fn($code) => $q->orWhereJsonContains('departments', $code)))
                    ->pluck('id')
                    ->each(fn($id) => SyncProjectChannelMembershipJob::dispatch($id));
            }

            if ($moved) {
                foreach ([Post::class => Post::NUDGE_KEY, Feed::class => Feed::NUDGE_KEY, Report::class => 'reports-controller:nudge'] as $class => $nudgeKey) {
                    $class::whereNotNull('departments')
                        ->where(fn($q) => collect($codes)->each(fn($code) => $q->orWhereJsonContains('departments', $code)))
                        ->pluck('id')
                        ->each(fn($id) => ReconcileNudge::dispatch($nudgeKey, $class, $id));
                }

                Photo::where(fn($q) => collect($codes)->each(fn($code) => $q->orWhere('department_id', $code)->orWhereJsonContains('departments', $code)))
                    ->pluck('id')
                    ->each(fn($id) => ReconcileNudge::dispatch('gallery-controller:nudge', Photo::class, $id));
            }

            Ticket::where('status', 'open')
                ->where(fn($q) => collect($codes)->each(fn($code) => $q->orWhere(fn($sq) => $sq->targetedAt($code))))
                ->pluck('id')
                ->each(fn($id) => ReconcileNudge::dispatch('ths-controller:nudge', Ticket::class, $id));
        });
    }
}
