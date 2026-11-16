<?php

namespace App\Models;

use App\Models\Concerns\HasActive;
use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasUser;
use App\Models\Concerns\Hooks\HasLaunchpadHooks;
use App\Services\Menu\StateService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Launchpad extends Model
{
    use HasActive,
        HasDepartment,
        HasUser,
        Prunable,
        SoftDeletes,
        HasLaunchpadHooks;

    protected $fillable = [
        'user_id',
        'department_id',
        'kind',
        'title',
        'description',
        'folder',
        'audience',
        'seen_by',
        'accessible_from',
        'accessible_to',
        'expire_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'audience' => 'array',
            'seen_by' => 'array',
            'settings' => 'array',
            'accessible_from' => 'date',
            'accessible_to' => 'date',
            'expire_at' => 'date',
        ];
    }

    public function scopeLive(Builder $query): Builder
    {
        $today = today()->toDateString();

        return $query->active()
            ->where(fn (Builder $q) => $q->whereNull('accessible_from')->orWhere('accessible_from', '<=', $today))
            ->where(fn (Builder $q) => $q->whereNull('accessible_to')->orWhere('accessible_to', '>=', $today))
            ->where(fn (Builder $q) => $q->whereNull('expire_at')->orWhere('expire_at', '>=', $today));
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->hasElevatedRole()
            ? $query
            : $query->where(fn (Builder $q) => $q->where('user_id', $user->id)
                ->orWhere(fn (Builder $s) => $s->live()->whereJsonContains('audience', $user->id)));
    }

    public function scopeUnseenBy(Builder $query, int $userId): Builder
    {
        return $query->live()
            ->whereJsonContains('audience', $userId)
            ->where('user_id', '!=', $userId)
            ->where(fn (Builder $q) => $q->whereNull('seen_by')->orWhereJsonDoesntContain('seen_by', $userId));
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (int) $this->user_id === (int) $user->id;
    }

    public function isAudience(?User $user): bool
    {
        return $user !== null && in_array((int) $user->id, self::ids($this->audience), true);
    }

    public function status(): string
    {
        $today = today();

        return match (true) {
            !$this->active => 'inactive',
            $this->expire_at?->lt($today) === true || $this->accessible_to?->lt($today) === true => 'expired',
            $this->accessible_from?->gt($today) === true => 'scheduled',
            $this->expire_at?->lte($today->copy()->addDays((int) config('launchpad.expiring_soon_days'))) === true => 'soon',
            default => 'live',
        };
    }

    public function markSeenBy(int $userId): void
    {
        if ((int) $this->user_id === $userId
            || !in_array($userId, self::ids($this->audience), true)
            || in_array($userId, self::ids($this->seen_by), true)) {
            return;
        }

        DB::transaction(function () use ($userId) {
            $row = static::query()->whereKey($this->id)->lockForUpdate()->first(['id', 'seen_by']);
            $seen = self::ids($row?->seen_by);
            if ($row === null || in_array($userId, $seen, true)) {
                return;
            }
            $seen[] = $userId;
            static::query()->whereKey($row->id)->toBase()->update(['seen_by' => json_encode($seen, JSON_THROW_ON_ERROR)]);
            DB::afterCommit(fn () => StateService::flush());
        });
    }

    public function directory(): ?string
    {
        return preg_match('/^[0-9A-Za-z]{26}$/', (string) $this->folder) === 1 ? 'launchpad/files/' . $this->folder : null;
    }

    public static function storedName(string $name): ?string
    {
        $name = class_exists(\Normalizer::class) ? ((string) \Normalizer::normalize($name, \Normalizer::FORM_C) ?: $name) : $name;
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return array_key_exists($extension, (array) config('launchpad.types')) ? sha1(mb_strtolower($name)) . '.' . $extension : null;
    }

    public function prunable(): Builder
    {
        return static::query()->where('expire_at', '<', today()->toDateString());
    }

    private static function ids(mixed $value): array
    {
        return is_array($value) ? array_map('intval', $value) : [];
    }
}
