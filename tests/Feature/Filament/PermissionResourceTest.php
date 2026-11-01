<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PermissionResource\Pages\CreatePermission;
use App\Filament\Resources\PermissionResource\Pages\EditPermission;
use App\Filament\Resources\PermissionResource\Pages\ListPermissions;
use App\Filament\Resources\PermissionResource;
use App\Filament\Resources\PermissionResource\Schemas\PermissionFormPresenter;
use App\Models\Permission;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PermissionResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
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

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    private function freshAdmin(): User
    {
        return User::factory()->create([
            'status' => 'active',
            'presence' => 'onsite',
            'role' => 'admin',
            'name' => '0-test-admin-' . uniqid(),
        ]);
    }

    public function test_list_renders_and_shows_factory_permission_record()
    {
        $admin = $this->developer();

        $adminUser = $this->freshAdmin();
        $permission = Permission::create([
            'user_id' => $adminUser->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'authority', 'actions' => ['view']]],
        ]);

        Livewire::actingAs($admin)->test(ListPermissions::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$permission]);
    }

    public function test_create_super_admin_permission_persists_with_excluded_module()
    {
        $admin = $this->developer();

        $adminUser = $this->freshAdmin();

        Livewire::actingAs($admin)->test(CreatePermission::class)
            ->fillForm([
                'user_id' => $adminUser->id,
                'is_super_admin' => true,
                'excluded_modules' => ['authority'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Permission::where('user_id', $adminUser->id)->first();

        $this->assertTrue($created->is_super_admin);
        $this->assertSame(['authority'], $created->excluded_modules);
        $this->assertNull($created->abilities);
    }

    public function test_create_regular_admin_permission_persists_abilities()
    {
        $admin = $this->developer();

        $adminUser = $this->freshAdmin();

        Livewire::actingAs($admin)->test(CreatePermission::class)
            ->fillForm([
                'user_id' => $adminUser->id,
                'is_super_admin' => false,
                'abilities' => [['module' => 'authority', 'actions' => ['view', 'create']]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Permission::where('user_id', $adminUser->id)->first();

        $this->assertFalse($created->is_super_admin);
        $this->assertSame([['module' => 'authority', 'actions' => ['view', 'create']]], $created->abilities);
        $this->assertNull($created->excluded_modules);
    }

    public function test_create_rejects_empty_abilities_when_not_super_admin()
    {
        $admin = $this->developer();

        $adminUser = $this->freshAdmin();

        Livewire::actingAs($admin)->test(CreatePermission::class)
            ->fillForm([
                'user_id' => $adminUser->id,
                'is_super_admin' => false,
                'abilities' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['abilities']);
    }

    public function test_create_rejects_duplicate_module_in_abilities()
    {
        $admin = $this->developer();

        $adminUser = $this->freshAdmin();

        Livewire::actingAs($admin)->test(CreatePermission::class)
            ->fillForm([
                'user_id' => $adminUser->id,
                'is_super_admin' => false,
                'abilities' => [
                    ['module' => 'authority', 'actions' => ['view']],
                    ['module' => 'authority', 'actions' => ['create']],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['abilities.0.module']);
    }

    public function test_edit_permission_toggling_to_super_admin_nulls_abilities()
    {
        $admin = $this->developer();

        $adminUser = $this->freshAdmin();
        $permission = Permission::create([
            'user_id' => $adminUser->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'authority', 'actions' => ['view']]],
        ]);

        Livewire::actingAs($admin)->test(EditPermission::class, ['record' => $permission->getRouteKey()])
            ->assertSuccessful()
            ->fillForm([
                'is_super_admin' => true,
                'excluded_modules' => [],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($permission->fresh()->is_super_admin);
        $this->assertNull($permission->fresh()->abilities);
    }

    public function test_delete_action_removes_permission_record()
    {
        $admin = $this->developer();

        $adminUser = $this->freshAdmin();
        $permission = Permission::create([
            'user_id' => $adminUser->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'authority', 'actions' => ['view']]],
        ]);

        Livewire::actingAs($admin)->test(ListPermissions::class)
            ->callTableAction(\Filament\Actions\DeleteAction::class, $permission);

        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }

    public function test_plain_admin_is_denied_every_ability_and_cannot_reach_pages()
    {
        $plain = $this->freshAdmin();
        Permission::create(['user_id' => $plain->id, 'is_super_admin' => false, 'abilities' => [['module' => 'permission', 'actions' => ['view', 'create', 'update', 'delete', 'restore']]]]);
        $target = Permission::create(['user_id' => $this->freshAdmin()->id, 'is_super_admin' => false, 'abilities' => [['module' => 'authority', 'actions' => ['view']]]]);
        $this->actingAs($plain);

        foreach (['viewAny', 'view', 'create', 'update', 'delete', 'deleteAny', 'forceDelete', 'restore'] as $ability) {
            $this->assertFalse(PermissionResource::getAuthorizationResponse($ability, $target)->allowed(), $ability);
        }

        $this->assertFalse(PermissionResource::canViewAny());
        Livewire::test(ListPermissions::class)->assertForbidden();
        Livewire::test(CreatePermission::class)->assertForbidden();
    }

    public function test_super_admin_can_create_and_edit_others_permissions()
    {
        $super = $this->freshAdmin();
        Permission::create(['user_id' => $super->id, 'is_super_admin' => true]);
        $target = Permission::create(['user_id' => $this->freshAdmin()->id, 'is_super_admin' => false, 'abilities' => [['module' => 'authority', 'actions' => ['view']]]]);
        $newcomer = $this->freshAdmin();

        Livewire::actingAs($super)->test(CreatePermission::class)
            ->fillForm(['user_id' => $newcomer->id, 'is_super_admin' => false, 'abilities' => [['module' => 'authority', 'actions' => ['view']]]])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::actingAs($super)->test(EditPermission::class, ['record' => $target->getRouteKey()])
            ->fillForm(['abilities' => [['module' => 'authority', 'actions' => ['view', 'update']]]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNotNull(Permission::where('user_id', $newcomer->id)->first());
        $this->assertSame([['module' => 'authority', 'actions' => ['view', 'update']]], $target->fresh()->abilities);
    }

    public function test_super_admin_and_developer_cannot_delete_or_force_delete_own_row_but_can_delete_anothers()
    {
        $super = $this->freshAdmin();
        $superRow = Permission::create(['user_id' => $super->id, 'is_super_admin' => true]);
        $developer = $this->developer();
        $other = Permission::create(['user_id' => $this->freshAdmin()->id, 'is_super_admin' => false, 'abilities' => [['module' => 'authority', 'actions' => ['view']]]]);

        foreach ([[$super, $superRow], [$developer, Permission::where('user_id', $developer->id)->first()]] as [$actor, $own]) {
            $this->actingAs($actor);

            foreach (['delete', 'forceDelete'] as $ability) {
                $this->assertFalse(PermissionResource::getAuthorizationResponse($ability, $own)->allowed(), $ability);
                $this->assertTrue(PermissionResource::getAuthorizationResponse($ability, $other)->allowed(), $ability);
            }

            Livewire::actingAs($actor)->test(ListPermissions::class)
                ->assertTableActionHidden(DeleteAction::class, $own)
                ->assertTableActionVisible(DeleteAction::class, $other);
        }

        Livewire::actingAs($super)->test(ListPermissions::class)->callTableAction(DeleteAction::class, $other);
        $this->assertDatabaseMissing('permissions', ['id' => $other->id]);
        $this->assertDatabaseHas('permissions', ['id' => $superRow->id]);
    }

    public function test_bulk_delete_skips_own_row()
    {
        $super = $this->freshAdmin();
        $own = Permission::create(['user_id' => $super->id, 'is_super_admin' => true]);
        $other = Permission::create(['user_id' => $this->freshAdmin()->id, 'is_super_admin' => false, 'abilities' => [['module' => 'authority', 'actions' => ['view']]]]);

        Livewire::actingAs($super)->test(ListPermissions::class)
            ->callTableBulkAction(DeleteBulkAction::class, [$own, $other]);

        $this->assertDatabaseHas('permissions', ['id' => $own->id]);
        $this->assertDatabaseMissing('permissions', ['id' => $other->id]);
    }

    public function test_module_pickers_do_not_offer_the_permission_module()
    {
        $adminUser = $this->freshAdmin();

        Livewire::actingAs($this->developer())->test(CreatePermission::class)
            ->fillForm(['user_id' => $adminUser->id, 'is_super_admin' => false, 'abilities' => [['module' => 'permission', 'actions' => ['view']]]])
            ->call('create')
            ->assertHasFormErrors(['abilities.0.module']);

        $this->assertArrayHasKey('permission', Permission::availableModules());
        $this->assertArrayNotHasKey('permission', PermissionFormPresenter::excludedModules()->getOptions());
    }

    public function test_super_only_filter_scopes_list_to_super_admin_records()
    {
        $admin = $this->developer();

        $superUser = $this->freshAdmin();
        $superPermission = Permission::create([
            'user_id' => $superUser->id,
            'is_super_admin' => true,
            'excluded_modules' => [],
        ]);

        $regularUser = $this->freshAdmin();
        $regularPermission = Permission::create([
            'user_id' => $regularUser->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'authority', 'actions' => ['view']]],
        ]);

        Livewire::actingAs($admin)->test(ListPermissions::class)
            ->filterTable('is_super_admin', true)
            ->assertCanSeeTableRecords([$superPermission])
            ->assertCanNotSeeTableRecords([$regularPermission]);
    }

    public function test_created_at_filter_scopes_to_the_given_date_range()
    {
        $admin = $this->developer();

        $recentUser = $this->freshAdmin();
        $inRange = Permission::create(['user_id' => $recentUser->id, 'is_super_admin' => true, 'excluded_modules' => []]);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $oldUser = $this->freshAdmin();
        $outOfRange = Permission::create(['user_id' => $oldUser->id, 'is_super_admin' => true, 'excluded_modules' => []]);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListPermissions::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(ListPermissions::class)
            ->assertSuccessful()
            ->set('tableGrouping', 'module_key')
            ->assertSuccessful();
    }

    public function test_sorting_by_id_created_at_and_is_super_admin_produces_correct_order()
    {
        $admin = $this->developer();

        $userA = $this->freshAdmin();
        $userB = $this->freshAdmin();
        $first = Permission::create(['user_id' => $userA->id, 'is_super_admin' => false, 'abilities' => []]);
        $second = Permission::create(['user_id' => $userB->id, 'is_super_admin' => true, 'excluded_modules' => []]);

        $component = Livewire::actingAs($admin)->test(ListPermissions::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('id')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('is_super_admin')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('is_super_admin', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $old = $first;
        $old->forceFill(['created_at' => now()->subYears(5)])->saveQuietly();
        $new = $second;
        $new->forceFill(['created_at' => now()])->saveQuietly();

        $component->sortTable('created_at')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$old->id, $new->id], array_values(array_intersect($ids, [$old->id, $new->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$new->id, $old->id], array_values(array_intersect($ids, [$old->id, $new->id])));
    }

    public function test_sorting_by_user_name_produces_correct_order()
    {
        $admin = $this->developer();
        $userA = User::factory()->create(['name' => 'AAA-sort-user', 'role' => 'admin']);
        $userZ = User::factory()->create(['name' => 'ZZZ-sort-user', 'role' => 'admin']);
        $first = Permission::create(['user_id' => $userA->id, 'is_super_admin' => false, 'abilities' => []]);
        $second = Permission::create(['user_id' => $userZ->id, 'is_super_admin' => false, 'abilities' => []]);

        $component = Livewire::actingAs($admin)->test(ListPermissions::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('user.name')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\PermissionResource\Pages\CreatePermission::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\PermissionResource\Pages\EditPermission::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\PermissionResource\Pages\ListPermissions::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\PermissionResource\Pages\CreatePermission::class, \App\Filament\Resources\PermissionResource\Pages\EditPermission::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/PermissionResource/Schemas/Grouping/ModuleGroup.php'),
        ];

        $recordMethods = ['getTitleFromRecordUsing', 'getKeyFromRecordUsing', 'getDescriptionFromRecordUsing'];
        $queryMethods = ['modifyQueryUsing', 'groupQueryUsing', 'orderQueryUsing', 'scopeQueryByKeyUsing', 'scopeQueryUsing', 'query'];

        $offenders = [];

        foreach ([[$recordMethods, 'record'], [$queryMethods, 'query']] as [$methods, $expectedName]) {
            $pattern = '/\\b(' . implode('|', $methods) . ')\\s*\\(\\s*(?:static\\s+)?(?:function|fn)\\s*\\(([^)]*)\\)/s';

            foreach ($files as $path) {
                $contents = file_get_contents($path);

                if (!preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                foreach ($matches as $match) {
                    $method = $match[1][0];
                    $paramList = $match[2][0];
                    $offset = $match[0][1];

                    $firstParam = trim(explode(',', $paramList)[0] ?? '');

                    if ($firstParam === '') {
                        continue;
                    }

                    if (!preg_match('/^(?<type>[^$]*)\\$(?<name>[A-Za-z_][A-Za-z0-9_]*)/', $firstParam, $m)) {
                        continue;
                    }

                    $hasType = trim($m['type']) !== '';
                    $name = $m['name'];

                    if ($name === $expectedName || $hasType) {
                        continue;
                    }

                    $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
                    $offenders[] = basename($path) . ":{$line} {$method}(fn(\${$name}) => ...)";
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Closure param must be named \$record/\$query (or carry an explicit type hint) so Filament's by-name evaluate() injection binds correctly:\n" . implode("\n", $offenders),
        );
    }
}
