<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AuthorityResource\Pages\CreateAuthority;
use App\Filament\Resources\AuthorityResource\Pages\EditAuthority;
use App\Filament\Resources\AuthorityResource\Pages\ListAuthorities;
use App\Models\Authority;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AuthorityResourceTest extends TestCase
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

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    public function test_list_renders_and_shows_factory_authority_record()
    {
        $admin = $this->developer();

        $authority = Authority::factory()->create(['sub_duty' => false]);

        Livewire::actingAs($admin)->test(ListAuthorities::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$authority]);
    }

    public function test_create_authority_persists_duty_and_impact_score()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateAuthority::class)
            ->fillForm([
                'sub_duty' => true,
                'details.duty' => '<p>Test duty description</p>',
                'details.impact_score' => 'high',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Authority::latest('id')->first();

        $this->assertSame(true, $created->sub_duty);
        $this->assertSame('high', $created->details['impact_score']);
        $this->assertStringContainsString('Test duty description', $created->details['duty']);
    }

    public function test_create_rejects_co_delegate_longer_than_255_characters()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateAuthority::class)
            ->fillForm([
                'sub_duty' => false,
                'details.co_delegate' => str_repeat('x', 256),
            ])
            ->call('create')
            ->assertHasFormErrors(['details.co_delegate' => 'max']);
    }

    public function test_edit_authority_updates_impact_score()
    {
        $admin = $this->developer();

        $authority = Authority::factory()->create([
            'sub_duty' => false,
            'details' => ['impact_score' => 'low'],
        ]);

        Livewire::actingAs($admin)->test(EditAuthority::class, ['record' => $authority->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['details.impact_score' => 'high'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('high', $authority->fresh()->details['impact_score']);
    }

    public function test_delete_action_removes_authority_record()
    {
        $admin = $this->developer();

        $authority = Authority::factory()->create();

        Livewire::actingAs($admin)->test(ListAuthorities::class)
            ->callTableAction(\Filament\Actions\DeleteAction::class, $authority);

        $this->assertDatabaseMissing('authorities', ['id' => $authority->id]);
    }

    public function test_impact_score_filter_scopes_list_to_matching_records()
    {
        $admin = $this->developer();

        $high = Authority::factory()->create(['details' => ['impact_score' => 'high']]);
        $low = Authority::factory()->create(['details' => ['impact_score' => 'low']]);

        Livewire::actingAs($admin)->test(ListAuthorities::class)
            ->filterTable('impact_score', 'high')
            ->assertCanSeeTableRecords([$high])
            ->assertCanNotSeeTableRecords([$low]);
    }

    public function test_sub_duty_ternary_filter_scopes_list_to_main_duty_records()
    {
        $admin = $this->developer();

        $main = Authority::factory()->create(['sub_duty' => false]);
        $sub = Authority::factory()->create(['sub_duty' => true]);

        Livewire::actingAs($admin)->test(ListAuthorities::class)
            ->filterTable('sub_duty', '0')
            ->assertCanSeeTableRecords([$main])
            ->assertCanNotSeeTableRecords([$sub]);
    }

    public function test_approved_delegation_filter_scopes_list_to_matching_records()
    {
        $admin = $this->developer();

        $decision = Authority::factory()->create(['details' => ['approved_delegation' => 'decision_implementation']]);
        $review = Authority::factory()->create(['details' => ['approved_delegation' => 'review_proposal']]);

        Livewire::actingAs($admin)->test(ListAuthorities::class)
            ->filterTable('approved_delegation', 'decision_implementation')
            ->assertCanSeeTableRecords([$decision])
            ->assertCanNotSeeTableRecords([$review]);
    }

    public function test_execution_procedure_filter_scopes_list_to_matching_records()
    {
        $admin = $this->developer();

        $yes = Authority::factory()->create(['details' => ['execution_procedure' => 'yes']]);
        $no = Authority::factory()->create(['details' => ['execution_procedure' => 'no']]);

        Livewire::actingAs($admin)->test(ListAuthorities::class)
            ->filterTable('execution_procedure', 'yes')
            ->assertCanSeeTableRecords([$yes])
            ->assertCanNotSeeTableRecords([$no]);
    }

    public function test_repeat_frequency_filter_scopes_list_to_matching_records()
    {
        $admin = $this->developer();

        $yearly = Authority::factory()->create(['details' => ['repeat_frequency' => 'yearly']]);
        $quarterly = Authority::factory()->create(['details' => ['repeat_frequency' => 'quarterly']]);

        Livewire::actingAs($admin)->test(ListAuthorities::class)
            ->filterTable('repeat_frequency', 'yearly')
            ->assertCanSeeTableRecords([$yearly])
            ->assertCanNotSeeTableRecords([$quarterly]);
    }

    public function test_department_filter_scopes_list_to_matching_records()
    {
        $admin = $this->developer();
        $departmentA = \App\Models\Department::factory()->create();
        $departmentB = \App\Models\Department::factory()->create();

        $inA = Authority::factory()->create(['department_id' => $departmentA->code]);
        $inB = Authority::factory()->create(['department_id' => $departmentB->code]);

        Livewire::actingAs($admin)->test(ListAuthorities::class)
            ->filterTable('department_id', $departmentA->code)
            ->assertCanSeeTableRecords([$inA])
            ->assertCanNotSeeTableRecords([$inB]);
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Authority::factory()->create(['details' => [
            'approved_delegation' => 'decision_implementation',
            'impact_score' => 'high',
            'execution_procedure' => 'yes',
            'repeat_frequency' => 'yearly',
        ]]);

        $component = Livewire::actingAs($admin)->test(ListAuthorities::class)->assertSuccessful();

        foreach (['department.description', 'approved_delegation', 'impact_score', 'execution_procedure', 'repeat_frequency'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }
    }

    public function test_sorting_by_id_produces_ascending_and_descending_order()
    {
        $admin = $this->developer();
        $first = Authority::factory()->create();
        $second = Authority::factory()->create();

        $component = Livewire::actingAs($admin)->test(ListAuthorities::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('id')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_created_at_produces_ascending_and_descending_order()
    {
        $admin = $this->developer();
        $old = Authority::factory()->create();
        $old->forceFill(['created_at' => now()->subYears(5)])->saveQuietly();
        $new = Authority::factory()->create();
        $new->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListAuthorities::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('created_at')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$old->id, $new->id], array_values(array_intersect($ids, [$old->id, $new->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$new->id, $old->id], array_values(array_intersect($ids, [$old->id, $new->id])));
    }

    public function test_sorting_by_department_name_produces_correct_order()
    {
        $admin = $this->developer();
        $deptA = \App\Models\Department::factory()->create(['code' => strtoupper(\Illuminate\Support\Str::random(8)), 'name' => 'AAA-sort-dept']);
        $deptZ = \App\Models\Department::factory()->create(['code' => strtoupper(\Illuminate\Support\Str::random(8)), 'name' => 'ZZZ-sort-dept']);
        $first = Authority::factory()->create(['department_id' => $deptA->code]);
        $second = Authority::factory()->create(['department_id' => $deptZ->code]);

        $component = Livewire::actingAs($admin)->test(ListAuthorities::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('department.name')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('department.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_user_name_produces_correct_order()
    {
        $admin = $this->developer();
        $userA = User::factory()->create(['name' => 'AAA-sort-user']);
        $userZ = User::factory()->create(['name' => 'ZZZ-sort-user']);
        $first = Authority::factory()->create(['user_id' => $userA->id]);
        $second = Authority::factory()->create(['user_id' => $userZ->id]);

        $component = Livewire::actingAs($admin)->test(ListAuthorities::class)
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
            \App\Filament\Resources\AuthorityResource\Pages\CreateAuthority::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\AuthorityResource\Pages\EditAuthority::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\AuthorityResource\Pages\ListAuthorities::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\AuthorityResource\Pages\CreateAuthority::class, \App\Filament\Resources\AuthorityResource\Pages\EditAuthority::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/AuthorityResource/Schemas/AuthorityTablePresenter.php'),
            base_path('app/Filament/Resources/AuthorityResource/Schemas/Grouping/JsonEnumGroup.php'),
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
