<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Livewire\Dashboard\Contact\Forms\MessageComposerForm;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SendMessageAction
{
    public function execute(MessageComposerForm $form, int $recipientId): Message
    {
        $form->validate();

        $senderId = auth()->id();

        return DB::transaction(function () use ($form, $recipientId, $senderId) {
            $stored = $this->storeAttachments($form->attachments, $senderId);

            try {
                return Message::create([
                    'sender_id'    => $senderId,
                    'recipient_id' => $recipientId,
                    'body'         => trim($form->body),
                    'attachments'  => $stored ?: null,
                    'reply_to_id'  => $this->resolveReplyToId($form->replyToId, $senderId, $recipientId),
                ]);
            } catch (\Throwable $e) {
                foreach ($stored as $item) {
                    Storage::disk('public')->delete($item['path']);
                }

                throw $e;
            }
        });
    }

    private function resolveReplyToId(?int $replyToId, int $senderId, int $recipientId): ?int
    {
        if (!$replyToId) return null;

        $isValidContext = Message::withoutTrashed()
            ->where('id', $replyToId)
            ->where(fn($q) => $q
                ->where(fn($q) => $q->where('sender_id', $senderId)->where('recipient_id', $recipientId))
                ->orWhere(fn($q) => $q->where('sender_id', $recipientId)->where('recipient_id', $senderId))
            )->exists();

        return $isValidContext ? $replyToId : null;
    }

    private function storeAttachments(array $attachments, int $senderId): array
    {
        $stored = [];

        try {
            foreach ($attachments as $file) {
                $name = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $originalName = $file->getClientOriginalName();
                $mime = $file->getMimeType();
                $size = $file->getSize();

                $path = $file->storeAs("messages/{$senderId}", $name, 'public');

                $stored[] = [
                    'path' => $path,
                    'name' => $originalName,
                    'mime' => $mime,
                    'size' => $size,
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
