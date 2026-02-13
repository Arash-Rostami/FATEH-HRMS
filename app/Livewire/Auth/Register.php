<?php
namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class Register extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|confirmed|min:7',
    ];

    protected $messages = [
        'name.required' => 'نام لازم است.',
        'name.max'      => 'نام طولانی است.',
        'email.required' => 'ایمیل لازم است.',
        'email.email'    => 'ایمیل نامعتبر است.',
        'email.unique'   => 'ایمیل قبلا ثبت شده.',
        'password.required' => 'رمز لازم است.',
        'password.min'      => 'رمز حداقل 7 حرف باشد.',
        'password.confirmed'=> 'رمزها مطابقت ندارند.',
    ];

    public function register()
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        Auth::login($user);
        session()->regenerate();

        return redirect(config('fortify.home', '/home'));
    }

    public function render()
    {
        return view('livewire.auth.register')->layout('layouts.auth');
    }
}
