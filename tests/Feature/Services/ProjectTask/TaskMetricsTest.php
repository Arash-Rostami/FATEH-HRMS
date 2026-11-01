<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use App\Services\ProjectTask\TaskMetrics;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskMetricsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function useMysql(): void
    {
        $env = base_path('.env');
        if (is_file($env)) {
            $vals = [];
            foreach (explode("\n", (string) file_get_contents($env)) as $line) {
                if (preg_match('/^\s*(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)\s*=\s*(.*)$/', $line, $m)) {
                    $vals[$m[1]] = trim(preg_replace('/\s+#.*$/', '', trim($m[2])), "\"' \t");
                }
            }
            $map = ['DB_HOST' => 'host', 'DB_PORT' => 'port', 'DB_DATABASE' => 'database', 'DB_USERNAME' => 'username', 'DB_PASSWORD' => 'password'];
            foreach ($map as $envKey => $cfgKey) {
                if (isset($vals[$envKey])) {
                    config(['database.connections.mysql.' . $cfgKey => $vals[$envKey]]);
                }
            }
        }
        DB::purge('mysql');
        config(['database.default' => 'mysql']);
    }

    private function project(): Project
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);

        return Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
    }

    private function task(Project $project, array $attrs = []): Task
    {
        return Task::factory()->forProject($project)->create([
            'user_id' => $project->owner_id,
            'assigned_to' => $project->owner_id,
            'status' => 'in-progress',
            'deadline' => now()->subDays(2),
            ...$attrs,
        ]);
    }

    public function test_overdue_means_the_due_day_fully_passed_and_ignores_settled_archived_trashed_and_suspended_tasks(): void
    {
        $project = $this->project();
        $overdue = $this->task($project);
        $today = $this->task($project, ['deadline' => now()->startOfDay()->addHour()]);
        $this->task($project, ['status' => 'done']);
        $this->task($project, ['status' => 'pending']);
        $this->task($project, ['archived_at' => now()]);
        $this->task($project)->delete();
        $suspended = $this->task($project);
        TaskDetail::factory()->create(['task_id' => $suspended->id, 'state' => TaskState::Suspension->value, 'responsible_user_id' => null]);

        $ids = TaskMetrics::overdue(Task::query()->where('project_id', $project->id))->pluck('id')->all();

        $this->assertSame([$overdue->id], $ids);
        $this->assertNotContains($today->id, $ids);
    }

    public function test_due_soon_window_is_today_through_the_end_of_the_third_day(): void
    {
        $project = $this->project();
        $inside = $this->task($project, ['deadline' => now()->addDays(3)->setTime(18, 0)]);
        $this->task($project, ['deadline' => now()->addDays(4)->startOfDay()->addHour()]);
        $this->task($project);

        $ids = TaskMetrics::dueSoon(Task::query()->where('project_id', $project->id))->pluck('id')->all();

        $this->assertSame([$inside->id], $ids);
    }

    public function test_deadline_filter_scope_resolves_each_board_deadline_filter_branch(): void
    {
        $project = $this->project();
        $weekStart = now()->startOfWeek(Carbon::SATURDAY);
        $overdue = $this->task($project, ['deadline' => $weekStart->copy()->subDays(2)]);
        $today = $this->task($project, ['deadline' => now()->setTime(12, 0)]);
        $later = $this->task($project, ['deadline' => $weekStart->copy()->addWeeks(2)]);
        $this->task($project, ['status' => 'done', 'deadline' => $weekStart->copy()->subDays(2)]);

        $ids = fn(string $filter) => TaskMetrics::deadlineFilterScope($filter)(Task::query()->where('project_id', $project->id))->pluck('id')->all();

        $this->assertSame([$overdue->id], $ids('overdue'));
        $this->assertSame([$today->id], $ids('today'));
        $this->assertContains($today->id, $ids('week'));
        $this->assertNotContains($later->id, $ids('week'));
        $this->assertCount(4, $ids(''));
    }

    public function test_status_counts_include_archived_tasks_and_totals_compute_the_done_percent(): void
    {
        $project = $this->project();
        $this->task($project, ['status' => 'done']);
        $this->task($project, ['status' => 'done', 'archived_at' => now()]);
        $this->task($project, ['status' => 'todo']);
        $this->task($project, ['status' => 'in-progress']);

        $counts = TaskMetrics::statusCounts(Task::query()->where('project_id', $project->id));
        $totals = TaskMetrics::totals($counts);

        $this->assertSame(2, $counts['done']);
        $this->assertSame(['total' => 4, 'done' => 2, 'percent' => 50.0], $totals);
        $this->assertSame(['total' => 0, 'done' => 0, 'percent' => 0.0], TaskMetrics::totals([]));
    }
}
