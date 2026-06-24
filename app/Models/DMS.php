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


    public function getExtraValue(string $key, mixed $default = null): mixed
    {
        return \Illuminate\Support\Arr::get($this->extra ?? [], $key, $default);
    }

    public function getStatusIcon()
    {
        return self::$statusIconMapping[$this->status] ?? $this->status;
    }

    public function getStatusInFarsi()
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

    protected static function booted(): void
    {
        static::updated(function (DMS $document): void {
            if ($document->wasChanged('file') || $document->wasChanged('revision')) {
                $document->reads()->update(['read' => false, 'read_count' => 0]);
                $document->newQuery()->whereKey($document->getKey())->update(['combined_read_count' => 0]);
            }
        });
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
