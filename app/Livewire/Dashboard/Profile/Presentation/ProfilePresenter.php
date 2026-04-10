<?php

namespace App\Livewire\Dashboard\Profile\Presentation;

use App\Models\User;

class ProfilePresenter
{
    public function completion(User $user): int
    {
        $profile = $user->profile;
        if (!$profile) return 0;

        $fields = [
            'personnel_id', 'gender', 'employment_type', 'marital_status',
            'id_card_number', 'degree', 'field', 'birthdate',
            'cellphone', 'address', 'department_id', 'position',
            'insurance', 'emergency_phone', 'start_date'
        ];

        $filled = collect($fields)->filter(fn($f) => !empty($profile->{$f}))->count();

        return (int) round(($filled / count($fields)) * 100);
    }
}
