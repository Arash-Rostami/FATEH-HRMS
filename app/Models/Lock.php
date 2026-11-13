<?php

namespace App\Models;

use App\Enums\Position;
use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\Hooks\HasLockHooks;
use App\Support\TopPositionPolicy;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;

class Lock extends Model
{
    use HasModelCache,
        HasLockHooks;

    public const SLOT_MASTER = 'master';
    public const SLOT_CEO = Position::Ceo->value;
    public const SLOT_CHAIRMAN = Position::Chairman->value;
    public const SLOT_OWNER = 'owner';
    public const RECORD_MODULES = ['channel'];
    public const SLOTS = [self::SLOT_MASTER, self::SLOT_CEO, self::SLOT_CHAIRMAN];
    public const MAX_CODE_BYTES = 72;
    public const MISSING_TABLE_STATE = '42S02';

    protected $fillable = [
        'lockable_type',
        'lockable_id',
        'slot',
        'secret_hash',
        'extra'
    ];

    protected $hidden = ['secret_hash'];

    protected function casts(): array
    {
        return ['extra' => 'array'];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'lockable_id')->withTrashed();
    }

    public static function slotFor(?User $user, ?string $requested = null): ?string
    {
        if (!$user) {
            return null;
        }

        if ($user->isDeveloper()) {
            return in_array($requested, self::SLOTS, true) ? $requested : self::SLOT_MASTER;
        }

        $position = $user->profile?->position;

        return TopPositionPolicy::isLocked($position) ? $position : null;
    }

    public static function ownsRecord(?User $user, string $module, int $recordId): bool
    {
        return $user !== null && match ($module) {
            'channel' => Channel::withoutTrashed()->where('owner_id', $user->id)->whereKey($recordId)->exists(),
            default => false,
        };
    }

    public static function moduleMap(): array
    {
        return static::mapOrEmpty('modules', static::buildModuleMap(...));
    }

    public static function recordMap(): array
    {
        return static::mapOrEmpty('records', static::buildRecordMap(...));
    }

    protected static function mapOrEmpty(string $key, Closure $build): array
    {
        try {
            return static::cached($key, $build);
        } catch (QueryException $e) {
            throw_unless((string)$e->getCode() === self::MISSING_TABLE_STATE, $e);

            return [];
        }
    }

    protected static function buildRecordMap(): array
    {
        return static::query()
            ->where('lockable_id', '>', 0)
            ->get(['id', 'lockable_type', 'lockable_id', 'secret_hash'])
            ->groupBy('lockable_type')
            ->map(fn($rows) => $rows->groupBy('lockable_id')
                ->map(fn($group) => $group->mapWithKeys(fn(self $row) => [$row->id => $row->fingerprint()])->all())
                ->all())
            ->all();
    }

    protected static function buildModuleMap(): array
    {
        return static::query()
            ->where('lockable_id', 0)
            ->get(['id', 'lockable_type', 'secret_hash'])
            ->groupBy('lockable_type')
            ->map(fn($rows) => $rows->mapWithKeys(fn(self $row) => [$row->id => $row->fingerprint()])->all())
            ->all();
    }

    public function fingerprint(): string
    {
        return substr(hash('sha256', $this->secret_hash), 0, 16);
    }
}
