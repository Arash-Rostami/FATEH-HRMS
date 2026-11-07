<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Services\Menu\StateService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasSharedRecipients
{
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSharedWith(Builder $query, int $userId): Builder
    {
        return $query->whereJsonContains('recipients', $userId)
            ->whereNotNull('saved_at')
            ->where('status', self::STATUS_READY);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->active()->where(
            fn (Builder $q) => $q->ownedBy($user->id)->orWhere(fn (Builder $s) => $s->sharedWith($user->id))
        );
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id;
    }

    public function isRecipient(?User $user): bool
    {
        return $user !== null && in_array($user->id, $this->recipients ?? []);
    }

    public function scopeUnseenBy(Builder $query, int $userId): Builder
    {
        return $query->active()
            ->sharedWith($userId)
            ->where(fn (Builder $q) => $q->whereNull('seen_by')->orWhereJsonDoesntContain('seen_by', $userId));
    }

    public function markSeenBy(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $row = static::query()->whereKey($this->id)->lockForUpdate()->first(['id', 'seen_by']);
            $seen = array_map('intval', $row?->seen_by ?? []);
            if ($row === null || in_array($userId, $seen, true)) {
                return;
            }
            $seen[] = $userId;
            static::query()->whereKey($row->id)->toBase()->update(['seen_by' => json_encode($seen, JSON_THROW_ON_ERROR)]);
            DB::afterCommit(fn () => StateService::flush());
        });
    }

    public static function maxRecipients(): int
    {
        return (int) config('ai_report.max_recipients');
    }
}
