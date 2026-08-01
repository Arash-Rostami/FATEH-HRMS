<?php

namespace App\Livewire\Dashboard\ReleaseRequest\Actions;

use App\Enums\ReleaseRequestStatus;
use App\Livewire\Dashboard\ReleaseRequest\Forms\ReleaseRequestForm;
use App\Models\ReleaseRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SubmitReleaseRequestAction
{
    public function execute(ReleaseRequestForm $form): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);

        $key = 'release-request:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'form.body' => 'تعداد درخواست‌ها بیش از حد مجاز است. ' . RateLimiter::availableIn($key) . ' ثانیه دیگر تلاش کنید.',
            ]);
        }

        $validated = $form->validate();

        ReleaseRequest::create([
            'user_id' => (int) $user->id,
            'type'    => $validated['type'],
            'title'   => $validated['title'],
            'body'    => $validated['body'],
            'status'  => ReleaseRequestStatus::Open->value,
        ]);

        RateLimiter::hit($key, 3600);
    }
}