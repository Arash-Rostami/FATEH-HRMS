<?php

namespace App\Livewire\Dashboard\ReleaseRequest\Actions;

use App\Enums\ReleaseRequestStatus;
use App\Livewire\Dashboard\ReleaseRequest\Forms\ReleaseRequestForm;
use App\Models\ReleaseRequest;
use App\Traits\StoresAttachedFiles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubmitReleaseRequestAction
{
    use StoresAttachedFiles;


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
        $this->validateAttachments($form);

        ReleaseRequest::create([
            'user_id'     => (int) $user->id,
            'type'        => $validated['type'],
            'title'       => $validated['title'],
            'body'        => $validated['body'],
            'status'      => ReleaseRequestStatus::Open->value,
            'attachments' => $this->storeAttachments($form),
        ]);

        RateLimiter::hit($key, 3600);
    }

    private function storeAttachments(ReleaseRequestForm $form): array
    {
        return collect($form->attachments)
            ->map(fn($file) => static::storeAttachment($file, 'release_request/attachments'))
            ->values()
            ->all();
    }

    private function validateAttachments(ReleaseRequestForm $form): void
    {
        Validator::make(['attachments' => $form->attachments], [
            'attachments'   => 'array|max:5',
            'attachments.*' => 'file|max:4096|mimes:jpg,jpeg,png,gif,bmp,webp,svg,pdf,doc,docx,xls,xlsx',
        ], [
            'attachments.max'     => __('resources/release_request/strings.validation.attachments.max_items'),
            'attachments.*.max'   => __('resources/release_request/strings.validation.attachments.max_size'),
            'attachments.*.mimes' => __('resources/release_request/strings.validation.attachments.mime_types'),
        ])->validate();
    }
}