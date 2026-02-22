<?php

namespace App\Livewire\Dashboard\Tab;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Profile extends Component
{
    public string $activeTab = 'info';

    public function render()
    {
        return view('livewire.dashboard.tab.profile.index', [
            'user' => Auth::user(),
            'completion' => $this->calculateCompletion(),
        ])->extends('layouts.app')->section('content');
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    private function calculateCompletion(): int
    {
        $profile = Auth::user()->profile;
        if (!$profile) return 0;

        $fields = [
            'personnel_id', 'gender', 'employment_type', 'marital_status',
            'id_card_number', 'degree', 'field', 'birthdate',
            'cellphone', 'address', 'department_id', 'position',
            'insurance', 'emergency_phone', 'start_date'
        ];

        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($profile->$field)) {
                $filled++;
            }
        }

        return (int)round(($filled / count($fields)) * 100);
    }
}
