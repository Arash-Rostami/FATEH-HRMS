<?php

namespace App\Models\Traits;

use App\Models\Reply;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HasReplies
{
    public function replies(): MorphMany
    {
        return $this->morphMany(Reply::class, 'repliable')->with('user')->oldest();
    }

    public function latestReply(): ?Reply
    {
        $this->loadMissing('replies');

        return $this->replies->last();
    }

    public function otherReplyParticipants(array $participantIds): array
    {
        $latest = $this->latestReply();

        if (!$latest) {
            return [];
        }

        return array_values(array_unique(array_filter(
            $participantIds,
            fn($id) => $id !== null && (int)$id !== (int)$latest->user_id
        )));
    }

    public function addReply(User $user, string $body, array $uploadedFiles = []): Reply
    {
        return DB::transaction(function () use ($user, $body, $uploadedFiles) {
            $reply = $this->replies()->create([
                'user_id' => $user->id,
                'body' => trim($body),
                'files' => [],
            ]);

            $files = $this->storeReplyFiles($uploadedFiles, $reply->id);

            if ($files) {
                $reply->update(['files' => $files]);
            }

            return $reply;
        });
    }

    private function storeReplyFiles(array $files, int $replyId): array
    {
        $directory = Str::snake(class_basename($this)) . "/replies/{$this->getKey()}/{$replyId}";
        $stored = [];

        try {
            foreach ($files as $file) {
                $path = $file->storeAs($directory, time() . '_' . Str::random(10) . '.' . $file->extension(), 'public');

                $stored[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        } catch (\Throwable $e) {
            foreach ($stored as $item) {
                Storage::disk('public')->delete($item['path']);
            }

            throw $e;
        }

        return $stored;
    }
}
