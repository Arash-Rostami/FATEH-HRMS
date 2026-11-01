<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\StructuralChart;
use App\Models\Department;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Report;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StructuralChartTest extends TestCase
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

    private function widget(): StructuralChart
    {
        return (new \ReflectionClass(StructuralChart::class))->newInstanceWithoutConstructor();
    }

    private function invoke(StructuralChart $widget, string $method, string $arg): array
    {
        $ref = new \ReflectionMethod(StructuralChart::class, $method);
        $ref->setAccessible(true);
        return $ref->invoke($widget, $arg);
    }

    private function getData(StructuralChart $widget): array
    {
        $ref = new \ReflectionMethod(StructuralChart::class, 'getData');
        $ref->setAccessible(true);
        return $ref->invoke($widget);
    }

    private function setFilters(StructuralChart $widget, ?string $module): void
    {
        $widget->filters = ['module' => $module];
    }

    private function dept(string $code, string $name): Department
    {
        return Department::factory()->create(['code' => $code, 'name' => $name, 'description' => $name]);
    }

    private function userInDept(string $deptCode): User
    {
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $deptCode]);
        return $user;
    }

    public function test_module_e_counts_open_tasks_and_tickets_per_department(): void
    {
        $dept = $this->dept('ZZ-E', 'ZZ Module E');
        $user = $this->userInDept($dept->code);

        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'todo']);
        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'in-progress']);
        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'done']);
        Ticket::factory()->create(['assigned_to' => $user->id, 'status' => 'open']);
        Ticket::factory()->create(['assigned_to' => $user->id, 'status' => 'closed', 'effectiveness' => 'resolved']);

        $result = $this->invoke($this->widget(), 'getModuleEData', $dept->code);

        $this->assertArrayHasKey('datasets', $result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertCount(1, $result['labels']);
        $this->assertSame('ZZ Module E', $result['labels'][0]);
        $this->assertCount(2, $result['datasets']);
        $this->assertSame(2, $result['datasets'][0]['data'][0]);
        $this->assertSame(1, $result['datasets'][1]['data'][0]);
    }

    public function test_module_f_combines_gender_and_employment_status_labels(): void
    {
        $dept = $this->dept('ZZ-F', 'ZZ Module F');

        Profile::factory()->create(['department_id' => $dept->code, 'gender' => 'male', 'employment_status' => 'working']);
        Profile::factory()->create(['department_id' => $dept->code, 'gender' => 'female', 'employment_status' => 'probational']);
        Profile::factory()->create(['department_id' => $dept->code, 'gender' => 'male', 'employment_status' => 'terminated']);

        $result = $this->invoke($this->widget(), 'getModuleFData', $dept->code);

        $this->assertArrayHasKey('datasets', $result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertCount(3, $result['labels']);
        $this->assertContains('آقا - فعال', $result['labels']);
        $this->assertContains('خانم - آزمایشی', $result['labels']);
        $this->assertContains('آقا - خاتمه‌یافته', $result['labels']);
        $this->assertSame(count($result['labels']), count($result['datasets'][0]['data']));
        foreach ($result['datasets'][0]['data'] as $value) {
            $this->assertIsInt($value);
            $this->assertGreaterThanOrEqual(0, $value);
        }
    }

    public function test_module_g_returns_30_days_and_increments_today_bucket(): void
    {
        $widget = $this->widget();
        $baseline = $this->invoke($widget, 'getModuleGData', '');

        $this->assertCount(30, $baseline['labels']);
        $this->assertCount(30, $baseline['datasets'][0]['data']);
        foreach ($baseline['datasets'][0]['data'] as $value) {
            $this->assertIsInt($value);
            $this->assertGreaterThanOrEqual(0, $value);
        }

        $user = User::factory()->create(['status' => 'active']);
        Post::factory()->create(['user_id' => $user->id]);
        Cache::flush();

        $after = $this->invoke($this->widget(), 'getModuleGData', '');
        $this->assertCount(30, $after['datasets'][0]['data']);
        $this->assertSame($baseline['datasets'][0]['data'][29] + 1, $after['datasets'][0]['data'][29]);
    }

    public function test_module_h_limits_to_ten_departments_and_counts_reports(): void
    {
        $result = $this->invoke($this->widget(), 'getModuleHData', '');

        $this->assertArrayHasKey('datasets', $result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertLessThanOrEqual(10, count($result['labels']));
        $this->assertSame(count($result['labels']), count($result['datasets'][0]['data']));
        $this->assertSame(count($result['labels']), count($result['datasets'][1]['data']));

        $again = $this->invoke($this->widget(), 'getModuleHData', '');
        $this->assertSame($result['labels'], $again['labels']);

        $dept = $this->dept('ZZ-H', 'ZZ Module H');
        $user = $this->userInDept($dept->code);
        Report::factory()->create(['user_id' => $user->id, 'department_id' => $dept->code]);

        $scoped = $this->invoke($this->widget(), 'getModuleHData', $dept->code);
        $this->assertCount(1, $scoped['labels']);
        $this->assertSame('ZZ Module H', $scoped['labels'][0]);
        $this->assertGreaterThanOrEqual(1, $scoped['datasets'][1]['data'][0]);
        $this->assertGreaterThanOrEqual(1, $scoped['datasets'][0]['data'][0]);
    }

    public function test_module_i_distributes_tenure_into_five_bands(): void
    {
        $dept = $this->dept('ZZ-I', 'ZZ Module I');

        Profile::factory()->create(['department_id' => $dept->code, 'start_date' => now()->subMonths(6)->toDateString(), 'employment_status' => 'working']);
        Profile::factory()->create(['department_id' => $dept->code, 'start_date' => now()->subYears(1)->subMonths(6)->toDateString(), 'employment_status' => 'working']);
        Profile::factory()->create(['department_id' => $dept->code, 'start_date' => now()->subYears(3)->subMonths(6)->toDateString(), 'employment_status' => 'working']);
        Profile::factory()->create(['department_id' => $dept->code, 'start_date' => now()->subYears(7)->toDateString(), 'employment_status' => 'working']);
        Profile::factory()->create(['department_id' => $dept->code, 'start_date' => now()->subYears(11)->toDateString(), 'employment_status' => 'working']);

        $result = $this->invoke($this->widget(), 'getModuleIData', $dept->code);

        $this->assertArrayHasKey('datasets', $result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertCount(5, $result['labels']);
        $this->assertCount(5, $result['datasets'][0]['data']);
        $data = $result['datasets'][0]['data'];
        $this->assertGreaterThanOrEqual(1, $data[0]);
        $this->assertGreaterThanOrEqual(1, $data[1]);
        $this->assertGreaterThanOrEqual(1, $data[2]);
        $this->assertGreaterThanOrEqual(1, $data[3]);
        $this->assertGreaterThanOrEqual(1, $data[4]);
    }

    public function test_module_i_excludes_terminated_employees(): void
    {
        $dept = $this->dept('ZZ-IT', 'ZZ Module I Term');

        Profile::factory()->create(['department_id' => $dept->code, 'start_date' => now()->subYears(11)->toDateString(), 'employment_status' => 'terminated']);

        $result = $this->invoke($this->widget(), 'getModuleIData', $dept->code);

        $this->assertCount(5, $result['datasets'][0]['data']);
        $this->assertSame([0, 0, 0, 0, 0], array_map('intval', $result['datasets'][0]['data']));
    }

    public function test_module_e_excludes_guest_type_users_from_task_and_ticket_counts(): void
    {
        $dept = $this->dept('ZZ-EG', 'ZZ Module E Guest');
        $employee = $this->userInDept($dept->code);
        $guest = User::factory()->create(['status' => 'active', 'type' => 'employee']);
        Profile::factory()->create(['user_id' => $guest->id, 'department_id' => $dept->code]);

        Task::factory()->create(['assigned_to' => $guest->id, 'status' => 'todo']);
        Task::factory()->create(['assigned_to' => $employee->id, 'status' => 'todo']);
        Ticket::factory()->create(['assigned_to' => $guest->id, 'status' => 'open', 'extra' => ['target_department' => $dept->code]]);
        Ticket::factory()->create(['assigned_to' => $employee->id, 'status' => 'open', 'extra' => ['target_department' => $dept->code]]);
        User::whereKey($guest->id)->toBase()->update(['type' => 'guest']);

        $result = $this->invoke($this->widget(), 'getModuleEData', $dept->code);

        $this->assertCount(1, $result['labels']);
        $this->assertSame(1, $result['datasets'][0]['data'][0]);
        $this->assertSame(1, $result['datasets'][1]['data'][0]);
    }

    public function test_module_f_excludes_guest_type_users_from_gender_status_counts(): void
    {
        $dept = $this->dept('ZZ-FG', 'ZZ Module F Guest');
        $guest = User::factory()->create(['status' => 'active', 'type' => 'guest']);
        Profile::factory()->create(['user_id' => $guest->id, 'department_id' => $dept->code, 'gender' => 'male', 'employment_status' => 'working']);
        Profile::factory()->create(['department_id' => $dept->code, 'gender' => 'female', 'employment_status' => 'working']);

        $result = $this->invoke($this->widget(), 'getModuleFData', $dept->code);

        $this->assertNotContains('آقا - فعال', $result['labels']);
        $this->assertContains('خانم - فعال', $result['labels']);
    }

    public function test_module_h_excludes_guest_type_users_from_user_and_report_counts(): void
    {
        $dept = $this->dept('ZZ-HG', 'ZZ Module H Guest');
        $guest = User::factory()->create(['status' => 'active', 'type' => 'guest']);
        Profile::factory()->create(['user_id' => $guest->id, 'department_id' => $dept->code]);
        $employee = $this->userInDept($dept->code);
        Report::factory()->create(['user_id' => $guest->id, 'department_id' => $dept->code]);
        Report::factory()->create(['user_id' => $employee->id, 'department_id' => $dept->code]);

        $scoped = $this->invoke($this->widget(), 'getModuleHData', $dept->code);

        $this->assertCount(1, $scoped['labels']);
        $this->assertSame(1, $scoped['datasets'][0]['data'][0]);
        $this->assertSame(1, $scoped['datasets'][1]['data'][0]);
    }

    public function test_module_i_excludes_guest_type_users_from_tenure_bands(): void
    {
        $dept = $this->dept('ZZ-IG', 'ZZ Module I Guest');
        $guest = User::factory()->create(['status' => 'active', 'type' => 'guest']);
        Profile::factory()->create(['user_id' => $guest->id, 'department_id' => $dept->code, 'start_date' => now()->subYears(11)->toDateString(), 'employment_status' => 'working']);
        Profile::factory()->create(['department_id' => $dept->code, 'start_date' => now()->subYears(11)->toDateString(), 'employment_status' => 'working']);

        $result = $this->invoke($this->widget(), 'getModuleIData', $dept->code);

        $data = array_map('intval', $result['datasets'][0]['data']);
        $this->assertSame([0, 0, 0, 0, 1], $data);
    }

    public function test_scope_condition_filters_to_single_department(): void
    {
        $deptA = $this->dept('ZZ-SA', 'ZZ Scope A');
        $deptB = $this->dept('ZZ-SB', 'ZZ Scope B');
        $userA = $this->userInDept($deptA->code);
        $userB = $this->userInDept($deptB->code);

        Task::factory()->create(['assigned_to' => $userA->id, 'status' => 'todo']);
        Task::factory()->create(['assigned_to' => $userB->id, 'status' => 'todo']);

        $resultA = $this->invoke($this->widget(), 'getModuleEData', $deptA->code);
        $resultB = $this->invoke($this->widget(), 'getModuleEData', $deptB->code);

        $this->assertCount(1, $resultA['labels']);
        $this->assertSame('ZZ Scope A', $resultA['labels'][0]);
        $this->assertSame(1, $resultA['datasets'][0]['data'][0]);

        $this->assertCount(1, $resultB['labels']);
        $this->assertSame('ZZ Scope B', $resultB['labels'][0]);
        $this->assertSame(1, $resultB['datasets'][0]['data'][0]);
    }

    public function test_get_data_returns_empty_when_no_module_selected(): void
    {
        $widget = $this->widget();
        $this->setFilters($widget, null);

        $result = $this->getData($widget);

        $this->assertSame(['datasets' => [], 'labels' => []], $result);
    }

    public function test_get_data_dispatches_to_each_module_with_scope(): void
    {
        $dept = $this->dept('ZZ-D', 'ZZ Dispatch');
        $user = $this->userInDept($dept->code);
        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'todo']);
        $this->actingAs($user);

        $deptNameScoped = ['module_e', 'module_h'];
        foreach (['module_e', 'module_f', 'module_g', 'module_h', 'module_i'] as $module) {
            $widget = $this->widget();
            $this->setFilters($widget, $module);

            $result = $this->getData($widget);

            $this->assertArrayHasKey('datasets', $result, "module {$module} missing datasets");
            $this->assertArrayHasKey('labels', $result, "module {$module} missing labels");
            $this->assertIsArray($result['datasets']);
            $this->assertIsArray($result['labels']);

            if (in_array($module, $deptNameScoped, true)) {
                $this->assertContains('ZZ Dispatch', $result['labels'], "module {$module} did not apply dept scope");
            }
            if ($module === 'module_f') {
                $this->assertCount(1, $result['labels'], 'module_f did not scope to single dept profile combo');
            }
        }
    }

    public function test_module_e_caches_per_department_without_cross_dept_contamination(): void
    {
        $deptA = $this->dept('ZZ-CON-A', 'ContamA');
        $deptB = $this->dept('ZZ-CON-B', 'ContamB');

        $userA = $this->userInDept($deptA->code);
        Task::factory()->create(['assigned_to' => $userA->id, 'status' => 'todo']);

        $widget = $this->widget();

        $a1 = $this->invoke($widget, 'getModuleEData', $deptA->code);
        $b = $this->invoke($widget, 'getModuleEData', $deptB->code);
        $a2 = $this->invoke($widget, 'getModuleEData', $deptA->code);

        $this->assertSame($a1, $a2, 'same dept arg must hit the same cached entry across an intervening other-dept call');
        $this->assertNotEquals($a1, $b, 'different dept args must resolve to different datasets (no arg-collapse contamination)');
    }
}