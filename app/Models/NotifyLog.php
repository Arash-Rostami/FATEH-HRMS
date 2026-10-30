<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Throwable;

class NotifyLog extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    public const STATUS_SUMMARY = 'summary';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    public const SOURCE_RULE = 'rule';

    public const SOURCE_STATUS = 'status';

    public const SOURCE_RESERVATION = 'reservation';

    protected $fillable = [
        'notify_rule_id',
        'run_id',
        'source',
        'sender_id',
        'count',
        'user_id',
        'event',
        'record_id',
        'recipient',
        'channel',
        'status',
        'error',
    ];

    public static function audit(string $source, int $senderId, ?int $recipientId, string $recipient, bool $ok, ?string $error = null): void
    {
        try {
            static::insert([
                'source' => $source,
                'sender_id' => $senderId,
                'user_id' => $recipientId,
                'recipient' => Str::mask($recipient, '*', 0, -4),
                'channel' => 'sms',
                'status' => $ok ? self::STATUS_SENT : self::STATUS_FAILED,
                'error' => $error === null ? null : mb_substr($error, 0, 500),
                'count' => 1,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(90));
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(NotifyRule::class, 'notify_rule_id');
    }
}
