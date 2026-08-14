<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Livewire\Dashboard\Channel\Forms\ChannelMessageComposerForm;
use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Traits\CleansAttachedFiles;
use App\Traits\StoresAttachedFiles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendChannelMessageAction
{
    use CleansAttachedFiles, StoresAttachedFiles;


    public function execute(ChannelMessageComposerForm $form, int $channelId): ChannelMessage
    {
        $form->validate();

        $senderId = auth()->id();

        $channel = Channel::withoutTrashed()
            ->whereKey($channelId)
            ->whereHas('memberUsers', fn($q) => $q->where('users.id', $senderId))
            ->first();

        $this->ensureMember($channel);

        $message = DB::transaction(function () use ($form, $channel, $channelId, $senderId) {
            $message = ChannelMessage::create([
                'channel_id'  => $channelId,
                'sender_id'   => $senderId,
                'body'        => trim($form->body),
                'attachments' => null,
                'reply_to_id' => $this->resolveReplyToId($form->replyToId, $channelId),
            ]);

            $attachments = $this->storeAttachments($form->attachments, $channelId, $message->id);

            if ($attachments) {
                $message->update(['attachments' => $attachments]);
            }

            $channel->memberUsers()->newPivotStatementForId($senderId)
                ->update(['last_read_message_id' => $message->id, 'updated_at' => now()]);

            return $message;
        });

        return $message;
    }

    private function ensureMember(?Channel $channel): void
    {
        abort_unless($channel, 403, 'شما عضو این کانال نیستید.');
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
        $stored = [];

        try {
            foreach ($attachments as $file) {
                $stored[] = static::storeAttachment(
                    $file,
                    "channel_messages/{$channelId}/{$messageId}",
                    fn($f) => time() . '_' . Str::random(10) . '.' . $f->extension()
                );
            }
        } catch (\Throwable $e) {
            static::deleteStoredFiles($stored);

            throw $e;
        }

        return $stored;
    }
}