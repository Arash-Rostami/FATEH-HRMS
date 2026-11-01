<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\HrEngagementChart;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HrEngagementChartTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        \Cache::flush();
    }

    protected function tearDown(): void
    {
        \Cache::flush();
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

    private function widget(): HrEngagementChart
    {
        return (new \ReflectionClass(HrEngagementChart::class))->newInstanceWithoutConstructor();
    }

    private function invokeMethod(HrEngagementChart $obj, string $method): mixed
    {
        $r = new \ReflectionMethod(HrEngagementChart::class, $method);
        $r->setAccessible(true);
        return $r->invoke($obj);
    }

    private function setFilters(HrEngagementChart $obj, ?array $filters): void
    {
        $obj->filters = $filters;
    }

    public function test_getData_dispatches_per_filter_and_empty_when_none(): void
    {
        $obj = $this->widget();

        $this->setFilters($obj, ['module' => 'hr_n']);
        $n = $this->invokeMethod($obj, 'getData');
        $this->assertSame($this->invokeMethod($this->widget(), 'getHrNData'), $n);

        $this->setFilters($obj, ['module' => 'hr_o']);
        $o = $this->invokeMethod($obj, 'getData');
        $this->assertSame($this->invokeMethod($this->widget(), 'getHrOData'), $o);

        $this->setFilters($obj, ['module' => 'hr_p']);
        $p = $this->invokeMethod($obj, 'getData');
        $this->assertSame($this->invokeMethod($this->widget(), 'getHrPData'), $p);

        $this->setFilters($obj, ['module' => 'hr_q']);
        $q = $this->invokeMethod($obj, 'getData');
        $this->assertSame($this->invokeMethod($this->widget(), 'getHrQData'), $q);

        foreach ([['module' => null], ['module' => 'bogus'], [], null] as $filters) {
            $this->setFilters($obj, $filters);
            $empty = $this->invokeMethod($obj, 'getData');
            $this->assertSame(['datasets' => [], 'labels' => []], $empty);
        }
    }

    public function test_get_type_returns_bar_for_every_module(): void
    {
        foreach (['hr_n', 'hr_o', 'hr_p', 'hr_q', null] as $module) {
            $obj = $this->widget();
            $this->setFilters($obj, ['module' => $module]);
            $this->assertSame('bar', $this->invokeMethod($obj, 'getType'));
        }
    }

    public function test_get_options_stacks_hr_n_and_unstacks_hr_o_p_q_keeping_begin_at_zero(): void
    {
        $obj = $this->widget();
        $this->setFilters($obj, ['module' => 'hr_n']);
        $optsN = $this->invokeMethod($obj, 'getOptions');
        $this->assertTrue($optsN['scales']['x']['stacked']);
        $this->assertTrue($optsN['scales']['x']['beginAtZero']);
        $this->assertTrue($optsN['scales']['y']['stacked']);

        foreach (['hr_o', 'hr_p', 'hr_q'] as $module) {
            $o = $this->widget();
            $this->setFilters($o, ['module' => $module]);
            $opts = $this->invokeMethod($o, 'getOptions');
            $this->assertFalse($opts['scales']['x']['stacked']);
            $this->assertFalse($opts['scales']['y']['stacked']);
            $this->assertTrue($opts['scales']['x']['beginAtZero']);
        }
    }
}
