<?php

namespace Tests\Feature\Traits;

use App\Filament\Resources\PostResource;
use App\Filament\Resources\ResourceResource;
use App\Models\Permission;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthorizesByPermissionTest extends TestCase
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

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    private function admin(): User
    {
        return User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'admin']);
    }

    public function test_module_key_strips_resource_suffix_and_snake_cases()
    {
        $this->assertSame('post', PostResource::moduleKey());
        $this->assertSame('resource', ResourceResource::moduleKey());
    }

    public function test_developer_bypasses_per_module_permissions()
    {
        $this->actingAs($this->developer());

        $this->assertTrue(PostResource::canViewAny());
        $this->assertTrue(PostResource::canCreate());
        $this->assertTrue(PostResource::canEdit(new Post()));
        $this->assertTrue(PostResource::canDelete(new Post()));
    }

    public function test_non_developer_admin_without_permission_row_is_denied_everywhere()
    {
        $this->actingAs($this->admin());

        $this->assertFalse(PostResource::canViewAny());
        $this->assertFalse(PostResource::canCreate());
        $this->assertFalse(PostResource::canEdit(new Post()));
        $this->assertFalse(PostResource::canDelete(new Post()));
    }

    public function test_non_developer_admin_granted_actions_pass_and_ungranted_fail()
    {
        $admin = $this->admin();
        Permission::create([
            'user_id' => $admin->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'post', 'actions' => ['view', 'create']]],
        ]);

        $this->actingAs($admin);

        $this->assertTrue(PostResource::canViewAny());
        $this->assertTrue(PostResource::canCreate());
        $this->assertFalse(PostResource::canEdit(new Post()));
        $this->assertFalse(PostResource::canDelete(new Post()));
    }

    public function test_authorization_response_for_view_only_admin_allows_only_view_abilities()
    {
        $admin = $this->admin();
        Permission::create(['user_id' => $admin->id, 'is_super_admin' => false, 'abilities' => [['module' => 'post', 'actions' => ['view']]]]);
        $this->actingAs($admin);

        foreach (['viewAny', 'view'] as $ability) {
            $this->assertTrue(PostResource::getAuthorizationResponse($ability)->allowed(), $ability);
        }

        foreach (['create', 'replicate', 'update', 'reorder', 'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny'] as $ability) {
            $this->assertFalse(PostResource::getAuthorizationResponse($ability)->allowed(), $ability);
        }
    }

    public function test_authorization_response_maps_abilities_to_their_permission_actions()
    {
        $admin = $this->admin();
        Permission::create(['user_id' => $admin->id, 'is_super_admin' => false, 'abilities' => [['module' => 'post', 'actions' => ['update', 'delete']]]]);
        $this->actingAs($admin);

        foreach (['update', 'reorder', 'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny'] as $ability) {
            $this->assertTrue(PostResource::getAuthorizationResponse($ability)->allowed(), $ability);
        }

        foreach (['viewAny', 'create', 'restore', 'restoreAny'] as $ability) {
            $this->assertFalse(PostResource::getAuthorizationResponse($ability)->allowed(), $ability);
        }
    }

    public function test_authorization_response_super_admin_allowed_except_excluded_module()
    {
        $admin = $this->admin();
        Permission::create(['user_id' => $admin->id, 'is_super_admin' => true, 'excluded_modules' => ['resource']]);
        $this->actingAs($admin);

        $this->assertTrue(PostResource::getAuthorizationResponse('delete')->allowed());
        $this->assertFalse(ResourceResource::getAuthorizationResponse('delete')->allowed());
        $this->assertFalse(ResourceResource::getAuthorizationResponse('viewAny')->allowed());
    }

    public function test_authorization_response_developer_allowed_and_unknown_ability_denied()
    {
        $this->actingAs($this->developer());

        $this->assertTrue(PostResource::getAuthorizationResponse('forceDeleteAny')->allowed());
        $this->assertFalse(PostResource::getAuthorizationResponse('somethingUnknown')->allowed());
    }

    public function test_unauthenticated_requests_are_denied()
    {
        Auth::logout();

        $this->assertFalse(PostResource::canViewAny());
        $this->assertFalse(PostResource::canCreate());
    }

    public function test_repeated_checks_in_one_request_reuse_a_single_permission_lookup()
    {
        $admin = $this->admin();
        Permission::create([
            'user_id' => $admin->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'post', 'actions' => ['view', 'create', 'update', 'delete']]],
        ]);

        $this->actingAs($admin);

        $this->assertTrue(PostResource::canViewAny());

        Cache::forget(Permission::cacheKey($admin->id));

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->assertTrue(PostResource::canViewAny());
        $this->assertTrue(PostResource::canCreate());
        $this->assertTrue(PostResource::canEdit(new Post()));
        $this->assertTrue(PostResource::canDelete(new Post()));
        $this->assertTrue(PostResource::getAuthorizationResponse('update')->allowed());

        DB::disableQueryLog();

        $permissionQueries = array_filter(DB::getQueryLog(), fn($q) => str_contains($q['query'], 'permissions'));
        $this->assertCount(0, $permissionQueries, 'five further checks after a mid-request cache rotation must not touch the permissions table — the instance memo reuses the single lookup from the first check');
    }
}