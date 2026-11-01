<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Resources\ProfileResource\Enums\Position;
use App\Filament\Widgets\HrUnitHealthChart;
use App\Models\Department;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class HrUnitHealthChartTest extends TestCase
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

    private function widget(): HrUnitHealthChart
    {
        return (new ReflectionClass(HrUnitHealthChart::class))->newInstanceWithoutConstructor();
    }

    private function invoke(object $obj, string $method, array $args = []): mixed
    {
        $rm = new ReflectionMethod($obj, $method);
        $rm->setAccessible(true);

        return $rm->invokeArgs($obj, $args);
    }

    private function setFilters(object $obj, ?array $filters): void
    {
        $rp = new ReflectionProperty($obj, 'filters');
        $rp->setAccessible(true);
        $rp->setValue($obj, $filters);
    }

    private function topTenThreshold(): int
    {
        $counts = DB::table('profiles')
            ->select('department_id', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->orderByDesc('cnt')
            ->limit(10)
            ->pluck('cnt')
            ->all();

        return $counts === [] ? 0 : (int) min($counts);
    }

    private function seedDept(string $code, string $name, ?string $description, int $profileCount, array $profileAttrs = []): Department
    {
        $dept = Department::create(['code' => $code, 'name' => $name, 'description' => $description]);
        $threshold = $this->topTenThreshold();
        $count = max($profileCount, $threshold + 3);
        for ($i = 0; $i < $count; $i++) {
            Profile::factory()->create(array_merge([
                'department_id' => $code,
                'user_id' => User::factory(),
                'personnel_id' => 'PC-' . $code . '-' . $i . '-' . uniqid(),
            ], $profileAttrs));
        }

        return $dept;
    }

    public function test_get_data_dispatches_by_module_filter(): void
    {
        $this->seedDept('ZZ-D', 'Unit D', 'Unit D Desc', 3, [
            'position' => Position::Employee->value,
            'gender' => 'male',
            'employment_status' => 'working',
            'birthdate' => now()->subYears(30)->format('Y-m-d'),
        ]);

        $map = [
            'hr_i' => 'getHrIData',
            'hr_j' => 'getHrJData',
            'hr_k' => 'getHrKData',
            'hr_l' => 'getHrLData',
            'hr_m' => 'getHrMData',
        ];

        foreach ($map as $module => $method) {
            $obj = $this->widget();
            $this->setFilters($obj, ['module' => $module]);
            $viaDispatch = $this->invoke($obj, 'getData');
            $direct = $this->invoke($obj, $method);

            $this->assertSame($direct, $viaDispatch);
            $this->assertArrayHasKey('datasets', $viaDispatch);
            $this->assertArrayHasKey('labels', $viaDispatch);
            $this->assertNotEmpty($viaDispatch['labels']);
        }
    }

    public function test_get_data_returns_empty_without_filter(): void
    {
        $obj = $this->widget();
        $this->setFilters($obj, null);
        $result = $this->invoke($obj, 'getData');

        $this->assertSame(['datasets' => [], 'labels' => []], $result);
    }

    public function test_get_type_returns_bar_for_every_module(): void
    {
        foreach (['hr_i', 'hr_j', 'hr_k', 'hr_l', 'hr_m', null] as $module) {
            $obj = $this->widget();
            $this->setFilters($obj, ['module' => $module]);
            $this->assertSame('bar', $this->invoke($obj, 'getType'));
        }
    }

    public function test_get_options_stacks_by_default_hr_i_flips_axis_hr_m_unstacks_with_max(): void
    {
        foreach (['hr_j', 'hr_k', 'hr_l'] as $module) {
            $obj = $this->widget();
            $this->setFilters($obj, ['module' => $module]);
            $opts = $this->invoke($obj, 'getOptions');
            $this->assertTrue($opts['scales']['x']['stacked']);
            $this->assertTrue($opts['scales']['y']['stacked']);
            $this->assertArrayNotHasKey('indexAxis', $opts);
        }

        $hrI = $this->widget();
        $this->setFilters($hrI, ['module' => 'hr_i']);
        $optsI = $this->invoke($hrI, 'getOptions');
        $this->assertSame('y', $optsI['indexAxis']);
        $this->assertTrue($optsI['scales']['x']['beginAtZero']);

        $hrM = $this->widget();
        $this->setFilters($hrM, ['module' => 'hr_m']);
        $optsM = $this->invoke($hrM, 'getOptions');
        $this->assertFalse($optsM['scales']['x']['stacked']);
        $this->assertFalse($optsM['scales']['y']['stacked']);
        $this->assertTrue($optsM['scales']['x']['beginAtZero']);
        $this->assertSame(100, $optsM['scales']['x']['max']);
    }
}
