<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ReconcileNudge;
use App\Models\Task;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use App\Services\Menu\NudgeService;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Feature coverage of ReconcileNudge: dispatch serialization, job metadata, and the
 * handle() -> NudgeService::reconcile() workflow (create on subject-present, delete on
 * subject-gone, safe no-op for an unregistered rule).
 */
class ReconcileNudgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Queue::fake();
        NudgeService::reset();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        NudgeService::reset();
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

    public function test_dispatch_after_commit_pushes_job_with_constructor_triple_intact(): void
    {
        dispatch(new ReconcileNudge('tasks-controller:nudge', Task::class, 42))->afterCommit();

        Queue::assertPushed(ReconcileNudge::class, fn ($job) => $job->ruleKey === 'tasks-controller:nudge'
            && $job->subjectClass === Task::class
            && $job->subjectId === 42);
    }

    public function test_job_exposes_tries_backoff_and_constructor_properties(): void
    {
        $job = new ReconcileNudge('tasks-controller:nudge', Task::class, 42);

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 30], $job->backoff);
        $this->assertSame('tasks-controller:nudge', $job->ruleKey);
        $this->assertSame(Task::class, $job->subjectClass);
        $this->assertSame(42, $job->subjectId);
    }

    public function test_handle_creates_notification_when_subject_is_present_and_recipient_matches(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $subject = Task::factory()->ownedBy($recipient)->create();
        $key = 'reconcile-test:nudge';

        NudgeService::register($this->stubNudge($key, $recipient));

        (new ReconcileNudge($key, Task::class, $subject->getKey()))->handle();

        $this->assertTrue(
            DB::table('notifications')
                ->where('type', FilamentDatabaseNotification::class)
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $recipient->id)
                ->where('data->menu_key', $key)
                ->where('data->item_id', (string) $subject->getKey())
                ->exists()
        );
    }

    public function test_handle_deletes_existing_nudge_when_subject_is_gone(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $key = 'reconcile-test:nudge';
        $itemId = 999999;

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => FilamentDatabaseNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
            'data' => json_encode(['menu_key' => $key, 'item_id' => (string) $itemId]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        NudgeService::register($this->stubNudge($key, $recipient));

        (new ReconcileNudge($key, Task::class, $itemId))->handle();

        $this->assertSame(
            0,
            DB::table('notifications')
                ->where('notifiable_id', $recipient->id)
                ->where('data->menu_key', $key)
                ->where('data->item_id', (string) $itemId)
                ->count(),
        );
    }

    public function test_handle_is_a_safe_no_op_when_rule_key_is_not_registered(): void
    {
        (new ReconcileNudge('never:registered:nudge', Task::class, 1))->handle();

        $this->assertSame(
            0,
            DB::table('notifications')
                ->where('type', FilamentDatabaseNotification::class)
                ->where('data->menu_key', 'never:registered:nudge')
                ->count(),
        );
    }

    private function stubNudge(string $key, User $recipient): MenuNudge
    {
        return new class($key, $recipient) implements MenuNudge {
            public function __construct(private string $key, private User $recipient) {}

            public function getKey(): string
            {
                return $this->key;
            }

            public function triggers(): array
            {
                return [];
            }

            public function show($subject, User $user): bool
            {
                return true;
            }

            public function for($subject)
            {
                return [$this->recipient];
            }

            public function title($subject, User $user): string
            {
                return 'title';
            }

            public function body($subject, User $user): string
            {
                return 'body';
            }

            public function refresh(): bool
            {
                return false;
            }
        };
    }

    public function test_unique_id_is_one_per_rule_and_subject_so_bursts_collapse_but_different_subjects_do_not(): void
    {
        $a = new ReconcileNudge('tasks-controller:nudge', Task::class, 42);
        $same = new ReconcileNudge('tasks-controller:nudge', Task::class, 42);

        $this->assertSame($a->uniqueId(), $same->uniqueId());
        $this->assertNotSame($a->uniqueId(), (new ReconcileNudge('tasks-controller:nudge', Task::class, 43))->uniqueId());
        $this->assertNotSame($a->uniqueId(), (new ReconcileNudge('tasks-controller:overdue-nudge', Task::class, 42))->uniqueId());
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing::class, $a);
        $this->assertSame(2, $a->uniqueFor);
    }
}
