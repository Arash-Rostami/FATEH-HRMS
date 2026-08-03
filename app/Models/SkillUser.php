<?php

namespace App\Models;

use App\Enums\SkillRequestStatus;
use App\Enums\SkillTier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use Illuminate\Support\Facades\Cache;

class SkillUser extends Model
{
    use HasFactory;
    use AsPivot;

    public const ACTIVE_WINDOW_DAYS = 90;

    public const ENDORSEMENT_THRESHOLD = 1;

    public const ENDORSEMENT_SATURATION_CAP = 4;

    public const NEW_BADGE_UNTIL = '2026-09-02';

    protected $table = 'skill_user';

    protected $fillable = [
        'user_id',
        'skill_id',
        'status',
        'requested_name',
        'last_used_at',
        'last_used_context',
        'is_private',
        'is_mentoring',
        'approved_at',
        'approved_by',
        'rejected_reason',
        'endorsers',
        'endorsements_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function hasEndorser(int $userId): bool
    {
        return in_array($userId, (array) $this->endorsers, true);
    }

    public function isActive(int $windowDays): bool
    {
        return $this->last_used_at?->greaterThanOrEqualTo(now()->subDays($windowDays)) ?? false;
    }

    public function isEndorsed(int $threshold): bool
    {
        return $this->endorsements_count >= $threshold;
    }

    public function stateTier(): SkillTier
    {
        return match (true) {
            $this->isEndorsed(self::ENDORSEMENT_SATURATION_CAP) => SkillTier::Endorsed,
            $this->isActive(self::ACTIVE_WINDOW_DAYS) => SkillTier::Active,
            default => SkillTier::Unused,
        };
    }

    public function isDormant(): bool
    {
        return $this->stateTier() === SkillTier::Endorsed && !$this->isActive(self::ACTIVE_WINDOW_DAYS);
    }

    public function isSoleEndorsement(): bool
    {
        return $this->endorsements_count === 1;
    }

    public function endorsementLabel(): string
    {
        $count = $this->endorsements_count;
        $cap = self::ENDORSEMENT_SATURATION_CAP;

        return match (true) {
            $count === 0 => 'بدون تأیید',
            $count === 1 => 'تأیید یک همکار',
            $count < $cap => "تأیید {$count} همکار (از {$cap})",
            default => "تأیید {$count} همکار",
        };
    }

    public function dormantBadgeClasses(): string
    {
        return 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]';
    }

    public function endorsementMetalClasses(): string
    {
        return match (true) {
            $this->endorsements_count >= self::ENDORSEMENT_SATURATION_CAP =>
                'bg-gradient-to-br from-amber-300 via-yellow-200 to-amber-500 text-amber-900',
            $this->endorsements_count > 0 =>
                'bg-gradient-to-br from-slate-300 via-slate-100 to-slate-400 text-slate-700',
            default => '',
        };
    }

    public static function newBadgeUntil(): Carbon
    {
        return Carbon::parse(self::NEW_BADGE_UNTIL);
    }

    public static function notify(int $userId, string $dedupeKey, string $body, string $type = 'success'): void
    {
        Cache::lock("skill_notices_lock:{$userId}", 5)->block(2, function () use ($userId, $dedupeKey, $body, $type) {
            $key = "skill_notices:{$userId}";
            $list = Cache::get($key, []);
            $list[$dedupeKey] = ['body' => $body, 'type' => $type, 'created_at' => now()->timestamp];
            Cache::put($key, $list, now()->addDays(7));
        });
    }

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'approved_at' => 'datetime',
            'is_private' => 'boolean',
            'is_mentoring' => 'boolean',
            'status' => SkillRequestStatus::class,
            'endorsers' => 'array',
            'endorsements_count' => 'integer',
        ];
    }
}
