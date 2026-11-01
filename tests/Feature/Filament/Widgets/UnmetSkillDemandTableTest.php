<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\UnmetSkillDemandTable;
use App\Models\Permission;
use App\Models\Skill;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class UnmetSkillDemandTableTest extends TestCase
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

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    private function viewOnlyUser(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'admin']);
        Permission::create([
            'user_id' => $user->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'skill', 'actions' => ['view']]],
        ]);

        return $user;
    }

    private function nonPermittedUser(): User
    {
        return User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'admin']);
    }

    public function test_query_lists_only_ghost_skills_ordered_by_search_count_desc(): void
    {
        $admin = $this->developer();
        $low = Skill::factory()->ghost()->create(['search_count' => 2]);
        $high = Skill::factory()->ghost()->create(['search_count' => 9]);
        $catalog = Skill::factory()->active()->create(['search_count' => 100]);

        Livewire::actingAs($admin)->test(UnmetSkillDemandTable::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$high, $low], inOrder: true)
            ->assertCanNotSeeTableRecords([$catalog]);
    }

    public function test_query_prunes_to_eight_columns_not_select_all(): void
    {
        $admin = $this->developer();
        Skill::factory()->ghost()->create(['search_count' => 5]);

        $records = Livewire::actingAs($admin)
            ->test(UnmetSkillDemandTable::class)
            ->instance()
            ->getTableRecords();

        $this->assertTrue($records->isNotEmpty());
        $expected = ['id', 'name', 'name_en', 'category', 'description', 'icon', 'search_count', 'last_searched_at'];
        $this->assertSame($expected, array_keys($records->first()->getAttributes()));
    }

    public function test_can_view_returns_true_for_developer_super_admin(): void
    {
        $this->actingAs($this->developer());

        $this->assertTrue(UnmetSkillDemandTable::canView());
    }

    public function test_can_view_returns_true_for_admin_with_view_ability(): void
    {
        $this->actingAs($this->viewOnlyUser());

        $this->assertTrue(UnmetSkillDemandTable::canView());
    }

    public function test_can_view_returns_false_for_admin_without_skill_permission(): void
    {
        $this->actingAs($this->nonPermittedUser());

        $this->assertFalse(UnmetSkillDemandTable::canView());
    }

    public function test_can_view_returns_false_when_not_authenticated(): void
    {
        $this->assertFalse(UnmetSkillDemandTable::canView());
    }

    public function test_promote_action_is_a_named_promote_action_instance(): void
    {
        $action = UnmetSkillDemandTable::promoteAction();

        $this->assertInstanceOf(Action::class, $action);
        $this->assertSame('promote', $action->getName());
    }

    public function test_promote_action_visible_for_developer_super_admin(): void
    {
        $this->actingAs($this->developer());

        $this->assertTrue(UnmetSkillDemandTable::promoteAction()->isVisible());
    }

    public function test_promote_action_hidden_for_admin_without_create_permission(): void
    {
        $this->actingAs($this->viewOnlyUser());

        $this->assertFalse(UnmetSkillDemandTable::promoteAction()->isVisible());
    }
}