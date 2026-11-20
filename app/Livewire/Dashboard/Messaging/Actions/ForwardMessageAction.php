<?php

namespace App\Livewire\Dashboard\Messaging\Actions;

use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Models\Message;
use App\Models\User;
use App\Traits\CleansAttachedFiles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ForwardMessageAction
{
    use CleansAttachedFiles;

    public const HEADING = '➦ باز ارسال‌شده';

    public const LEGACY_HEADING = '➦ بازفرستاده‌شده';

    public static function parts(string $body): array
    {
        $markers = preg_quote(self::HEADING, '/') . '|' . preg_quote(self::LEGACY_HEADING, '/');

        if (!preg_match('/\A(?:(.*?)\n\n)?(?:' . $markers . ')(?:\n\n(.*))?\z/su', $body, $m)) {
            return ['note' => $body, 'forwarded' => null];
        }

        return ['note' => $m[1] ?? '', 'forwarded' => $m[2] ?? ''];
    }

    public function source(string $type, int $id): Message|ChannelMessage
    {
        abort_unless(auth()->user()?->isActive(), 403);
        $userId = (int) auth()->id();

        $query = match ($type) {
            'contact' => Message::query()->where(fn ($q) => $q
                ->where('sender_id', $userId)->orWhere('recipient_id', $userId)),
            'channel' => ChannelMessage::query()->whereHas('channel.memberUsers', fn ($q) => $q->where('users.id', $userId)),
            default => abort(404),
        };

        return $query->findOrFail($id);
    }

    public function execute(string $sourceType, int $sourceId, string $destinationType, int $destinationId, string $note = ''): Message|ChannelMessage
    {
        $source = $this->source($sourceType, $sourceId);
        $userId = (int) auth()->id();
        $channel = null;

        if ($destinationType === 'contact') {
            User::visibleOnBoard()->where('id', '!=', $userId)->findOrFail($destinationId);
            $sameThread = $source instanceof Message && in_array($destinationId, [(int) $source->sender_id, (int) $source->recipient_id], true);
        } elseif ($destinationType === 'channel') {
            $channel = Channel::query()->whereHas('memberUsers', fn ($q) => $q->where('users.id', $userId))->findOrFail($destinationId);
            $sameThread = $source instanceof ChannelMessage && (int) $source->channel_id === $destinationId;
        } else {
            abort(422);
        }

        if ($sameThread) {
            throw ValidationException::withMessages(['forwardDestinationId' => 'گفتگو یا گروه دیگری را انتخاب کنید.']);
        }

        $original = trim((string) $source->body);
        $text = self::parts($original)['forwarded'] ?? $original;
        $body = self::HEADING . ($text !== '' ? "\n\n" . $text : '');

        if (trim($note) !== '') $body = trim($note) . "\n\n" . $body;

        $limit = $destinationType === 'contact' ? 2000 : 4000;
        if (mb_strlen($body) > $limit) {
            throw ValidationException::withMessages(['forwardMessage' => 'متن پیام و یادداشت از سقف ' . convertToPersian((string) $limit) . ' کاراکتر مقصد بیشتر است.']);
        }

        $copied = [];
        $committed = false;
        $message = null;
        try {
            return DB::transaction(function () use ($source, $destinationType, $destinationId, $userId, $channel, $body, &$copied, &$committed, &$message) {
                DB::afterCommit(function () use (&$committed) { $committed = true; });
                $previousLastId = $channel ? ChannelMessage::lastIdForChannel($destinationId) : null;
                $message = $destinationType === 'contact'
                    ? Message::create(['sender_id' => $userId, 'recipient_id' => $destinationId, 'body' => $body])
                    : ChannelMessage::create(['sender_id' => $userId, 'channel_id' => $destinationId, 'body' => $body]);

                $directory = $destinationType === 'contact'
                    ? "messages/{$userId}"
                    : "channel_messages/{$destinationId}/{$message->id}";
                $disk = Storage::disk('public');

                foreach ($source->attachments ?? [] as $attachment) {
                    $path = $attachment['path'] ?? '';
                    if (!is_string($path) || !static::isSafeStoredPath($path) || !$disk->exists($path)) {
                        throw ValidationException::withMessages(['forwardMessage' => 'بازارسال انجام نشد. یکی از پیوست‌های پیام دیگر در دسترس نیست.']);
                    }

                    $extension = pathinfo($path, PATHINFO_EXTENSION);
                    $copy = $directory . '/' . Str::uuid() . ($extension !== '' ? '.' . $extension : '');
                    $copied[] = ['path' => $copy];
                    if (!$disk->copy($path, $copy)) {
                        throw new RuntimeException('Could not copy forwarded attachment.');
                    }
                    $copied[array_key_last($copied)] = [
                        'path' => $copy,
                        'name' => $attachment['name'] ?? basename($path),
                        'mime' => $attachment['mime'] ?? 'application/octet-stream',
                        'size' => $attachment['size'] ?? $disk->size($copy),
                    ];
                }

                if ($copied) {
                    $message->update(['attachments' => $copied]);
                }

                if ($channel) {
                    $channel->memberUsers()->newPivotStatementForId($userId)
                        ->when($previousLastId !== null, fn ($q) => $q->where('last_read_message_id', '>=', $previousLastId))
                        ->where(fn ($q) => $q->whereNull('last_read_message_id')->orWhere('last_read_message_id', '<', $message->id))
                        ->update(['last_read_message_id' => $message->id, 'updated_at' => now()]);
                }

                return $message;
            });
        } catch (Throwable $e) {
            if ($committed && $message) {
                report($e);
                return $message;
            }
            static::deleteStoredFiles($copied);
            throw $e;
        }
    }
}
