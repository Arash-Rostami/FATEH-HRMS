<?php

namespace App\Livewire\Dashboard\Profile;

use App\Models\Department;
use App\Models\Profile;
use App\Traits\ProfileState;
use App\Traits\ProfileValidation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Morilog\Jalali\Jalalian;

class Info extends Component
{
    use WithFileUploads;
    use ProfileState;
    use ProfileValidation;
    #[On('confirm-delete-profile-image')]
    public function deleteImage(): void
    {
        $profile = Auth::user()->profile;
        if ($profile && $profile->image) {
            $profile->image = null;
            $profile->save();
            $this->existingImage = null;
            $this->dispatch('toast', message: 'تصویر پروفایل با موفقیت حذف شد.', type: 'success');
        }
    }

    public function mount(): void
    {
        $user = Auth::user();
        $profile = $user->profile;

        if ($profile) {
            $this->state = array_merge($this->state, $profile->only(array_keys($this->state)));
            $this->existingImage = $profile->image;

            $this->favoriteColors = is_array($profile->favorite_colors)
                ? $profile->favorite_colors
                : (is_string($profile->favorite_colors) ? explode(',', $profile->favorite_colors) : []);

            if ($profile->birthdate) {
                $jalali = Jalalian::fromCarbon($profile->birthdate);
                $this->birthYear = $jalali->getYear();
                $this->birthMonth = $jalali->getMonth();
                $this->birthDay = $jalali->getDay();
            }
        }

        $this->state['email'] = $user->email ?? '';
    }

    public function render()
    {
        return view('livewire.dashboard.profile.info', [
            'departments' => Department::pluck('name', 'code')->toArray()
        ]);
    }

    public function save(): void
    {
        $this->validate();

        $user = Auth::user();
        $profile = $user->profile ?? new Profile(['user_id' => $user->id]);

        $profile->fill(Arr::only($this->state, [
            'gender', 'marital_status', 'number_of_children', 'id_card_number', 'id_booklet_number',
            'degree', 'field', 'landline', 'cellphone', 'license_plate', 'zip_code', 'address',
            'accessibility', 'insurance', 'emergency_phone', 'emergency_relationship',
            'work_experience', 'interests'
        ]));

        if ($this->birthYear && $this->birthMonth && $this->birthDay) {
            try {
                $profile->birthdate = Jalalian::fromFormat(
                    'Y/n/j',
                    "{$this->birthYear}/{$this->birthMonth}/{$this->birthDay}"
                )->toCarbon();
            } catch (\Exception $e) {
                $this->dispatch('toast', message: 'تاریخ تولد نامعتبر است.', type: 'error');
                return;
            }
        }

        if ($this->image) {
            $path = $this->image->store('profiles', 'public');
            $profile->image = $path;
            $this->existingImage = $path;
            $this->image = null;
        }

        $profile->favorite_colors = $this->favoriteColors;
        $profile->save();

        $this->dispatch('toast', message: 'اطلاعات پروفایل با موفقیت ذخیره شد.', type: 'success');
    }
}
