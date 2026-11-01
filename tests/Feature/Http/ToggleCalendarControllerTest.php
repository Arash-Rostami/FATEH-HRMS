<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ToggleCalendarControllerTest extends TestCase
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

    public function test_redirects_a_guest(): void
    {
        $this->get('/toggle-calendar')->assertRedirect();
    }

    public function test_each_call_flips_the_persian_dates_preference_and_returns_the_new_value(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->get('/toggle-calendar')->assertOk()->assertExactJson(['persian' => false]);
        $this->assertFalse($user->fresh()->getPreference('persian_dates', true));

        $this->actingAs($user)->get('/toggle-calendar')->assertOk()->assertExactJson(['persian' => true]);
        $this->assertTrue($user->fresh()->getPreference('persian_dates', true));
    }
}
