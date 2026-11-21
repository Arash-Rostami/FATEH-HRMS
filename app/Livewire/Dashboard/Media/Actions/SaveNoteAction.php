<?php

namespace App\Livewire\Dashboard\Media\Actions;

use App\Models\Media;
use App\Models\MediaNote;
use App\Models\User;

class SaveNoteAction
{
    public static function execute(User $user, string $action, array $data): ?MediaNote
    {
        return match ($action) {
            'add' => self::add($user, $data),
            'delete' => self::delete($user, $data),
            default => null,
        };
    }

    public static function capReached(User $user, array $data): bool
    {
        $input = self::input($data);

        return $input !== null
            && Media::playableFor($user, [$input[0]])->isNotEmpty()
            && self::atCap($user, $input[0]);
    }

    private static function input(array $data): ?array
    {
        $mediaId = filter_var($data['media_id'] ?? null, FILTER_VALIDATE_INT);
        $position = filter_var($data['position'] ?? null, FILTER_VALIDATE_INT);
        $body = is_string($data['body'] ?? null) ? trim($data['body']) : '';

        if ($mediaId === false || $mediaId < 1 || $position === false || $position < 0 || $position > Media::MAX_SECONDS || $body === '' || mb_strlen($body) > 500) {
            return null;
        }

        return [$mediaId, $position, $body];
    }

    private static function atCap(User $user, int $mediaId): bool
    {
        return MediaNote::query()->where('user_id', $user->id)->where('media_id', $mediaId)->count() >= (int) config('media.notes_max', 100);
    }

    private static function add(User $user, array $data): ?MediaNote
    {
        $input = self::input($data);

        if ($input === null) {
            return null;
        }

        [$mediaId, $position, $body] = $input;

        if (Media::playableFor($user, [$mediaId])->isEmpty() || self::atCap($user, $mediaId)) {
            return null;
        }

        return MediaNote::create([
            'user_id' => $user->id,
            'media_id' => $mediaId,
            'position' => $position,
            'body' => $body,
        ]);
    }

    private static function delete(User $user, array $data): ?MediaNote
    {
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

        if ($id === false || $id < 1) {
            return null;
        }

        $note = MediaNote::query()->whereKey($id)->where('user_id', $user->id)->first();

        if ($note === null) {
            return null;
        }

        $note->delete();

        return $note;
    }
}
