<?php

namespace App\Livewire\Dashboard\Profile;

use App\Actions\Fortify\UpdateUserPassword;
use App\Livewire\Dashboard\Profile\Forms\PasswordForm;
use App\Traits\FocusOnRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Credentials extends Component
{
    use FocusOnRecord;

    public string $search = '';

    public PasswordForm $passwordForm;

    public function placeholder(): View
    {
        return view('livewire.dashboard.profile.credentials.placeholder');
    }

    public function savePassword(UpdateUserPassword $action): void
    {
        $this->passwordForm->validate();

        $action->update(Auth::user(), [
            'current_password' => $this->passwordForm->current_password,
            'password' => $this->passwordForm->password,
            'password_confirmation' => $this->passwordForm->password_confirmation,
        ]);

        $this->passwordForm->reset();

        $this->dispatch('toast', message: 'رمز عبور با موفقیت تغییر کرد.', type: 'success');
    }

    #[Computed]
    public function credentials()
    {
        return Auth::user()->credentials()
            ->when(
                $this->open,
                // FOCUS MODE: pin to the single record chosen in the command palette
                fn($q) => $q->whereKey($this->open),
                // NORMAL MODE: apply the search box.
                fn($q) => $q->when($this->search !== '', fn($q) => $q
                    ->where('app_name', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%")
                )
            )
            ->get();
    }

    #[Computed]
    public function hasAnyCredentials(): bool
    {
        return Auth::user()->credentials()->exists();
    }

    public function render()
    {
        return view('livewire.dashboard.profile.credentials');
    }

    public function updatedSearch(): void
    {
        $this->open = null;
    }
}
