<?php

namespace Tests\Feature\Models;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Once;
use RuntimeException;
use Tests\TestCase;

class PermissionModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Once::flush();
        Cache::forget('permission_modules');
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Once::flush();
        Cache::forget('permission_modules');
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

    public function test_creating_a_second_permission_for_the_same_user_throws(): void
    {
        $user = User::factory()->create();
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        $this->expectException(RuntimeException::class);

        Permission::create(['user_id' => $user->id, 'is_super_admin' => false]);
    }

    public function test_actions_constant_lists_the_five_capability_actions(): void
    {
        $this->assertSame(['view', 'create', 'update', 'delete', 'restore'], Permission::ACTIONS);
    }

    public function test_cache_key_formats_user_id_into_prefixed_string(): void
    {
        $this->assertStringContainsString('user_permission:42', Permission::cacheKey(42));
    }

    public function test_casts_declares_boolean_for_super_admin_and_array_for_abilities_and_excluded_modules(): void
    {
        $permission = new Permission();

        $casts = $permission->getCasts();

        $this->assertSame('boolean', $casts['is_super_admin']);
        $this->assertSame('array', $casts['abilities']);
        $this->assertSame('array', $casts['excluded_modules']);
    }

    public function test_is_module_excluded_returns_true_when_module_in_excluded_modules(): void
    {
        $permission = new Permission(['excluded_modules' => ['dms', 'task']]);

        $this->assertTrue($permission->isModuleExcluded('dms'));
        $this->assertFalse($permission->isModuleExcluded('gallery'));
    }

    public function test_is_module_excluded_returns_false_when_excluded_modules_is_null(): void
    {
        $permission = new Permission();

        $this->assertFalse($permission->isModuleExcluded('dms'));
    }

    public function test_can_returns_true_for_super_admin_when_module_not_excluded(): void
    {
        $permission = new Permission(['is_super_admin' => true, 'excluded_modules' => ['task']]);

        $this->assertTrue($permission->can('dms', 'view'));
        $this->assertTrue($permission->can('dms', 'delete'));
    }

    public function test_can_returns_false_for_super_admin_when_module_is_excluded(): void
    {
        $permission = new Permission(['is_super_admin' => true, 'excluded_modules' => ['dms']]);

        $this->assertFalse($permission->can('dms', 'view'));
    }

    public function test_can_returns_true_for_non_super_admin_when_ability_matches_module_and_action(): void
    {
        $permission = new Permission([
            'is_super_admin' => false,
            'abilities' => [['module' => 'dms', 'actions' => ['view', 'create']]],
        ]);

        $this->assertTrue($permission->can('dms', 'view'));
        $this->assertTrue($permission->can('dms', 'create'));
    }

    public function test_can_returns_false_for_non_super_admin_when_action_not_in_abilities(): void
    {
        $permission = new Permission([
            'is_super_admin' => false,
            'abilities' => [['module' => 'dms', 'actions' => ['view']]],
        ]);

        $this->assertFalse($permission->can('dms', 'delete'));
    }

    public function test_can_returns_false_for_non_super_admin_when_module_not_in_abilities(): void
    {
        $permission = new Permission([
            'is_super_admin' => false,
            'abilities' => [['module' => 'dms', 'actions' => ['view']]],
        ]);

        $this->assertFalse($permission->can('task', 'view'));
    }

    public function test_can_returns_false_for_non_super_admin_with_null_abilities(): void
    {
        $permission = new Permission(['is_super_admin' => false]);

        $this->assertFalse($permission->can('dms', 'view'));
    }

    public function test_allowed_modules_for_super_admin_returns_all_modules_minus_excluded(): void
    {
        $modules = ['dms' => 'DMS', 'task' => 'Task', 'gallery' => 'Gallery'];
        Cache::put('permission_modules', $modules, now()->addDay());

        $permission = new Permission([
            'is_super_admin' => true,
            'excluded_modules' => ['task'],
        ]);

        $this->assertSame(['dms', 'gallery'], $permission->allowedModules());
    }

    public function test_allowed_modules_for_super_admin_returns_all_modules_when_excluded_is_null(): void
    {
        $modules = ['dms' => 'DMS', 'task' => 'Task'];
        Cache::put('permission_modules', $modules, now()->addDay());

        $permission = new Permission(['is_super_admin' => true]);

        $this->assertSame(['dms', 'task'], $permission->allowedModules());
    }

    public function test_allowed_modules_for_non_super_admin_returns_unique_module_names_from_abilities(): void
    {
        $permission = new Permission([
            'is_super_admin' => false,
            'abilities' => [
                ['module' => 'dms', 'actions' => ['view']],
                ['module' => 'task', 'actions' => ['view']],
            ],
        ]);

        $this->assertSame(['dms', 'task'], $permission->allowedModules());
    }

    public function test_allowed_modules_for_non_super_admin_returns_empty_array_when_abilities_null(): void
    {
        $permission = new Permission(['is_super_admin' => false]);

        $this->assertSame([], $permission->allowedModules());
    }

    public function test_available_modules_reads_seeded_cache_and_memoizes_via_once(): void
    {
        $modules = ['dms' => 'DMS', 'task' => 'Task'];
        Cache::put('permission_modules', $modules, now()->addDay());

        $first = Permission::availableModules();

        Cache::put('permission_modules', ['gallery' => 'Gallery'], now()->addDay());

        $second = Permission::availableModules();

        $this->assertSame($modules, $first);
        $this->assertSame($modules, $second);
    }

    public function test_available_modules_picks_up_new_cache_value_after_once_flush(): void
    {
        Cache::put('permission_modules', ['dms' => 'DMS'], now()->addDay());
        $this->assertSame(['dms' => 'DMS'], Permission::availableModules());

        Once::flush();
        Cache::put('permission_modules', ['task' => 'Task'], now()->addDay());

        $this->assertSame(['task' => 'Task'], Permission::availableModules());
    }

    public function test_for_user_returns_permission_row_for_user_id(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $permission = Permission::factory()->create([
            'user_id' => $user->id,
            'is_super_admin' => true,
        ]);

        $resolved = Permission::forUser($user->id);

        $this->assertNotNull($resolved);
        $this->assertSame($permission->id, $resolved->id);
    }

    public function test_for_user_returns_null_when_no_permission_row_exists(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->assertNull(Permission::forUser($user->id));
    }

    public function test_for_user_caches_resolved_permission_across_calls(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Permission::factory()->create([
            'user_id' => $user->id,
            'is_super_admin' => true,
        ]);

        Permission::forUser($user->id);

        DB::table('permissions')->where('user_id', $user->id)->update(['is_super_admin' => false]);

        $cached = Permission::forUser($user->id);

        $this->assertNotNull($cached);
        $this->assertTrue($cached->is_super_admin);
    }

    public function test_saving_hook_nulls_abilities_for_super_admin(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $permission = Permission::factory()->create([
            'user_id' => $user->id,
            'is_super_admin' => true,
            'abilities' => [['module' => 'dms', 'actions' => ['view']]],
        ]);

        $permission->abilities = [['module' => 'task', 'actions' => ['view']]];
        $permission->save();

        $this->assertNull($permission->fresh()->abilities);
    }

    public function test_saving_hook_nulls_excluded_modules_for_non_super_admin(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $permission = Permission::factory()->create([
            'user_id' => $user->id,
            'is_super_admin' => false,
            'excluded_modules' => ['dms'],
        ]);

        $permission->excluded_modules = ['task'];
        $permission->save();

        $this->assertNull($permission->fresh()->excluded_modules);
    }

    public function test_saved_hook_flushes_permission_cache_key(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $permission = Permission::factory()->create([
            'user_id' => $user->id,
            'is_super_admin' => true,
        ]);

        Permission::forUser($user->id);
        $this->assertTrue(Cache::has(Permission::cacheKey($user->id)));

        $permission->is_super_admin = false;
        $permission->save();

        $this->assertFalse(Cache::has(Permission::cacheKey($user->id)));
    }

    public function test_deleted_hook_flushes_permission_cache_key(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $permission = Permission::factory()->create([
            'user_id' => $user->id,
            'is_super_admin' => true,
        ]);

        Permission::forUser($user->id);
        $this->assertTrue(Cache::has(Permission::cacheKey($user->id)));

        $permission->delete();

        $this->assertFalse(Cache::has(Permission::cacheKey($user->id)));
    }
}