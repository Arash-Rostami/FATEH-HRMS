<?php

namespace App\Livewire\Dashboard\Tab\Profile;

use App\Models\Department;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Carbon\Carbon;

class Info extends Component
{
    use WithFileUploads;

    public Profile $profile;
    public $image;
    public $departments = [];

    // Date parts
    public $birthYear;
    public $birthMonth;
    public $birthDay;

    protected function rules()
    {
        return [
            'profile.personnel_id' => 'nullable|string|max:255',
            'profile.gender' => 'nullable|in:male,female',
            'profile.employment_type' => 'nullable|in:fulltime,parttime,contract',
            'profile.marital_status' => 'nullable|in:single,married',
            'profile.number_of_children' => 'nullable|integer|min:0',
            'profile.employment_status' => 'nullable|in:probational,working,terminated',
            'profile.id_card_number' => 'nullable|string|max:20',
            'profile.id_booklet_number' => 'nullable|string|max:20',
            'profile.degree' => 'nullable|string|max:255',
            'profile.field' => 'nullable|string|max:255',
            'profile.birthdate' => 'nullable|date',
            'profile.landline' => 'nullable|string|max:20',
            'profile.cellphone' => 'required|string|max:20',
            'profile.license_plate' => 'nullable|string|max:20',
            'profile.zip_code' => 'nullable|string|max:20',
            'profile.address' => 'nullable|string|max:1000',
            'profile.accessibility' => 'nullable|string|max:500',
            'profile.department_id' => 'nullable|exists:departments,code',
            'profile.position' => 'nullable|string|max:255',
            'profile.insurance' => 'nullable|string|max:50',
            'profile.emergency_phone' => 'nullable|string|max:20',
            'profile.emergency_relationship' => 'nullable|string|max:50',
            'profile.start_date' => 'nullable|date',
            'profile.end_date' => 'nullable|date',
            'profile.work_experience' => 'nullable|string|max:50',
            'profile.interests' => 'nullable|string|max:1000',
            'profile.favorite_colors' => 'nullable|array',

            'birthYear' => 'nullable|integer',
            'birthMonth' => 'nullable|integer',
            'birthDay' => 'nullable|integer',
        ];
    }

    public function mount()
    {
        $this->profile = Auth::user()->profile ?? new Profile(['user_id' => Auth::id()]);
        $this->departments = Department::pluck('name', 'code')->toArray();

        if ($this->profile->birthdate) {
             $date = $this->profile->birthdate;
             if ($date) {
                 $this->birthYear = $date->year;
                 $this->birthMonth = $date->month;
                 $this->birthDay = $date->day;
             }
        }
    }

    public function save()
    {
        if ($this->birthYear && $this->birthMonth && $this->birthDay) {
            try {
                $this->profile->birthdate = Carbon::createFromDate($this->birthYear, $this->birthMonth, $this->birthDay);
            } catch (\Exception $e) {
                // Handle invalid date
            }
        }

        $this->validate();

        if ($this->image) {
            $this->validate(['image' => 'image|max:2048']);
            $path = $this->image->store('profiles', 'public');
            $this->profile->image = $path;
        }

        $this->profile->user_id = Auth::id();
        $this->profile->save();

        $this->dispatch('notify', message: 'Profile updated successfully!', type: 'success');
        $this->dispatch('profile-updated');
    }

    public function deleteImage()
    {
        if ($this->profile->image) {
            $this->profile->image = null;
            $this->profile->save();
        }
    }

    public function render()
    {
        return view('livewire.dashboard.tab.profile.info');
    }
}
