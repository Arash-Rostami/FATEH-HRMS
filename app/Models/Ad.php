<?php

namespace App\Models;

use App\Models\Traits\HasMenuState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use HasMenuState,
        HasFactory;

    protected $table = 'ads';

    protected $fillable = [
        'position',
        'certificate',
        'skill',
        'experience',
        'gender',
        'link',
        'active',
        'extra'
    ];

    public static function countActiveJobs(): int
    {
        return self::active()->count();
    }

    public static function countBothGender(): int
    {
        return self::gender('Any')->count();
    }

    public static function countFemales(): int
    {
        return self::gender('Female')->count();
    }

    public static function countInactiveJobs(): int
    {
        return self::inactive()->count();
    }

    public static function countMales(): int
    {
        return self::gender('Male')->count();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeGender(Builder $query, string $gender): Builder
    {
        return $query->where('gender', $gender);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('active', false);
    }

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->gender) {
                'Male' => ['icon' => 'manage_accounts', 'color' => 'blue', 'title' => 'آقایان'],
                'Female' => ['icon' => 'badge', 'color' => 'pink', 'title' => 'خانم‌ها'],
                default => ['icon' => 'group', 'color' => 'green', 'title' => 'همه'],
            }
        );
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'extra' => 'array',
        ];
    }
}
