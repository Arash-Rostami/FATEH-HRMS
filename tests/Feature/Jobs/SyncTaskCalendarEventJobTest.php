<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SyncTaskCalendarEventJob;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectTask\EventSyncService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SyncTaskCalendarEventJobTest extends TestCase
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

    public function test_handle_creates_a_mirrored_event_for_a_task_with_a_deadline(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->create([
            'project_id' => null,
            'deadline' => now()->addDays(3),
            'archived_at' => null,
        ]);

        (new SyncTaskCalendarEventJob($task->id))->handle(app(EventSyncService::class));

        $this->assertTrue(
            Event::where('user_id', $user->id)
                ->where('description', 'like', EventSyncService::descriptionPrefix() . '%')
                ->exists()
        );
    }

    public function test_handle_is_a_safe_no_op_when_the_task_no_longer_exists(): void
    {
        $job = new SyncTaskCalendarEventJob(999999999);

        $job->handle(app(EventSyncService::class));

        $this->assertTrue(true);
    }

    public function test_handle_purges_the_mirrored_event_when_task_has_no_deadline(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->create([
            'project_id' => null,
            'deadline' => now()->addDays(3),
            'archived_at' => null,
        ]);

        (new SyncTaskCalendarEventJob($task->id))->handle(app(EventSyncService::class));
        $this->assertTrue(Event::where('user_id', $user->id)->exists());

        $task->update(['deadline' => null]);
        (new SyncTaskCalendarEventJob($task->id))->handle(app(EventSyncService::class));

        $this->assertFalse(
            Event::where('user_id', $user->id)
                ->where('description', 'like', EventSyncService::descriptionPrefix() . '%')
                ->exists()
        );
    }
}
