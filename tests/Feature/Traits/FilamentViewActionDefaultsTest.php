<?php

namespace Tests\Feature\Traits;

use App\Filament\Resources\ChannelResource\Pages\EditChannel;
use App\Filament\Resources\ChannelResource\RelationManagers\ChannelMembersRelationManager;
use App\Filament\Resources\EnergyResource\Pages\ListEnergyTests;
use App\Filament\Resources\NotifyRuleResource\Pages\ListNotifyLogs;
use App\Filament\Resources\PostResource\Pages\ListPosts;
use App\Filament\Resources\SkillRequestResource\Pages\ListSkillRequests;
use App\Filament\Resources\ProjectResource\Pages\EditProject;
use App\Filament\Resources\ProjectResource\RelationManagers\TasksRelationManager;
use App\Models\Channel;
use App\Models\Permission;
use App\Models\Project;
use App\Models\User;
use App\Traits\FilamentViewActionDefaults;
use Filament\Actions\ViewAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentViewActionDefaultsTest extends TestCase
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

    private function tableShortcuts(string $listPage): array
    {
        $admin = $this->developer();

        $component = Livewire::actingAs($admin)->test($listPage);

        return array_map(
            fn ($action) => $action->getName(),
            FilamentViewActionDefaults::viewModalShortcuts($component->instance()->getTable()),
        );
    }

    public function test_view_modal_shortcuts_mirror_the_row_edit_and_delete_actions(): void
    {
        $this->assertSame(['create', 'edit', 'delete'], $this->tableShortcuts(ListPosts::class));
    }

    public function test_view_modal_shortcuts_skip_edit_when_rows_have_no_edit_action(): void
    {
        $this->assertSame(['create', 'delete'], $this->tableShortcuts(ListEnergyTests::class));
    }

    public function test_registration_wires_the_shortcuts_into_every_new_view_action(): void
    {
        $admin = $this->developer();

        $component = Livewire::actingAs($admin)->test(ListPosts::class);

        $action = ViewAction::make()
            ->livewire($component->instance())
            ->table($component->instance()->getTable());

        $this->assertSame(
            ['create', 'edit', 'delete'],
            array_values(array_map(fn ($footerAction) => $footerAction->getName(), $action->getExtraModalFooterActions())),
        );
    }

    public function test_create_shortcut_stays_hidden_on_a_resource_that_forbids_creating(): void
    {
        $admin = $this->developer();

        $component = Livewire::actingAs($admin)->test(ListEnergyTests::class);

        $create = collect(FilamentViewActionDefaults::viewModalShortcuts($component->instance()->getTable()))
            ->first(fn ($action) => $action->getName() === 'create');

        $this->assertFalse((clone $create)->livewire($component->instance())->isVisible());
    }

    public function test_relation_manager_shortcuts_mirror_the_rm_header_create(): void
    {
        $admin = $this->developer();

        $channel = Channel::factory()->create(['name' => '0-test-mirror-channel']);
        $membersTable = Livewire::actingAs($admin)
            ->test(ChannelMembersRelationManager::class, ['ownerRecord' => $channel, 'pageClass' => EditChannel::class])
            ->instance()->getTable();

        $this->assertNotContains('create', array_map(
            fn ($action) => $action->getName(),
            FilamentViewActionDefaults::viewModalShortcuts($membersTable),
        ));

        $project = Project::factory()->create(['owner_id' => $admin->id, 'name' => '0-test-mirror-project', 'member_ids' => [], 'departments' => []]);
        $tasksTable = Livewire::actingAs($admin)
            ->test(TasksRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
            ->instance()->getTable();

        $this->assertContains('create', array_map(
            fn ($action) => $action->getName(),
            FilamentViewActionDefaults::viewModalShortcuts($tasksTable),
        ));
    }

    public function test_create_less_list_pages_get_no_create_shortcut(): void
    {
        $this->assertNotContains('create', $this->tableShortcuts(ListNotifyLogs::class));
        $this->assertNotContains('create', $this->tableShortcuts(ListSkillRequests::class));
    }
}