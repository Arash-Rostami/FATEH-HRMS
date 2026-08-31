<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Livewire\Dashboard\Profile\Forms\DocumentForm;
use App\Models\Profile;
use App\Traits\CleansAttachedFiles;
use App\Traits\StoresAttachedFiles;
use Illuminate\Support\Facades\Auth;

class UploadStandardDocumentAction
{
    use CleansAttachedFiles, StoresAttachedFiles;

    public function __construct(
        private ResetDocumentStateAction $resetAction
    ) {}

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
            $newPath = static::storeAttachment($uploadedFile, "profiles/docs/{$userProfile->getDocsPathToken()}", $fileName)['path'];

            $currentAttachments = collect($userProfile->attachments ?? []);
            $oldPaths = $currentAttachments
                ->filter(fn ($item) => str_contains($item['path'] ?? '', "doc_standard_{$key}_"))
                ->pluck('path');

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

            static::deleteStoredFiles($oldPaths);

            $this->resetAction->removeFile($form, $key);

            return ['success' => true, 'path' => $newPath, 'error' => null];

        } catch (\Exception $e) {
            report($e);
            return ['success' => false, 'path' => null, 'error' => 'خطایی در بارگذاری فایل رخ داد.'];
        }
    }
}
