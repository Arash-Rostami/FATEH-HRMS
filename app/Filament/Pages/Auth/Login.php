<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as FilamentLogin;
use Filament\Facades\Filament;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Validation\ValidationException;

class Login extends FilamentLogin
{
    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        $user = Filament::auth()->user();

        if ($user && !$user->isActive()) {
            Filament::auth()->logout();
            throw ValidationException::withMessages([
                'data.email' => 'حساب کاربری شما غیرفعال است.',
            ]);
        }

        return $response;
    }
}