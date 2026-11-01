<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\ManagePreferencesWidget;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class ManagePreferencesWidgetTest extends TestCase
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

    public function test_get_preferences_columns_returns_expected_int(): void
    {
        $widget = (new ReflectionClass(ManagePreferencesWidget::class))->newInstanceWithoutConstructor();

        $method = new ReflectionMethod($widget, 'getPreferencesColumns');
        $method->setAccessible(true);

        $this->assertSame(4, $method->invoke($widget));
    }

    public function test_widget_mounts_and_renders_successfully(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $admin->id, 'is_super_admin' => true]);

        Livewire::actingAs($admin)->test(ManagePreferencesWidget::class)->assertSuccessful();
    }
}