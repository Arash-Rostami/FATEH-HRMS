<?php

namespace App\Filament\Resources\SuggestionResource\Pages;

use App\Filament\Resources\SuggestionResource;
use App\Models\Review;
use App\Models\Suggestion;
use App\Models\User;
use App\Support\SuggestionAccessPolicy;
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
        return static::createSuggestionRecord($data);
    }

    public static function createSuggestionRecord(array $data): Suggestion
    {
        $submitter = User::with('profile.department')->find($data['user_id']) ?? Auth::user();
        $submitterDept = $submitter?->profile?->department_id;
        $submitterIsManager = (bool)($submitter?->isDeptHead() ?? false);

        return DB::transaction(function () use ($data, $submitterDept, $submitterIsManager): Suggestion {
            $departments = collect($data['departments'] ?? [])
                ->filter(fn($dept) => $dept !== 'MA')
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
                'priority' => $data['priority'] ?? 'medium',
                'stage' => 'pending',
                'user_id' => $data['user_id'],
            ]);

            if ($rows = static::buildReviewRows($suggestion, $data, $submitterDept, $submitterIsManager)) {
                Review::insert($rows);
            }

            $suggestion->load('reviews')->syncStage();

            return $suggestion;
        });
    }

    private static function buildReviewRows(Suggestion $suggestion, array $data, ?string $submitterDept, bool $submitterIsManager): array
    {
        return SuggestionAccessPolicy::buildReviewRows(
            suggestionId: $suggestion->id,
            departments: $suggestion->departments,
            homeDept: $submitterDept,
            submitterUserId: $data['user_id'],
            submitterIsManager: $submitterIsManager,
            selfFill: (bool) ($data['self_fill'] ?? false),
        );
    }
}
