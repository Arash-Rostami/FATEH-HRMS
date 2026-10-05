<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Services\Messaging\PollDigestService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DownloadChannelAttachmentAction
{
    public function execute(int $messageId, int $index, int $userId): ?Response
    {
        $message = ChannelMessage::withoutTrashed()->find($messageId);
        if (!$message) {
            return null;
        }

        if (!Channel::withoutTrashed()
            ->whereKey($message->channel_id)
            ->whereHas('memberUsers', fn($memberQ) => $memberQ->where('users.id', $userId))
            ->exists()) {
            return null;
        }

        $attachment = ($message->attachments ?? [])[$index] ?? null;
        if (!is_array($attachment) || !isset($attachment['path'], $attachment['name'])) {
            return null;
        }

        $disk = Storage::disk('public');
        $root = realpath($disk->path(''));
        $real = realpath($disk->path($attachment['path']));

        if ($root === false || $real === false || !is_file($real) || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
            return null;
        }

        if (!isset($attachment['views'][(string) $userId])) {
            $recorded = DB::transaction(function () use ($messageId, $index, $userId): bool {
                $locked = ChannelMessage::withoutTrashed()->whereKey($messageId)->lockForUpdate()->first();
                if (!$locked || !isset($locked->attachments[$index]) || isset($locked->attachments[$index]['views'][(string) $userId])) {
                    return false;
                }

                $attachments = $locked->attachments;
                $attachments[$index]['views'][(string) $userId] = now()->toISOString();

                DB::table('channel_messages')->where('id', $messageId)->update([
                    'attachments' => json_encode($attachments, JSON_UNESCAPED_UNICODE),
                ]);

                return true;
            });

            if ($recorded) {
                PollDigestService::bump("ch:rv:{$message->channel_id}");
            }
        }

        return tap(response()->download($real), fn($r) => $r->setContentDisposition(
            'attachment',
            $attachment['name'],
            basename($attachment['path'])
        ));
    }
}