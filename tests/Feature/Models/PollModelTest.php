<?php

namespace Tests\Feature\Models;

use App\Models\Feed;
use App\Models\Poll;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PollModelTest extends TestCase
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

    public function test_casts_declares_option_index_as_integer(): void
    {
        $method = new \ReflectionMethod(Poll::class, 'casts');
        $method->setAccessible(true);

        $this->assertSame(['option_index' => 'integer'], $method->invoke(new Poll()));
    }

    public function test_option_index_cast_coerces_string_to_integer_in_memory(): void
    {
        $poll = new Poll(['option_index' => '2']);

        $this->assertSame(2, $poll->option_index);
        $this->assertIsInt($poll->option_index);
    }

    public function test_option_index_cast_coerces_null_to_integer_in_memory(): void
    {
        $poll = new Poll(['option_index' => null]);

        $this->assertNull($poll->option_index);
    }

    public function test_option_index_cast_round_trips_integer_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create(['user_id' => $user->id]);
        $poll = Poll::factory()->create([
            'feed_id' => $feed->id,
            'user_id' => $user->id,
            'option_index' => 3,
        ]);

        $reloaded = $poll->fresh();

        $this->assertSame(3, $reloaded->option_index);
        $this->assertIsInt($reloaded->option_index);
    }
}