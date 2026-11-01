<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Models\Project;
use App\Models\Reply;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectTask\LastTouchResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LastTouchResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::flush();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
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

    public function test_resolve_returns_latest_user_reply_per_task_with_name_and_iso8601_timestamp(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $worker = User::factory()->create(['status' => 'active', 'name' => 'سارا احمدی']);
        $other = User::factory()->create(['status' => 'active', 'name' => 'رضا کریمی']);
        $taskA = Task::factory()->ownedBy($owner)->withStatus('todo')->create();
        $taskB = Task::factory()->ownedBy($owner)->withStatus('todo')->create();

        $taskA->replies()->create(['user_id' => $owner->id, 'body' => 'اولین پاسخ']);
        $latestA = $taskA->replies()->create(['user_id' => $worker->id, 'body' => 'آخرین پاسخ']);
        $replyB = $taskB->replies()->create(['user_id' => $other->id, 'body' => 'پاسخ وظیفهٔ دوم']);

        $result = app(LastTouchResolver::class)->resolve([$taskA->id, $taskB->id]);

        $this->assertSame([
            $taskA->id => [
                'user_id' => $worker->id,
                'user_name' => 'سارا احمدی',
                'created_at' => $latestA->created_at->toIso8601String(),
            ],
            $taskB->id => [
                'user_id' => $other->id,
                'user_name' => 'رضا کریمی',
                'created_at' => $replyB->created_at->toIso8601String(),
            ],
        ], $result);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $result[$taskA->id]['created_at']);
    }

    public function test_resolve_ignores_non_task_replies_and_null_user_replies(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $worker = User::factory()->create(['status' => 'active', 'name' => 'سارا احمدی']);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create();

        Reply::create(['repliable_type' => Project::class, 'repliable_id' => $task->id, 'user_id' => $owner->id, 'body' => 'پاسخ پروژه']);
        $reply = $task->replies()->create(['user_id' => $worker->id, 'body' => 'پاسخ وظیفه']);
        $task->replies()->create(['user_id' => null, 'body' => 'پاسخ بدون کاربر']);

        $result = app(LastTouchResolver::class)->resolve([$task->id]);

        $this->assertSame([
            $task->id => [
                'user_id' => $worker->id,
                'user_name' => 'سارا احمدی',
                'created_at' => $reply->created_at->toIso8601String(),
            ],
        ], $result);
    }

    public function test_resolve_with_empty_input_returns_empty_array_without_querying(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $result = app(LastTouchResolver::class)->resolve([]);

        DB::disableQueryLog();

        $this->assertSame([], $result);
        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_resolve_query_count_stays_constant_regardless_of_task_count(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $tasks = collect(range(1, 5))->map(fn() => Task::factory()->ownedBy($owner)->withStatus('todo')->create());
        $tasks->each(fn(Task $task) => $task->replies()->create(['user_id' => $owner->id, 'body' => 'پاسخ']));
        $ids = $tasks->pluck('id')->all();

        User::getCachedAllOptions();

        $resolver = app(LastTouchResolver::class);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $resolver->resolve([$ids[0]]);
        DB::disableQueryLog();
        $single = count(DB::getQueryLog());

        DB::flushQueryLog();
        DB::enableQueryLog();
        $resolver->resolve($ids);
        DB::disableQueryLog();
        $many = count(DB::getQueryLog());

        $this->assertSame(1, $single, 'one batched replies query; user names come from the warm options cache, zero queries');
        $this->assertSame($single, $many, 'resolving N tasks must issue the same number of queries as resolving one');
    }

    public function test_resolve_reflects_a_user_rename_on_the_next_call(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $worker = User::factory()->create(['status' => 'active', 'name' => 'سارا احمدی']);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create();
        $task->replies()->create(['user_id' => $worker->id, 'body' => 'پاسخ']);

        $this->assertSame('سارا احمدی', app(LastTouchResolver::class)->resolve([$task->id])[$task->id]['user_name']);

        User::find($worker->id)->update(['name' => 'سارا کریمی']);

        $this->assertSame('سارا کریمی', app(LastTouchResolver::class)->resolve([$task->id])[$task->id]['user_name'], 'a User save bumps the options-cache version, so the next resolve reads the fresh name');
    }
}