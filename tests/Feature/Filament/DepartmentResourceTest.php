<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\DepartmentResource\Pages\CreateDepartment;
use App\Filament\Resources\DepartmentResource\Pages\EditDepartment;
use App\Filament\Resources\DepartmentResource\Pages\ListDepartments;
use App\Filament\Resources\DepartmentResource\RelationManagers\UsersRelationManager;
use App\Filament\Resources\DepartmentResource\Schemas\DepartmentFormPresenter;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class DepartmentResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::forget('department_options_v2');
        Cache::forget('department_options_with_tickets_v2');
        Cache::forget('department_models');
        Cache::forget('department_any_has_ticket_options');
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

    private function uniqueCode(): string
    {
        return strtoupper(Str::random(8));
    }

    public function test_list_renders_and_shows_factory_department_row()
    {
        $admin = $this->developer();
        $department = Department::factory()->create(['code' => $this->uniqueCode(), 'name' => '0List Render Dept']);

        Livewire::actingAs($admin)->test(ListDepartments::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$department]);
    }

    public function test_users_relation_manager_lists_only_the_departments_users()
    {
        $admin = $this->developer();
        $department = Department::factory()->create(['code' => $this->uniqueCode(), 'name' => '0Users RM Dept']);
        $member = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $member->id, 'department_id' => $department->code]);
        $outsider = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $outsider->id, 'department_id' => Department::factory()->create(['code' => $this->uniqueCode(), 'name' => '0Outsider Dept'])->code]);

        Livewire::actingAs($admin)
            ->test(UsersRelationManager::class, ['ownerRecord' => $department, 'pageClass' => EditDepartment::class])
            ->assertCanSeeTableRecords([$member])
            ->assertCanNotSeeTableRecords([$outsider]);
    }

    public function test_users_relation_manager_booking_toggle_cascades_without_a_data_property()
    {
        $admin = $this->developer();
        $department = Department::factory()->create(['code' => $this->uniqueCode(), 'name' => '0Toggle RM Dept']);
        $member = User::factory()->create(['status' => 'active', 'role' => 'user', 'type' => 'employee', 'booking' => [['key' => 'all', 'value' => false], ['key' => 'seat', 'value' => false]]]);
        Profile::factory()->create(['user_id' => $member->id, 'department_id' => $department->code]);

        $component = Livewire::actingAs($admin)
            ->test(UsersRelationManager::class, ['ownerRecord' => $department, 'pageClass' => EditDepartment::class])
            ->mountTableAction('edit', $member);

        $rows = collect($component->get('mountedActions.0.data.booking'));
        $component->set('mountedActions.0.data.booking.' . $rows->search(fn($row) => $row['key'] === 'all') . '.value', true)
            ->assertHasNoErrors();

        $this->assertTrue(collect($component->get('mountedActions.0.data.booking'))->every(fn($row) => $row['value'] === true));
    }

    public function test_edit_page_explains_tabs_hidden_by_the_admins_permissions()
    {
        $this->app->setLocale('fa');
        $department = Department::factory()->create(['code' => $this->uniqueCode(), 'name' => '0Hidden Tabs Dept']);
        $admin = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'admin']);
        Permission::create(['user_id' => $admin->id, 'is_super_admin' => false, 'abilities' => [['module' => 'department', 'actions' => ['view', 'update']]]]);

        Livewire::actingAs($admin)->test(EditDepartment::class, ['record' => $department->getRouteKey()])
            ->assertSuccessful()
            ->assertSeeText('به‌دلیل سطح دسترسی شما نمایش داده نمی‌شوند')
            ->assertSeeText('ماژول user')
            ->assertSeeText('ماژول authority');

        Livewire::actingAs($this->developer())->test(EditDepartment::class, ['record' => $department->getRouteKey()])
            ->assertDontSeeText('به‌دلیل سطح دسترسی شما');
    }

    public function test_create_department_persists_code_name_and_description()
    {
        $admin = $this->developer();
        $code = $this->uniqueCode();

        Livewire::actingAs($admin)->test(CreateDepartment::class)
            ->fillForm([
                'code' => $code,
                'name' => 'Research and Development',
                'description' => 'Handles engineering and product research.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('departments', [
            'code' => $code,
            'name' => 'Research and Development',
            'description' => 'Handles engineering and product research.',
        ]);
    }

    public function test_create_department_requires_code()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateDepartment::class)
            ->fillForm([
                'name' => 'Operations',
                'description' => 'Daily operations.',
            ])
            ->call('create')
            ->assertHasFormErrors(['code' => 'required']);
    }

    public function test_create_department_requires_name()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateDepartment::class)
            ->fillForm([
                'code' => $this->uniqueCode(),
                'description' => 'A department without a name.',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_create_department_persists_ticket_options_repeater()
    {
        $admin = $this->developer();
        $code = $this->uniqueCode();

        Livewire::actingAs($admin)->test(CreateDepartment::class)
            ->fillForm([
                'code' => $code,
                'name' => 'IT Support',
                'description' => 'Information technology support desk.',
                'ticket_options' => [
                    ['request_type' => 'incident', 'area_key' => 'nw', 'area_label' => 'Network'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Department::where('code', $code)->first();
        $this->assertNotNull($created);
        $this->assertIsArray($created->ticket_options);
        $this->assertSame('incident', $created->ticket_options[0]['request_type']);
    }

    public function test_edit_department_updates_name_and_description()
    {
        $admin = $this->developer();
        $department = Department::factory()->create([
            'code' => $this->uniqueCode(),
            'name' => 'Old Name',
            'description' => 'Old description.',
        ]);

        Livewire::actingAs($admin)->test(EditDepartment::class, ['record' => $department->getRouteKey()])
            ->assertSuccessful()
            ->fillForm([
                'name' => 'Updated Name',
                'description' => 'Updated description text.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $department->fresh();
        $this->assertSame('Updated Name', $fresh->name);
        $this->assertSame('Updated description text.', $fresh->description);
    }

    public function test_delete_department_removes_row_from_database()
    {
        $admin = $this->developer();
        $department = Department::factory()->create(['code' => $this->uniqueCode()]);

        Livewire::actingAs($admin)->test(ListDepartments::class)
            ->callTableAction(DeleteAction::class, $department);

        $this->assertDatabaseMissing('departments', ['code' => $department->code]);
    }

    public function test_has_users_filter_scopes_list_to_departments_with_users()
    {
        $admin = $this->developer();

        $withUsers = Department::factory()->create(['code' => $this->uniqueCode(), 'name' => '0With Users Dept']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $withUsers->code]);

        $withoutUsers = Department::factory()->create(['code' => $this->uniqueCode(), 'name' => '0Empty Dept']);

        Livewire::actingAs($admin)->test(ListDepartments::class)
            ->assertSuccessful()
            ->filterTable('has_users', true)
            ->assertCanSeeTableRecords([$withUsers])
            ->assertCanNotSeeTableRecords([$withoutUsers]);
    }

    public function test_has_users_filter_scopes_list_to_departments_without_users()
    {
        $admin = $this->developer();

        $withUsers = Department::factory()->create(['code' => $this->uniqueCode(), 'name' => '0Populated Dept']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $withUsers->code]);

        $withoutUsers = Department::factory()->create(['code' => $this->uniqueCode(), 'name' => '0Lonely Dept']);

        Livewire::actingAs($admin)->test(ListDepartments::class)
            ->assertSuccessful()
            ->filterTable('has_users', '0')
            ->assertCanSeeTableRecords([$withoutUsers])
            ->assertCanNotSeeTableRecords([$withUsers]);
    }

    public function test_created_at_filter_scopes_to_the_given_date_range()
    {
        $admin = $this->developer();

        $inRange = Department::factory()->create(['code' => $this->uniqueCode(), 'name' => '0In Range Dept']);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = Department::factory()->create(['code' => $this->uniqueCode(), 'name' => '0Out Of Range Dept']);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListDepartments::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_icon_options_render_svg_and_persian_label_matching_the_curated_resource_type_set()
    {
        $method = new ReflectionMethod(DepartmentFormPresenter::class, 'curatedIconOptions');
        $method->setAccessible(true);
        $options = $method->invoke(null);

        $this->assertCount(14, $options);
        $this->assertArrayHasKey('heroicon-o-key', $options);
        $this->assertStringContainsString('<svg', $options['heroicon-o-key']);
        $this->assertStringContainsString('کلید', $options['heroicon-o-key']);
    }

    public function test_icon_option_label_falls_back_to_readable_english_name_for_an_off_catalog_icon()
    {
        $method = new ReflectionMethod(DepartmentFormPresenter::class, 'curatedIconOptionLabel');
        $method->setAccessible(true);

        $curated = $method->invoke(null, 'heroicon-o-computer-desktop');
        $this->assertStringContainsString('<svg', $curated);
        $this->assertStringContainsString('میز', $curated);

        $offCatalog = $method->invoke(null, 'heroicon-o-academic-cap');
        $this->assertStringContainsString('<svg', $offCatalog);
        $this->assertStringContainsString('Academic Cap', $offCatalog);
        $this->assertStringNotContainsString('o-academic-cap', $offCatalog);
    }

    public function test_create_department_defaults_level_to_zero_and_subordinate_to_null()
    {
        $admin = $this->developer();
        $code = $this->uniqueCode();

        Livewire::actingAs($admin)->test(CreateDepartment::class)
            ->fillForm(['code' => $code, 'name' => 'Level Default', 'description' => 'T'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('departments', ['code' => $code, 'level' => 0, 'subordinate_to' => null]);
    }

    public function test_create_department_rejects_a_level_outside_zero_one_two()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateDepartment::class)
            ->fillForm(['code' => $this->uniqueCode(), 'name' => 'T', 'description' => 'T', 'level' => 5])
            ->call('create')
            ->assertHasFormErrors(['level']);
    }

    public function test_create_department_persists_a_subordinate_to_reference()
    {
        $admin = $this->developer();
        $parent = Department::factory()->create(['code' => $this->uniqueCode(), 'level' => 1]);
        $code = $this->uniqueCode();

        Livewire::actingAs($admin)->test(CreateDepartment::class)
            ->fillForm(['code' => $code, 'name' => 'Child', 'description' => 'T', 'level' => 2, 'subordinate_to' => $parent->code])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('departments', ['code' => $code, 'subordinate_to' => $parent->code, 'level' => 2]);
    }

    public function test_create_department_auto_corrects_level_zero_when_subordinate_to_is_set()
    {
        $admin = $this->developer();
        $parent = Department::factory()->create(['code' => $this->uniqueCode(), 'level' => 1]);
        $code = $this->uniqueCode();

        Livewire::actingAs($admin)->test(CreateDepartment::class)
            ->fillForm(['code' => $code, 'name' => 'Child', 'description' => 'T', 'level' => 0, 'subordinate_to' => $parent->code])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('departments', ['code' => $code, 'level' => 1]);
    }

    public function test_create_department_rejects_self_reference_in_subordinate_to()
    {
        $admin = $this->developer();
        $code = $this->uniqueCode();

        Livewire::actingAs($admin)->test(CreateDepartment::class)
            ->fillForm(['code' => $code, 'name' => 'T', 'description' => 'T', 'level' => 1, 'subordinate_to' => $code])
            ->call('create')
            ->assertHasFormErrors(['subordinate_to']);
    }

    public function test_edit_department_rejects_a_cyclic_subordinate_to()
    {
        $admin = $this->developer();
        $a = Department::factory()->create(['code' => $this->uniqueCode(), 'level' => 1]);
        $b = Department::factory()->create(['code' => $this->uniqueCode(), 'level' => 1, 'subordinate_to' => $a->code]);

        Livewire::actingAs($admin)->test(EditDepartment::class, ['record' => $a->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['subordinate_to' => $b->code])
            ->call('save')
            ->assertHasFormErrors(['subordinate_to']);
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Department::factory()->create(['code' => $this->uniqueCode(), 'name' => '0Group Test Dept']);

        Livewire::actingAs($admin)->test(ListDepartments::class)
            ->assertSuccessful()
            ->set('tableGrouping', 'users_count')
            ->assertSuccessful();
    }

    public function test_sorting_by_code_and_name_produces_correct_order()
    {
        $admin = $this->developer();

        $first = Department::factory()->create(['code' => 'AAA' . $this->uniqueCode(), 'name' => '0Sort First']);
        $second = Department::factory()->create(['code' => 'ZZZ' . $this->uniqueCode(), 'name' => '0Sort Second']);

        $component = Livewire::actingAs($admin)->test(ListDepartments::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('code')
            ->assertSuccessful();

        $codes = $component->instance()->getTableRecords()->pluck('code')->values()->all();
        $this->assertSame([$first->code, $second->code], array_values(array_intersect($codes, [$first->code, $second->code])));

        $component->sortTable('code', 'desc')->assertSuccessful();
        $codes = $component->instance()->getTableRecords()->pluck('code')->values()->all();
        $this->assertSame([$second->code, $first->code], array_values(array_intersect($codes, [$first->code, $second->code])));

        $component->sortTable('name')->assertSuccessful();
        $codes = $component->instance()->getTableRecords()->pluck('code')->values()->all();
        $this->assertSame([$first->code, $second->code], array_values(array_intersect($codes, [$first->code, $second->code])));
    }

    public function test_sorting_by_id_produces_ascending_and_descending_order()
    {
        $admin = $this->developer();
        $first = Department::factory()->create(['code' => $this->uniqueCode()])->refresh();
        $second = Department::factory()->create(['code' => $this->uniqueCode()])->refresh();

        $component = Livewire::actingAs($admin)->test(ListDepartments::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('id')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_level_produces_correct_order()
    {
        $admin = $this->developer();
        $low = Department::factory()->create(['code' => $this->uniqueCode(), 'level' => 0]);
        $high = Department::factory()->create(['code' => $this->uniqueCode(), 'level' => 1]);

        $component = Livewire::actingAs($admin)->test(ListDepartments::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('level')
            ->assertSuccessful();

        $codes = $component->instance()->getTableRecords()->pluck('code')->values()->all();
        $this->assertSame([$low->code, $high->code], array_values(array_intersect($codes, [$low->code, $high->code])));

        $component->sortTable('level', 'desc')->assertSuccessful();
        $codes = $component->instance()->getTableRecords()->pluck('code')->values()->all();
        $this->assertSame([$high->code, $low->code], array_values(array_intersect($codes, [$low->code, $high->code])));
    }

    public function test_sorting_by_created_at_produces_ascending_and_descending_order()
    {
        $admin = $this->developer();
        $old = Department::factory()->create(['code' => $this->uniqueCode()]);
        $old->forceFill(['created_at' => now()->subYears(5)])->saveQuietly();
        $new = Department::factory()->create(['code' => $this->uniqueCode()]);
        $new->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListDepartments::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('created_at')
            ->assertSuccessful();

        $codes = $component->instance()->getTableRecords()->pluck('code')->values()->all();
        $this->assertSame([$old->code, $new->code], array_values(array_intersect($codes, [$old->code, $new->code])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $codes = $component->instance()->getTableRecords()->pluck('code')->values()->all();
        $this->assertSame([$new->code, $old->code], array_values(array_intersect($codes, [$old->code, $new->code])));
    }

    public function test_sorting_by_users_count_produces_correct_order()
    {
        $admin = $this->developer();
        $empty = Department::factory()->create(['code' => $this->uniqueCode()]);
        $populated = Department::factory()->create(['code' => $this->uniqueCode()]);
        $member = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $member->id, 'department_id' => $populated->code]);

        $component = Livewire::actingAs($admin)->test(ListDepartments::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('users_count')
            ->assertSuccessful();

        $codes = $component->instance()->getTableRecords()->pluck('code')->values()->all();
        $this->assertSame([$empty->code, $populated->code], array_values(array_intersect($codes, [$empty->code, $populated->code])));

        $component->sortTable('users_count', 'desc')->assertSuccessful();
        $codes = $component->instance()->getTableRecords()->pluck('code')->values()->all();
        $this->assertSame([$populated->code, $empty->code], array_values(array_intersect($codes, [$empty->code, $populated->code])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\DepartmentResource\Pages\CreateDepartment::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\DepartmentResource\Pages\EditDepartment::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\DepartmentResource\Pages\ListDepartments::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\DepartmentResource\Pages\CreateDepartment::class, \App\Filament\Resources\DepartmentResource\Pages\EditDepartment::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }

        $managers = [\App\Filament\Resources\DepartmentResource\RelationManagers\AuthoritiesRelationManager::class, \App\Filament\Resources\DepartmentResource\RelationManagers\ReportsRelationManager::class, \App\Filament\Resources\DepartmentResource\RelationManagers\UsersRelationManager::class];

        foreach ($managers as $manager) {
            $this->assertSame(\App\Filament\RelationManagers\RelationManager::class, get_parent_class($manager));
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, class_uses_recursive($manager));
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/DepartmentResource/Schemas/Grouping/UserCountGroup.php'),
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
