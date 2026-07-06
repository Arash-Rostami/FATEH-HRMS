<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\ChannelMember;
use App\Models\ChannelMessage;
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

        if (!ChannelMember::where('channel_id', $message->channel_id)->where('user_id', $userId)->exists()) {
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

        return response()->download($real, $attachment['name']);
    }
}