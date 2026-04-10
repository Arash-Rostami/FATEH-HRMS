<?php

namespace App\Models\Traits;

use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasSuggestionAlert
{
    public static function requiresAttentionFor(Suggestion|int $suggestion, ?User $user = null): bool
    {
        $id = $suggestion instanceof Suggestion ? $suggestion->getKey() : $suggestion;

        return static::attentionRequired($user)
            ->whereKey($id)
            ->exists();
    }

    public function scopeAttentionRequired(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();
        $deptId = $user?->profile?->department_id;

        if (!$deptId) {
            return $query->whereRaw('1=0');
        }

        return $query->where(function ($q) use ($user, $deptId) {

            if ($user->isCeo()) {
                $q->orWhere('stage', 'awaiting_decision');
            }

            if ($user->isDeptHead()) {
               //  Initial feedback
                $q->orWhere(fn($q) => $q
                    ->where('stage', 'dept_remarks')
                    ->whereJsonContains('departments', $deptId)
                    ->whereDoesntHave('reviews', fn($r) => $r
                        ->where('department_id', $deptId)
                        ->whereIn('feedback', ['agree', 'disagree', 'neutral'])
                    )
                )
                    // Referral action
                    ->orWhere(fn($q) => $q
                        ->whereHas('reviews', fn($r) => $r
                            ->whereJsonContains('referral', $deptId)
                            ->where('complete', false)
                        )
                    );
            }
        });
    }
}
