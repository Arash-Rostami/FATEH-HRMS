<?php

namespace Tests\Feature\Models;

use App\Models\Feed;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReactionModelTest extends TestCase
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

    public function test_factory_persists_reaction_with_fillable_fields(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create(['user_id' => $user->id]);
        $reaction = Reaction::factory()->create([
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => '👍',
        ]);

        $this->assertDatabaseHas('reactions', [
            'id' => $reaction->id,
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => bin2hex('👍'),
        ]);

        $this->assertSame('👍', $reaction->fresh()->emoji);
    }

    public function test_feed_relation_resolves_to_owning_feed(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create(['user_id' => $user->id]);
        $reaction = Reaction::factory()->create([
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => '👍',
        ]);

        $this->assertSame($feed->id, $reaction->feed->id);
        $this->assertSame($feed->id, $reaction->feed_id);
    }

    public function test_user_relation_resolves_to_owning_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create(['user_id' => $user->id]);
        $reaction = Reaction::factory()->create([
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => '👍',
        ]);

        $this->assertSame($user->id, $reaction->user->id);
        $this->assertSame($user->id, $reaction->user_id);
    }

    public function test_unique_constraint_blocks_duplicate_user_feed_emoji(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create(['user_id' => $user->id]);
        Reaction::factory()->create([
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => '👍',
        ]);

        $this->expectException(QueryException::class);

        Reaction::factory()->create([
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => '👍',
        ]);
    }

    public function test_unique_constraint_allows_same_user_different_emoji_on_same_feed(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create(['user_id' => $user->id]);

        Reaction::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id, 'emoji' => '👍']);
        Reaction::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id, 'emoji' => '👎']);

        $this->assertSame(2, Reaction::where('feed_id', $feed->id)->count());

        $emojis = Reaction::where('feed_id', $feed->id)->orderBy('id')->get()->map(fn($r) => $r->emoji)->all();
        $this->assertSame(['👍', '👎'], $emojis);
    }

    public function test_unique_constraint_allows_different_users_same_emoji_on_same_feed(): void
    {
        $userA = User::factory()->create(['status' => 'active']);
        $userB = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create(['user_id' => $userA->id]);
        Reaction::factory()->create([
            'user_id' => $userA->id,
            'feed_id' => $feed->id,
            'emoji' => '👍',
        ]);
        Reaction::factory()->create([
            'user_id' => $userB->id,
            'feed_id' => $feed->id,
            'emoji' => '👍',
        ]);

        $this->assertSame(2, Reaction::where('feed_id', $feed->id)->count());

        $emojis = Reaction::where('feed_id', $feed->id)->orderBy('id')->get()->map(fn($r) => $r->emoji)->all();
        $this->assertSame(['👍', '👍'], $emojis);
    }
}