<?php

namespace App\Models\Concerns;

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

            $q->whereRaw('1=0');

            if ($user->isSeniorDecisionMaker()) {
                $q->orWhere('stage', 'awaiting_decision');
            }

            if ($user->isDeptHead() && !$user->isTopExecutive()) {
                $q->orWhere(fn($q) => $q
                    ->where('stage', 'team_remarks')
                    ->where('departments->[0]', $deptId)
                );

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
                            ->where('department_id', 'MA')
                            ->whereJsonContains('referral', $deptId)
                        )
                        ->whereHas('reviews', fn($r) => $r
                            ->where('department_id', $deptId)
                            ->where('complete', false)
                        )
                    );
            }
        });
    }
}
