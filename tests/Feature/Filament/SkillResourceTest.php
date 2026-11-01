<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SkillResource;
use App\Filament\Resources\SkillResource\Pages\CreateSkill;
use App\Filament\Resources\SkillResource\Pages\EditSkill;
use App\Filament\Resources\SkillResource\Pages\ListSkills;
use App\Filament\Resources\SkillResource\RelationManagers\MembersRelationManager;
use App\Filament\Widgets\ModuleAnalyticsWidget;
use App\Filament\Widgets\UnmetSkillDemandTable;
use App\Models\Permission;
use App\Models\Skill;
use App\Models\SkillUser;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SkillResourceTest extends TestCase
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

    public function test_members_relation_manager_is_read_only()
    {
        $this->assertTrue((new MembersRelationManager())->isReadOnly());
    }

    public function test_members_relation_manager_lists_skill_users()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'Members RM Skill ' . uniqid(), 'category' => 'General']);
        $member = User::factory()->create(['status' => 'active']);
        $skillUser = SkillUser::create([
            'user_id' => $member->id,
            'skill_id' => $skill->id,
            'status' => 'approved',
        ]);

        Livewire::actingAs($admin)
            ->test(MembersRelationManager::class, ['ownerRecord' => $skill, 'pageClass' => EditSkill::class])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$skillUser]);
    }

    public function test_list_renders_and_shows_active_skill_row()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'List Render Skill ' . uniqid(), 'category' => 'General']);

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$skill]);
    }

    public function test_skills_and_approvals_share_one_menu_entry_with_a_header_switch()
    {
        $admin = $this->developer();
        $this->actingAs($admin);

        $this->assertTrue(\App\Filament\Resources\SkillResource::shouldRegisterNavigation());
        $this->assertFalse(\App\Filament\Resources\SkillRequestResource::shouldRegisterNavigation());

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->assertActionHasUrl('switch_skill_resource', \App\Filament\Resources\SkillResource::getUrl())
            ->assertActionHasUrl('switch_skill_request_resource', \App\Filament\Resources\SkillRequestResource::getUrl())
            ->assertActionHasColor('switch_skill_resource', 'primary')
            ->assertActionHasColor('switch_skill_request_resource', 'gray');

        Livewire::actingAs($admin)->test(\App\Filament\Resources\SkillRequestResource\Pages\ListSkillRequests::class)
            ->assertActionHasColor('switch_skill_resource', 'gray')
            ->assertActionHasColor('switch_skill_request_resource', 'primary');
    }

    public function test_the_header_switch_only_offers_resources_the_user_may_view()
    {
        $skillOnly = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        Permission::create(['user_id' => $skillOnly->id, 'is_super_admin' => false, 'abilities' => [['module' => 'skill', 'actions' => ['view']]]]);

        Livewire::actingAs($skillOnly)->test(ListSkills::class)
            ->assertActionExists('switch_skill_resource')
            ->assertActionDoesNotExist('switch_skill_request_resource');
    }

    public function test_list_excludes_ghost_skill_by_default()
    {
        $admin = $this->developer();
        $ghost = Skill::create(['name' => 'Ghost List Skill ' . uniqid(), 'is_active' => false, 'is_ghost' => true, 'category' => 'General']);

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->assertSuccessful()
            ->assertCanNotSeeTableRecords([$ghost]);
    }

    public function test_ghosts_tab_shows_only_ghost_skills()
    {
        $admin = $this->developer();
        $catalog = Skill::create(['name' => 'Catalog Tab Skill ' . uniqid(), 'category' => 'General']);
        $ghost = Skill::create(['name' => 'Ghost Tab Skill ' . uniqid(), 'is_active' => false, 'is_ghost' => true, 'category' => 'General']);

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->assertSuccessful()
            ->set('activeTab', 'ghosts')
            ->assertCanSeeTableRecords([$ghost])
            ->assertCanNotSeeTableRecords([$catalog]);
    }

    public function test_inactive_tab_shows_only_inactive_non_ghost_skills()
    {
        $admin = $this->developer();
        $catalog = Skill::create(['name' => 'Catalog Tab Skill ' . uniqid(), 'category' => 'General']);
        $inactive = Skill::create(['name' => 'Inactive Tab Skill ' . uniqid(), 'is_active' => false, 'is_ghost' => false, 'category' => 'General']);

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->assertSuccessful()
            ->set('activeTab', 'inactive')
            ->assertCanSeeTableRecords([$inactive])
            ->assertCanNotSeeTableRecords([$catalog]);
    }

    public function test_catalog_inactive_and_ghosts_tabs_partition_active_draft_and_ghost_varieties()
    {
        $admin = $this->developer();
        $suffix = uniqid();
        $active = Skill::create(['name' => "Variety Active $suffix", 'is_active' => true, 'is_ghost' => false, 'category' => 'General']);
        $draft = Skill::create(['name' => "Variety Draft $suffix", 'is_active' => false, 'is_ghost' => false, 'category' => 'General']);
        $ghost = Skill::create(['name' => "Variety Ghost $suffix", 'is_active' => false, 'is_ghost' => true, 'category' => 'General']);

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$draft, $ghost]);

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->set('activeTab', 'inactive')
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$active, $ghost]);

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->filterTable('is_active', 1)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$draft, $ghost]);

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->set('activeTab', 'inactive')
            ->filterTable('is_active', 0)
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$active, $ghost]);

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->set('activeTab', 'ghosts')
            ->assertCanSeeTableRecords([$ghost])
            ->assertCanNotSeeTableRecords([$active, $draft]);
    }

    public function test_module_analytics_skills_tab_counts_every_skill_and_request_variety()
    {
        $admin = $this->developer();

        $statValues = fn() => collect(
            Livewire::actingAs($admin)->test(ModuleAnalyticsWidget::class)
                ->call('setTab', 'skills')
                ->instance()->skillsData()
        )->map(fn($stat) => (int) $stat->getValue())->values()->all();

        $before = $statValues();
        $this->assertCount(7, $before);

        $suffix = uniqid();
        Skill::create(['name' => "Analytics Active $suffix", 'is_active' => true, 'is_ghost' => false]);
        Skill::create(['name' => "Analytics Draft $suffix", 'is_active' => false, 'is_ghost' => false]);
        Skill::create(['name' => "Analytics Ghost $suffix", 'is_active' => false, 'is_ghost' => true]);

        $skillForRequests = Skill::create(['name' => "Analytics Request Skill $suffix", 'is_active' => true, 'is_ghost' => false]);
        $pendingUser = User::factory()->create(['status' => 'active']);
        $approvedUser = User::factory()->create(['status' => 'active']);
        $rejectedUser = User::factory()->create(['status' => 'active']);
        SkillUser::create(['user_id' => $pendingUser->id, 'skill_id' => $skillForRequests->id, 'status' => 'pending']);
        SkillUser::create(['user_id' => $approvedUser->id, 'skill_id' => $skillForRequests->id, 'status' => 'approved', 'approved_at' => now(), 'approved_by' => $admin->id]);
        SkillUser::create(['user_id' => $rejectedUser->id, 'skill_id' => $skillForRequests->id, 'status' => 'rejected', 'rejected_reason' => 'not needed']);

        $after = $statValues();

        $this->assertSame([4, 2, 1, 1, 1, 1, 1], array_map(fn($a, $b) => $a - $b, $after, $before));
    }

    public function test_edit_action_hidden_for_ghost_skill_row()
    {
        $admin = $this->developer();
        $ghost = Skill::create(['name' => 'Ghost Edit Guard Skill ' . uniqid(), 'is_active' => false, 'is_ghost' => true, 'category' => 'General']);

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->set('activeTab', 'ghosts')
            ->assertTableActionHidden('edit', $ghost);
    }

    public function test_create_skill_persists_name_category_description_icon_and_active_flag()
    {
        $admin = $this->developer();
        $name = 'Probe Skill ' . uniqid();

        Livewire::actingAs($admin)->test(CreateSkill::class)
            ->fillForm([
                'name' => $name,
                'name_en' => 'Probe Skill EN',
                'category' => 'Engineering',
                'description' => 'A probe skill for testing',
                'icon' => 'bolt',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $skill = Skill::where('name', $name)->firstOrFail();
        $this->assertSame('Probe Skill EN', $skill->name_en);
        $this->assertSame('Engineering', $skill->category);
        $this->assertSame('A probe skill for testing', $skill->description);
        $this->assertSame('bolt', $skill->icon);
        $this->assertTrue($skill->is_active);
        $this->assertFalse($skill->is_ghost);
    }

    public function test_create_skill_requires_name()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateSkill::class)
            ->fillForm(['name' => ''])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_edit_skill_updates_fields()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'Old Name Skill ' . uniqid(), 'category' => 'Old Cat']);
        $newName = 'New Name Skill ' . uniqid();

        Livewire::actingAs($admin)->test(EditSkill::class, ['record' => $skill->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['name' => $newName, 'category' => 'New Cat'])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $skill->fresh();
        $this->assertSame($newName, $fresh->name);
        $this->assertSame('New Cat', $fresh->category);
    }

    public function test_edit_skill_setting_name_en_to_match_another_skills_name_is_blocked_with_error()
    {
        $admin = $this->developer();
        $suffix = uniqid();
        Skill::create(['name' => "Conflict Target $suffix", 'is_active' => true, 'is_ghost' => false]);
        $skill = Skill::create(['name' => "Conflict Source $suffix", 'is_active' => true, 'is_ghost' => false]);

        Livewire::actingAs($admin)->test(EditSkill::class, ['record' => $skill->getRouteKey()])
            ->fillForm(['name_en' => "Conflict Target $suffix"])
            ->call('save')
            ->assertHasFormErrors(['name_en']);

        $this->assertNull($skill->fresh()->name_en);
    }

    public function test_edit_skill_keeping_its_own_name_unchanged_does_not_trigger_conflict_error()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'Self Edit Skill ' . uniqid(), 'category' => 'General']);

        Livewire::actingAs($admin)->test(EditSkill::class, ['record' => $skill->getRouteKey()])
            ->fillForm(['category' => 'Updated'])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_edit_skill_with_legacy_off_list_icon_saves_without_error()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'Legacy Icon Skill ' . uniqid(), 'icon' => 'sparkles']);

        Livewire::actingAs($admin)->test(EditSkill::class, ['record' => $skill->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['category' => 'New Cat'])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $skill->fresh();
        $this->assertSame('sparkles', $fresh->icon);
        $this->assertSame('New Cat', $fresh->category);
    }

    public function test_delete_action_removes_skill_with_zero_members()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'Deletable Skill ' . uniqid(), 'category' => 'General']);

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->callTableAction(DeleteAction::class, $skill);

        $this->assertDatabaseMissing('skills', ['id' => $skill->id]);
    }

    public function test_delete_action_hidden_when_skill_has_members()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'Guarded Skill ' . uniqid(), 'category' => 'General']);
        $member = User::factory()->create(['status' => 'active']);
        SkillUser::create(['user_id' => $member->id, 'skill_id' => $skill->id, 'status' => 'approved']);

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->assertTableActionHidden('delete', $skill);
    }

    public function test_create_skill_reuses_existing_ghost_row_by_name_instead_of_duplicating()
    {
        $admin = $this->developer();
        $name = 'Reusable Ghost Skill ' . uniqid();
        $ghost = Skill::create(['name' => $name, 'is_active' => false, 'is_ghost' => true, 'search_count' => 3, 'category' => 'General']);

        Livewire::actingAs($admin)->test(CreateSkill::class)
            ->fillForm(['name' => $name, 'category' => 'Promoted'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Skill::where('name', $name)->count());
        $promoted = Skill::where('name', $name)->firstOrFail();
        $this->assertSame($ghost->id, $promoted->id);
        $this->assertTrue($promoted->is_active);
        $this->assertFalse($promoted->is_ghost);
        $this->assertSame('Promoted', $promoted->category);
    }

    public function test_create_skill_matching_existing_ghost_row_by_name_en_reuses_it_instead_of_duplicating()
    {
        $admin = $this->developer();
        $suffix = uniqid();
        $ghost = Skill::create([
            'name' => 'Ghost Native Name ' . $suffix,
            'name_en' => 'Shared English Name ' . $suffix,
            'is_active' => false,
            'is_ghost' => true,
            'search_count' => 2,
        ]);

        Livewire::actingAs($admin)->test(CreateSkill::class)
            ->fillForm(['name' => 'Shared English Name ' . $suffix, 'name_en' => 'Shared English Name ' . $suffix, 'category' => 'Promoted'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(
            1,
            Skill::where('name', 'Ghost Native Name ' . $suffix)
                ->orWhere('name', 'Shared English Name ' . $suffix)
                ->orWhere('name_en', 'Shared English Name ' . $suffix)
                ->count()
        );
        $promoted = Skill::findOrFail($ghost->id);
        $this->assertTrue($promoted->is_active);
        $this->assertFalse($promoted->is_ghost);
    }

    public function test_create_skill_colliding_with_already_active_skill_by_name_en_is_blocked_with_error()
    {
        $admin = $this->developer();
        $suffix = uniqid();
        $active = Skill::create([
            'name' => 'Active Native Name ' . $suffix,
            'name_en' => 'Active Shared English ' . $suffix,
            'category' => 'Existing',
            'is_active' => true,
            'is_ghost' => false,
        ]);

        Livewire::actingAs($admin)->test(CreateSkill::class)
            ->fillForm(['name' => 'Active Shared English ' . $suffix, 'category' => 'Duplicate Attempt'])
            ->call('create')
            ->assertHasFormErrors();

        $this->assertSame(1, Skill::where('name_en', 'Active Shared English ' . $suffix)->count());
        $this->assertSame('Existing', $active->fresh()->category);
    }

    public function test_members_count_reflects_actual_skill_user_membership()
    {
        $skill = Skill::create(['name' => 'Counted Skill ' . uniqid()]);
        $member1 = User::factory()->create(['status' => 'active']);
        $member2 = User::factory()->create(['status' => 'active']);
        SkillUser::create(['user_id' => $member1->id, 'skill_id' => $skill->id, 'status' => 'approved']);
        SkillUser::create(['user_id' => $member2->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        $row = SkillResource::getEloquentQuery()->find($skill->id);

        $this->assertSame(2, $row->skill_users_count);
    }

    public function test_ghosts_tab_badge_reflects_live_ghost_count()
    {
        $admin = $this->developer();
        $before = (int) Skill::where('is_ghost', true)->count();
        Skill::create(['name' => 'Badge Ghost One ' . uniqid(), 'is_active' => false, 'is_ghost' => true]);
        Skill::create(['name' => 'Badge Ghost Two ' . uniqid(), 'is_active' => false, 'is_ghost' => true]);

        $tabs = Livewire::actingAs($admin)->test(ListSkills::class)->instance()->getTabs();

        $this->assertEquals($before + 2, $tabs['ghosts']->getBadge());
    }

    public function test_inactive_tab_badge_reflects_live_inactive_count()
    {
        $admin = $this->developer();
        $before = (int) Skill::where('is_ghost', false)->where('is_active', false)->count();
        Skill::create(['name' => 'Badge Inactive One ' . uniqid(), 'is_active' => false, 'is_ghost' => false]);
        Skill::create(['name' => 'Badge Inactive Two ' . uniqid(), 'is_active' => false, 'is_ghost' => false]);

        $tabs = Livewire::actingAs($admin)->test(ListSkills::class)->instance()->getTabs();

        $this->assertEquals($before + 2, $tabs['inactive']->getBadge());
    }

    public function test_widget_table_lists_only_ghosts_ordered_by_search_count_descending()
    {
        $admin = $this->developer();
        $low = Skill::create(['name' => 'Widget Low Demand ' . uniqid(), 'is_active' => false, 'is_ghost' => true, 'search_count' => 2]);
        $high = Skill::create(['name' => 'Widget High Demand ' . uniqid(), 'is_active' => false, 'is_ghost' => true, 'search_count' => 9]);
        $catalogNoise = Skill::create(['name' => 'Widget Catalog Noise ' . uniqid(), 'is_active' => true, 'is_ghost' => false, 'search_count' => 100]);

        Livewire::actingAs($admin)->test(UnmetSkillDemandTable::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$high, $low], inOrder: true)
            ->assertCanNotSeeTableRecords([$catalogNoise]);
    }

    public function test_widget_table_paginates_so_only_ten_ghosts_show_on_first_page()
    {
        $admin = $this->developer();
        $ghosts = collect(range(1, 12))->map(fn (int $i) => Skill::create([
            'name' => 'Widget Page Ghost ' . $i . ' ' . uniqid(),
            'is_active' => false,
            'is_ghost' => true,
            'search_count' => $i,
        ]));
        $lowestDemand = $ghosts->sortBy('search_count')->take(2);

        Livewire::actingAs($admin)->test(UnmetSkillDemandTable::class)
            ->assertCanNotSeeTableRecords($lowestDemand);
    }

    public function test_widget_promote_action_activates_ghost_with_submitted_data_and_notifies_success()
    {
        $admin = $this->developer();
        $ghost = Skill::create(['name' => 'Promotable Ghost ' . uniqid(), 'is_active' => false, 'is_ghost' => true, 'search_count' => 5]);

        Livewire::actingAs($admin)->test(UnmetSkillDemandTable::class)
            ->callTableAction('promote', $ghost, data: [
                'name_en' => 'Promoted EN',
                'category' => 'Engineering',
                'description' => 'Promoted from unmet demand',
                'icon' => 'code',
            ])
            ->assertHasNoTableActionErrors()
            ->assertNotified(__('resources/skill/strings.notifications.ghost_activated'));

        $fresh = $ghost->fresh();
        $this->assertTrue($fresh->is_active);
        $this->assertFalse($fresh->is_ghost);
        $this->assertSame('Promoted EN', $fresh->name_en);
        $this->assertSame('Engineering', $fresh->category);
    }

    public function test_widget_promote_action_hidden_for_admin_without_create_permission()
    {
        $limitedAdmin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        Permission::create([
            'user_id' => $limitedAdmin->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'skill', 'actions' => ['view']]],
        ]);
        $ghost = Skill::create(['name' => 'Permission Guarded Ghost ' . uniqid(), 'is_active' => false, 'is_ghost' => true]);

        Livewire::actingAs($limitedAdmin)->test(UnmetSkillDemandTable::class)
            ->assertTableActionHidden('promote', $ghost);
    }

    public function test_cached_category_filter_returns_all_categories_and_invalidates_on_new_category(): void
    {
        $suffix = uniqid();
        Skill::create(['name' => "CatFilter A $suffix", 'category' => "CatFilterAlpha $suffix"]);
        Skill::create(['name' => "CatFilter B $suffix", 'category' => "CatFilterBeta $suffix"]);

        $filters = Skill::cachedCategoryFilter();

        $this->assertArrayHasKey("CatFilterAlpha $suffix", $filters);
        $this->assertArrayHasKey("CatFilterBeta $suffix", $filters);

        Skill::create(['name' => "CatFilter C $suffix", 'category' => "CatFilterGamma $suffix"]);

        $refreshed = Skill::cachedCategoryFilter();
        $this->assertArrayHasKey("CatFilterGamma $suffix", $refreshed);
    }

    public function test_category_filter_scopes_list_to_matching_skills()
    {
        $admin = $this->developer();
        $suffix = uniqid();
        $engineering = Skill::create(['name' => "Category Filter Eng $suffix", 'category' => "Engineering $suffix"]);
        $sales = Skill::create(['name' => "Category Filter Sales $suffix", 'category' => "Sales $suffix"]);

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->assertSuccessful()
            ->filterTable('category', "Engineering $suffix")
            ->assertCanSeeTableRecords([$engineering])
            ->assertCanNotSeeTableRecords([$sales]);
    }

    public function test_created_at_filter_scopes_to_the_given_date_range()
    {
        $admin = $this->developer();

        $inRange = Skill::create(['name' => 'Created Range In ' . uniqid(), 'category' => 'General']);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = Skill::create(['name' => 'Created Range Out ' . uniqid(), 'category' => 'General']);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Skill::create(['name' => 'Group Render Skill ' . uniqid(), 'category' => 'General']);

        Livewire::actingAs($admin)->test(ListSkills::class)
            ->assertSuccessful()
            ->set('tableGrouping', 'category')
            ->assertSuccessful();
    }

    public function test_sorting_by_category_name_and_created_at_produces_correct_order()
    {
        $admin = $this->developer();
        $suffix = uniqid();
        $first = Skill::create(['name' => "AAA-sort-skill $suffix", 'category' => "AAA-sort-cat $suffix", 'is_active' => true, 'is_ghost' => false]);
        $second = Skill::create(['name' => "ZZZ-sort-skill $suffix", 'category' => "ZZZ-sort-cat $suffix", 'is_active' => true, 'is_ghost' => false]);

        $component = Livewire::actingAs($admin)->test(ListSkills::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('category')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('category', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('name')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $old = $first;
        $old->forceFill(['created_at' => now()->subYears(5)])->saveQuietly();
        $new = $second;
        $new->forceFill(['created_at' => now()])->saveQuietly();

        $component->sortTable('created_at')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$old->id, $new->id], array_values(array_intersect($ids, [$old->id, $new->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$new->id, $old->id], array_values(array_intersect($ids, [$old->id, $new->id])));
    }

    public function test_sorting_by_is_active_and_members_count_produces_correct_order()
    {
        $admin = $this->developer();
        $inactive = Skill::create(['name' => 'Sort Inactive Skill ' . uniqid(), 'is_active' => false, 'is_ghost' => false]);
        $active = Skill::create(['name' => 'Sort Active Skill ' . uniqid(), 'is_active' => true, 'is_ghost' => false]);

        $component = Livewire::actingAs($admin)->test(ListSkills::class)
            ->set('activeTab', null)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('is_active')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$inactive->id, $active->id], array_values(array_intersect($ids, [$inactive->id, $active->id])));

        $component->sortTable('is_active', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$active->id, $inactive->id], array_values(array_intersect($ids, [$inactive->id, $active->id])));

        $empty = Skill::create(['name' => 'Sort No Members Skill ' . uniqid(), 'is_active' => true, 'is_ghost' => false]);
        $populated = Skill::create(['name' => 'Sort Has Members Skill ' . uniqid(), 'is_active' => true, 'is_ghost' => false]);
        $member = User::factory()->create(['status' => 'active']);
        SkillUser::create(['user_id' => $member->id, 'skill_id' => $populated->id, 'status' => 'approved']);

        $component->sortTable('skill_users_count')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$empty->id, $populated->id], array_values(array_intersect($ids, [$empty->id, $populated->id])));

        $component->sortTable('skill_users_count', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$populated->id, $empty->id], array_values(array_intersect($ids, [$empty->id, $populated->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\SkillResource\Pages\CreateSkill::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\SkillResource\Pages\EditSkill::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\SkillResource\Pages\ListSkills::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\SkillResource\Pages\CreateSkill::class, \App\Filament\Resources\SkillResource\Pages\EditSkill::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }

        $managers = [\App\Filament\Resources\SkillResource\RelationManagers\MembersRelationManager::class];

        foreach ($managers as $manager) {
            $this->assertSame(\App\Filament\RelationManagers\RelationManager::class, get_parent_class($manager));
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, class_uses_recursive($manager));
        }
    }
}
