<?php

namespace Tests\Feature\Rules;

use App\Models\Department;
use App\Models\Profile;
use App\Models\User;
use App\Rules\SuggestionSubmitterNotFromMaOrMg;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuggestionSubmitterNotFromMaOrMgTest extends TestCase
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

    private function fails(mixed $userId): bool
    {
        $failed = false;
        (new SuggestionSubmitterNotFromMaOrMg())->validate('user_id', $userId, function () use (&$failed): void {
            $failed = true;
        });

        return $failed;
    }

    public function test_ma_department_submitter_fails(): void
    {
        Department::firstOrCreate(['code' => 'MA'], ['name' => 'MA', 'description' => 'MA']);
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'MA', 'position' => 'manager']);

        $this->assertTrue($this->fails($user->id));
    }

    public function test_mg_department_submitter_fails(): void
    {
        Department::firstOrCreate(['code' => 'MG'], ['name' => 'MG', 'description' => 'MG']);
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'MG', 'position' => 'manager']);

        $this->assertTrue($this->fails($user->id));
    }

    public function test_non_ma_or_mg_department_submitter_passes(): void
    {
        Department::firstOrCreate(['code' => 'TST'], ['name' => 'TST', 'description' => 'TST']);
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'TST', 'position' => 'expert']);

        $this->assertFalse($this->fails($user->id));
    }

    public function test_submitter_without_profile_passes(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->assertFalse($this->fails($user->id));
    }

    public function test_unresolvable_value_falls_back_to_authenticated_ma_user_and_fails(): void
    {
        Department::firstOrCreate(['code' => 'MA'], ['name' => 'MA', 'description' => 'MA']);
        $authUser = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $authUser->id, 'department_id' => 'MA', 'position' => 'manager']);

        $this->actingAs($authUser);

        $this->assertTrue($this->fails(999999999));
    }
}
