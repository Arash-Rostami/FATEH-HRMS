<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SaveSignCodeAction
{
    public function execute(string $currentPassword, string $code): void
    {
        $user = $this->guard($currentPassword);

        if (Hash::check($code, $user->password)) {
            throw ValidationException::withMessages([
                'signCodeForm.code' => __('resources/correspondence/strings.profile.sign_code_same_as_password'),
            ]);
        }

        $hash = Hash::make($code);

        DB::transaction(function () use ($user, $hash): void {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail(['id', 'extra']);

            $extra = $locked->extra;
            Arr::set($extra, 'admin.sign_code_hash', $hash);
            $locked->extra = $extra;
            $locked->save();

            $user->extra = $locked->extra;
        });
    }

    public function clear(string $currentPassword): void
    {
        $user = $this->guard($currentPassword);

        DB::transaction(function () use ($user): void {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail(['id', 'extra']);

            $extra = $locked->extra;
            Arr::forget($extra, 'admin.sign_code_hash');
            $locked->extra = $extra;
            $locked->save();

            $user->extra = $locked->extra;
        });
    }

    private function guard(string $currentPassword): User
    {
        $user = Auth::user();

        $message = __('resources/correspondence/strings.profile.sign_code_password_wrong');

        if (!$user instanceof User || !$user->isActive() || tenantOff('correspondence')) {
            throw ValidationException::withMessages(['signCodeForm.current_password' => $message]);
        }

        $key = "sign-code:profile:{$user->id}";
        $attempts = (int) config('ai.correspondence.sign_code_attempts', 5);
        $decay = max(1, (int) config('ai.correspondence.sign_code_minutes', 15)) * 60;

        if (RateLimiter::tooManyAttempts($key, $attempts)) {
            throw ValidationException::withMessages(['signCodeForm.current_password' => $message]);
        }

        RateLimiter::hit($key, $decay);

        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages(['signCodeForm.current_password' => $message]);
        }

        RateLimiter::clear($key);

        return $user;
    }
}
