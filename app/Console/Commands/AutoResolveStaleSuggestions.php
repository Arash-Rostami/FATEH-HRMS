<?php

namespace App\Console\Commands;

use App\Models\Review;
use App\Models\Suggestion;
use Illuminate\Console\Command;

class AutoResolveStaleSuggestions extends Command
{
    protected $signature = 'suggestions:auto-resolve-stale';

    protected $description = 'Auto-resolve suggestions that have sat unanswered for 48+ hours: fill neutral for any department whose review is still unknown/missing — team_remarks targets the home department, dept_remarks targets every listed department. Existing verdicts (agree/disagree/neutral/incomplete) are preserved.';

    public function handle(): void
    {
        $cutoff = now()->subHours(48);

        Suggestion::where('stage', 'team_remarks')
            ->where('updated_at', '<=', $cutoff)
            ->with('reviews')
            ->each(function (Suggestion $suggestion) {
                $homeDept = $suggestion->departments[0] ?? null;
                if (!$homeDept) return;

                $this->autoFillNeutral($suggestion, $homeDept);
                $suggestion->load('reviews')->syncStage();
            });

        Suggestion::where('stage', 'dept_remarks')
            ->where('updated_at', '<=', $cutoff)
            ->with('reviews')
            ->each(function (Suggestion $suggestion) {
                foreach ((array) $suggestion->departments as $dept) {
                    $this->autoFillNeutral($suggestion, $dept);
                }

                $suggestion->load('reviews')->syncStage();
            });
    }

    private function autoFillNeutral(Suggestion $suggestion, string $dept): void
    {
        $answered = $suggestion->reviews->pluck('feedback', 'department_id');

        if (in_array($answered->get($dept), ['agree', 'disagree', 'neutral', 'incomplete'], true)) return;

        Review::updateOrCreate(
            ['suggestion_id' => $suggestion->id, 'department_id' => $dept],
            [
                'feedback' => 'neutral',
                'comments' => Review::AUTO_RESOLVE_COMMENT,
                'user_id' => $suggestion->user_id,
                'complete' => false,
            ]
        );
    }
}
