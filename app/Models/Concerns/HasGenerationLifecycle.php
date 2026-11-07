<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

trait HasGenerationLifecycle
{
    public function isStale(): bool
    {
        return $this->status === self::STATUS_GENERATING
            && $this->updated_at !== null
            && $this->updated_at->lt(now()->subMinutes(self::staleMinutes()));
    }

    public function scopeFreshGenerating(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)
            ->where('status', self::STATUS_GENERATING)
            ->where('updated_at', '>=', now()->subMinutes(self::staleMinutes()));
    }

    public function markFailed(string $reason): bool
    {
        $failed = static::whereKey($this->id)
            ->where('status', self::STATUS_GENERATING)
            ->when($reason === 'stale', fn (Builder $q) => $q->where('updated_at', '<=', now()->subMinutes(self::staleMinutes())))
            ->update(['status' => self::STATUS_FAILED, 'fail_reason' => $reason]) === 1;

        if ($failed) {
            if (!in_array($reason, config('ai_report.no_refund'), true)) {
                $last = array_key_exists('versions', $this->getAttributes())
                    ? $this->lastVersion()
                    : static::whereKey($this->id)->first(['versions'])?->lastVersion();
                self::refundQuota($this->user_id, $last['quota_day'] ?? null);
            }
            Storage::disk('local')->deleteDirectory($this->tempDirectory());
        }

        return $failed;
    }

    public function tempDirectory(): string
    {
        return "ai-report/tmp/{$this->id}";
    }

    public static function staleMinutes(): int
    {
        return (int) config('ai_report.stale_minutes');
    }
}
