<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\OperationalChart;
use App\Models\Department;
use App\Models\EnergyTest;
use App\Models\Profile;
use App\Models\Resource;
use App\Models\Suggestion;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationalChartTest extends TestCase
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
        Cache::flush();
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

    private function widget(): OperationalChart
    {
        return (new \ReflectionClass(OperationalChart::class))->newInstanceWithoutConstructor();
    }

    private function invoke(object $obj, string $method, array $args = []): mixed
    {
        $m = new \ReflectionMethod($obj, $method);
        $m->setAccessible(true);
        return $m->invokeArgs($obj, $args);
    }

    private function deptUser(string $code): array
    {
        $dept = Department::factory()->create([
            'code' => $code,
            'name' => 'Dept-' . $code,
            'description' => '',
        ]);
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $code]);
        return [$dept, $user];
    }

    private function assertChartShape(array $result): void
    {
        $this->assertArrayHasKey('datasets', $result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertIsArray($result['datasets']);
        $this->assertIsArray($result['labels']);
    }

    public function test_module_a_data_shape_seeded_energy_and_pending_tasks_for_dept_then_all_depts(): void
    {
        [$dept, $user] = $this->deptUser('ZZA1');

        Task::factory()->count(3)->create(['assigned_to' => $user->id, 'user_id' => $user->id, 'status' => 'todo']);
        Task::factory()->count(2)->create(['assigned_to' => $user->id, 'user_id' => $user->id, 'status' => 'in-progress']);
        Task::factory()->create(['assigned_to' => $user->id, 'user_id' => $user->id, 'status' => 'done']);

        EnergyTest::factory()->create(['user_id' => $user->id, 'overall_score' => 10, 'completed_at' => now()]);
        EnergyTest::factory()->create(['user_id' => $user->id, 'overall_score' => 20, 'completed_at' => now()]);

        $scoped = $this->invoke($this->widget(), 'getModuleAData', ['ZZA1']);
        $this->assertChartShape($scoped);
        $this->assertCount(1, $scoped['labels']);
        $this->assertSame('Dept-ZZA1', $scoped['labels'][0]);
        $this->assertCount(1, $scoped['datasets'][0]['data']);
        $this->assertCount(1, $scoped['datasets'][1]['data']);
        $this->assertSame(5, (int) $scoped['datasets'][1]['data'][0]);
        $this->assertEqualsWithDelta(15.0, (float) $scoped['datasets'][0]['data'][0], 0.01);

        $all = $this->invoke($this->widget(), 'getModuleAData', ['']);
        $this->assertChartShape($all);
        $this->assertGreaterThanOrEqual(1, count($all['labels']));
        $idx = array_search('Dept-ZZA1', $all['labels'], true);
        $this->assertNotFalse($idx);
        $this->assertSame(5, (int) $all['datasets'][1]['data'][$idx]);
        $this->assertEqualsWithDelta(15.0, (float) $all['datasets'][0]['data'][$idx], 0.01);
        $this->assertSame(count($all['datasets'][0]['data']), count($all['labels']));
    }

    public function test_module_a_data_scope_filter_reduces_labels_to_single_dept(): void
    {
        [$dept, $user] = $this->deptUser('ZZA2');

        $all = $this->invoke($this->widget(), 'getModuleAData', ['']);
        $scoped = $this->invoke($this->widget(), 'getModuleAData', ['ZZA2']);

        $allCount = count($all['labels']);
        $this->assertGreaterThanOrEqual(1, $allCount);
        $this->assertSame(1, count($scoped['labels']));
        $this->assertSame('Dept-ZZA2', $scoped['labels'][0]);
        if ($allCount > 1) {
            $this->assertLessThan($allCount, count($scoped['labels']));
        }
    }

    public function test_module_b_data_avg_resolution_days_per_origin_dept(): void
    {
        [$dept, $user] = $this->deptUser('ZZB1');

        Ticket::factory()->create([
            'requester_id' => $user->id,
            'status' => 'closed',
            'effectiveness' => 'yes',
            'created_at' => now()->subDays(5),
            'completion_date' => now()->subDay(),
        ]);

        $scoped = $this->invoke($this->widget(), 'getModuleBData', ['ZZB1']);
        $this->assertChartShape($scoped);
        $this->assertCount(1, $scoped['labels']);
        $this->assertSame('Dept-ZZB1', $scoped['labels'][0]);
        $this->assertIsNumeric($scoped['datasets'][0]['data'][0]);
        $this->assertEqualsWithDelta(4.0, (float) $scoped['datasets'][0]['data'][0], 0.1);
        $this->assertSame(count($scoped['datasets'][0]['data']), count($scoped['labels']));
    }

    public function test_module_c_data_suggestion_funnel_matches_stage_translations_and_seeded_counts(): void
    {
        $expectedLabels = [
            'در انتظار بررسی',
            'نظرات تیم',
            'نظرات واحد',
            'در انتظار تصمیم',
            'تایید شده',
            'رد شده',
            'در حال بررسی',
            'بسته شده',
        ];

        $baseline = $this->invoke($this->widget(), 'getModuleCData', ['']);
        $basePending = (int) $baseline['datasets'][0]['data'][0];
        $baseAccepted = (int) $baseline['datasets'][0]['data'][4];
        $baseUnderReview = (int) $baseline['datasets'][0]['data'][6];
        $baseRejected = (int) $baseline['datasets'][0]['data'][5];

        Suggestion::factory()->count(2)->create(['stage' => 'pending']);
        Suggestion::factory()->count(3)->create(['stage' => 'accepted']);
        Suggestion::factory()->create(['stage' => 'under_review']);

        Cache::forget('op:module_c');
        $result = $this->invoke($this->widget(), 'getModuleCData', ['']);
        $this->assertChartShape($result);
        $this->assertCount(8, $result['labels']);
        $this->assertSame($expectedLabels, $result['labels']);
        $this->assertCount(8, $result['datasets'][0]['data']);
        $this->assertSame($basePending + 2, (int) $result['datasets'][0]['data'][0]);
        $this->assertSame($baseAccepted + 3, (int) $result['datasets'][0]['data'][4]);
        $this->assertSame($baseUnderReview + 1, (int) $result['datasets'][0]['data'][6]);
        $this->assertSame($baseRejected, (int) $result['datasets'][0]['data'][5]);
    }

    public function test_module_d_data_reservation_density_24_hours_and_seeded_counts(): void
    {
        [$dept, $user] = $this->deptUser('ZZD1');
        $resource = Resource::factory()->create();

        $baseline = $this->invoke($this->widget(), 'getModuleDData', ['']);
        $this->assertCount(24, $baseline['labels']);
        $this->assertCount(24, $baseline['datasets'][0]['data']);
        $expectedLabels = array_map(fn($h) => str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00', range(0, 23));
        $this->assertSame($expectedLabels, $baseline['labels']);

        $baseHour9 = (int) $baseline['datasets'][0]['data'][9];
        $baseHour14 = (int) $baseline['datasets'][0]['data'][14];

        $rows = [
            $this->resRow($user->id, $resource->id, 9),
            $this->resRow($user->id, $resource->id, 9),
            $this->resRow($user->id, $resource->id, 14),
        ];
        DB::table('reservations')->insert($rows);

        Cache::forget('op:module_d');
        $result = $this->invoke($this->widget(), 'getModuleDData', ['']);
        $this->assertChartShape($result);
        $this->assertCount(24, $result['labels']);
        $this->assertCount(24, $result['datasets'][0]['data']);
        $this->assertSame($baseHour9 + 2, (int) $result['datasets'][0]['data'][9]);
        $this->assertSame($baseHour14 + 1, (int) $result['datasets'][0]['data'][14]);
        foreach ($result['datasets'][0]['data'] as $value) {
            $this->assertGreaterThanOrEqual(0, (int) $value);
        }
    }

    public function test_get_data_dispatches_each_module_and_returns_empty_without_filter(): void
    {
        [$dept, $user] = $this->deptUser('ZZG1');
        $this->actingAs($user);

        $widget = $this->widget();

        $widget->filters = ['module' => 'module_a'];
        $a = $this->invoke($widget, 'getData');
        $this->assertChartShape($a);
        $this->assertContains('Dept-ZZG1', $a['labels']);

        $widget->filters = ['module' => 'module_b'];
        $b = $this->invoke($widget, 'getData');
        $this->assertChartShape($b);

        $widget->filters = ['module' => 'module_c'];
        $c = $this->invoke($widget, 'getData');
        $this->assertChartShape($c);
        $this->assertCount(8, $c['labels']);

        $widget->filters = ['module' => 'module_d'];
        $d = $this->invoke($widget, 'getData');
        $this->assertChartShape($d);
        $this->assertCount(24, $d['labels']);

        $widget->filters = [];
        $empty = $this->invoke($widget, 'getData');
        $this->assertSame(['datasets' => [], 'labels' => []], $empty);

        $widget->filters = ['module' => 'unknown_module'];
        $unknown = $this->invoke($widget, 'getData');
        $this->assertSame(['datasets' => [], 'labels' => []], $unknown);
    }

    public function test_module_a_data_excludes_guest_type_users_from_energy_avg_and_pending_tasks(): void
    {
        [$dept, $employee] = $this->deptUser('ZZAG');
        $guest = User::factory()->create(['status' => 'active', 'type' => 'employee']);
        Profile::factory()->create(['user_id' => $guest->id, 'department_id' => 'ZZAG']);

        EnergyTest::factory()->create(['user_id' => $guest->id, 'overall_score' => 100, 'completed_at' => now()]);
        EnergyTest::factory()->create(['user_id' => $employee->id, 'overall_score' => 0, 'completed_at' => now()]);

        Task::factory()->create(['assigned_to' => $guest->id, 'user_id' => $guest->id, 'status' => 'todo']);
        Task::factory()->create(['assigned_to' => $employee->id, 'user_id' => $employee->id, 'status' => 'todo']);
        User::whereKey($guest->id)->toBase()->update(['type' => 'guest']);

        $scoped = $this->invoke($this->widget(), 'getModuleAData', ['ZZAG']);
        $this->assertChartShape($scoped);
        $this->assertCount(1, $scoped['labels']);
        $this->assertEqualsWithDelta(0.0, (float) $scoped['datasets'][0]['data'][0], 0.01);
        $this->assertSame(1, (int) $scoped['datasets'][1]['data'][0]);
    }

    public function test_module_b_data_excludes_guest_type_requesters_from_resolution_avg(): void
    {
        [$dept, $employee] = $this->deptUser('ZZBG');
        $guest = User::factory()->create(['status' => 'active', 'type' => 'guest']);
        Profile::factory()->create(['user_id' => $guest->id, 'department_id' => 'ZZBG']);

        Ticket::factory()->create([
            'requester_id' => $guest->id,
            'status' => 'closed',
            'effectiveness' => 'yes',
            'created_at' => now()->subDays(10),
            'completion_date' => now()->subDay(),
        ]);
        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'status' => 'closed',
            'effectiveness' => 'yes',
            'created_at' => now(),
            'completion_date' => now(),
        ]);

        $scoped = $this->invoke($this->widget(), 'getModuleBData', ['ZZBG']);
        $this->assertChartShape($scoped);
        $this->assertCount(1, $scoped['labels']);
        $this->assertEqualsWithDelta(0.0, (float) $scoped['datasets'][0]['data'][0], 0.1);
    }

    public function test_module_a_caches_per_department_without_cross_dept_contamination(): void
    {
        [$deptA, $userA] = $this->deptUser('ZZ-CON-A');
        [$deptB, $userB] = $this->deptUser('ZZ-CON-B');

        Task::factory()->count(3)->create(['assigned_to' => $userA->id, 'user_id' => $userA->id, 'status' => 'todo']);
        Task::factory()->count(1)->create(['assigned_to' => $userB->id, 'user_id' => $userB->id, 'status' => 'todo']);

        $widget = $this->widget();

        $a1 = $this->invoke($widget, 'getModuleAData', ['ZZ-CON-A']);
        $b = $this->invoke($widget, 'getModuleAData', ['ZZ-CON-B']);
        $a2 = $this->invoke($widget, 'getModuleAData', ['ZZ-CON-A']);

        $this->assertSame(3, (int) $a1['datasets'][1]['data'][0]);
        $this->assertSame(1, (int) $b['datasets'][1]['data'][0]);
        $this->assertSame($a1, $a2, 'same dept arg must hit the same cached entry across an intervening other-dept call');
        $this->assertNotEquals($a1, $b, 'different dept args must resolve to different datasets (no arg-collapse contamination)');
    }

    private function resRow(int $userId, int $resourceId, int $hour): array
    {
        $start = Carbon::today()->setTime($hour, 0, 0);
        return [
            'user_id' => $userId,
            'resource_id' => $resourceId,
            'start_time' => $start->toDateTimeString(),
            'end_time' => $start->copy()->addHour()->toDateTimeString(),
            'is_full_day' => 0,
            'status' => 'active',
            'cancelled_by_id' => null,
            'cancelled_at' => null,
            'cancel_reason' => null,
            'parent_id' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    public function test_module_a_pending_tasks_count_only_active_work_of_active_users(): void
    {
        [$dept, $user] = $this->deptUser('ZZAT');
        [, $gone] = [null, User::factory()->create(['status' => 'active'])];
        Profile::factory()->create(['user_id' => $gone->id, 'department_id' => 'ZZAT']);
        $mk = fn(User $u, array $a = []) => Task::factory()->create(['assigned_to' => $u->id, 'user_id' => $u->id, 'status' => 'todo', 'deadline' => now()->addDays(9), ...$a]);

        $mk($user);
        $mk($user, ['archived_at' => now()]);
        $suspended = $mk($user);
        \App\Models\TaskDetail::factory()->create(['task_id' => $suspended->id, 'state' => 'suspension', 'responsible_user_id' => null]);
        $mk($user)->delete();
        $mk($gone);
        User::whereKey($gone->id)->toBase()->update(['status' => 'inactive']);

        $scoped = $this->invoke($this->widget(), 'getModuleAData', ['ZZAT']);

        $this->assertSame(1, (int) $scoped['datasets'][1]['data'][0]);
    }
}
