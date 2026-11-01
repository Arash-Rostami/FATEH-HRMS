<?php

namespace Tests\Feature\Models;

use App\Filament\Resources\TaskResource\Enums\TaskState;
use Illuminate\Validation\ValidationException;
use App\Models\Ticket;
use App\Enums\TaskActivityType;
use App\Jobs\SyncTaskCalendarEventJob;
use App\Livewire\Dashboard\TaskBoard\Actions\AssignTaskAction;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use App\Services\ProjectTask\EventSyncService;
use App\Services\ProjectTask\RankGenerator;
use App\Services\ProjectTask\ReopenTaskAction;
use App\Services\ProjectTask\ReportingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

enum TaskTestStatus: string
{
    case Todo = 'todo';
    case Manager = 'manager';
}

class TaskModelTest extends TestCase
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

    public function test_scope_for_user_includes_assigned_tasks(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $assigned = Task::factory()->create(['assigned_to' => $user->id, 'user_id' => User::factory()->create(['status' => 'active'])->id]);

        $this->assertContains($assigned->id, Task::forUser($user->id)->pluck('id')->all());
    }

    public function test_scope_for_user_includes_owned_tasks_without_assignee(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $owned = Task::factory()->create(['user_id' => $user->id, 'assigned_to' => null]);

        $this->assertContains($owned->id, Task::forUser($user->id)->pluck('id')->all());
    }

    public function test_scope_for_user_excludes_owned_tasks_with_other_assignee(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $delegated = Task::factory()->create(['user_id' => $user->id, 'assigned_to' => $other->id]);

        $this->assertNotContains($delegated->id, Task::forUser($user->id)->pluck('id')->all());
    }

    public function test_scope_status_filters_by_status_column(): void
    {
        $todo = Task::factory()->create(['status' => 'todo', 'assigned_to' => User::factory()->create(['status' => 'active'])->id]);
        $done = Task::factory()->create(['status' => 'done', 'assigned_to' => User::factory()->create(['status' => 'active'])->id]);

        $query = Task::query();
        $query->status('todo');

        $this->assertContains($todo->id, $query->pluck('id')->all());
        $this->assertNotContains($done->id, $query->pluck('id')->all());
    }

    public function test_scope_label_filter_requires_every_selected_label_exactly_and_normalizes_persian_variants(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $both = Task::factory()->ownedBy($user)->withStatus('todo')->create(['labels' => ['urgent', 'backend']]);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['labels' => ['urgent']]);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['labels' => ['urgent-fix']]);
        $canonicalLabel = "\u{06CC}\u{06A9}";
        $arabicVariantLabel = "\u{064A}\u{0643}";
        $canonical = Task::factory()->ownedBy($user)->withStatus('todo')->create(['labels' => [$canonicalLabel]]);
        $arabicVariant = Task::factory()->ownedBy($user)->withStatus('todo')->create(['labels' => [$arabicVariantLabel]]);

        $ids = fn(array $labels) => Task::query()->ownedBy($user->id)->labelFilter($labels)->pluck('id')->all();

        $this->assertSame([$both->id], $ids(['urgent', 'backend']));
        $this->assertNotContains($both->id, $ids(['backend', 'missing']));
        $this->assertEqualsCanonicalizing([$canonical->id, $arabicVariant->id], $ids([$canonicalLabel]));
        $this->assertEqualsCanonicalizing([$canonical->id, $arabicVariant->id], $ids([$arabicVariantLabel]));
    }

    public function test_get_status_counts_aggregates_by_status_for_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'todo']);
        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'todo']);
        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'in-progress']);

        $counts = Task::getStatusCounts($user->id);

        $this->assertSame(2, $counts['todo']);
        $this->assertSame(1, $counts['in-progress']);
        $this->assertSame(0, $counts['pending']);
    }

    public function test_get_status_counts_returns_zero_for_user_with_no_tasks(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $counts = Task::getStatusCounts($user->id);

        $this->assertSame(0, $counts['todo']);
        $this->assertSame(0, $counts['in-progress']);
        $this->assertSame(0, $counts['pending']);
    }

    public function test_get_status_count_helpers_return_individual_counts(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'todo']);
        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'in-progress']);
        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'pending']);

        $this->assertSame(1, Task::getTodoCount($user->id));
        $this->assertSame(1, Task::getInProgressCount($user->id));
        $this->assertSame(1, Task::getPendingCount($user->id));
    }

    public function test_can_change_status_returns_true_for_assignee(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['assigned_to' => $user->id, 'user_id' => User::factory()->create(['status' => 'active'])->id]);

        $this->actingAs($user);
        $this->assertTrue($task->can_change_status);
    }

    public function test_can_change_status_returns_true_for_creator_without_assignee(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => null]);

        $this->actingAs($creator);
        $this->assertTrue($task->can_change_status);
    }

    public function test_can_change_status_returns_false_for_stranger(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => null]);

        $this->actingAs($stranger);
        $this->assertFalse($task->can_change_status);
    }

    public function test_can_delete_returns_true_only_for_creator(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => null]);

        $this->actingAs($creator);
        $this->assertTrue($task->can_delete);

        $this->actingAs($stranger);
        $this->assertFalse($task->can_delete);
    }

    public function test_is_delegator_returns_true_when_creator_delegated_to_other(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => $assignee->id]);

        $this->actingAs($creator);
        $this->assertTrue($task->is_delegator);
    }

    public function test_is_delegator_returns_false_when_creator_is_also_assignee(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => $creator->id]);

        $this->actingAs($creator);
        $this->assertFalse($task->is_delegator);
    }

    public function test_is_delegator_returns_false_for_non_creator(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => $assignee->id]);

        $this->actingAs($stranger);
        $this->assertFalse($task->is_delegator);
    }

    public function test_assignee_name_accessor_returns_assignee_name(): void
    {
        $assignee = User::factory()->create(['status' => 'active', 'name' => 'Alice']);
        $task = Task::factory()->create(['assigned_to' => $assignee->id, 'user_id' => User::factory()->create(['status' => 'active'])->id]);

        $task->load('assignee');
        $this->assertSame('Alice', $task->assignee_name);
    }

    public function test_assignee_name_accessor_returns_null_when_no_assignee(): void
    {
        $task = Task::factory()->create(['assigned_to' => null, 'user_id' => User::factory()->create(['status' => 'active'])->id]);

        $this->assertNull($task->assignee_name);
    }

    public function test_delegator_name_accessor_returns_creator_name(): void
    {
        $creator = User::factory()->create(['status' => 'active', 'name' => 'Bob']);
        $task = Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => null]);

        $task->load('creator');
        $this->assertSame('Bob', $task->delegator_name);
    }

    public function test_status_set_mutator_extracts_value_from_backed_enum_instance(): void
    {
        $task = new Task();
        $task->status = TaskTestStatus::Manager;

        $this->assertSame('manager', $task->getAttributes()['status']);
    }

    public function test_status_set_mutator_passes_through_plain_string(): void
    {
        $task = new Task();
        $task->status = 'todo';

        $this->assertSame('todo', $task->getAttributes()['status']);
    }

    public function test_deadline_formatted_accessor_returns_null_when_no_deadline(): void
    {
        $task = new Task();

        $this->assertNull($task->deadline_formatted);
    }

    public function test_deadline_formatted_uses_weekday_within_seven_days_and_full_date_otherwise(): void
    {
        $soon = new Task();
        $soon->deadline = now()->addDays(3);

        $far = new Task();
        $far->deadline = now()->addDays(30);

        $past = new Task();
        $past->deadline = now()->subDays(3);

        $this->assertSame(toJalali($soon->deadline, 'l j F'), $soon->deadline_formatted);
        $this->assertSame(toJalali($far->deadline, 'j F Y'), $far->deadline_formatted);
        $this->assertSame(toJalali($past->deadline, 'j F Y'), $past->deadline_formatted);
    }

    public function test_created_formatted_accessor_converts_to_jalali_not_gregorian(): void
    {
        $task = new Task();
        $task->created_at = '2026-03-24 10:00:00';

        $formatted = $task->created_formatted;

        $this->assertNotEmpty($formatted);
        $this->assertStringNotContainsString('2026', $formatted);
    }

    public function test_urgency_state_marks_overdue_task(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('in-progress')->create([
            'deadline' => now()->subDays(3),
            'updated_at' => now()->subDay(),
        ]);

        $state = $task->urgency_state;

        $this->assertSame('overdue', $state['kind']);
        $this->assertSame('3 روز تأخیر', $state['label']);
        $this->assertSame(1.0, $state['score']);
    }

    public function test_urgency_state_due_today_label(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['deadline' => now()]);

        $state = $task->urgency_state;

        $this->assertSame('due', $state['kind']);
        $this->assertSame('امروز', $state['label']);
        $this->assertEqualsWithDelta(0.7, $state['score'], 0.0001);
    }

    public function test_urgency_state_due_tomorrow_label(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['deadline' => now()->addDay()]);

        $state = $task->urgency_state;

        $this->assertSame('due', $state['kind']);
        $this->assertSame('فردا', $state['label']);
    }

    public function test_urgency_state_due_within_three_days_label(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['deadline' => now()->addDays(3)]);

        $state = $task->urgency_state;

        $this->assertSame('due', $state['kind']);
        $this->assertSame('3 روز دیگر', $state['label']);
    }

    public function test_urgency_state_far_deadline_and_fresh_is_calm(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['deadline' => now()->addDays(20)]);

        $state = $task->urgency_state;

        $this->assertNull($state['kind']);
        $this->assertNull($state['label']);
        $this->assertSame(0.0, $state['score']);
    }

    public function test_urgency_state_idle_when_untouched_over_a_week(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('in-progress')->updatedAt('-8 days')->create(['deadline' => null]);

        $state = $task->urgency_state;

        $this->assertSame('idle', $state['kind']);
        $this->assertSame('چند روز بی‌تغیر', $state['label']);
        $this->assertEqualsWithDelta(0.45, $state['score'], 0.0001);
    }

    public function test_urgency_state_idle_mild_when_untouched_four_days(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('in-progress')->updatedAt('-4 days')->create(['deadline' => null]);

        $state = $task->urgency_state;

        $this->assertSame('idle', $state['kind']);
        $this->assertEqualsWithDelta(0.22, $state['score'], 0.0001);
    }

    public function test_urgency_state_done_suppresses_overdue(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-5 days')->create(['deadline' => now()->subDays(30)]);

        $state = $task->urgency_state;

        $this->assertNull($state['kind']);
        $this->assertSame(0.0, $state['score']);
    }

    public function test_urgency_state_pending_suppresses_overdue(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('pending')->create(['deadline' => now()->subDays(5)]);

        $state = $task->urgency_state;

        $this->assertNull($state['kind']);
        $this->assertSame(0.0, $state['score']);
    }

    public function test_urgency_state_overdue_wins_over_idle(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('in-progress')->updatedAt('-10 days')->create(['deadline' => now()->subDays(2)]);

        $state = $task->urgency_state;

        $this->assertSame('overdue', $state['kind']);
        $this->assertSame('2 روز تأخیر', $state['label']);
        $this->assertSame(1.0, $state['score']);
    }

    public function test_urgency_state_with_no_deadline_and_fresh_is_calm(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['deadline' => null]);

        $state = $task->urgency_state;

        $this->assertNull($state['kind']);
        $this->assertSame(0.0, $state['score']);
    }

    public function test_urgency_state_urgent_priority_with_no_deadline_applies_floor(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['deadline' => null, 'priority' => 'urgent']);

        $state = $task->urgency_state;

        $this->assertEqualsWithDelta(0.85, $state['score'], 0.0001);
        $this->assertNull($state['kind']);
        $this->assertNull($state['label']);
    }

    public function test_urgency_state_high_priority_with_no_deadline_applies_floor(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['deadline' => null, 'priority' => 'high']);

        $state = $task->urgency_state;

        $this->assertEqualsWithDelta(0.55, $state['score'], 0.0001);
        $this->assertNull($state['kind']);
        $this->assertNull($state['label']);
    }

    public function test_urgency_state_medium_and_low_priority_with_no_deadline_have_no_floor(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $medium = Task::factory()->ownedBy($user)->withStatus('todo')->create(['deadline' => null, 'priority' => 'medium']);
        $low = Task::factory()->ownedBy($user)->withStatus('todo')->create(['deadline' => null, 'priority' => 'low']);

        $this->assertSame(0.0, $medium->urgency_state['score']);
        $this->assertSame(0.0, $low->urgency_state['score']);
    }

    public function test_urgency_state_priority_floor_does_not_override_overdue_score(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('in-progress')->create([
            'deadline' => now()->subDays(3),
            'updated_at' => now()->subDay(),
            'priority' => 'urgent',
        ]);

        $state = $task->urgency_state;

        $this->assertSame('overdue', $state['kind']);
        $this->assertSame(1.0, $state['score']);
    }

    public function test_rank_for_priority_inserts_between_higher_and_lower_tier_siblings(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'urgent', 'rank' => 'b']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'medium', 'rank' => 'd']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'low', 'rank' => 'f']);

        $rank = Task::rankForPriority(null, $user->id, 'todo', 'high');

        $this->assertSame(RankGenerator::between('b', 'd'), $rank);
    }

    public function test_rank_for_priority_appends_after_last_sibling_when_no_lower_tier_exists(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'urgent', 'rank' => 'b']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'high', 'rank' => 'd']);

        $rank = Task::rankForPriority(null, $user->id, 'todo', 'medium');

        $this->assertSame(RankGenerator::between('d', null), $rank);
    }

    public function test_rank_for_priority_excludes_self_from_sibling_scan(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'urgent', 'rank' => 'b']);
        $self = Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'low', 'rank' => 'd']);
        Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => 'medium', 'rank' => 'f']);

        $rank = Task::rankForPriority(null, $user->id, 'todo', 'urgent', $self->id);

        $this->assertSame(RankGenerator::between('b', 'f'), $rank);
    }

    public function test_is_archived_accessor_reflects_archived_at_column(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $active = Task::factory()->ownedBy($user)->withStatus('done')->create(['archived_at' => null]);
        $archived = Task::factory()->ownedBy($user)->withStatus('done')->create(['archived_at' => now()]);

        $this->assertFalse($active->is_archived);
        $this->assertTrue($archived->is_archived);
    }

    public function test_reopening_archived_task_clears_archived_at(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('done')->create(['archived_at' => now()]);

        $task->update(['status' => 'in-progress']);

        $this->assertNull($task->fresh()->archived_at);
    }

    public function test_updating_archived_task_without_status_change_keeps_archived_at(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('done')->create(['archived_at' => now()]);

        $task->update(['title' => 'Edited title']);

        $this->assertNotNull($task->fresh()->archived_at);
    }

    public function test_force_deleting_removes_task_detail_attachment_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('task/attachments/x.pdf', 'content');
        $task = Task::factory()->create();
        TaskDetail::create([
            'task_id' => $task->id,
            'attachments' => [['path' => 'task/attachments/x.pdf', 'name' => 'x.pdf']],
        ]);

        $task->forceDelete();

        Storage::disk('public')->assertMissing('task/attachments/x.pdf');
    }

    public function test_soft_deleting_does_not_remove_task_detail_attachment_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('task/attachments/y.pdf', 'content');
        $task = Task::factory()->create();
        TaskDetail::create([
            'task_id' => $task->id,
            'attachments' => [['path' => 'task/attachments/y.pdf', 'name' => 'y.pdf']],
        ]);

        $task->delete();

        Storage::disk('public')->assertExists('task/attachments/y.pdf');
    }

    public function test_force_deleting_task_with_no_detail_does_not_error(): void
    {
        Storage::fake('public');
        $task = Task::factory()->create();

        $task->forceDelete();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_project_relation_returns_the_linked_project(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);

        $this->assertTrue($task->project->is($project));
    }

    public function test_project_relation_is_null_for_an_unfiled_task(): void
    {
        $task = Task::factory()->create(['project_id' => null]);

        $this->assertNull($task->project);
    }

    public function test_labels_cast_round_trips_through_the_database(): void
    {
        $task = Task::factory()->create(['labels' => ['bug', 'urgent']]);

        $this->assertSame(['bug', 'urgent'], $task->fresh()->labels);
    }

    public function test_priority_casts_to_the_backed_enum(): void
    {
        $task = Task::factory()->create(['priority' => 'urgent']);

        $this->assertSame(\App\Filament\Resources\TaskResource\Enums\TaskPriority::Urgent, $task->fresh()->priority);
    }

    public function test_force_deleting_cleans_up_replies_and_their_stored_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('task/replies/1/x.pdf', 'content');
        $task = Task::factory()->create();
        $task->replies()->create([
            'user_id' => $task->user_id,
            'body' => 'a comment',
            'files' => [['path' => 'task/replies/1/x.pdf', 'name' => 'x.pdf']],
        ]);

        $task->forceDelete();

        $this->assertSame(0, \App\Models\Reply::where('repliable_id', $task->id)->where('repliable_type', Task::class)->count());
        Storage::disk('public')->assertMissing('task/replies/1/x.pdf');
    }

    public function test_saved_hook_does_not_log_activity_on_creation(): void
    {
        $task = Task::factory()->create(['status' => 'todo']);

        $this->assertSame(1, $task->replies()->count(), 'creating a task must not itself produce an activity-log entry (only the creation history row exists)');
    }

    public function test_saved_hook_logs_status_change_on_update(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        $this->actingAs($user);

        Task::find($task->id)->update(['status' => 'in-progress']);

        $reply = $task->replies()->reorder()->latest('id')->first();
        $this->assertNotNull($reply);
        $this->assertSame(TaskActivityType::StatusChange, $reply->type);
        $this->assertEquals(['from' => 'todo', 'to' => 'in-progress'], $reply->payload);
    }

    public function test_saved_hook_never_logs_activity_for_rank_changes(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->create(['rank' => 'm']);
        $this->actingAs($user);

        Task::find($task->id)->update(['rank' => 'z']);

        $this->assertSame(1, $task->replies()->count(), 'rank (drag-order) is noise and must never write an activity entry (only the creation history row exists)');
    }

    public function test_saved_hook_dispatches_calendar_sync_when_deadline_changes(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->create(['deadline' => null]);
        $this->actingAs($user);
        Queue::fake();
        $this->travel(6)->seconds();

        Task::find($task->id)->update(['deadline' => now()->addDays(3)]);

        Queue::assertPushed(SyncTaskCalendarEventJob::class, 1);
    }

    public function test_soft_deleting_a_task_purges_its_calendar_event(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->create(['deadline' => now()->addDays(3)]);
        app(EventSyncService::class)->sync($task->fresh(['creator', 'assignee', 'detail']));
        $this->assertDatabaseHas('events', ['description' => EventSyncService::descriptionPrefix() . $task->id]);

        $task->delete();

        $this->assertDatabaseMissing('events', ['description' => EventSyncService::descriptionPrefix() . $task->id]);
    }

    public function test_force_deleting_a_task_does_not_double_purge_in_a_way_that_errors(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->create(['deadline' => now()->addDays(3)]);
        app(EventSyncService::class)->sync($task->fresh(['creator', 'assignee', 'detail']));

        $task->forceDelete();

        $this->assertDatabaseMissing('events', ['description' => EventSyncService::descriptionPrefix() . $task->id]);
    }

    public function test_restoring_a_task_re_syncs_its_calendar_event(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->create(['deadline' => now()->addDays(3)]);
        app(EventSyncService::class)->sync($task->fresh(['creator', 'assignee', 'detail']));
        $task->delete();
        $this->assertDatabaseMissing('events', ['description' => EventSyncService::descriptionPrefix() . $task->id]);

        $task->restore();

        $this->assertDatabaseHas('events', ['description' => EventSyncService::descriptionPrefix() . $task->id]);
    }

    public function test_saved_hook_does_not_dispatch_calendar_sync_for_unrelated_changes(): void
    {
        $task = Task::factory()->create(['deadline' => now()->addDays(3)]);
        Queue::fake();

        Task::find($task->id)->update(['title' => 'Renamed title']);

        Queue::assertNotPushed(SyncTaskCalendarEventJob::class);
    }

    public function test_unlinking_project_clears_the_mirrored_project_string_on_detail(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'name' => 'Alpha Project', 'member_ids' => [], 'departments' => []]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $task->detail()->create(['project' => $project->name]);
        $this->actingAs($owner);

        Task::find($task->id)->update(['project_id' => null]);

        $this->assertNull($task->detail->fresh()->project);
    }

    public function test_progress_percent_returns_100_when_status_is_done_regardless_of_checklist(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $emptyChecklist = Task::factory()->ownedBy($user)->withStatus('done')->create();
        $this->assertSame(100, $emptyChecklist->progress_percent);

        $partiallyChecked = Task::factory()->ownedBy($user)->withStatus('done')->create();
        $partiallyChecked->detail()->create(['checklist' => [
            ['text' => 'a', 'done' => true, 'weight' => 40],
            ['text' => 'b', 'done' => false, 'weight' => 60],
        ]]);
        $this->assertSame(100, $partiallyChecked->fresh()->progress_percent);

        $noneChecked = Task::factory()->ownedBy($user)->withStatus('done')->create();
        $noneChecked->detail()->create(['checklist' => [
            ['text' => 'a', 'done' => false, 'weight' => 100],
        ]]);
        $this->assertSame(100, $noneChecked->fresh()->progress_percent);
    }

    public function test_progress_percent_status_based_standin_when_checklist_is_empty(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $todo = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        $inProgress = Task::factory()->ownedBy($user)->withStatus('in-progress')->create();
        $pending = Task::factory()->ownedBy($user)->withStatus('pending')->create();

        $this->assertSame(0, $todo->progress_percent);
        $this->assertSame(50, $inProgress->progress_percent);
        $this->assertSame(75, $pending->progress_percent);
    }

    public function test_progress_percent_default_arm_returns_zero_for_unrecognized_status(): void
    {
        $task = new Task(['status' => 'archived']);
        $task->setRelation('detail', null);

        $this->assertSame(0, $task->progress_percent);
    }

    public function test_progress_percent_sums_weight_of_done_items_for_weighted_checklist(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('in-progress')->create();
        $task->detail()->create(['checklist' => [
            ['text' => 'a', 'done' => true, 'weight' => 50],
            ['text' => 'b', 'done' => false, 'weight' => 30],
            ['text' => 'c', 'done' => true, 'weight' => 20],
        ]]);

        $this->assertSame(70, $task->fresh()->progress_percent);
    }

    public function test_progress_percent_weighted_checklist_does_not_renormalize_when_weights_dont_sum_to_100(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('in-progress')->create();
        $task->detail()->create(['checklist' => [
            ['text' => 'a', 'done' => true, 'weight' => 30],
            ['text' => 'b', 'done' => true, 'weight' => 30],
            ['text' => 'c', 'done' => false, 'weight' => 10],
        ]]);

        $this->assertSame(60, $task->fresh()->progress_percent);
    }

    public function test_progress_percent_falls_back_to_done_ratio_for_legacy_checklist_without_weight_key(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('in-progress')->create();
        $task->detail()->create(['checklist' => [
            ['text' => 'a', 'done' => true],
            ['text' => 'b', 'done' => false],
            ['text' => 'c', 'done' => true],
        ]]);

        $this->assertSame(67, $task->fresh()->progress_percent);
    }

    public function test_reporting_service_progress_percent_delegates_to_task_accessor(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $done = Task::factory()->ownedBy($user)->withStatus('done')->create();

        $inProgress = Task::factory()->ownedBy($user)->withStatus('in-progress')->create();
        $inProgress->detail()->create(['checklist' => [
            ['text' => 'a', 'done' => true, 'weight' => 65],
            ['text' => 'b', 'done' => false, 'weight' => 35],
        ]]);
        $inProgress = $inProgress->fresh();

        $service = app(ReportingService::class);

        $this->assertSame(100, $service->progressPercent($done));
        $this->assertSame($done->progress_percent, $service->progressPercent($done));
        $this->assertSame(65, $service->progressPercent($inProgress));
        $this->assertSame($inProgress->progress_percent, $service->progressPercent($inProgress));
    }

    public function test_updating_deadline_past_project_deadline_succeeds_and_flags_urgency(): void
    {
        Queue::fake();
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['deadline' => now()->addDays(10)->toDateString()],
        ]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => null]);
        $this->actingAs($owner);

        Task::find($task->id)->update(['deadline' => now()->addDays(20)]);

        $fresh = Task::with('project')->find($task->id);
        $this->assertSame(now()->addDays(20)->toDateString(), $fresh->deadline->toDateString());

        $state = $fresh->urgency_state;
        $this->assertSame('project-deadline-exceeded', $state['kind']);
        $this->assertSame('فراتر از مهلت پروژه', $state['label']);
        $this->assertEqualsWithDelta(0.5, $state['score'], 0.0001);
    }

    public function test_urgency_state_flags_sla_breach_when_task_is_older_than_project_sla(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['sla' => 1],
        ]);
        $breached = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->updatedAt('-3 hours')->create(['deadline' => null]);
        $fresh = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => null]);
        Task::whereIn('id', [$breached->id, $fresh->id])->toBase()->update(['deadline' => null]);

        $state = Task::with('project')->find($breached->id)->urgency_state;
        $this->assertSame('sla', $state['kind']);
        $this->assertSame('نقض SLA', $state['label']);
        $this->assertEqualsWithDelta(0.9, $state['score'], 0.0001);

        $this->assertNull(Task::with('project')->find($fresh->id)->urgency_state['kind']);
    }

    public function test_urgency_state_never_flags_project_deadline_for_a_task_without_deadline(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['deadline' => now()->addDays(5)->toDateString()],
        ]);
        $noDeadline = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => null]);
        $within = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['deadline' => now()->addDays(4)]);

        $this->assertNull(Task::with('project')->find($noDeadline->id)->urgency_state['kind']);
        $this->assertNull(Task::with('project')->find($within->id)->urgency_state['kind']);
    }

    public function test_reading_urgency_state_does_not_mutate_the_deadline_or_updated_at_carbons(): void
    {
        Queue::fake();
        $owner = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['deadline' => now()->addDays(5)->setTime(14, 37)]);
        $this->actingAs($owner);
        Task::find($task->id)->forceFill(['updated_at' => now()->subDay()->setTime(14, 37)])->save();

        $fresh = Task::find($task->id);
        $deadline = $fresh->deadline;
        $updatedAt = $fresh->updated_at;

        $state = $fresh->urgency_state;

        $this->assertIsArray($state);
        $this->assertSame('14:37:00', $deadline->format('H:i:s'));
        $this->assertSame('14:37:00', $updatedAt->format('H:i:s'));
        $this->assertSame('14:37:00', $fresh->deadline->format('H:i:s'));
        $this->assertSame('14:37:00', $fresh->updated_at->format('H:i:s'));
    }

    private function lifeUser(): User
    {
        return User::factory()->create(['status' => 'active', 'role' => 'user']);
    }

    private function lifeProject(User $owner, array $settings = [], array $members = []): Project
    {
        return Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => $members, 'departments' => [], 'settings' => $settings]);
    }

    public function test_reopening_a_done_task_clears_completion_approval_and_archive_without_an_approval_row(): void
    {
        $owner = $this->lifeUser();
        $project = $this->lifeProject($owner, ['requires_approval' => true]);
        $task = Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $owner->id, 'status' => 'in-progress', 'deadline' => now()->addDays(10)]);
        $this->actingAs($owner);

        $task = Task::find($task->id);
        $task->update(['status' => 'done']);
        $task = Task::find($task->id);
        $this->assertNotNull($task->completed_at);
        $this->assertNotNull($task->approved_at);
        $task->update(['archived_at' => now()]);
        $approvalRows = $task->replies()->where('type', TaskActivityType::Approval->value)->count();

        Task::find($task->id)->update(['status' => 'in-progress']);

        $fresh = Task::find($task->id);
        $this->assertNull($fresh->completed_at);
        $this->assertNull($fresh->approved_at);
        $this->assertNull($fresh->approved_by);
        $this->assertNull($fresh->archived_at);
        $this->assertSame($approvalRows, $fresh->replies()->where('type', TaskActivityType::Approval->value)->count());
    }

    public function test_turning_approval_off_system_approves_pending_done_tasks_as_a_system_row(): void
    {
        $owner = $this->lifeUser();
        $member = $this->lifeUser();
        $project = $this->lifeProject($owner, ['requires_approval' => true], [$member->id]);
        $task = Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $member->id, 'status' => 'todo', 'deadline' => now()->addDays(10)]);
        $this->actingAs($member);
        Task::find($task->id)->update(['status' => 'done']);
        $this->assertTrue(Task::find($task->id)->isPendingApproval());

        $this->actingAs($owner);
        Project::find($project->id)->update(['settings' => []]);

        $fresh = Task::find($task->id);
        $this->assertNotNull($fresh->approved_at);
        $this->assertNull($fresh->approved_by);
        $row = $fresh->replies()->where('type', TaskActivityType::Approval->value)->latest('id')->first();
        $this->assertNull($row->user_id);
        $this->assertTrue($row->payload['system']);
        $this->assertSame('approval_disabled', $row->payload['reason']);
    }

    public function test_a_member_moving_a_task_to_done_with_a_partially_loaded_project_stays_pending_approval(): void
    {
        $owner = $this->lifeUser();
        $member = $this->lifeUser();
        $project = $this->lifeProject($owner, ['requires_approval' => true], [$member->id]);
        $task = Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $member->id, 'status' => 'todo', 'deadline' => now()->addDays(10)]);
        $this->actingAs($member);

        Task::with('project:id,owner_id')->find($task->id)->update(['status' => 'done']);

        $fresh = Task::find($task->id);
        $this->assertNull($fresh->approved_at);
        $this->assertTrue($fresh->isPendingApproval());
    }

    public function test_a_member_cannot_change_or_delete_an_approved_task_but_the_owner_can(): void
    {
        $owner = $this->lifeUser();
        $member = $this->lifeUser();
        $project = $this->lifeProject($owner, ['requires_approval' => true], [$member->id]);
        $task = Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $member->id, 'status' => 'todo', 'deadline' => now()->addDays(10)]);
        $this->actingAs($owner);
        Task::find($task->id)->update(['status' => 'done']);
        $this->assertNotNull(Task::find($task->id)->approved_at);

        $this->actingAs($member);
        foreach ([['status' => 'in-progress'], ['title' => 'changed'], ['assigned_to' => $owner->id], ['priority' => 'high']] as $changes) {
            try {
                Task::find($task->id)->update($changes);
                $this->fail('an approved task must be frozen for a non-owner');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('form', $e->errors());
            }
        }

        try {
            Task::find($task->id)->delete();
            $this->fail('an approved task must not be deletable by a non-owner');
        } catch (ValidationException $e) {
            $this->assertNotNull(Task::find($task->id));
        }

        $this->assertTrue(Task::find($task->id)->isFrozenFor($member));
        $this->assertFalse(Task::find($task->id)->isFrozenFor($owner));

        $this->actingAs($owner);
        Task::find($task->id)->update(['title' => 'owner edit']);
        $this->assertSame('owner edit', Task::find($task->id)->title);
    }

    public function test_a_member_cannot_move_a_frozen_task_out_of_its_approval_project(): void
    {
        $owner = $this->lifeUser();
        $member = $this->lifeUser();
        $project = $this->lifeProject($owner, ['requires_approval' => true], [$member->id]);
        $task = Task::factory()->forProject($project)->withStatus('done')->create(['user_id' => $member->id, 'assigned_to' => $member->id, 'approved_at' => now(), 'approved_by' => $owner->id]);
        $this->actingAs($member);

        try {
            Task::find($task->id)->update(['project_id' => null]);
            $this->fail('moving a frozen task out of its project must be refused');
        } catch (ValidationException $e) {
            $this->assertSame($project->id, Task::find($task->id)->project_id);
        }
    }

    public function test_an_assignee_can_still_reassign_a_done_task_in_a_project_without_approval(): void
    {
        $owner = $this->lifeUser();
        $first = $this->lifeUser();
        $second = $this->lifeUser();
        $project = $this->lifeProject($owner, [], [$first->id, $second->id]);
        $task = Task::factory()->forProject($project)->withStatus('done')->create(['user_id' => $owner->id, 'assigned_to' => $first->id, 'approved_at' => now()]);
        $this->actingAs($first);

        $moved = app(AssignTaskAction::class)->execute($task->id, $second->id);

        $this->assertNotNull($moved);
        $this->assertSame($second->id, Task::find($task->id)->assigned_to);
    }

    public function test_only_the_owner_can_reopen_an_approved_task_and_the_revocation_is_logged(): void
    {
        $owner = $this->lifeUser();
        $member = $this->lifeUser();
        $project = $this->lifeProject($owner, ['requires_approval' => true], [$member->id]);
        $task = Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $member->id, 'status' => 'todo', 'deadline' => now()->addDays(10)]);
        $this->actingAs($owner);
        Task::find($task->id)->update(['status' => 'done']);

        $this->actingAs($member);
        $this->assertFalse(app(ReopenTaskAction::class)->execute(Task::find($task->id), $member));
        $this->assertNotNull(Task::find($task->id)->approved_at);

        $this->actingAs($owner);
        $this->assertTrue(app(ReopenTaskAction::class)->execute(Task::find($task->id), $owner));

        $fresh = Task::find($task->id);
        $this->assertSame('in-progress', $fresh->status);
        $this->assertNull($fresh->approved_at);
        $row = $fresh->replies()->where('type', TaskActivityType::Approval->value)->reorder()->latest('id')->first();
        $this->assertFalse($row->payload['approved']);
        $this->assertSame($owner->id, $row->user_id);
    }

    public function test_ticket_task_rejects_changes_outside_the_ticket_sync_window(): void
    {
        $owner = $this->lifeUser();
        $ticket = Ticket::factory()->create();
        $task = Task::factory()->create(['user_id' => $owner->id, 'assigned_to' => $owner->id, 'ticket_id' => $ticket->id, 'title' => 'original']);

        try {
            Task::find($task->id)->update(['title' => 'hacked']);
            $this->fail('ticket task edit must be refused');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('form', $e->errors());
        }

        try {
            Task::find($task->id)->delete();
            $this->fail('ticket task delete must be refused');
        } catch (ValidationException $e) {
            $this->assertNotNull(Task::find($task->id));
        }

        Task::find($task->id)->update(['archived_at' => now()]);
        Task::syncingFromTicket(fn() => Task::find($task->id)->update(['title' => 'synced']));

        $this->assertSame('synced', Task::find($task->id)->title);
    }

    public function test_rank_is_recomputed_when_status_or_priority_changes_but_not_on_plain_edits(): void
    {
        $owner = $this->lifeUser();
        $project = $this->lifeProject($owner);
        $make = fn(array $a) => Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $owner->id, 'deadline' => now()->addDays(10), ...$a]);
        $top = $make(['status' => 'todo', 'priority' => 'low', 'rank' => 'm']);
        $subject = $make(['status' => 'todo', 'priority' => 'low', 'rank' => 'z']);

        Task::find($subject->id)->update(['title' => 'renamed']);
        $this->assertSame('z', Task::find($subject->id)->rank);

        Task::find($subject->id)->update(['priority' => 'urgent']);
        $this->assertLessThan('m', Task::find($subject->id)->rank);

        $before = Task::find($subject->id)->rank;
        Task::find($subject->id)->update(['status' => 'in-progress']);
        $after = Task::find($subject->id)->rank;
        $this->assertNotNull($after);
        $this->assertNotSame($before, $after);
        $this->assertSame('m', Task::find($top->id)->rank);
    }

    public function test_creation_writes_exactly_one_created_history_row(): void
    {
        $owner = $this->lifeUser();
        $task = Task::factory()->ownedBy($owner)->create();
        Task::find($task->id)->update(['title' => 'edited']);

        $created = $task->replies()->where('type', TaskActivityType::Assignment->value)->get()->filter(fn($r) => ($r->payload['created'] ?? false) === true);

        $this->assertCount(1, $created);
    }

    public function test_roster_validation_applies_only_to_newly_added_people(): void
    {
        $owner = $this->lifeUser();
        $member = $this->lifeUser();
        $legacy = $this->lifeUser();
        $newcomer = $this->lifeUser();
        $project = $this->lifeProject($owner, [], [$member->id]);
        $task = Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $member->id, 'deadline' => now()->addDays(10)]);
        $task->forceFill(['assigned_to' => $legacy->id])->saveQuietly();
        TaskDetail::factory()->create(['task_id' => $task->id, 'collaborators' => [], 'responsible_user_id' => null]);
        TaskDetail::where('task_id', $task->id)->update(['collaborators' => json_encode([$legacy->id])]);

        Task::find($task->id)->update(['title' => 'still fine']);
        TaskDetail::where('task_id', $task->id)->first()->update(['unit' => 'u2']);
        $this->assertSame('still fine', Task::find($task->id)->title);

        try {
            Task::find($task->id)->update(['assigned_to' => $newcomer->id]);
            $this->fail('new outsider assignee must be refused');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('form', $e->errors());
        }

        $this->expectException(ValidationException::class);
        TaskDetail::where('task_id', $task->id)->first()->update(['collaborators' => [$legacy->id, $newcomer->id]]);
    }

    public function test_moving_a_pending_done_task_to_a_project_without_approval_approves_it(): void
    {
        $owner = $this->lifeUser();
        $strict = $this->lifeProject($owner, ['requires_approval' => true]);
        $relaxed = $this->lifeProject($owner);
        $task = Task::factory()->forProject($strict)->create(['user_id' => $owner->id, 'assigned_to' => $owner->id, 'deadline' => now()->addDays(10)]);
        $task->forceFill(['status' => 'done', 'approved_at' => null])->saveQuietly();
        $this->actingAs($owner);

        $relaxed->forceFill(['member_ids' => [$owner->id]])->saveQuietly();
        Task::find($task->id)->update(['project_id' => $relaxed->id]);

        $fresh = Task::find($task->id);
        $this->assertNotNull($fresh->approved_at);
        $this->assertSame($owner->id, $fresh->approved_by);
    }

    public function test_stopped_task_urgency_is_quiet_even_when_overdue(): void
    {
        $owner = $this->lifeUser();
        $task = Task::factory()->ownedBy($owner)->withStatus('in-progress')->create(['deadline' => now()->subDays(5)]);
        TaskDetail::factory()->create(['task_id' => $task->id, 'state' => TaskState::Suspension->value, 'responsible_user_id' => null]);

        $state = Task::with('detail')->find($task->id)->urgency_state;

        $this->assertSame('suspended', $state['kind']);
        $this->assertSame(0.0, $state['score']);
    }
}
