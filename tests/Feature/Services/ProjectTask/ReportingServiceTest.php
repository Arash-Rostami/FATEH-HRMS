<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Models\Project;
use App\Models\Reply;
use App\Models\Task;
use App\Models\User;
use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Services\ProjectTask\ReportingService;
use App\Services\ProjectTask\TaskMetrics;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportingServiceTest extends TestCase
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

    public function test_visible_project_ids_returns_only_projects_the_user_can_see(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $ownedProject = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        Project::factory()->create(['owner_id' => $stranger->id, 'member_ids' => [], 'departments' => []]);

        $ids = app(ReportingService::class)->visibleProjectIds($owner);

        $this->assertTrue($ids->contains($ownedProject->id));
        $this->assertCount(1, $ids);
    }

    public function test_query_includes_tasks_from_visible_projects_plus_own_and_assigned_and_collaborator_tasks(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $collaborator = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $inProject = Task::factory()->forProject($project)->create(['user_id' => $stranger->id]);
        $ownTask = Task::factory()->ownedBy($owner)->create(['project_id' => null]);
        $assignedTask = Task::factory()->create(['user_id' => $stranger->id, 'assigned_to' => $owner->id, 'project_id' => null]);
        $collabTask = Task::factory()->create(['user_id' => $stranger->id, 'project_id' => null]);
        $collabTask->detail()->create(['collaborators' => [$owner->id]]);
        $invisibleTask = Task::factory()->create(['user_id' => $stranger->id, 'assigned_to' => null, 'project_id' => null]);

        $ids = app(ReportingService::class)->query($owner)->pluck('id')->all();

        $this->assertContains($inProject->id, $ids);
        $this->assertContains($ownTask->id, $ids);
        $this->assertContains($assignedTask->id, $ids);
        $this->assertContains($collabTask->id, $ids);
        $this->assertNotContains($invisibleTask->id, $ids);

        $collaboratorIds = app(ReportingService::class)->query($collaborator)->pluck('id')->all();
        $this->assertNotContains($collabTask->id, $collaboratorIds, 'a user not yet listed as a collaborator must not see the task');
    }

    public function test_query_filters_by_project_department_assignee_and_status(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $otherProject = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $match = Task::factory()->forProject($project)->delegatedBy($owner, $assignee)->withStatus('todo')->create();
        $match->detail()->create(['department_id' => 'ENG']);
        Task::factory()->forProject($otherProject)->ownedBy($owner)->withStatus('done')->create();

        $service = app(ReportingService::class);

        $this->assertSame([$match->id], $service->query($owner, ['project_id' => $project->id])->pluck('id')->all());
        $this->assertSame([$match->id], $service->query($owner, ['department' => 'ENG'])->pluck('id')->all());
        $this->assertSame([$match->id], $service->query($owner, ['assignee_id' => $assignee->id])->pluck('id')->all());
        $this->assertSame([$match->id], $service->query($owner, ['status' => 'todo'])->pluck('id')->all());
    }

    public function test_query_filters_by_scheme_and_action_source_domain_and_action_source(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $match = Task::factory()->forProject($project)->ownedBy($owner)->create();
        $match->detail()->create(['scheme' => 'Scheme A', 'action_source_domain' => 'IT', 'action_source' => 'Helpdesk']);
        $other = Task::factory()->forProject($project)->ownedBy($owner)->create();
        $other->detail()->create(['scheme' => 'Scheme B', 'action_source_domain' => 'HR', 'action_source' => 'Onboarding']);

        $service = app(ReportingService::class);

        $this->assertSame([$match->id], $service->query($owner, ['scheme' => 'Scheme A'])->pluck('id')->all());
        $this->assertSame([$match->id], $service->query($owner, ['action_source_domain' => 'IT'])->pluck('id')->all());
        $this->assertSame([$match->id], $service->query($owner, ['action_source' => 'Helpdesk'])->pluck('id')->all());
    }

    public function test_rows_paginates_and_flags_has_more(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        Task::factory()->count(3)->ownedBy($owner)->create(['project_id' => null]);

        $result = app(ReportingService::class)->rows($owner, [], 2);

        $this->assertCount(2, $result['rows']);
        $this->assertTrue($result['hasMore']);

        $resultAll = app(ReportingService::class)->rows($owner, [], 10);
        $this->assertCount(3, $resultAll['rows']);
        $this->assertFalse($resultAll['hasMore']);
    }

    public function test_summary_computes_total_done_and_percent_and_caches_for_five_minutes(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create();
        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create();

        $summary = app(ReportingService::class)->summary($project->id, $owner->id);

        $this->assertSame(2, $summary['total']);
        $this->assertSame(1, $summary['done']);
        $this->assertSame(50.0, $summary['percent']);

        $this->assertSame($summary, app(ReportingService::class)->summary($project->id, $owner->id));

        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create();
        $recomputed = app(ReportingService::class)->summary($project->id, $owner->id);

        $this->assertSame(3, $recomputed['total'], 'a Task write rotates the Task cache version so the summary recomputes');
    }

    public function test_summary_done_counts_only_approved_tasks_when_project_requires_approval(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => [], 'settings' => ['requires_approval' => true]]);
        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['approved_at' => now(), 'approved_by' => $owner->id]);
        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['approved_at' => null]);

        $summary = app(ReportingService::class)->summary($project->id, $owner->id);

        $this->assertSame(1, $summary['done']);
        $this->assertSame(50.0, $summary['percent']);
    }

    public function test_summary_at_risk_counts_sla_and_cap_breaches_excluding_done_and_pending(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['sla' => 24, 'deadline' => now()->addDays(5)->toDateString()],
        ]);

        $slaBreach = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => null]);
        DB::table('tasks')->where('id', $slaBreach->id)->update(['created_at' => now()->subDays(3), 'deadline' => null]);

        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('in-progress')->create(['deadline' => now()->addDays(9)]);

        foreach (['done', 'pending'] as $excludedStatus) {
            $excluded = Task::factory()->forProject($project)->ownedBy($owner)->withStatus($excludedStatus)->create(['deadline' => null]);
            DB::table('tasks')->where('id', $excluded->id)->update(['created_at' => now()->subDays(3)]);
        }

        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => now()->addDays(2)]);

        $summary = app(ReportingService::class)->summary($project->id, $owner->id);

        $this->assertSame(2, $summary['at_risk']);
    }

    public function test_summary_at_risk_stays_zero_without_sla_or_cap_settings(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $stale = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => now()->addDays(9)]);
        DB::table('tasks')->where('id', $stale->id)->update(['created_at' => now()->subDays(10)]);

        $summary = app(ReportingService::class)->summary($project->id, $owner->id);

        $this->assertSame(0, $summary['at_risk']);
    }

    public function test_progress_percent_prefers_checklist_ratio_over_status_fallback(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($owner)->withStatus('in-progress')->create(['project_id' => null]);
        $task->detail()->create(['checklist' => [
            ['text' => 'a', 'done' => true],
            ['text' => 'b', 'done' => false],
        ]]);

        $this->assertSame(50, app(ReportingService::class)->progressPercent($task->fresh(['detail'])));

        $noChecklist = Task::factory()->ownedBy($owner)->withStatus('in-progress')->create(['project_id' => null]);
        $this->assertSame(50, app(ReportingService::class)->progressPercent($noChecklist));

        $done = Task::factory()->ownedBy($owner)->withStatus('done')->create(['project_id' => null]);
        $this->assertSame(100, app(ReportingService::class)->progressPercent($done));
    }

    public function test_query_filters_by_search_priority_and_label(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $searchHit = Task::factory()->forProject($project)->ownedBy($owner)->create(['title' => 'special keyword task', 'deadline' => null]);
        $urgent = Task::factory()->forProject($project)->ownedBy($owner)->create(['priority' => 'urgent', 'title' => 'other', 'deadline' => null]);
        $labeled = Task::factory()->forProject($project)->ownedBy($owner)->create(['labels' => ['design', 'backend'], 'title' => 'third', 'deadline' => null]);
        Task::factory()->forProject($project)->ownedBy($owner)->create(['title' => 'unrelated', 'deadline' => null]);

        $service = app(ReportingService::class);

        $this->assertSame([$searchHit->id], $service->query($owner, ['project_id' => $project->id, 'search' => 'keyword'])->pluck('id')->all());
        $this->assertSame([$urgent->id], $service->query($owner, ['project_id' => $project->id, 'priority' => 'urgent'])->pluck('id')->all());
        $this->assertSame([$labeled->id], $service->query($owner, ['project_id' => $project->id, 'label' => 'design'])->pluck('id')->all());
    }

    public function test_query_sorts_priority_ascending_urgent_first_and_descending_low_first(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        Task::factory()->forProject($project)->ownedBy($owner)->create(['priority' => 'low', 'deadline' => null]);
        Task::factory()->forProject($project)->ownedBy($owner)->create(['priority' => 'urgent', 'deadline' => null]);
        Task::factory()->forProject($project)->ownedBy($owner)->create(['priority' => 'medium', 'deadline' => null]);

        $service = app(ReportingService::class);

        $asc = $service->query($owner, ['project_id' => $project->id, 'sort' => ['field' => 'priority', 'dir' => 'asc']])
            ->pluck('priority')->map(fn($p) => $p?->value)->all();
        $this->assertSame(['urgent', 'medium', 'low'], $asc);

        $desc = $service->query($owner, ['project_id' => $project->id, 'sort' => ['field' => 'priority', 'dir' => 'desc']])
            ->pluck('priority')->map(fn($p) => $p?->value)->all();
        $this->assertSame(['low', 'medium', 'urgent'], $desc);
    }

    public function test_query_sorts_deadline_with_nulls_last(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $future = Task::factory()->forProject($project)->ownedBy($owner)->create(['deadline' => now()->addDays(5)]);
        $noDeadline = Task::factory()->forProject($project)->ownedBy($owner)->create(['deadline' => null]);

        $ids = app(ReportingService::class)->query($owner, ['project_id' => $project->id, 'sort' => ['field' => 'deadline', 'dir' => 'asc']])
            ->pluck('id')->all();
        $this->assertSame([$future->id, $noDeadline->id], $ids, 'non-null deadline must sort before null (nulls-last on asc)');

        $idsDesc = app(ReportingService::class)->query($owner, ['project_id' => $project->id, 'sort' => ['field' => 'deadline', 'dir' => 'desc']])
            ->pluck('id')->all();
        $this->assertSame([$future->id, $noDeadline->id], $idsDesc, 'nulls must stay last even on desc');
    }

    public function test_query_sort_rejects_non_whitelisted_field_and_defaults_to_deadline(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $near = Task::factory()->forProject($project)->ownedBy($owner)->create(['deadline' => now()->addDays(1)]);
        $far = Task::factory()->forProject($project)->ownedBy($owner)->create(['deadline' => now()->addDays(10)]);

        $ids = app(ReportingService::class)->query($owner, ['project_id' => $project->id, 'sort' => ['field' => 'title; DROP TABLE tasks; --', 'dir' => 'asc']])
            ->pluck('id')->all();
        $this->assertSame([$near->id, $far->id], $ids, 'a non-whitelisted sort field must fall back to deadline asc, never reach the raw clause');
    }

    public function test_summary_single_query_numbers_equal_the_task_metrics_definitions(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['sla' => 24, 'deadline' => now()->addDays(10)->toDateString()],
        ]);

        $late = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => now()->addDay()]);
        DB::table('tasks')->where('id', $late->id)->update(['deadline' => now()->subDays(2), 'created_at' => now()->subDays(4)]);
        $suspended = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => now()->addDay()]);
        DB::table('tasks')->where('id', $suspended->id)->update(['deadline' => now()->subDays(2)]);
        DB::table('task_details')->updateOrInsert(['task_id' => $suspended->id], ['state' => TaskState::Suspension->value]);
        $soon = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('in-progress')->create(['deadline' => now()->addDays(2)]);
        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('pending')->create(['deadline' => now()->addDays(2)]);
        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['deadline' => now()->subDays(2)]);
        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => now()->addDays(20)]);

        $summary = app(ReportingService::class)->summary($project->id, $owner->id);
        $scope = fn() => Task::where('project_id', $project->id);

        $this->assertSame(TaskMetrics::overdue($scope())->count(), $summary['overdue']);
        $this->assertSame(TaskMetrics::dueSoon($scope())->count(), $summary['due_soon']);
        $this->assertSame(TaskMetrics::breached($scope(), 24, $project->charter()->cap())->count(), $summary['at_risk']);
        $this->assertSame(1, $summary['overdue']);
        $this->assertSame(1, $summary['due_soon']);
        $this->assertSame(6, $summary['total']);
    }

    public function test_summary_includes_by_status_and_overdue(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        Cache::forget("task-report:{$project->id}:{$owner->id}");

        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => null]);
        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('in-progress')->create(['deadline' => null]);
        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['deadline' => now()->subDay()]);
        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => now()->subDay()]);

        $summary = app(ReportingService::class)->summary($project->id, $owner->id);

        $this->assertSame(4, $summary['total']);
        $this->assertSame(1, $summary['done']);
        $this->assertSame(['todo' => 2, 'in-progress' => 1, 'pending' => 0, 'done' => 1], $summary['by_status']);
        $this->assertSame(1, $summary['overdue'], 'only the non-done task with a past deadline counts as overdue');
    }

    public function test_filter_options_returns_project_scoped_assignees(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        Task::factory()->forProject($project)->delegatedBy($owner, $assignee)->create();
        Task::factory()->forProject($project)->ownedBy($owner)->create();

        $options = app(ReportingService::class)->filterOptions($project->id);

        $this->assertEqualsCanonicalizing([$owner->id => $owner->name, $assignee->id => $assignee->name], $options['assignees']);
    }

    public function test_scheme_progress_groups_by_scheme_when_no_scheme_filter_is_active(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $doneA = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create();
        $doneA->detail()->create(['scheme' => 'Scheme A']);
        $todoA = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create();
        $todoA->detail()->create(['scheme' => 'Scheme A']);
        $doneB = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create();
        $doneB->detail()->create(['scheme' => 'Scheme B']);
        $noScheme = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create();

        $progress = app(ReportingService::class)->schemeProgress($owner, ['project_id' => $project->id]);

        $this->assertSame([
            ['scheme' => 'Scheme A', 'done' => 1, 'total' => 2],
            ['scheme' => 'Scheme B', 'done' => 1, 'total' => 1],
        ], $progress);
    }

    public function test_scheme_progress_returns_only_the_active_scheme_when_filtered(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $doneA = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create();
        $doneA->detail()->create(['scheme' => 'Scheme A']);
        $doneB = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create();
        $doneB->detail()->create(['scheme' => 'Scheme B']);

        $progress = app(ReportingService::class)->schemeProgress($owner, ['project_id' => $project->id, 'scheme' => 'Scheme A']);

        $this->assertSame([['scheme' => 'Scheme A', 'done' => 1, 'total' => 1]], $progress);
    }

    public function test_scheme_progress_excludes_tasks_whose_detail_scheme_is_blank(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $doneA = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create();
        $doneA->detail()->create(['scheme' => 'Real Scheme']);
        $todoA = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create();
        $todoA->detail()->create(['scheme' => 'Real Scheme']);
        $nullScheme = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create();
        $nullScheme->detail()->create(['scheme' => null]);
        $whitespaceScheme = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create();
        $whitespaceScheme->detail()->create(['scheme' => '   ']);

        $progress = app(ReportingService::class)->schemeProgress($owner, ['project_id' => $project->id]);

        $this->assertSame([['scheme' => 'Real Scheme', 'done' => 1, 'total' => 2]], $progress);
    }

    public function test_attachments_report_excludes_tasks_without_attachments_and_keeps_payload(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $withFiles = Task::factory()->forProject($project)->ownedBy($owner)->create(['title' => 'Carries files']);
        $withFiles->detail()->create(['attachments' => [
            ['path' => 'task/attachments/a.pdf', 'name' => 'a.pdf', 'mime' => 'application/pdf', 'size' => 11],
        ]]);
        $nullAttachments = Task::factory()->forProject($project)->ownedBy($owner)->create(['title' => 'Null attachments']);
        $nullAttachments->detail()->create(['attachments' => null]);
        $emptyAttachments = Task::factory()->forProject($project)->ownedBy($owner)->create(['title' => 'Empty attachments']);
        $emptyAttachments->detail()->create(['attachments' => []]);

        $report = app(ReportingService::class)->attachments($owner, ['project_id' => $project->id]);

        $this->assertCount(1, $report);
        $this->assertSame($withFiles->id, $report[0]['task_id']);
        $this->assertSame('Carries files', $report[0]['task_title']);
        $this->assertEquals([
            ['path' => 'task/attachments/a.pdf', 'name' => 'a.pdf', 'mime' => 'application/pdf', 'size' => 11],
        ], $report[0]['attachments']);
    }

    public function test_rows_present_row_carries_enriched_detail_payload(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $task = Task::factory()->forProject($project)->delegatedBy($owner, $assignee)->withStatus('in-progress')->create([
            'title' => 'enriched task',
            'description' => 'a description',
            'labels' => ['design'],
            'priority' => 'urgent',
            'deadline' => now()->addDays(2),
            'ticket_id' => 777,
        ]);
        $task->detail()->create([
            'checklist' => [['text' => 'a', 'done' => true], ['text' => 'b', 'done' => false]],
            'attachments' => [['path' => 'x.pdf', 'name' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 10]],
        ]);

        $row = app(ReportingService::class)->rows($owner, ['project_id' => $project->id])['rows'][0];

        $this->assertSame($task->id, $row['id']);
        $this->assertSame('enriched task', $row['title']);
        $this->assertSame('a description', $row['description']);
        $this->assertSame($project->name, $row['project_name']);
        $this->assertSame($owner->name, $row['creator_name']);
        $this->assertSame($assignee->name, $row['assignee_name']);
        $this->assertSame('in-progress', $row['status']);
        $this->assertSame('urgent', $row['priority']);
        $this->assertSame(['design'], $row['labels']);
        $this->assertSame(['done' => 1, 'total' => 2], $row['checklist']);
        $this->assertSame(50, $row['progress_percent']);
        $this->assertSame(0, $row['replies_count']);
        $this->assertSame(1, $row['attachments_count']);
        $this->assertSame(777, $row['ticket_id']);
        $this->assertIsArray($row['urgency']);
        $this->assertNotNull($row['deadline_formatted']);
        $this->assertNotNull($row['created_formatted']);
    }

    public function test_summary_cache_rotates_when_a_task_detail_state_changes(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => now()->subDays(2)]);

        $this->assertSame(1, app(ReportingService::class)->summary($project->id, $owner->id)['overdue']);

        $task->detail()->create(['state' => TaskState::Suspension->value]);

        $this->assertSame(0, app(ReportingService::class)->summary($project->id, $owner->id)['overdue']);
    }

    public function test_summary_sla_breach_ignores_tasks_that_have_a_deadline(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['sla' => 24],
        ]);
        $withDeadline = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => now()->addDays(9)]);
        $without = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => null]);
        DB::table('tasks')->whereIn('id', [$withDeadline->id, $without->id])->update(['created_at' => now()->subDays(3)]);
        DB::table('tasks')->where('id', $without->id)->update(['deadline' => null]);

        $this->assertSame(1, app(ReportingService::class)->summary($project->id, $owner->id)['at_risk']);
        $this->assertSame(1, TaskMetrics::breached(Task::where('project_id', $project->id), 24, null)->count());
    }

    public function test_stale_kpi_ignores_suspended_and_pending_tasks(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $open = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create();
        $suspended = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create();
        $pending = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('pending')->create();
        $suspended->detail()->create(['state' => TaskState::Suspension->value]);
        DB::table('tasks')->whereIn('id', [$open->id, $suspended->id, $pending->id])->update(['updated_at' => now()->subDays(40)]);

        $insights = app(ReportingService::class)->analyticsInsights($project->id);

        $this->assertSame(1, $insights['flow']['stale']['idle_30_plus']);
    }

    public function test_fire_count_is_the_number_of_tasks_not_cells(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        Task::factory()->count(3)->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['priority' => 'urgent']);
        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('in-progress')->create(['priority' => 'urgent']);

        $matrix = app(ReportingService::class)->analyticsInsights($project->id)['risk']['matrix'];

        $this->assertCount(2, $matrix['fire']);
        $this->assertSame(4, $matrix['fireCount']);
    }

    public function test_deadline_horizon_keeps_past_deadline_pending_tasks_in_the_overdue_bar(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => now()->subDays(2)]);
        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('pending')->create(['deadline' => now()->subDays(2)]);
        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => null]);

        $data = app(ReportingService::class)->analyticsInsights($project->id)['risk']['horizon']['datasets'][0]['data'];

        $this->assertSame(3, array_sum($data));
    }

    public function test_replies_count_ignores_system_history_rows(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create();
        DB::table('replies')->where('repliable_type', Task::class)->where('repliable_id', $task->id)->delete();
        foreach (['comment', 'attachment', 'status_change', 'assignment'] as $type) {
            Reply::create(['repliable_type' => Task::class, 'repliable_id' => $task->id, 'user_id' => $owner->id, 'body' => 'x', 'type' => $type]);
        }

        $row = app(ReportingService::class)->rows($owner, ['project_id' => $project->id])['rows'][0];

        $this->assertSame(2, $row['replies_count']);
    }

    public function test_done_events_fall_back_from_completed_at_to_approved_at_to_updated_at_for_legacy_rows(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $make = fn() => Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $owner->id, 'status' => 'done', 'deadline' => now()->addDays(5)]);
        $a = $make();
        $b = $make();
        $c = $make();
        DB::table('tasks')->where('id', $a->id)->update(['completed_at' => '2031-01-01 10:00:00', 'approved_at' => '2031-02-01 10:00:00', 'updated_at' => '2031-03-01 10:00:00']);
        DB::table('tasks')->where('id', $b->id)->update(['completed_at' => null, 'approved_at' => '2031-02-02 10:00:00', 'updated_at' => '2031-03-02 10:00:00']);
        DB::table('tasks')->where('id', $c->id)->update(['completed_at' => null, 'approved_at' => null, 'updated_at' => '2031-03-03 10:00:00']);

        $method = new \ReflectionMethod(ReportingService::class, 'doneEvents');
        $events = $method->invoke(app(ReportingService::class), $project->id);

        $this->assertSame(['2031-01-01 10:00:00', '2031-02-02 10:00:00', '2031-03-03 10:00:00'], array_map(fn($e) => (string) $e->done_at, $events));
    }
}
