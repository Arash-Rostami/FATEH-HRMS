<?php

namespace Tests\Feature\Rules;

use App\Rules\TaskLabelLength;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskLabelLengthTest extends TestCase
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

    private function fails(mixed $value): bool
    {
        $failed = false;
        (new TaskLabelLength())->validate('labels', $value, function () use (&$failed): void {
            $failed = true;
        });

        return $failed;
    }

    public function test_label_over_thirty_chars_fails(): void
    {
        $this->assertTrue($this->fails(['short', str_repeat('x', 31)]));
    }

    public function test_label_at_thirty_chars_passes(): void
    {
        $this->assertFalse($this->fails([str_repeat('x', 30)]));
    }

    public function test_non_array_value_passes(): void
    {
        $this->assertFalse($this->fails('not-an-array'));
        $this->assertFalse($this->fails(null));
    }

    public function test_empty_label_array_passes(): void
    {
        $this->assertFalse($this->fails([]));
    }

    public function test_non_string_tag_is_skipped(): void
    {
        $this->assertFalse($this->fails([123, null, ['nested']]));
    }
}