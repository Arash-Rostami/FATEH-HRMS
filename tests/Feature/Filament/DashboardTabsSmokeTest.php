<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\Dashboard;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTabsSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('fa');
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

    public function test_default_tab_mounts_only_overview_widgets(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $admin->id, 'is_super_admin' => true]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertSuccessful();
        $response->assertSee('نمای کلی');
        $response->assertSee('تحلیل‌های نموداری');
        $response->assertSee('App\\Filament\\Widgets\\AccountWidget', false);
        $response->assertDontSee('App\\Filament\\Widgets\\HrDemographicsChart', false);
        $response->assertDontSee('App\\Filament\\Widgets\\StructuralChart', false);
    }

    public function test_switching_to_charts_tab_mounts_only_chart_widgets(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $admin->id, 'is_super_admin' => true]);

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->set('activeDashboardTab', 'charts')
            ->assertSuccessful()
            ->assertSee('App\\Filament\\Widgets\\HrDemographicsChart', false)
            ->assertSee('App\\Filament\\Widgets\\StructuralChart', false)
            ->assertDontSee('App\\Filament\\Widgets\\AccountWidget', false);
    }
}
