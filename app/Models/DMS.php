<?php

namespace App\Models;

use App\Models\Traits\HasDepartmentHelpers;
use App\Models\Traits\HasDmsCountHelpers;
use App\Models\Traits\HasUserHelpers;
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

    private static array $statusMapping = [
        'live' => 'فعال',
        'under_review' => 'در حال بررسی',
        'obsolete' => 'منسوخ شده',
    ];

    private static array $statusIconMapping = [
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

    /**
     * Cache container for resolved relations to prevent N+1 without leaking memory
     */
    protected static function resolveCache(string $type, array $ids, callable $resolver): Collection
    {
        // Using an object to store state that is cached by once() with a static key
        $cacheObj = once(static function () {
            return new class {
                public array $data = [];
            };
        });

        if (!isset($cacheObj->data[$type])) {
            $cacheObj->data[$type] = [];
        }

        $missingIds = array_diff($ids, array_keys($cacheObj->data[$type]));

        if (!empty($missingIds)) {
            $resolved = $resolver($missingIds);
            foreach ($missingIds as $id) {
                $cacheObj->data[$type][$id] = $resolved->get($id, false);
            }
        }

        return collect($ids)
            ->map(fn ($id) => $cacheObj->data[$type][$id] ?? null)
            ->filter(fn ($item) => $item !== false && $item !== null)
            ->pipe(fn ($c) => new Collection($c->all()));
    }

    /**
     * Resolve owners (department codes) to Department models.
     */
    public function departments(): Collection
    {
        if (empty($this->owners)) {
            return new Collection();
        }

        return self::resolveCache('departments', $this->owners, fn($codes) => Department::whereIn('code', $codes)->get()->keyBy('code'));
    }

    public function getStatusIcon(): string
    {
        return self::$statusIconMapping[$this->status] ?? $this->status;
    }

    public function getStatusInFarsi(): string
    {
        return self::$statusMapping[$this->status] ?? $this->status;
    }

    public function reads(): HasMany
    {
        return $this->hasMany(Read::class, 'document_id');
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
                    ->orWhereJsonContains('owners', auth()->user()?->profile?->department)
                    ->orWhereJsonContains('users', (string) auth()->id())
                    ->orWhereJsonContains('users', auth()->id());
            });
    }

    /**
     * Resolve user IDs to User models.
     */
    public function users(): Collection
    {
        if (empty($this->users)) {
            return new Collection();
        }

        return self::resolveCache('users', $this->users, fn($ids) => User::whereIn('id', $ids)->get()->keyBy('id'));
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