<?php

namespace Tests\Feature\Traits;

use App\Filament\Resources\ReservationPolicyResource;
use App\Filament\Resources\ReservationResource;
use App\Filament\Resources\ResourceResource;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FilamentResourceSwitchTest extends TestCase
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

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    public function test_switch_actions_list_every_viewable_resource_of_the_group_with_snake_names_and_urls(): void
    {
        $this->actingAs($this->developer());

        $actions = ReservationResource::switchActions(ReservationResource::class);

        $this->assertSame(
            ['switch_reservation_resource', 'switch_reservation_policy_resource', 'switch_resource_resource'],
            array_map(fn ($action) => $action->getName(), $actions),
        );

        $this->assertSame(ReservationResource::getUrl(), $actions[0]->getUrl());
        $this->assertSame(ReservationPolicyResource::getUrl(), $actions[1]->getUrl());
        $this->assertSame(ResourceResource::getUrl(), $actions[2]->getUrl());
    }

    public function test_only_the_active_resource_is_primary_and_solid(): void
    {
        $this->actingAs($this->developer());

        $actions = ReservationResource::switchActions(ReservationPolicyResource::class);

        $this->assertSame('gray', $actions[0]->getColor());
        $this->assertTrue($actions[0]->isOutlined());
        $this->assertSame('primary', $actions[1]->getColor());
        $this->assertFalse($actions[1]->isOutlined());
        $this->assertSame('gray', $actions[2]->getColor());
        $this->assertTrue($actions[2]->isOutlined());
    }

    public function test_switch_actions_hide_resources_the_user_may_not_view(): void
    {
        $reservationOnly = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        Permission::create(['user_id' => $reservationOnly->id, 'is_super_admin' => false, 'abilities' => [['module' => 'reservation', 'actions' => ['view']]]]);
        $this->actingAs($reservationOnly);

        $actions = ReservationResource::switchActions(ReservationResource::class);

        $this->assertSame(
            ['switch_reservation_resource'],
            array_map(fn ($action) => $action->getName(), $actions),
        );
    }
}