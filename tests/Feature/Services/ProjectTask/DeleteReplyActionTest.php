<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Enums\TaskActivityType;
use App\Services\ProjectTask\DeleteReplyAction;
use App\Models\Project;
use App\Models\Reply;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectTask\ActivityLogger;
use App\Services\ProjectTask\ProjectHeartbeat;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeleteReplyActionTest extends TestCase
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

    private function projectWithReply(): array
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $reply = app(ActivityLogger::class)->comment($task, $owner, 'hello team');

        return [$project, $reply, $owner];
    }

    private function taskWithReply(): array
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $reply = app(ActivityLogger::class)->comment($task, $owner, 'hello there');

        return [$task, $reply, $owner];
    }

    public function test_delete_removes_own_recent_comment(): void
    {
        [, $reply, $owner] = $this->projectWithReply();

        $result = app(DeleteReplyAction::class)->execute($reply->id, $owner->id);

        $this->assertTrue($result);
        $this->assertNull(Reply::find($reply->id));
    }

    public function test_delete_rejects_someone_elses_comment(): void
    {
        [, $reply] = $this->projectWithReply();
        $stranger = User::factory()->create(['status' => 'active']);

        $result = app(DeleteReplyAction::class)->execute($reply->id, $stranger->id);

        $this->assertFalse($result);
        $this->assertNotNull(Reply::find($reply->id));
    }

    public function test_delete_rejects_after_time_window(): void
    {
        [, $reply, $owner] = $this->projectWithReply();
        $reply->forceFill(['created_at' => now()->subSeconds(700)])->save();

        $result = app(DeleteReplyAction::class)->execute($reply->id, $owner->id);

        $this->assertFalse($result);
        $this->assertNotNull(Reply::find($reply->id));
    }

    public function test_delete_rejects_system_entries(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $entry = app(ActivityLogger::class)->system($task, $owner, TaskActivityType::StatusChange);

        $result = app(DeleteReplyAction::class)->execute($entry->id, $owner->id);

        $this->assertFalse($result, 'system-generated audit entries must never be user-deletable, even by their own actor');
        $this->assertNotNull(Reply::find($entry->id));
    }

    public function test_delete_returns_false_for_nonexistent_reply(): void
    {
        $owner = User::factory()->create(['status' => 'active']);

        $result = app(DeleteReplyAction::class)->execute(999999999, $owner->id);

        $this->assertFalse($result);
    }

    public function test_delete_on_project_level_reply_bumps_project_heartbeat(): void
    {
        [$project, $reply, $owner] = $this->projectWithReply();
        $before = ProjectHeartbeat::version($project->id);

        app(DeleteReplyAction::class)->execute($reply->id, $owner->id);

        $this->assertNotSame($before, ProjectHeartbeat::version($project->id));
    }

    public function test_delete_on_task_level_reply_bumps_project_heartbeat_via_the_tasks_project(): void
    {
        [$task, $reply, $owner] = $this->taskWithReply();
        $before = ProjectHeartbeat::version($task->project_id);

        app(DeleteReplyAction::class)->execute($reply->id, $owner->id);

        $this->assertNotSame($before, ProjectHeartbeat::version($task->project_id));
    }
}
