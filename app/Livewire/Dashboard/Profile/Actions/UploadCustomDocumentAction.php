<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Livewire\Dashboard\Profile\Forms\DocumentForm;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UploadCustomDocumentAction
{
    public function __construct(
        private ResetDocumentStateAction $resetAction
    ) {}

    /**
     * Execute custom document upload.
     *
     * @return array{success: bool, path: string|null, error: string|null}
     */
    public function execute(DocumentForm $form, string $pendingFileName): array
    {
        if (!$form->customFile) {
            return ['success' => false, 'path' => null, 'error' => 'فایل یافت نشد.'];
        }

        try {
            $timestamp = time();
            $extension = $form->customFile->getClientOriginalExtension();
            $slug = Str::slug($form->customType, '-');

            if (empty($slug)) {
                $slug = 'doc';
            }

            $userProfile = Auth::user()->profile ?? new Profile(['user_id' => Auth::id()]);

            if (!$userProfile->exists) {
                $userProfile->save();
            }

            $fileName = "doc_custom_{$slug}_{$timestamp}.{$extension}";
            $newPath = $form->customFile->storeAs("profiles/docs/{$userProfile->id}", $fileName, 'public');

            $currentAttachments = collect($userProfile->attachments ?? []);

            $userProfile->attachments = $currentAttachments
                ->reject(fn ($item) => str_contains($item['path'] ?? '', "doc_custom_{$slug}_"))
                ->push([
                    'key'      => $form->customType,
                    'path'     => $newPath,
                    'category' => 'custom',
                ])
                ->values()
                ->all();

            $userProfile->save();

            $this->resetAction->resetCustom($form);

            return ['success' => true, 'path' => $newPath, 'error' => null];

        } catch (\Exception $e) {
            return ['success' => false, 'path' => null, 'error' => 'خطایی در ذخیره مدرک سفارشی رخ داد.'];
        }
    }
}
