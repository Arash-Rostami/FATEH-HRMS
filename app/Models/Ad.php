<?php

namespace App\Models;

use App\Models\Concerns\HasMenuState;
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
        return self::getActiveCounts()['active'];
    }

    public static function countFemales(): int
    {
        return self::getGenderCounts()['Female'];
    }

    public static function countInactiveJobs(): int
    {
        return self::getActiveCounts()['inactive'];
    }

    public static function countMales(): int
    {
        return self::getGenderCounts()['Male'];
    }

    public static function getActiveCounts(): array
    {
        $counts = self::query()->selectRaw('active, count(*) as aggregate')->groupBy('active')->pluck('aggregate', 'active');

        return [
            'active' => (int)($counts[1] ?? 0),
            'inactive' => (int)($counts[0] ?? 0),
        ];
    }

    public static function getGenderCounts(): array
    {
        $counts = self::query()->selectRaw('gender, count(*) as aggregate')->groupBy('gender')->pluck('aggregate', 'gender');

        return [
            'Male' => (int)($counts['Male'] ?? 0),
            'Female' => (int)($counts['Female'] ?? 0),
            'Any' => (int)($counts['Any'] ?? 0),
        ];
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
