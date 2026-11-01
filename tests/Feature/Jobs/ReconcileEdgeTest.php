<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ReconcileEdge;
use App\Models\Edge;
use App\Models\Task;
use App\Models\User;
use App\Services\Menu\Contracts\MenuEdge;
use App\Services\Menu\EdgeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReconcileEdgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Queue::fake();
        EdgeService::reset();
        Cache::flush();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        EdgeService::reset();
        Cache::flush();
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

    private function stubEdge(string $key, array $recipients, bool $show = true): MenuEdge
    {
        return new class($key, $recipients, $show) implements MenuEdge {
            public function __construct(
                private string $key,
                private array $recipients,
                private bool $show,
            ) {}

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
                return $this->show;
            }

            public function for($subject)
            {
                return User::whereIn('id', array_map(fn ($u) => $u->id, $this->recipients))->get();
            }

            public function title($subject, User $user): string
            {
                return 'edge-title-' . $subject->getKey();
            }

            public function body($subject, User $user): string
            {
                return 'edge-body-' . $subject->getKey();
            }
        };
    }

    private function createEdge(User $user, string $key, string $subjectId, array $payload = []): Edge
    {
        return Edge::create($payload + [
            'user_id' => $user->id,
            'edge_key' => $key,
            'subject_type' => Task::class,
            'subject_id' => $subjectId,
            'icon' => 'old-icon',
            'title' => 'old-title',
            'body' => 'old-body',
            'url' => 'old-url',
        ]);
    }

    public function test_dispatch_pushes_job_with_constructor_triple_intact(): void
    {
        dispatch(new ReconcileEdge('tasks-controller:edge', Task::class, 42))->afterCommit();

        Queue::assertPushed(ReconcileEdge::class, fn ($job) => $job->ruleKey === 'tasks-controller:edge'
            && $job->subjectClass === Task::class
            && $job->subjectId === 42);
    }

    public function test_job_exposes_tries_backoff_and_accepts_string_subject_id(): void
    {
        $job = new ReconcileEdge('tasks-controller:edge', Task::class, 42);

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 30], $job->backoff);
        $this->assertSame('tasks-controller:edge', $job->ruleKey);
        $this->assertSame(Task::class, $job->subjectClass);
        $this->assertSame(42, $job->subjectId);

        $stringJob = new ReconcileEdge('channels-controller:edge', Task::class, 'abc123');
        $this->assertSame('abc123', $stringJob->subjectId);
    }

    public function test_handle_creates_edge_rows_for_recipients_with_default_payload(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $subject = Task::factory()->ownedBy($recipient)->create();
        $key = 'reconcile-test:edge';

        EdgeService::register($this->stubEdge($key, [$recipient]));

        (new ReconcileEdge($key, Task::class, $subject->getKey()))->handle();

        $edge = Edge::query()
            ->where('user_id', $recipient->id)
            ->where('edge_key', $key)
            ->where('subject_type', Task::class)
            ->where('subject_id', (string) $subject->getKey())
            ->first();

        $this->assertNotNull($edge);
        $this->assertSame('info', $edge->icon);
        $this->assertSame('edge-title-' . $subject->getKey(), $edge->title);
        $this->assertSame('edge-body-' . $subject->getKey(), $edge->body);
        $this->assertSame('', $edge->url);
        $this->assertNull($edge->dismissed_at);
    }

    public function test_handle_creates_edge_rows_for_multiple_recipients_at_once(): void
    {
        $recipients = User::factory()->count(3)->create(['status' => 'active']);
        $subject = Task::factory()->ownedBy($recipients->first())->create();
        $key = 'reconcile-test:edge-bulk';

        EdgeService::register($this->stubEdge($key, $recipients->all()));

        (new ReconcileEdge($key, Task::class, $subject->getKey()))->handle();

        $rows = Edge::query()->where('edge_key', $key)->get();

        $this->assertCount(3, $rows);
        $this->assertEqualsCanonicalizing($recipients->pluck('id')->all(), $rows->pluck('user_id')->all());

        foreach ($rows as $row) {
            $this->assertSame('info', $row->icon);
            $this->assertSame('edge-title-' . $subject->getKey(), $row->title);
            $this->assertSame('edge-body-' . $subject->getKey(), $row->body);
            $this->assertSame(Task::class, $row->subject_type);
            $this->assertSame((string) $subject->getKey(), $row->subject_id);
            $this->assertSame('', $row->url);
        }
    }

    public function test_handle_refreshes_existing_edge_payload_in_place(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $subject = Task::factory()->ownedBy($recipient)->create();
        $key = 'reconcile-test:edge';

        $existing = $this->createEdge($recipient, $key, (string) $subject->getKey());

        EdgeService::register($this->stubEdge($key, [$recipient]));

        (new ReconcileEdge($key, Task::class, $subject->getKey()))->handle();

        $this->assertSame(1, Edge::query()->where('edge_key', $key)->count());

        $fresh = Edge::find($existing->id);
        $this->assertSame('edge-title-' . $subject->getKey(), $fresh->title);
        $this->assertSame('edge-body-' . $subject->getKey(), $fresh->body);
        $this->assertSame('info', $fresh->icon);
    }

    public function test_handle_keeps_existing_edge_payload_when_refreshed_payload_is_identical(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $subject = Task::factory()->ownedBy($recipient)->create();
        $key = 'reconcile-test:edge';

        EdgeService::register($this->stubEdge($key, [$recipient]));
        (new ReconcileEdge($key, Task::class, $subject->getKey()))->handle();
        $first = Edge::query()->where('edge_key', $key)->first();

        (new ReconcileEdge($key, Task::class, $subject->getKey()))->handle();

        $this->assertNotNull(Edge::find($first->id));
        $this->assertSame(1, Edge::query()->where('edge_key', $key)->count());
    }

    public function test_handle_deletes_edges_for_recipients_that_no_longer_qualify(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $subject = Task::factory()->ownedBy($recipient)->create();
        $key = 'reconcile-test:edge';

        $kept = $this->createEdge($recipient, $key, (string) $subject->getKey());
        $dropped = $this->createEdge($other, $key, (string) $subject->getKey());

        EdgeService::register($this->stubEdge($key, [$recipient]));

        (new ReconcileEdge($key, Task::class, $subject->getKey()))->handle();

        $this->assertNotNull(Edge::find($kept->id));
        $this->assertNull(Edge::find($dropped->id));
    }

    public function test_handle_deletes_existing_edge_when_show_condition_no_longer_holds(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $subject = Task::factory()->ownedBy($recipient)->create();
        $key = 'reconcile-test:edge';

        $existing = $this->createEdge($recipient, $key, (string) $subject->getKey());

        EdgeService::register($this->stubEdge($key, [$recipient], show: false));

        (new ReconcileEdge($key, Task::class, $subject->getKey()))->handle();

        $this->assertNull(Edge::find($existing->id));
        $this->assertSame(0, Edge::query()->where('edge_key', $key)->count());
    }

    public function test_handle_creates_nothing_when_recipient_list_is_empty(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $subject = Task::factory()->ownedBy($recipient)->create();
        $key = 'reconcile-test:edge';

        EdgeService::register($this->stubEdge($key, [], show: false));

        (new ReconcileEdge($key, Task::class, $subject->getKey()))->handle();

        $this->assertSame(0, Edge::query()->where('edge_key', $key)->count());
    }

    public function test_handle_deletes_edges_when_subject_is_gone(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $key = 'reconcile-test:edge';
        $goneId = 999999;

        $existing = $this->createEdge($recipient, $key, (string) $goneId);

        EdgeService::register($this->stubEdge($key, [$recipient]));

        (new ReconcileEdge($key, Task::class, $goneId))->handle();

        $this->assertNull(Edge::find($existing->id));
    }

    public function test_handle_is_a_safe_no_op_when_rule_key_is_not_registered(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $existing = $this->createEdge($recipient, 'never:registered:edge', '7');

        (new ReconcileEdge('never:registered:edge', Task::class, 7))->handle();

        $this->assertNotNull(Edge::find($existing->id));
        $this->assertSame(1, Edge::query()->where('edge_key', 'never:registered:edge')->count());
    }
}