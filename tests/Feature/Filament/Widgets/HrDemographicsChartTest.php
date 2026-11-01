<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\HrDemographicsChart;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HrDemographicsChartTest extends TestCase
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

    private function widget(): HrDemographicsChart
    {
        $rc = new \ReflectionClass(HrDemographicsChart::class);
        $obj = $rc->newInstanceWithoutConstructor();
        $obj->filters = [];
        return $obj;
    }

    private function invoke(object $obj, string $method): mixed
    {
        $rm = new \ReflectionMethod($obj, $method);
        $rm->setAccessible(true);
        return $rm->invoke($obj);
    }

    private function setFilters(object $obj, array $filters): void
    {
        $rp = new \ReflectionProperty(HrDemographicsChart::class, 'filters');
        $rp->setAccessible(true);
        $rp->setValue($obj, $filters);
    }

    public function test_get_data_dispatches_to_each_module(): void
    {
        foreach (['hr_a' => 'getHrAData', 'hr_b' => 'getHrBData', 'hr_c' => 'getHrCData', 'hr_d' => 'getHrDData'] as $module => $method) {
            $obj = $this->widget();
            $this->setFilters($obj, ['module' => $module]);
            $dispatched = $this->invoke($obj, 'getData');
            $direct = $this->invoke($obj, $method);
            $this->assertSame($direct, $dispatched);
        }
    }

    public function test_get_data_returns_empty_for_null_or_invalid_module(): void
    {
        $obj = $this->widget();
        $this->setFilters($obj, ['module' => null]);
        $this->assertSame(['datasets' => [], 'labels' => []], $this->invoke($obj, 'getData'));

        $obj2 = $this->widget();
        $this->setFilters($obj2, ['module' => 'zzz']);
        $this->assertSame(['datasets' => [], 'labels' => []], $this->invoke($obj2, 'getData'));

        $obj3 = $this->widget();
        $this->setFilters($obj3, []);
        $this->assertSame(['datasets' => [], 'labels' => []], $this->invoke($obj3, 'getData'));
    }

    public function test_get_type_returns_doughnut_for_hr_d_and_bar_otherwise(): void
    {
        $obj = $this->widget();
        $this->setFilters($obj, ['module' => 'hr_d']);
        $this->assertSame('doughnut', $this->invoke($obj, 'getType'));

        foreach (['hr_a', 'hr_b', 'hr_c'] as $module) {
            $o = $this->widget();
            $this->setFilters($o, ['module' => $module]);
            $this->assertSame('bar', $this->invoke($o, 'getType'));
        }

        $null = $this->widget();
        $this->setFilters($null, ['module' => null]);
        $this->assertSame('bar', $this->invoke($null, 'getType'));
    }

    public function test_get_options_removes_scales_for_hr_d_and_stacks_for_hr_a(): void
    {
        $hrD = $this->widget();
        $this->setFilters($hrD, ['module' => 'hr_d']);
        $optsD = $this->invoke($hrD, 'getOptions');
        $this->assertArrayNotHasKey('scales', $optsD);

        $hrA = $this->widget();
        $this->setFilters($hrA, ['module' => 'hr_a']);
        $optsA = $this->invoke($hrA, 'getOptions');
        $this->assertArrayHasKey('scales', $optsA);
        $this->assertTrue($optsA['scales']['x']['stacked'] ?? false);
        $this->assertTrue($optsA['scales']['y']['stacked'] ?? false);

        $hrB = $this->widget();
        $this->setFilters($hrB, ['module' => 'hr_b']);
        $optsB = $this->invoke($hrB, 'getOptions');
        $this->assertArrayHasKey('scales', $optsB);
        $this->assertArrayNotHasKey('stacked', $optsB['scales']['x']);
    }
}
