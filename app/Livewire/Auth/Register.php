<?php
namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuthValidationRules;

class Register extends Component
{
    use AuthValidationRules;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register()
    {
        $this->validate(
            [
                'name' => 'required|string|max:255',
                'email' => $this->emailRules(true),
                'password' => $this->passwordRules(true),
            ],
            $this->validationMessages()
        );

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        Auth::login($user);
        session()->regenerate();

        return redirect(config('fortify.home', '/dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.register')->layout('layouts.auth');
    }
}
