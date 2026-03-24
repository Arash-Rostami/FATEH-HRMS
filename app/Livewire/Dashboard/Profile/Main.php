<?php

namespace App\Livewire\Dashboard\Profile;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Main extends Component
{
    public string $activeTab = 'info';

    public function confirmAction(string $event)
    {
        $this->dispatch($event);
    }
    public function render()
    {
        return view('livewire.dashboard.profile.index', [
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

        if (!$profile) {
            return 0;
        }

        $fields = [
            'personnel_id', 'gender', 'employment_type', 'marital_status',
            'id_card_number', 'degree', 'field', 'birthdate',
            'cellphone', 'address', 'department_id', 'position',
            'insurance', 'emergency_phone', 'start_date'
        ];

        $filled = collect($fields)->filter(fn($field) => !empty($profile->{$field}))->count();

        return (int)round(($filled / count($fields)) * 100);
    }
}
