<?php

namespace App\Models;

use App\Filament\Resources\DmsResource\Enums\DocumentStatus;
use App\Models\Concerns\HasDepartmentHelpers;
use App\Models\Concerns\HasDmsCountHelpers;
use App\Models\Concerns\HasMenuState;
use App\Models\Concerns\HasUserHelpers;
use App\Services\Dms\DmsKeyGrouper;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;


class DMS extends Model
{
    use HasFactory;
    use HasUserHelpers;
    use HasDepartmentHelpers;
    use HasDmsCountHelpers;
    use HasMenuState;
    use CleansAttachedFiles;


    private static $statusMapping = [
        'live' => 'فعال',
        'under_review' => 'در حال بررسی',
        'obsolete' => 'منسوخ شده',
    ];
    private static $statusColorMapping = [
        'live' => 'text-green-500',
        'under_review' => 'text-yellow-500',
        'obsolete' => 'text-red-500',
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
        'extra_files',
        'tags',
    ];

    public function departments(): Collection
    {
        if (empty($this->owners)) return new Collection();

        return Department::getCachedModels()
            ->filter(fn($model, $code) => in_array($code, $this->owners, true))
            ->values();
    }

    public function getStatusIcon()
    {
        $status = DocumentStatus::tryFrom($this->status);

        if (!$status) {
            return $this->status;
        }

        $color = self::$statusColorMapping[$this->status] ?? 'text-gray-500';

        return '<span class="material-symbols-rounded ' . $color . '">' . $status->getMaterialIcon() . '</span>';
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

        $readsByUser = $this->readsBySignedUser();
        $signedIds = $readsByUser->filter(fn($r) => !$r->contains(fn($x) => (int)$x->read_count === 0))->keys()->all();

        $owners = collect($this->owners ?? []);

        if ($owners->contains('ALL')) {
            $users = User::active()->whereNotIn('id', $signedIds)->get();
        } else {
            $users = User::active()
                ->whereNotIn('id', $signedIds)
                ->whereHas('profile', fn(Builder $q) => $q->whereIn('department_id', $owners->all()))
                ->get();

            $explicit = collect($this->users ?? [])->filter()->map(fn($i) => (int)$i)->all();

            if (!empty($explicit)) {
                $users = $users->merge(
                    User::active()->whereNotIn('id', $signedIds)->whereIn('id', $explicit)->get()
                );
            }
        }

        return $users->unique('id')->values();
    }

    public function reads(): HasMany
    {
        return $this->hasMany(Read::class, 'document_id');
    }

    public function renditions(): array
    {
        return collect([$this->file, ...($this->extra_files ?? [])])
            ->filter()
            ->unique()
            ->values()
            ->map(fn($path) => [
                'path' => $path,
                'ext' => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            ])
            ->all();
    }

    protected function readerNamesTooltip(): Attribute
    {
        return Attribute::make(get: function (): ?string {
            $readerIds = $this->reads->pluck('user_id')->unique()->values();

            if ($readerIds->isEmpty()) {
                return null;
            }

            $names = array_filter(array_map(fn($id) => static::userNames()[$id] ?? null, $readerIds->all()));

            return implode(' ┆ ', $names) ?: null;
        });
    }

    public function requiresReadFor(int $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->where('read', true)->where('read_count', 0)->exists();
    }

    public function requiresSignFor(int $userId): bool
    {
        return !$this->reads()->where('user_id', $userId)->where('read', true)->exists();
    }

    public function signedUserIds(): array
    {
        return $this->readsBySignedUser()->keys()->all();
    }

    private function readsBySignedUser(): Collection
    {
        return $this->reads()->where('read', true)->get(['user_id', 'read_count'])->groupBy('user_id');
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
        static::saved(fn() => static::forgetListCaches());

        static::updated(function (DMS $document): void {
            if ($document->wasChanged('file') || $document->wasChanged('revision')) {
                $document->reads()->update(['read' => false, 'read_count' => 0]);
                $document->newQuery()->whereKey($document->getKey())->update(['combined_read_count' => 0]);
            }
        });

        static::deleting(function (DMS $document): void {
            static::deleteStoredFiles($document->file);
            static::deleteStoredFiles($document->extra_files);
        });

        static::deleted(function (DMS $document): void {
            $document->reads()->delete();
            static::forgetListCaches();
        });
    }

    private static function forgetListCaches(): void
    {
        Cache::forget('dms_document_counts');
        Cache::forget(DmsKeyGrouper::CACHE_KEY);
    }

    protected function casts(): array
    {
        return [
            'owners' => 'array',
            'users' => 'array',
            'extra' => 'array',
            'extra_files' => 'array',
            'tags' => 'array',
            'type' => 'boolean',
        ];
    }
}
