<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StateServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::flush();
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
            foreach (explode("\n", (string)file_get_contents($env)) as $line) {
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

    public function test_viewed_today_is_false_for_guest_and_before_marking(): void
    {
        $this->assertFalse(StateService::viewedToday('home'));

        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        $this->assertFalse(StateService::viewedToday('home'));
    }

    public function test_mark_viewed_makes_tab_viewed_today_for_authed_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        StateService::markViewed('home');

        $this->assertTrue(StateService::viewedToday('home'));
    }

    public function test_mark_viewed_is_idempotent_and_does_not_overwrite_the_cached_date(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        StateService::markViewed('home');
        $key = "tab_viewed:home:{$user->id}";
        $first = Cache::get($key);

        StateService::markViewed('home');
        $this->assertSame($first, Cache::get($key));
    }

    public function test_flush_bumps_version_cache_key_to_a_new_precise_timestamp(): void
    {
        Cache::forever('menu_state:version', 1000);

        StateService::flush();

        $this->assertNotSame(1000, Cache::get('menu_state:version'));
    }

    public function test_viewed_today_is_per_tab_and_per_user(): void
    {
        $user1 = User::factory()->create(['status' => 'active']);
        $user2 = User::factory()->create(['status' => 'active']);

        $this->actingAs($user1);
        StateService::markViewed('home');
        $this->assertTrue(StateService::viewedToday('home'));
        $this->assertFalse(StateService::viewedToday('links'));

        $this->actingAs($user2);
        $this->assertFalse(StateService::viewedToday('home'));
    }
}