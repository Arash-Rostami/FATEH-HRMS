<?php

namespace App\Models;

use App\Models\Traits\HasDepartmentHelpers;
use App\Models\Traits\HasDmsCountHelpers;
use App\Models\Traits\HasMenuState;
use App\Models\Traits\HasUserHelpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class DMS extends Model
{
    use HasFactory;
    use HasUserHelpers;
    use HasDepartmentHelpers;
    use HasDmsCountHelpers;
    use HasMenuState;


    private static $statusMapping = [
        'live' => 'فعال',
        'under_review' => 'در حال بررسی',
        'obsolete' => 'منسوخ شده',
    ];
    private static $statusIconMapping = [
        'live' => '<span class="material-symbols-rounded text-green-500">check_circle</span>',
        'under_review' => '<span class="material-symbols-rounded text-yellow-500">hourglass_empty</span>',
        'obsolete' => '<span class="material-symbols-rounded text-red-500">cancel</span>',
    ];

    protected $table = 'dms';
    protected $fillable = [
        'file',
        'code',
        'version',
        'title',
        'status',
        'type',
        'owners',
        'users',
        'revision',
        'combined_read_count',
        'extra',
        'tags',
    ];

    public function departments(): Collection
    {
        if (empty($this->owners)) return new Collection();

        return once(fn() => Department::whereIn('code', $this->owners)->get());
    }

    public function getStatusIcon()
    {
        return self::$statusIconMapping[$this->status] ?? $this->status;
    }

    public function getStatusInFarsi()
    {
        return self::$statusMapping[$this->status] ?? $this->status;
    }

    public static function hasPendingFor(int $userId): bool
    {
        $dept = User::with('profile')->find($userId)?->profile?->department_id;

        return static::needsSignCount($userId, $dept) > 0 || static::needsReadCount($userId, $dept) > 0;
    }

    public function isPendingFor(int $userId): bool
    {
        return $this->requiresSignFor($userId) || $this->requiresReadFor($userId);
    }

    public static function needsReadCount(int $userId, ?string $dept = null): int
    {
        return static::visibleTo($userId, $dept)
            ->whereHas('reads', fn(Builder $q) => $q->where('user_id', $userId)->where('read', true)->where('read_count', 0))
            ->count();
    }

    public static function needsSignCount(int $userId, ?string $dept = null): int
    {
        return static::visibleTo($userId, $dept)
            ->whereDoesntHave('reads', fn(Builder $q) => $q->where('user_id', $userId)->where('read', true))
            ->count();
    }

    public static function pendingCount(int $userId): int
    {
        return static::needsSignCount($userId) + static::needsReadCount($userId);
    }

    public function pendingRecipients(): Collection
    {
        if ($this->status !== 'live') {
            return new Collection();
        }

        $owners = collect($this->owners ?? []);

        if ($owners->contains('ALL')) {
            $users = User::active()->get();
        } else {
            $users = User::active()
                ->whereHas('profile', fn(Builder $q) => $q->whereIn('department_id', $owners->all()))
                ->get();

            $explicit = collect($this->users ?? [])->filter()->map(fn($i) => (int)$i)->all();

            if (!empty($explicit)) {
                $users = $users->merge(User::active()->whereIn('id', $explicit)->get());
            }
        }

        $reads = $this->reads()->where('read', true)->get(['user_id', 'read_count']);

        return $users->unique('id')->filter(fn(User $u) =>
            $reads->where('user_id', $u->id)->isEmpty()
            || $reads->where('user_id', $u->id)->contains(fn($r) => (int)$r->read_count === 0)
        )->values();
    }

    public function reads(): HasMany
    {
        return $this->hasMany(Read::class, 'document_id');
    }

    public function requiresReadFor(int $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->where('read', true)->where('read_count', 0)->exists();
    }

    public function requiresSignFor(int $userId): bool
    {
        return !$this->reads()->where('user_id', $userId)->where('read', true)->exists();
    }

    public function scopeNonSystematic($query)
    {
        return $query->where('type', false);
    }

    public function scopeSystematic($query)
    {
        return $query->where('type', true);
    }

    public function scopeVisibleToUser($query)
    {
        return $query->where('status', 'live')
            ->where(function ($query) {
                $query
                    ->whereJsonContains('owners', 'ALL')
                    ->orWhereJsonContains('owners', auth()->user()?->profile?->department_id)
                    ->orWhereJsonContains('users', (string)auth()->id())
                    ->orWhereJsonContains('users', auth()->id());
            });
    }

    public function users(): Collection
    {
        if (empty($this->users)) return new Collection();

        return once(fn() => User::whereIn('id', $this->users)->get());
    }

    public static function visibleTo(int $userId, ?string $dept = null): Builder
    {
        $dept ??= User::with('profile')->find($userId)?->profile?->department_id;

        return static::query()->where('status', 'live')
            ->where(function (Builder $query) use ($userId, $dept) {
                $query
                    ->whereJsonContains('owners', 'ALL')
                    ->orWhereJsonContains('owners', $dept)
                    ->orWhereJsonContains('users', (string)$userId)
                    ->orWhereJsonContains('users', $userId);
            });
    }

    protected static function booted(): void
    {
        static::updated(function (DMS $document): void {
            if ($document->wasChanged('file') || $document->wasChanged('revision')) {
                $document->reads()->update(['read' => false, 'read_count' => 0]);
                $document->newQuery()->whereKey($document->getKey())->update(['combined_read_count' => 0]);
            }
        });

        static::deleted(fn (DMS $document) => $document->reads()->delete());
    }

    protected function casts(): array
    {
        return [
            'owners' => 'array',
            'users' => 'array',
            'extra' => 'array',
            'tags' => 'array',
            'type' => 'boolean',
        ];
    }
}
