<?php

namespace App\Livewire\Dashboard\Profile;

use App\Livewire\Dashboard\Profile\Actions\DeleteProfileImageAction;
use App\Livewire\Dashboard\Profile\Actions\SaveProfileAction;
use App\Livewire\Dashboard\Profile\Forms\ProfileForm;
use App\Livewire\Dashboard\Profile\Presentation\InfoPresenter;
use App\Models\Department;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Morilog\Jalali\Jalalian;

class Info extends Component
{
    use WithFileUploads;

    public ProfileForm $form;

    public ?string $existingImage = null;

    #[On('confirm-delete-profile-image')]
    public function deleteImage(DeleteProfileImageAction $action): void
    {
        $deleted = $action->execute();

        if ($deleted) {
            $this->existingImage = null;
            $this->dispatch('toast', message: 'تصویر پروفایل با موفقیت حذف شد.', type: 'success');
        }
    }

    #[Computed]
    public function departments(): array
    {
        return Department::getCachedOptions()->toArray();
    }

    public function mount(): void
    {
        $user = Auth::user();
        $profile = $user->profile ?? new Profile(['user_id' => $user->id]);

        $this->form->fill(
            collect($profile->only([
                'gender', 'marital_status', 'number_of_children', 'id_card_number',
                'id_booklet_number', 'degree', 'field', 'landline', 'cellphone',
                'license_plate', 'zip_code', 'address', 'accessibility', 'insurance',
                'emergency_phone', 'emergency_relationship', 'work_experience',
                'interests', 'email'
            ]))->map(fn($v, $k) => match ($k) {
                'number_of_children' => (int)($v ?? 0),
                default => $v ?? '',
            }, ARRAY_FILTER_USE_BOTH)->toArray()
        );

        $this->form->favoriteColors = is_array($profile->favorite_colors)
            ? $profile->favorite_colors
            : (is_string($profile->favorite_colors)
                ? explode(',', $profile->favorite_colors)
                : []);

        if ($profile->birthdate) {
            $jalali = Jalalian::fromCarbon($profile->birthdate);
            $this->form->birthYear = $jalali->getYear();
            $this->form->birthMonth = $jalali->getMonth();
            $this->form->birthDay = $jalali->getDay();
        }

        $this->existingImage = $profile->image;
        $this->form->email = $user->email ?? '';
    }

    public function render(InfoPresenter $presenter)
    {
        return view(
            'livewire.dashboard.profile.info',
            $presenter->toView($this->form, $this->existingImage, $this->departments)
        );
    }

    public function save(SaveProfileAction $action): void
    {
        $result = $action->execute($this->form);

        if ($result['imagePath'] ?? null) {
            $this->existingImage = $result['imagePath'];
            $this->form->image = null;
        }

        $this->dispatch('toast', message: 'اطلاعات پروفایل با موفقیت ذخیره شد.', type: 'success');
    }
}
