<?php

namespace App\Filament\Resources\SuggestionResource\Pages;

use App\Filament\Resources\SuggestionResource;
use App\Models\Review;
use App\Models\Suggestion;
use App\Models\User;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateSuggestion extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = SuggestionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Suggestion {
            $submitter = User::with('profile.department')->find($data['user_id']) ?? Auth::user();
            $submitterDept = $submitter?->profile?->department_id;
            $submitterIsManager = (bool)($submitter?->isDeptHead() ?? false);

            $departments = collect($data['departments'] ?? [])
                ->when($submitterDept, fn($c) => $c->prepend($submitterDept))
                ->unique()
                ->values()
                ->all();

            $suggestion = Suggestion::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'departments' => $departments,
                'purpose' => $data['purpose'] ?? [],
                'rule' => $data['rule'] ?? [],
                'attachment' => $data['attachment'] ?? null,
                'self_fill' => (bool)($data['self_fill'] ?? false),
                'priority' => $data['priority'] ?? 'low',
                'stage' => 'pending',
                'user_id' => $data['user_id'],
            ]);

            if ($rows = $this->buildReviewRows($suggestion, $data, $submitterDept, $submitterIsManager)) {
                Review::insert($rows);
            }

            $suggestion->load('reviews')->syncStage();

            return $suggestion;
        });
    }

    private function buildReviewRows(Suggestion $suggestion, array $data, ?string $submitterDept, bool $submitterIsManager): array
    {
        $selfFill = (bool)($data['self_fill'] ?? false);

        if (!$selfFill && !$submitterIsManager) {
            return [];
        }

        $now = now();
        $managerNote = 'توجه به اینکه پیشنهاد دهنده هستم، نیاز به بررسی و اعلام نظر از سمت مدیریت';

        $base = [
            'suggestion_id' => $suggestion->id,
            'department_id' => $submitterDept,
            'user_id' => $data['user_id'],
            'complete' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if ($submitterIsManager && !$selfFill) {
            return [array_merge($base, [
                'feedback' => 'agree',
                'comments' => $managerNote,
            ])];
        }

        $defaultFeedback = $submitterIsManager ? 'agree' : 'unknown';
        $defaultComment = $submitterIsManager ? $managerNote : null;

        $rows = [array_merge($base, [
            'feedback' => $defaultFeedback,
            'comments' => $defaultComment,
        ])];

        foreach ($suggestion->departments as $dept) {
            if ($dept === $submitterDept) continue;

            $rows[] = array_merge($base, [
                'department_id' => $dept,
                'feedback' => 'unknown',
                'comments' => null,
            ]);
        }

        return $rows;
    }
}
