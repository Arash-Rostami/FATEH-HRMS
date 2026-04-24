<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Livewire\Dashboard\Profile\Forms\DocumentForm;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;

class UploadStandardDocumentAction
{
    public function __construct(
        private ResetDocumentStateAction $resetAction
    ) {}

    /**
     * Execute standard document upload.
     *
     * @return array{success: bool, path: string|null, error: string|null}
     */
    public function execute(DocumentForm $form, string $key, string $pendingFileName): array
    {
        $uploadedFile = $this->resetAction->getFile($form, $key);

        if (!$uploadedFile) {
            return ['success' => false, 'path' => null, 'error' => 'فایل یافت نشد.'];
        }

        try {
            $timestamp = time();
            $extension = $uploadedFile->getClientOriginalExtension();

            $userProfile = Auth::user()->profile ?? new Profile(['user_id' => Auth::id()]);

            if (!$userProfile->exists) {
                $userProfile->save();
            }

            $fileName = "doc_standard_{$key}_{$timestamp}.{$extension}";
            $newPath = $uploadedFile->storeAs("profiles/docs/{$userProfile->id}", $fileName, 'public');

            $currentAttachments = collect($userProfile->attachments ?? []);

            $userProfile->attachments = $currentAttachments
                ->reject(fn ($item) => str_contains($item['path'] ?? '', "doc_standard_{$key}_"))
                ->push([
                    'key'      => $key,
                    'path'     => $newPath,
                    'category' => 'standard',
                ])
                ->values()
                ->all();

            $userProfile->save();

            // Reset the file from form after successful upload
            $this->resetAction->removeFile($form, $key);

            return ['success' => true, 'path' => $newPath, 'error' => null];

        } catch (\Exception $e) {
            return ['success' => false, 'path' => null, 'error' => 'خطایی در بارگذاری فایل رخ داد.'];
        }
    }
}
