<?php

namespace Tests\Feature\Providers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FilamentServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
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

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    public function test_scripts_before_hook_renders_every_core_script_not_just_the_last_one(): void
    {
        $admin = $this->developer();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('theme-manager', false);
        $response->assertSee('topbar-autohide', false);
        $response->assertSee('filament-menu', false);
    }

    public function test_body_end_hook_renders_both_screen_saver_and_spinner_not_just_the_last_one(): void
    {
        $admin = $this->developer();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('global-app-loader', false);
        $response->assertSee('idleTimer', false);
    }
}
