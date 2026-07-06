<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Livewire\Dashboard\Channel\Forms\ChannelMessageComposerForm;
use App\Models\ChannelMember;
use App\Models\ChannelMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class SendChannelMessageAction
{
    public function execute(ChannelMessageComposerForm $form, int $channelId): ChannelMessage
    {
        $form->validate();

        $senderId = auth()->id();

        $this->ensureMember($channelId, $senderId);

        $message = DB::transaction(function () use ($form, $channelId, $senderId) {
            $message = ChannelMessage::create([
                'channel_id'  => $channelId,
                'sender_id'   => $senderId,
                'body'        => trim($form->body),
                'attachments' => null,
                'reply_to_id' => $this->resolveReplyToId($form->replyToId, $channelId),
            ]);

            ChannelMember::where('channel_id', $channelId)
                ->where('user_id', $senderId)
                ->update(['last_read_message_id' => $message->id]);

            return $message;
        });

        $attachments = $this->storeAttachments($form->attachments, $channelId, $message->id);

        if ($attachments) {
            $message->update(['attachments' => $attachments]);
        }

        return $message->fresh();
    }

    private function ensureMember(int $channelId, int $userId): void
    {
        $isMember = ChannelMember::where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->exists();

        abort_unless($isMember, 403, 'شما عضو این کانال نیستید.');
    }

    private function resolveReplyToId(?int $replyToId, int $channelId): ?int
    {
        if (!$replyToId) {
            return null;
        }

        $isValidContext = ChannelMessage::withoutTrashed()
            ->where('id', $replyToId)
            ->where('channel_id', $channelId)
            ->exists();

        return $isValidContext ? $replyToId : null;
    }

    private function storeAttachments(array $attachments, int $channelId, int $messageId): array
    {
        return collect($attachments)->map(function ($file) use ($channelId, $messageId) {
            $name = time() . '_' . Str::random(10) . '.' . $file->extension();
            $path = $file->storeAs("channel_messages/{$channelId}/{$messageId}", $name, 'public');

            return [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        })->values()->all();
    }
}