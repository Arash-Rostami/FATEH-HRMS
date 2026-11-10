<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadAttachmentAction
{
    public function execute(User $user, int $id, int $index): BinaryFileResponse
    {
        $letter = Correspondence::query()->find($id, ['id', 'user_id', 'status', 'recipients', 'parties', 'spec', 'deleted_at']);
        if ($letter === null || !CorrespondenceAccessPolicy::canView($user, $letter)) {
            LetterRules::deny();
        }

        $file = array_values((array) ($letter->spec['attachments'] ?? []))[$index] ?? null;
        $path = is_array($file) ? ($file['path'] ?? null) : null;
        $disk = Storage::disk('local');
        if (!is_string($path) || !LetterRules::ownsPath($path, $id) || !$disk->exists($path)) {
            LetterRules::deny();
        }

        $name = trim((string) preg_replace('#[/\\\\\x00-\x1f\x7f]+#', '-', (string) ($file['name'] ?? ''))) ?: basename($path);
        $full = $disk->path($path);

        return response()->file($full)->setContentDisposition(
            strtolower(pathinfo($full, PATHINFO_EXTENSION)) === 'pdf' ? 'inline' : 'attachment',
            $name,
        );
    }
}
