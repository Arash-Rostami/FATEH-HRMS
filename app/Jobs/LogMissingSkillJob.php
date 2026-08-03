<?php

namespace App\Jobs;

use App\Models\Skill;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LogMissingSkillJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30];

    public function __construct(public string $searchTerm)
    {
    }

    public function handle(): void
    {
        $name = trim(preg_replace('/\s+/', ' ', $this->searchTerm));

        if ($name === '') {
            return;
        }

        DB::transaction(function () use ($name) {
            $skill = Skill::matchingName($name)->lockForUpdate()->first();

            if (!$skill) {
                try {
                    Skill::create([
                        'name' => $name,
                        'is_active' => false,
                        'is_ghost' => true,
                        'search_count' => 1,
                        'last_searched_at' => now(),
                    ]);
                } catch (QueryException $e) {
                    if (!Str::contains($e->getMessage(), ['Duplicate', '1062'])) {
                        throw $e;
                    }

                    $skill = Skill::matchingName($name)->lockForUpdate()->first();

                    $skill?->recordSearch();
                }

                return;
            }

            if ($skill->is_ghost) {
                $skill->recordSearch();
            }
        });
    }
}
