<?php

namespace App\Livewire\Dashboard\Tab\Profile;

use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Info extends Component
{
    public $profileData = [];
    public $departments = [];

    public function rules()
    {
        return [
            'profileData.personnel_id' => 'nullable|string|max:50',
            'profileData.gender' => 'nullable|in:male,female',
            'profileData.marital_status' => 'nullable|in:single,married',
            'profileData.number_of_children' => 'nullable|integer|min:0',
            'profileData.id_card_number' => 'nullable|string|max:20',
            'profileData.id_booklet_number' => 'nullable|string|max:20',
            'profileData.birthdate' => 'nullable|date',
            'profileData.department_id' => 'nullable|string|exists:departments,code',
            'profileData.position' => 'nullable|string|max:100',
            'profileData.employment_type' => 'nullable|in:fulltime,parttime,contract',
            'profileData.employment_status' => 'nullable|in:probational,working,terminated',
            'profileData.insurance' => 'nullable|string|max:50',
            'profileData.work_experience' => 'nullable|integer|min:0',
            'profileData.cellphone' => 'nullable|string|max:20',
            'profileData.landline' => 'nullable|string|max:20',
            'profileData.emergency_phone' => 'nullable|string|max:20',
            'profileData.emergency_relationship' => 'nullable|string|max:50',
            'profileData.zip_code' => 'nullable|string|max:20',
            'profileData.address' => 'nullable|string|max:500',
            'profileData.degree' => 'nullable|in:undergraduate,graduate,postgraduate',
            'profileData.field' => 'nullable|string|max:100',
            'profileData.interests' => 'nullable|string|max:500',
            'profileData.accessibility' => 'nullable|string|max:500',
        ];
    }

    public function mount()
    {
        $user = Auth::user();
        if (!$user->profile) {
            $user->profile()->create();
            $user->load('profile');
        }

        $this->profileData = $user->profile->toArray();
        $this->departments = Department::pluck('name', 'code')->toArray();
    }

    public function save()
    {
        $validated = $this->validate();

        Auth::user()->profile->update($validated['profileData']);

        $this->dispatch('notify', message: 'اطلاعات پروفایل با موفقیت ذخیره شد.', type: 'success');
    }

    public function calculateCompletion(): int
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

    public function render()
    {
        return view('livewire.dashboard.tab.profile.info', [
            'user' => Auth::user(),
            'completion' => $this->calculateCompletion(),
        ]);
    }
}
