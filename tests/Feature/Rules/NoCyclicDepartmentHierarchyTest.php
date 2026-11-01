<?php

namespace Tests\Feature\Rules;

use App\Models\Department;
use App\Rules\NoCyclicDepartmentHierarchy;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NoCyclicDepartmentHierarchyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::flush();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::flush();
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

    private function fails(NoCyclicDepartmentHierarchy $rule, mixed $value): bool
    {
        $failed = false;
        $rule->validate('subordinate_to', $value, function () use (&$failed): void {
            $failed = true;
        });

        return $failed;
    }

    private function forgetDepartmentCache(): void
    {
        Cache::forget(ModelCacheVersion::key(Department::class, 'department_models'));
    }

    private function seedChain(): void
    {
        Department::factory()->create(['code' => 'ZZA', 'level' => 0, 'subordinate_to' => null]);
        Department::factory()->create(['code' => 'ZZC', 'level' => 2, 'subordinate_to' => 'ZZA']);
        Department::factory()->create(['code' => 'ZZB', 'level' => 1, 'subordinate_to' => 'ZZC']);
        $this->forgetDepartmentCache();
    }

    public function test_no_cycle_passes(): void
    {
        $this->seedChain();

        $this->assertFalse($this->fails(new NoCyclicDepartmentHierarchy('ZZB'), 'ZZA'));
        $this->assertFalse($this->fails(new NoCyclicDepartmentHierarchy('ZZC'), 'ZZA'));
        $this->assertFalse($this->fails(new NoCyclicDepartmentHierarchy('ZZB'), 'ZZC'));
    }

    public function test_direct_self_reference_fails(): void
    {
        $this->seedChain();

        $this->assertTrue($this->fails(new NoCyclicDepartmentHierarchy('ZZA'), 'ZZA'));
    }

    public function test_three_node_cycle_fails(): void
    {
        $this->seedChain();

        $this->assertTrue($this->fails(new NoCyclicDepartmentHierarchy('ZZA'), 'ZZB'));
    }

    public function test_assigned_parent_that_is_a_descendant_of_the_code_fails(): void
    {
        $this->seedChain();

        $this->assertTrue($this->fails(new NoCyclicDepartmentHierarchy('ZZC'), 'ZZB'));
    }

    public function test_null_code_and_null_value_pass_without_false_rejection(): void
    {
        $this->seedChain();

        $this->assertFalse($this->fails(new NoCyclicDepartmentHierarchy(null), 'ZZA'));
        $this->assertFalse($this->fails(new NoCyclicDepartmentHierarchy('ZZA'), null));
    }

    public function test_blank_and_non_existent_target_pass_without_false_rejection(): void
    {
        $this->seedChain();

        $this->assertFalse($this->fails(new NoCyclicDepartmentHierarchy('ZZA'), ''));
        $this->assertFalse($this->fails(new NoCyclicDepartmentHierarchy('ZZA'), 'ZZZ'));
    }

    public function test_message_is_translated_without_raw_placeholders(): void
    {
        $this->seedChain();
        $this->app->setLocale('fa');

        $message = null;
        (new NoCyclicDepartmentHierarchy('ZZA'))
            ->validate('subordinate_to', 'ZZB', function (string $m) use (&$message): void {
                $message = $m;
            });

        $this->assertNotNull($message);
        $this->assertNotSame('', $message);
        $this->assertStringContainsString('زیرمجموعه', $message);
        $this->assertStringNotContainsString(':', $message);
    }
}