<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\ChannelMessage;
use App\Services\Messaging\PollDigestService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DownloadChannelAttachmentAction
{
    public function execute(int $messageId, int $index, int $userId): ?Response
    {
        $message = ChannelMessage::withoutTrashed()
            ->whereKey($messageId)
            ->whereHas('channel.memberUsers', fn ($q) => $q->whereKey($userId))
            ->first();

        $attachment = $message?->attachments[$index] ?? null;

        $real = is_array($attachment) && isset($attachment['path'], $attachment['name'])
            ? $this->resolvePath((string) $attachment['path'])
            : null;

        if (! $real) {
            return null;
        }

        if (! isset($attachment['views'][(string) $userId]) && $this->recordView($messageId, $index, $userId)) {
            PollDigestService::bump("ch:rv:{$message->channel_id}");
        }

        return response()->download($real)->setContentDisposition(
            'attachment',
            $attachment['name'],
            basename($attachment['path'])
        );
    }

    private function resolvePath(string $path): ?string
    {
        $disk = Storage::disk('public');
        $root = realpath($disk->path(''));
        $real = realpath($disk->path($path));

        return $root !== false
            && $real !== false
            && is_file($real)
            && str_starts_with($real, $root.DIRECTORY_SEPARATOR)
                ? $real
                : null;
    }

    private function recordView(int $messageId, int $index, int $userId): bool
    {
        return DB::transaction(function () use ($messageId, $index, $userId): bool {
            $attachments = ChannelMessage::withoutTrashed()->lockForUpdate()->find($messageId)?->attachments;

            if (! isset($attachments[$index]) || isset($attachments[$index]['views'][(string) $userId])) {
                return false;
            }

            $attachments[$index]['views'][(string) $userId] = now()->toISOString();

            DB::table('channel_messages')->where('id', $messageId)->update([
                'attachments' => json_encode($attachments, JSON_UNESCAPED_UNICODE),
            ]);

            return true;
        });
    }
}