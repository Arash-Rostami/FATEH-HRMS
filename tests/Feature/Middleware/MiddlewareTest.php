<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\EnsureHasPermission;
use App\Http\Middleware\EnsureUserModulePermission;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Once;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Once::flush();
        Cache::flush();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::flush();
        Once::flush();
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

    public function test_ensure_has_permission_aborts_403_for_guest(): void
    {
        Route::get('/_test/ehp', fn () => response('OK'))->middleware([EnsureHasPermission::class]);

        $this->get('/_test/ehp')->assertStatus(403);
    }

    public function test_ensure_has_permission_passes_developer_without_permission_row(): void
    {
        $dev = User::factory()->create(['status' => 'active', 'role' => 'developer']);
        Route::get('/_test/ehp', fn () => response('OK'))->middleware([EnsureHasPermission::class]);

        $this->actingAs($dev)->get('/_test/ehp')->assertStatus(200);
    }

    public function test_ensure_has_permission_aborts_403_for_user_without_permission_row(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Route::get('/_test/ehp', fn () => response('OK'))->middleware([EnsureHasPermission::class]);

        $this->actingAs($user)->get('/_test/ehp')->assertStatus(403);
    }

    public function test_ensure_has_permission_aborts_403_for_non_super_admin_with_empty_abilities(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Permission::factory()->create([
            'user_id' => $user->id,
            'is_super_admin' => false,
            'abilities' => [],
            'excluded_modules' => [],
        ]);
        Route::get('/_test/ehp', fn () => response('OK'))->middleware([EnsureHasPermission::class]);

        $this->actingAs($user)->get('/_test/ehp')->assertStatus(403);
    }

    public function test_ensure_has_permission_passes_super_admin(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Permission::factory()->create([
            'user_id' => $user->id,
            'is_super_admin' => true,
            'abilities' => [],
            'excluded_modules' => [],
        ]);
        Route::get('/_test/ehp', fn () => response('OK'))->middleware([EnsureHasPermission::class]);

        $this->actingAs($user)->get('/_test/ehp')->assertStatus(200);
    }

    public function test_ensure_has_permission_passes_non_super_admin_with_abilities(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Permission::factory()->create([
            'user_id' => $user->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'foo', 'actions' => ['view']]],
            'excluded_modules' => [],
        ]);
        Route::get('/_test/ehp', fn () => response('OK'))->middleware([EnsureHasPermission::class]);

        $this->actingAs($user)->get('/_test/ehp')->assertStatus(200);
    }

    public function test_ensure_user_module_permission_redirects_guest_to_login(): void
    {
        Route::get('/_test/eump', fn () => response('OK'))
            ->middleware([EnsureUserModulePermission::class . ':foo']);

        $this->get('/_test/eump')->assertRedirect(route('login'));
    }

    public function test_ensure_user_module_permission_passes_developer(): void
    {
        $dev = User::factory()->create(['status' => 'active', 'role' => 'developer']);
        Route::get('/_test/eump', fn () => response('OK'))
            ->middleware([EnsureUserModulePermission::class . ':foo']);

        $this->actingAs($dev)->get('/_test/eump')->assertStatus(200);
    }

    public function test_ensure_user_module_permission_aborts_403_for_user_without_permission_row(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Route::get('/_test/eump', fn () => response('OK'))
            ->middleware([EnsureUserModulePermission::class . ':foo']);

        $this->actingAs($user)->get('/_test/eump')->assertStatus(403);
    }

    public function test_ensure_user_module_permission_aborts_403_when_module_not_in_abilities(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Permission::factory()->create([
            'user_id' => $user->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'bar', 'actions' => ['view']]],
            'excluded_modules' => [],
        ]);
        Route::get('/_test/eump', fn () => response('OK'))
            ->middleware([EnsureUserModulePermission::class . ':foo']);

        $this->actingAs($user)->get('/_test/eump')->assertStatus(403);
    }

    public function test_ensure_user_module_permission_aborts_403_when_ability_lacks_view_action(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Permission::factory()->create([
            'user_id' => $user->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'foo', 'actions' => ['create']]],
            'excluded_modules' => [],
        ]);
        Route::get('/_test/eump', fn () => response('OK'))
            ->middleware([EnsureUserModulePermission::class . ':foo']);

        $this->actingAs($user)->get('/_test/eump')->assertStatus(403);
    }

    public function test_ensure_user_module_permission_passes_when_ability_grants_view(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Permission::factory()->create([
            'user_id' => $user->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'foo', 'actions' => ['view', 'create']]],
            'excluded_modules' => [],
        ]);
        Route::get('/_test/eump', fn () => response('OK'))
            ->middleware([EnsureUserModulePermission::class . ':foo']);

        $this->actingAs($user)->get('/_test/eump')->assertStatus(200);
    }

    public function test_ensure_user_module_permission_passes_super_admin_when_module_not_excluded(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Permission::factory()->create([
            'user_id' => $user->id,
            'is_super_admin' => true,
            'abilities' => [],
            'excluded_modules' => [],
        ]);
        Route::get('/_test/eump', fn () => response('OK'))
            ->middleware([EnsureUserModulePermission::class . ':foo']);

        $this->actingAs($user)->get('/_test/eump')->assertStatus(200);
    }

    public function test_ensure_user_module_permission_aborts_403_for_super_admin_with_excluded_module(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Permission::factory()->create([
            'user_id' => $user->id,
            'is_super_admin' => true,
            'abilities' => [],
            'excluded_modules' => ['foo'],
        ]);
        Route::get('/_test/eump', fn () => response('OK'))
            ->middleware([EnsureUserModulePermission::class . ':foo']);

        $this->actingAs($user)->get('/_test/eump')->assertStatus(403);
    }

    public function test_update_last_seen_touches_user_on_first_request(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'user',
            'last_seen' => now()->subDay(),
        ]);
        Route::get('/_test/uls', fn () => response('OK'))->middleware([UpdateLastSeen::class]);

        $this->actingAs($user)->get('/_test/uls')->assertStatus(200);

        $fresh = $user->fresh();
        $this->assertNotNull($fresh->last_seen);
        $this->assertGreaterThan(now()->subMinute(), $fresh->last_seen);
    }

    public function test_update_last_seen_skips_touch_when_cache_key_present(): void
    {
        $old = now()->subDay();
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'user',
            'last_seen' => $old,
        ]);
        Cache::put('last_seen:' . $user->id, true, now()->addMinutes(5));
        Route::get('/_test/uls', fn () => response('OK'))->middleware([UpdateLastSeen::class]);

        $this->actingAs($user)->get('/_test/uls')->assertStatus(200);

        $this->assertEquals($old->timestamp, $user->fresh()->last_seen->timestamp);
    }

    public function test_update_last_seen_noop_for_guest(): void
    {
        Route::get('/_test/uls', fn () => response('OK'))->middleware([UpdateLastSeen::class]);

        $this->get('/_test/uls')->assertStatus(200);
    }
}