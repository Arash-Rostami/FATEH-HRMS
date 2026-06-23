<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Livewire\Dashboard\Contact\Forms\MessageComposerForm;
use App\Models\Message;
use Illuminate\Support\Str;

class SendMessageAction
{
    public function execute(MessageComposerForm $form, int $recipientId, ?int $replyToId): Message
    {
        $form->validate();

        $senderId = auth()->id();

        return Message::create([
            'sender_id'    => $senderId,
            'recipient_id' => $recipientId,
            'body'         => trim($form->body),
            'attachments'  => $this->storeAttachments($form->attachments, $senderId) ?: null,
            'reply_to_id'  => $this->resolveReplyToId($replyToId, $senderId, $recipientId),
        ]);
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
        return collect($attachments)->map(function ($file) use ($senderId) {
            $name = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("messages/{$senderId}", $name, 'public');

            return [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        })->values()->all();
    }
}
