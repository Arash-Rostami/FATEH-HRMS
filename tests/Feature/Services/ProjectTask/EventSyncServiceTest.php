<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectTask\EventSyncService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventSyncServiceTest extends TestCase
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

    public function test_sync_creates_a_private_event_owned_by_the_creator(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->create(['project_id' => null, 'deadline' => now()->addDays(2)]);

        app(EventSyncService::class)->sync($task->fresh(['creator', 'assignee', 'detail']));

        $event = Event::where('description', EventSyncService::descriptionPrefix() . $task->id)->first();

        $this->assertNotNull($event);
        $this->assertSame($creator->id, $event->user_id);
        $this->assertTrue((bool) $event->private);
        $this->assertSame($task->title, $event->title);
    }

    public function test_sync_purges_the_event_when_deadline_is_cleared(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->create(['project_id' => null, 'deadline' => now()->addDays(2)]);
        app(EventSyncService::class)->sync($task->fresh(['creator', 'assignee', 'detail']));

        $task->deadline = null;
        $task->save();
        app(EventSyncService::class)->sync($task->fresh(['creator', 'assignee', 'detail']));

        $this->assertDatabaseMissing('events', ['description' => EventSyncService::descriptionPrefix() . $task->id]);
    }

    public function test_sync_purges_the_event_when_task_is_archived(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->create(['project_id' => null, 'deadline' => now()->addDays(2)]);
        app(EventSyncService::class)->sync($task->fresh(['creator', 'assignee', 'detail']));

        $task->archived_at = now();
        $task->save();
        app(EventSyncService::class)->sync($task->fresh(['creator', 'assignee', 'detail']));

        $this->assertDatabaseMissing('events', ['description' => EventSyncService::descriptionPrefix() . $task->id]);
    }

    public function test_sync_shares_the_event_with_assignee_and_collaborators_excluding_the_owner(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $collaborator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->delegatedBy($creator, $assignee)->create(['project_id' => null, 'deadline' => now()->addDays(2)]);
        $task->detail()->create(['collaborators' => [$collaborator->id, $creator->id]]);

        app(EventSyncService::class)->sync($task->fresh(['creator', 'assignee', 'detail']));

        $event = Event::where('description', EventSyncService::descriptionPrefix() . $task->id)->firstOrFail();
        $sharedUserIds = $event->shares()->pluck('user_id')->all();

        $this->assertContains($assignee->id, $sharedUserIds);
        $this->assertContains($collaborator->id, $sharedUserIds);
        $this->assertNotContains($creator->id, $sharedUserIds, 'the event owner must never be shared with themselves');
    }

    public function test_sync_shares_the_event_with_the_responsible_user_excluding_the_owner(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $responsible = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->create(['project_id' => null, 'deadline' => now()->addDays(2)]);
        $task->detail()->create(['responsible_user_id' => $responsible->id]);

        app(EventSyncService::class)->sync($task->fresh(['creator', 'assignee', 'detail']));

        $event = Event::where('description', EventSyncService::descriptionPrefix() . $task->id)->firstOrFail();
        $sharedUserIds = $event->shares()->pluck('user_id')->all();

        $this->assertContains($responsible->id, $sharedUserIds);
    }

    public function test_sync_excludes_an_inactive_assignee_or_collaborator_from_shares(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $inactiveAssignee = User::factory()->create(['status' => 'active']);
        $inactiveCollaborator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->delegatedBy($creator, $inactiveAssignee)->create(['project_id' => null, 'deadline' => now()->addDays(2)]);
        $task->detail()->create(['collaborators' => [$inactiveCollaborator->id]]);
        User::whereIn('id', [$inactiveAssignee->id, $inactiveCollaborator->id])->toBase()->update(['status' => 'inactive']);

        app(EventSyncService::class)->sync($task->fresh(['creator', 'assignee', 'detail']));

        $event = Event::where('description', EventSyncService::descriptionPrefix() . $task->id)->firstOrFail();
        $sharedUserIds = $event->shares()->pluck('user_id')->all();

        $this->assertNotContains($inactiveAssignee->id, $sharedUserIds, 'an inactive assignee must not receive a calendar share');
        $this->assertNotContains($inactiveCollaborator->id, $sharedUserIds, 'an inactive collaborator must not receive a calendar share');
    }

    public function test_sync_ignores_collaborator_ids_that_do_not_correspond_to_a_real_user(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->create(['project_id' => null, 'deadline' => now()->addDays(2)]);
        $detail = $task->detail()->create(['collaborators' => []]);
        DB::table('task_details')->where('id', $detail->id)->update(['collaborators' => json_encode([999999])]);

        app(EventSyncService::class)->sync($task->fresh(['creator', 'assignee', 'detail']));

        $event = Event::where('description', EventSyncService::descriptionPrefix() . $task->id)->firstOrFail();
        $this->assertSame(0, $event->shares()->count());
    }

    public function test_sync_removes_a_share_for_a_collaborator_that_was_dropped(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $collaborator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->create(['project_id' => null, 'deadline' => now()->addDays(2)]);
        $task->detail()->create(['collaborators' => [$collaborator->id]]);
        app(EventSyncService::class)->sync($task->fresh(['creator', 'assignee', 'detail']));

        $this->actingAs($creator);
        $task->detail->update(['collaborators' => []]);
        app(EventSyncService::class)->sync($task->fresh(['creator', 'assignee', 'detail']));

        $event = Event::where('description', EventSyncService::descriptionPrefix() . $task->id)->firstOrFail();
        $this->assertSame(0, $event->shares()->count());
    }

    public function test_purge_deletes_the_event_for_the_task(): void
    {
        $creator = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($creator)->create(['project_id' => null, 'deadline' => now()->addDays(2)]);
        app(EventSyncService::class)->sync($task->fresh(['creator', 'assignee', 'detail']));

        app(EventSyncService::class)->purge($task);

        $this->assertDatabaseMissing('events', ['description' => EventSyncService::descriptionPrefix() . $task->id]);
    }

    public function test_is_task_event_and_task_id_from_round_trip_the_description_prefix(): void
    {
        $description = EventSyncService::descriptionPrefix() . '42';

        $this->assertTrue(EventSyncService::isTaskEvent($description));
        $this->assertSame(42, EventSyncService::taskIdFrom($description));
        $this->assertFalse(EventSyncService::isTaskEvent('unrelated description'));
        $this->assertNull(EventSyncService::taskIdFrom('unrelated description'));
    }
}
