<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\HrQualificationChart;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class HrQualificationChartTest extends TestCase
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

    private function widget(): HrQualificationChart
    {
        return (new ReflectionClass(HrQualificationChart::class))->newInstanceWithoutConstructor();
    }

    private function invoke(HrQualificationChart $obj, string $method, array $args = []): mixed
    {
        $m = new ReflectionMethod($obj, $method);
        $m->setAccessible(true);
        return $m->invokeArgs($obj, $args);
    }

    private function assertChartShape(array $result): void
    {
        $this->assertArrayHasKey('datasets', $result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertIsArray($result['datasets']);
        $this->assertIsArray($result['labels']);
        $labelCount = count($result['labels']);
        foreach ($result['datasets'] as $ds) {
            $this->assertArrayHasKey('data', $ds);
            $this->assertArrayHasKey('label', $ds);
            $this->assertCount($labelCount, $ds['data']);
            foreach ($ds['data'] as $v) {
                $this->assertIsInt($v);
                $this->assertGreaterThanOrEqual(0, $v);
            }
        }
    }

    public function test_get_data_returns_empty_when_no_filter_selected(): void
    {
        $obj = $this->widget();
        $obj->filters = [];
        $result = $this->invoke($obj, 'getData');
        $this->assertSame(['datasets' => [], 'labels' => []], $result);
    }

    public function test_get_data_dispatches_to_each_hr_module(): void
    {
        foreach (['hr_e' => 'getHrEData', 'hr_f' => 'getHrFData', 'hr_g' => 'getHrGData', 'hr_h' => 'getHrHData'] as $filter => $method) {
            $obj = $this->widget();
            $obj->filters = ['module' => $filter];
            $direct = $this->invoke($obj, $method);

            $obj2 = $this->widget();
            $obj2->filters = ['module' => $filter];
            $dispatched = $this->invoke($obj2, 'getData');

            $this->assertChartShape($dispatched);
            $this->assertNotEmpty($dispatched['datasets']);
            $this->assertSame($direct['labels'], $dispatched['labels']);
        }
    }

    public function test_get_type_returns_bar_for_every_module(): void
    {
        foreach (['hr_e', 'hr_f', 'hr_g', 'hr_h', null] as $module) {
            $obj = $this->widget();
            $obj->filters = ['module' => $module];
            $this->assertSame('bar', $this->invoke($obj, 'getType'));
        }
    }

    public function test_get_options_stacks_by_default_and_unstacks_only_for_hr_f(): void
    {
        foreach (['hr_e', 'hr_g', 'hr_h'] as $module) {
            $obj = $this->widget();
            $obj->filters = ['module' => $module];
            $opts = $this->invoke($obj, 'getOptions');
            $this->assertTrue($opts['scales']['x']['stacked']);
            $this->assertTrue($opts['scales']['y']['stacked']);
        }

        $obj = $this->widget();
        $obj->filters = ['module' => 'hr_f'];
        $optsF = $this->invoke($obj, 'getOptions');
        $this->assertFalse($optsF['scales']['x']['stacked']);
        $this->assertFalse($optsF['scales']['y']['stacked']);
    }
}
