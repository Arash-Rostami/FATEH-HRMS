<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class Profile extends Model
{
    use HasFactory;

    protected  = [
        'user_id',
        'personnel_id',
        'birthdate',
        'start_date',
    ];

    protected  = [
        'birthdate' => 'date',
        'start_date' => 'date',
    ];

    public function user()
    {
        return ->belongsTo(User::class);
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

    public function countDaysPassedSince($date)
    {
        if (!$date) return null;

        return Cache::remember('days_passed_work_' . $this->id, now()->addHours(12), function () use ($date) {
            return Carbon::parse($date)->diffInDays(now()) . ' روز';
        });
    }
}
