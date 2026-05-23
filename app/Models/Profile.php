<?php

namespace App\Models;

use App\Models\Traits\HasDateHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasFactory, HasDateHelpers;

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
        'department_id',
        'position',
        'insurance',
        'emergency_phone',
        'emergency_relationship',
        'start_date',
        'end_date',
        'work_experience',
        'interests',
        'favorite_colors',
        'about_me',
        'user_id',
    ];

    public function age(): ?int
    {
        return $this->birthdate?->age;
    }


    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'code');
    }

    public function isMarried(): bool
    {
        return $this->marital_status === 'married';
    }

    public function isProbational(): bool
    {
        return $this->employment_type === 'probational';
    }

    public function isTerminated(): bool
    {
        return $this->employment_type === 'terminated';
    }

    public function isWorking(): bool
    {
        return $this->employment_type === 'working';
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
            'about_me' => 'array',
        ];
    }
}
