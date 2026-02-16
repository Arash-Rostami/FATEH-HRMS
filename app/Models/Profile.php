<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Profile extends Model
{

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
        'department',
        'position',
        'insurance',
        'emergency_phone',
        'emergency_relationship',
        'start_date',
        'end_date',
        'work_experience',
        'interests',
        'favorite_colors',
        'user_id',
    ];

    public function age(): ?int
    {
        return $this->birthdate?->age;
    }

    public function countDaysPassedSince($date)
    {
        if (!$date) return null;

        return Cache::remember('days_passed_work_' . $this->id, now()->addHours(12), function () use ($date) {
            return Carbon::parse($date)->diffInDays(now()) . ' روز';
        });
    }

    public function countNumberOfDaysTo($date)
    {
        if (!$date) return null;

        return Cache::remember('days_to_birthday_' . $this->id, now()->addHours(12), function () use ($date) {
            $birthday = Carbon::parse($date)->year(now()->year);
            if ($birthday->isPast()) {
                $birthday->addYear();
            }
            return $birthday->diffInDays(now()) . ' روز';
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department', 'code');
    }

    public function isMarried(): bool
    {
        return $this->marital_status === 'married';
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

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'favorite_colors' => 'array',
            'birthdate' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'number_of_children' => 'integer',
        ];
    }
}
