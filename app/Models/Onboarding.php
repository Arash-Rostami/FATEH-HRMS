<?php

namespace App\Models;

use App\Models\Concerns\HasExtraCatalog;
use App\Models\Concerns\HasUser;
use App\Models\Concerns\Hooks\HasOnboardingHooks;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasNotifyEvents;

class Onboarding extends Model
{
    use HasFactory,
        HasExtraCatalog,
        CleansAttachedFiles,
        HasNotifyEvents,
        HasUser,
        HasOnboardingHooks;

    protected $fillable = [
        'welcome',
        'videos',
        'mission',
        'vision',
        'guides',
        'schedule',
        'extras',
        'is_active',
        'user_id'
    ];

    public function scopeVisibleTo(Builder $query, ?int $userId): Builder
    {
        return $query->where(fn(Builder $q) => $q->where('user_id', $userId)->orWhereNull('user_id'));
    }

    public static function notifyFields(): array
    {
        return ['user_id' => 'user'];
    }

    protected function casts(): array
    {
        return [
            'videos' => AsCollection::class,
            'guides' => AsCollection::class,
            'extras' => AsArrayObject::class,
            'is_active' => 'boolean',
        ];
    }
}
