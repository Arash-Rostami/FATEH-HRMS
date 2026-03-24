<?php

namespace App\Models;

use App\Models\Traits\HasDepartmentHelpers;
use App\Models\Traits\HasDmsCountHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class DMS extends Model
{
    use HasFactory;
    use HasDepartmentHelpers;
    use HasDmsCountHelpers;


    private static $statusMapping = [
        'live' => 'فعال',
        'under_review' => 'در حال بررسی',
        'obsolete' => 'منسوخ شده',
    ];
    private static $statusIconMapping = [
        'live' => '<i class="material-icons text-green-500">check_circle</i>',
        'under_review' => '<i class="material-icons text-yellow-500">hourglass_bottom</i>',
        'obsolete' => '<i class="material-icons text-red-500">cancel</i>',
    ];
    protected $table = 'dms';
    protected $fillable = [
        'file',
        'code',
        'version',
        'title',
        'status',
        'owners',
        'users',
        'revision',
        'combined_read_count',
        'extra'
    ];
    protected function casts(): array
    {
        return [
            'owners' => 'array',
            'users' => 'array',
            'extra' => 'array',
        ];
    }
    public function getStatusIcon()
    {
        return self::$statusIconMapping[$this->status] ?? $this->status;
    }

    public function getStatusInFarsi()
    {
        return self::$statusMapping[$this->status] ?? $this->status;
    }
    public function departments()
    {
        return Department::whereIn('code', $this->owners)->get();
    }
    public function users()
    {
        return User::whereIn('id', $this->users)->get();
    }
    public function reads()
    {
        return $this->hasMany(Read::class, 'document_id');
    }
    public function scopeVisibleToUser($query)
    {
        return $query->where('status', 'live')
            ->where(function ($query) {
                $query
                    ->whereJsonContains('owners', 'ALL')
                    ->orWhereJsonContains('owners', auth()->user()?->profile->department);
            });
    }
}
