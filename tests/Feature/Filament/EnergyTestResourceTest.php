<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\EnergyResource\Pages\ListEnergyTests;
use App\Filament\Resources\EnergyResource\Pages\ViewEnergyTest;
use App\Models\EnergyTest;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class EnergyTestResourceTest extends TestCase
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

    public function test_read_only_list_renders_and_shows_factory_energy_test_row()
    {
        $admin = $this->developer();

        $test = EnergyTest::factory()->create(['overall_score' => 10]);

        Livewire::actingAs($admin)->test(ListEnergyTests::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$test->fresh()]);
    }

    public function test_read_only_view_page_renders_for_a_factory_energy_test_record()
    {
        $admin = $this->developer();

        $test = EnergyTest::factory()->create(['overall_score' => 12]);

        Livewire::actingAs($admin)->test(ViewEnergyTest::class, ['record' => $test->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_low_score_filter_scopes_list_to_tests_with_overall_score_at_or_below_eight()
    {
        $admin = $this->developer();

        $low = EnergyTest::factory()->create([
            'overall_score' => 4,
            'mind_score' => 1,
            'emotion_score' => 1,
            'physique_score' => 1,
            'soul_score' => 1,
        ]);

        $high = EnergyTest::factory()->create([
            'overall_score' => 16,
            'mind_score' => 4,
            'emotion_score' => 4,
            'physique_score' => 4,
            'soul_score' => 4,
        ]);

        Livewire::actingAs($admin)->test(ListEnergyTests::class)
            ->assertSuccessful()
            ->filterTable('low_score')
            ->assertCanSeeTableRecords([$low->fresh()])
            ->assertCanNotSeeTableRecords([$high->fresh()]);
    }

    public function test_last_month_filter_scopes_list_to_tests_completed_within_thirty_days()
    {
        $admin = $this->developer();

        $recent = EnergyTest::factory()->completedDaysAgo(5)->create(['overall_score' => 10]);

        $old = EnergyTest::factory()->completedDaysAgo(60)->create(['overall_score' => 10]);

        Livewire::actingAs($admin)->test(ListEnergyTests::class)
            ->assertSuccessful()
            ->filterTable('last_month')
            ->assertCanSeeTableRecords([$recent->fresh()])
            ->assertCanNotSeeTableRecords([$old->fresh()]);
    }

    public function test_score_range_filter_scopes_list_to_tests_within_overall_score_bounds()
    {
        $admin = $this->developer();

        $inRange = EnergyTest::factory()->create(['overall_score' => 10]);
        $outOfRange = EnergyTest::factory()->create(['overall_score' => 18]);

        Livewire::actingAs($admin)->test(ListEnergyTests::class)
            ->assertSuccessful()
            ->filterTable('score_range', ['min' => 5, 'max' => 12])
            ->assertCanSeeTableRecords([$inRange->fresh()])
            ->assertCanNotSeeTableRecords([$outOfRange->fresh()]);
    }

    public function test_completed_at_range_filter_scopes_list_to_tests_in_date_range()
    {
        $admin = $this->developer();

        $recent = EnergyTest::factory()->completedDaysAgo(5)->create(['overall_score' => 10]);
        $old = EnergyTest::factory()->completedDaysAgo(60)->create(['overall_score' => 10]);

        Livewire::actingAs($admin)->test(ListEnergyTests::class)
            ->assertSuccessful()
            ->filterTable('completed_at_range', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$recent->fresh()])
            ->assertCanNotSeeTableRecords([$old->fresh()]);
    }

    public function test_user_filter_scopes_list_to_tests_owned_by_selected_user()
    {
        $admin = $this->developer();

        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $other = User::factory()->create(['status' => 'active', 'role' => 'user']);

        $matching = EnergyTest::factory()->create(['user_id' => $owner->id, 'overall_score' => 10]);
        $nonMatching = EnergyTest::factory()->create(['user_id' => $other->id, 'overall_score' => 10]);

        Livewire::actingAs($admin)->test(ListEnergyTests::class)
            ->assertSuccessful()
            ->filterTable('user_id', $owner->id)
            ->assertCanSeeTableRecords([$matching->fresh()])
            ->assertCanNotSeeTableRecords([$nonMatching->fresh()]);
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        EnergyTest::factory()->create(['overall_score' => 10]);

        $component = Livewire::actingAs($admin)->test(ListEnergyTests::class)->assertSuccessful();

        foreach (['user.name', 'month_index'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }
    }

    public function test_sorting_by_completed_at_and_created_at_orders_records_correctly()
    {
        $admin = $this->developer();
        $older = EnergyTest::factory()->completedDaysAgo(60)->create(['overall_score' => 10]);
        $older->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();
        $newer = EnergyTest::factory()->completedDaysAgo(5)->create(['overall_score' => 10]);
        $newer->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListEnergyTests::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('completed_at')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('completed_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_sorting_by_score_columns_orders_records_correctly()
    {
        $admin = $this->developer();
        $low = EnergyTest::factory()->create([
            'mind_score' => 0,
            'emotion_score' => 0,
            'physique_score' => 0,
            'soul_score' => 0,
            'overall_score' => 0,
        ]);
        $high = EnergyTest::factory()->create([
            'mind_score' => 20,
            'emotion_score' => 20,
            'physique_score' => 20,
            'soul_score' => 20,
            'overall_score' => 20,
        ]);

        $component = Livewire::actingAs($admin)->test(ListEnergyTests::class)
            ->set('tableRecordsPerPage', 'all')
            ->assertSuccessful();

        foreach (['mind_score', 'emotion_score', 'physique_score', 'soul_score', 'overall_score'] as $column) {
            $component->sortTable($column)->assertSuccessful();
            $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
            $this->assertSame([$low->id, $high->id], array_values(array_intersect($ids, [$low->id, $high->id])), "ascending sort by {$column}");

            $component->sortTable($column, 'desc')->assertSuccessful();
            $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
            $this->assertSame([$high->id, $low->id], array_values(array_intersect($ids, [$low->id, $high->id])), "descending sort by {$column}");
        }
    }

    public function test_sorting_by_id_orders_records_correctly()
    {
        $admin = $this->developer();
        $first = EnergyTest::factory()->create(['overall_score' => 10]);
        $second = EnergyTest::factory()->create(['overall_score' => 10]);

        $component = Livewire::actingAs($admin)->test(ListEnergyTests::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('id')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_user_orders_records_correctly()
    {
        $admin = $this->developer();
        $userA = User::factory()->create(['status' => 'active', 'role' => 'user', 'name' => 'AAA-sort-user']);
        $userB = User::factory()->create(['status' => 'active', 'role' => 'user', 'name' => 'ZZZ-sort-user']);
        $first = EnergyTest::factory()->create(['user_id' => $userA->id, 'overall_score' => 10]);
        $second = EnergyTest::factory()->create(['user_id' => $userB->id, 'overall_score' => 10]);

        $component = Livewire::actingAs($admin)->test(ListEnergyTests::class)
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
            \App\Filament\Resources\EnergyResource\Pages\ListEnergyTests::class => \App\Filament\Pages\ListRecords::class,
            \App\Filament\Resources\EnergyResource\Pages\ViewEnergyTest::class => \App\Filament\Pages\ViewRecord::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/EnergyResource/Schemas/EnergyTestTablePresenter.php'),
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
