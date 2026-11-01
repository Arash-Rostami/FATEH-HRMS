<?php

namespace Tests\Feature\Filament;

use App\Enums\SkillRequestStatus;
use App\Filament\Resources\EventResource\RelationManagers\EventSharesRelationManager;
use App\Filament\Resources\ReservationResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\RelationManagers\ProfileRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\SkillsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\TasksRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\TicketsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\WorkflowsRelationManager;
use App\Filament\Resources\UserResource\Schemas\UserFormPresenter;
use App\Models\Department;
use App\Models\Event;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Reservation;
use App\Models\Skill;
use App\Models\SkillUser;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workflow;
use App\Services\User\UserKeyGrouper;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
        Cache::forget(UserKeyGrouper::CACHE_KEY);
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::forget(UserKeyGrouper::CACHE_KEY);
        Mockery::close();
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

    public function test_list_renders_and_shows_a_factory_user_row()
    {
        $admin = $this->developer();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($admin)->test(ListUsers::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$user]);
    }

    public function test_create_user_persists_name_email_and_role()
    {
        $admin = $this->developer();
        $email = 'usr-test-' . uniqid() . '@example.com';

        Livewire::actingAs($admin)->test(CreateUser::class)
            ->fillForm([
                'name' => '0 Test User',
                'email' => $email,
                'password' => 'Secret123!',
                'password_confirmation' => 'Secret123!',
                'type' => 'employee',
                'role' => 'admin',
                'status' => 'active',
                'presence' => 'onsite',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'name' => '0 Test User',
            'role' => 'admin',
            'type' => 'employee',
            'status' => 'active',
            'presence' => 'onsite',
        ]);
    }

    public function test_create_user_requires_a_name()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateUser::class)
            ->fillForm([
                'name' => '',
                'email' => 'usr-no-name-' . uniqid() . '@example.com',
                'password' => 'Secret123!',
                'password_confirmation' => 'Secret123!',
                'type' => 'employee',
                'role' => 'user',
                'status' => 'active',
                'presence' => 'onsite',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_create_user_rejects_mismatched_password_confirmation()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateUser::class)
            ->fillForm([
                'name' => '0 Confirm Mismatch',
                'email' => 'usr-mismatch-' . uniqid() . '@example.com',
                'password' => 'Secret123!',
                'password_confirmation' => 'DifferentPass!',
                'type' => 'employee',
                'role' => 'user',
                'status' => 'active',
                'presence' => 'onsite',
            ])
            ->call('create')
            ->assertHasFormErrors(['password' => 'confirmed']);
    }

    public function test_edit_user_saves_changed_name_and_status()
    {
        $admin = $this->developer();
        $user = User::factory()->create([
            'name' => '0 Before Edit',
            'status' => 'active',
            'presence' => 'onsite',
            'role' => 'user',
            'type' => 'employee',
        ]);

        Livewire::actingAs($admin)->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertSuccessful()
            ->fillForm([
                'name' => '0 After Edit',
                'status' => 'inactive',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $user->fresh();
        $this->assertSame('0 After Edit', $fresh->name);
        $this->assertSame('inactive', $fresh->status);
    }

    public function test_toggle_status_table_action_deactivates_then_reactivates_the_user()
    {
        $admin = $this->developer();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($admin)->test(ListUsers::class)
            ->callTableAction('toggleStatus', $user);

        $this->assertSame('inactive', $user->fresh()->status);
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        Livewire::actingAs($admin)->test(ListUsers::class)
            ->callTableAction('toggleStatus', $user);

        $this->assertSame('active', $user->fresh()->status);
    }

    public function test_toggle_status_action_is_hidden_for_own_account()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(ListUsers::class)
            ->assertTableActionHidden('toggleStatus', $admin);
    }

    public function test_toggle_status_action_is_hidden_for_suspended_users()
    {
        $admin = $this->developer();
        $user = User::factory()->create(['status' => 'suspended', 'presence' => 'onsite']);

        Livewire::actingAs($admin)->test(ListUsers::class)
            ->assertTableActionHidden('toggleStatus', $user);
    }

    public function test_edit_page_header_has_no_delete_action()
    {
        $admin = $this->developer();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($admin)->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertActionDoesNotExist('delete');
    }

    public function test_bulk_deactivate_action_inactivates_selected_users_but_skips_self()
    {
        $admin = $this->developer();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($admin)->test(ListUsers::class)
            ->callTableBulkAction('bulk_deactivate', [$user, $admin]);

        $this->assertSame('inactive', $user->fresh()->status);
        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_status_filter_scopes_list_to_matching_users()
    {
        $admin = $this->developer();

        $active = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'name' => '0 Status Active']);
        $inactive = User::factory()->create(['status' => 'inactive', 'presence' => 'onsite', 'name' => '0 Status Inactive']);

        Livewire::actingAs($admin)->test(ListUsers::class)
            ->filterTable('status', 'active')
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_role_filter_scopes_list_to_admin_role_only()
    {
        $admin = $this->developer();

        $manager = User::factory()->create(['role' => 'admin', 'status' => 'active', 'presence' => 'onsite', 'name' => '0 Role Admin']);
        $regular = User::factory()->create(['role' => 'user', 'status' => 'active', 'presence' => 'onsite', 'name' => '0 Role User']);

        Livewire::actingAs($admin)->test(ListUsers::class)
            ->filterTable('role', 'admin')
            ->assertCanSeeTableRecords([$manager])
            ->assertCanNotSeeTableRecords([$regular]);
    }

    public function test_type_filter_scopes_list_to_matching_users()
    {
        $admin = $this->developer();

        $employee = User::factory()->create(['type' => 'employee', 'status' => 'active', 'presence' => 'onsite', 'name' => '0 Type Employee']);
        $guest = User::factory()->create(['type' => 'guest', 'status' => 'active', 'presence' => 'onsite', 'name' => '0 Type Guest']);

        Livewire::actingAs($admin)->test(ListUsers::class)
            ->filterTable('type', 'employee')
            ->assertCanSeeTableRecords([$employee])
            ->assertCanNotSeeTableRecords([$guest]);
    }

    public function test_presence_filter_scopes_list_to_matching_users()
    {
        $admin = $this->developer();

        $onsite = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'name' => '0 Presence Onsite']);
        $remote = User::factory()->create(['status' => 'active', 'presence' => 'remote', 'name' => '0 Presence Remote']);

        Livewire::actingAs($admin)->test(ListUsers::class)
            ->filterTable('presence', 'onsite')
            ->assertCanSeeTableRecords([$onsite])
            ->assertCanNotSeeTableRecords([$remote]);
    }

    public function test_created_at_filter_scopes_to_the_given_date_range()
    {
        $admin = $this->developer();

        $inRange = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListUsers::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_active_tab_lists_only_active_status_users()
    {
        $admin = $this->developer();

        $active = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $inactive = User::factory()->create(['status' => 'inactive', 'presence' => 'onsite', 'type' => 'employee']);

        Livewire::actingAs($admin)->test(ListUsers::class)
            ->assertSuccessful()
            ->set('activeTab', 'active')
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_admins_tab_lists_admin_and_developer_roles_only()
    {
        $admin = $this->developer();

        $manager = User::factory()->create(['role' => 'admin', 'status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $regular = User::factory()->create(['role' => 'user', 'status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);

        Livewire::actingAs($admin)->test(ListUsers::class)
            ->assertSuccessful()
            ->set('activeTab', 'admins')
            ->assertCanSeeTableRecords([$admin, $manager])
            ->assertCanNotSeeTableRecords([$regular]);
    }

    public function test_hidden_tab_lists_guest_type_users_only()
    {
        $admin = $this->developer();

        $guest = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'guest']);
        $employee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);

        Livewire::actingAs($admin)->test(ListUsers::class)
            ->assertSuccessful()
            ->set('activeTab', 'hidden')
            ->assertCanSeeTableRecords([$guest])
            ->assertCanNotSeeTableRecords([$employee]);
    }

    public function test_dynamic_classifier_filter_scopes_user_list()
    {
        $tehran = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['Company' => 'Tehran']]);
        $shiraz = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['Company' => 'Shiraz']]);
        $untagged = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['preferences' => ['theme' => 'light']]]);

        $filters = UserKeyGrouper::filters();
        $this->assertNotEmpty($filters, 'at least one dynamic filter should be discovered from extra keys');

        $companyFilter = collect($filters)->firstWhere(fn ($f) => $f->getLabel() === 'Company');
        $this->assertNotNull($companyFilter, 'Company filter must be auto-discovered');

        $query = User::query();
        $companyFilter->apply($query, ['value' => 'Tehran']);
        $results = $query->pluck('id');

        $this->assertContains($tehran->id, $results);
        $this->assertNotContains($shiraz->id, $results);
        $this->assertNotContains($untagged->id, $results);
    }

    public function test_dynamic_classifier_filter_options_are_distinct_values()
    {
        User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['Company' => 'Tehran']]);
        User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['Company' => 'Shiraz']]);
        User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['Company' => 'Tehran']]);

        $options = UserKeyGrouper::distinctValues(['Company']);

        $this->assertEqualsCanonicalizing(['Tehran', 'Shiraz'], array_keys($options));
        $this->assertEqualsCanonicalizing(['Tehran', 'Shiraz'], array_values($options));
    }

    public function test_create_form_documents_guest_and_underscore_prefix_conventions()
    {
        $admin = $this->developer();

        $html = Livewire::actingAs($admin)->test(CreateUser::class)
            ->assertSuccessful()
            ->html();

        $this->assertStringContainsString(
            __('resources/user/strings.hints.type'),
            $html,
            'the type field must explain that "guest" hides a user from the colleague status board',
        );
        $this->assertStringContainsString('مهمان', $html);
        $this->assertStringContainsString('تخته وضعیت همکاران', $html);

        $this->assertStringContainsString(
            __('resources/user/strings.hints.extra'),
            $html,
            'the extra field must explain the leading-underscore hidden-key convention',
        );
    }

    public function test_user_status_hint_documents_reservation_block_and_login_block()
    {
        $admin = $this->developer();

        $html = Livewire::actingAs($admin)->test(CreateUser::class)
            ->assertSuccessful()
            ->html();

        $this->assertStringContainsString(
            __('resources/user/strings.hints.status'),
            $html,
            'the status field must explain the real effect (blocks reservations/ranking and login)',
        );
        $this->assertStringNotContainsString(
            'این وضعیت مانع ورود به سیستم نمی‌شود',
            $html,
            'the old inaccurate "status does not prevent login" claim must be gone',
        );
    }

    public function test_profile_relation_manager_create_action_visible_when_no_profile_exists()
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($admin)
            ->test(ProfileRelationManager::class, ['ownerRecord' => $target, 'pageClass' => EditUser::class])
            ->assertSuccessful()
            ->assertTableActionVisible('create');
    }

    public function test_profile_relation_manager_create_action_hidden_when_profile_exists()
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $target->id, 'department_id' => Department::firstOrCreate(['code' => 'PRM'], ['name' => 'PRM', 'description' => 'PRM'])->code]);

        Livewire::actingAs($admin)
            ->test(ProfileRelationManager::class, ['ownerRecord' => $target, 'pageClass' => EditUser::class])
            ->assertSuccessful()
            ->assertTableActionHidden('create');
    }

    public function test_skills_relation_manager_hidden_when_user_has_no_skills()
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active']);

        $this->actingAs($admin);
        $this->assertFalse(SkillsRelationManager::canViewForRecord($target, EditUser::class));
    }

    public function test_skills_relation_manager_visible_and_lists_users_skill()
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active']);
        $skill = Skill::create(['name' => '0Relation Manager Skill', 'is_active' => true, 'is_ghost' => false]);
        $skillUser = SkillUser::create([
            'user_id' => $target->id,
            'skill_id' => $skill->id,
            'status' => SkillRequestStatus::Approved,
            'approved_at' => now(),
        ]);

        $this->actingAs($admin);
        $this->assertTrue(SkillsRelationManager::canViewForRecord($target, EditUser::class));

        Livewire::actingAs($admin)
            ->test(SkillsRelationManager::class, ['ownerRecord' => $target, 'pageClass' => EditUser::class])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$skillUser]);
    }

    private function adminWith(array $abilities): User
    {
        $admin = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'admin']);
        Permission::create(['user_id' => $admin->id, 'is_super_admin' => false, 'abilities' => $abilities]);

        return $admin;
    }

    public function test_relation_manager_tab_follows_the_related_modules_permission_not_the_owners()
    {
        $target = User::factory()->create(['status' => 'active']);

        $this->actingAs($this->adminWith([['module' => 'user', 'actions' => ['view']]]));
        $this->assertFalse(TasksRelationManager::canViewForRecord($target, EditUser::class));

        $this->actingAs($this->adminWith([['module' => 'user', 'actions' => ['view']], ['module' => 'task', 'actions' => ['view']]]));
        $this->assertTrue(TasksRelationManager::canViewForRecord($target, EditUser::class));
    }

    public function test_edit_page_explains_tabs_hidden_by_the_admins_permissions()
    {
        $target = User::factory()->create(['status' => 'active']);
        $admin = $this->adminWith([['module' => 'user', 'actions' => ['view', 'update']]]);

        Livewire::actingAs($admin)->test(EditUser::class, ['record' => $target->getRouteKey()])
            ->assertSuccessful()
            ->assertSeeText('به‌دلیل سطح دسترسی شما نمایش داده نمی‌شوند')
            ->assertSeeText('ماژول ths')
            ->assertSeeText('ماژول suggestion')
            ->assertDontSeeText('ماژول permission')
            ->assertSeeText('قوانین دسترسی در عمل');
    }

    public function test_edit_page_shows_no_hidden_tab_notice_for_full_access_admin_or_developer()
    {
        $target = User::factory()->create(['status' => 'active']);
        $all = collect(array_keys(Arr::except(Permission::availableModules(), 'permission')))
            ->map(fn($module) => ['module' => $module, 'actions' => ['view', 'update']])->all();

        foreach ([$this->adminWith($all), $this->developer()] as $actor) {
            Livewire::actingAs($actor)->test(EditUser::class, ['record' => $target->getRouteKey()])
                ->assertSuccessful()
                ->assertDontSeeText('نمایش داده نمی‌شوند');
        }
    }

    public function test_hidden_tab_clue_ignores_empty_skills_and_open_children()
    {
        $target = User::factory()->create(['status' => 'active']);
        $this->actingAs($this->adminWith([['module' => 'user', 'actions' => ['view']]]));

        $this->assertNull(SkillsRelationManager::deniedByPermission($target));
        $this->assertSame('task', TasksRelationManager::deniedByPermission($target));
        $this->assertNull(WorkflowsRelationManager::deniedByPermission($target));

        $skill = Skill::create(['name' => '0Clue Skill', 'is_active' => true, 'is_ghost' => false]);
        SkillUser::create(['user_id' => $target->id, 'skill_id' => $skill->id, 'status' => SkillRequestStatus::Approved, 'approved_at' => now()]);
        $this->assertSame('skill_request', SkillsRelationManager::deniedByPermission($target));
    }

    private function relationPages(): array
    {
        $pages = [];

        foreach (Filament::getResources() as $resource) {
            if (!$resource::getRelations()) {
                continue;
            }

            foreach (['edit', 'view'] as $name) {
                if ($page = ($resource::getPages()[$name] ?? null)) {
                    $pages[] = [$resource, $name, $page->getPage()];
                }
            }
        }

        return $pages;
    }

    private function hasHiddenTab(string $resource, $record): bool
    {
        return collect($resource::getRelations())
            ->map(fn($rm) => $rm instanceof RelationManagerConfiguration ? $rm->relationManager : $rm)
            ->contains(fn($rm) => $rm::deniedByPermission($record) !== null);
    }

    public function test_every_relation_page_loads_for_all_actors_and_shows_the_clue_only_when_a_tab_is_hidden()
    {
        $developer = $this->developer();
        $super = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'admin']);
        Permission::create(['user_id' => $super->id, 'is_super_admin' => true]);
        $noRow = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'admin']);
        $covered = [];

        foreach ($this->relationPages() as [$resource, $name, $pageClass]) {
            try {
                $record = $resource::getModel()::factory()->create();
            } catch (\Throwable) {
                continue;
            }

            $covered[class_basename($resource)] = true;
            $scoped = $this->adminWith([['module' => $resource::moduleKey(), 'actions' => ['view', 'update']]]);
            $param = ['record' => $record->getRouteKey()];

            foreach ([$developer, $super] as $actor) {
                Livewire::actingAs($actor)->test($pageClass, $param)->assertSuccessful()->assertDontSeeText('به‌دلیل سطح دسترسی شما');
            }

            $this->actingAs($scoped);
            $expected = $this->hasHiddenTab($resource, $record);
            $component = Livewire::actingAs($scoped)->test($pageClass, $param)->assertSuccessful();
            $expected ? $component->assertSeeText('به‌دلیل سطح دسترسی شما') : $component->assertDontSeeText('به‌دلیل سطح دسترسی شما');

            Livewire::actingAs($noRow)->test($pageClass, $param)->assertForbidden();
        }

        $this->assertGreaterThanOrEqual(13, count($covered));
    }

    public function test_clue_fails_soft_when_the_lookup_throws_or_the_relation_list_is_empty()
    {
        $target = User::factory()->create(['status' => 'active']);
        $admin = $this->adminWith([['module' => 'user', 'actions' => ['view', 'update']]]);
        $param = ['record' => $target->getRouteKey()];

        foreach ([ThrowingListEditUser::class, ThrowingManagerEditUser::class, EmptyListEditUser::class] as $page) {
            Livewire::actingAs($admin)->test($page, $param)->assertSuccessful()->assertDontSeeText('به‌دلیل سطح دسترسی شما');
        }
    }

    public function test_event_shares_tab_is_not_flagged_when_the_event_has_no_shares()
    {
        $event = Event::factory()->create();
        $this->actingAs($this->adminWith([['module' => 'event', 'actions' => ['view', 'update']]]));

        $this->assertNull(EventSharesRelationManager::deniedByPermission($event));
    }

    public function test_relation_manager_row_actions_follow_the_related_modules_permission()
    {
        $target = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create(['user_id' => $target->id, 'assigned_to' => $target->id]);

        $viewer = $this->adminWith([['module' => 'user', 'actions' => ['view']], ['module' => 'task', 'actions' => ['view']]]);
        Livewire::actingAs($viewer)
            ->test(TasksRelationManager::class, ['ownerRecord' => $target, 'pageClass' => EditUser::class])
            ->assertCanSeeTableRecords([$task])
            ->assertTableActionHidden('delete', $task);

        $deleter = $this->adminWith([['module' => 'task', 'actions' => ['view', 'delete']]]);
        Livewire::actingAs($deleter)
            ->test(TasksRelationManager::class, ['ownerRecord' => $target, 'pageClass' => EditUser::class])
            ->assertTableActionVisible('delete', $task);
    }

    public function test_skills_relation_manager_is_read_only()
    {
        $target = User::factory()->create(['status' => 'active']);

        $this->assertTrue((new SkillsRelationManager())->isReadOnly());
    }

    public function test_skills_relation_manager_shows_tier_and_endorsement_label()
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active']);
        $skill = Skill::create(['name' => 'Tier Column Skill ' . uniqid(), 'is_active' => true, 'is_ghost' => false]);
        SkillUser::create([
            'user_id' => $target->id,
            'skill_id' => $skill->id,
            'status' => SkillRequestStatus::Approved,
            'approved_at' => now(),
            'endorsements_count' => SkillUser::ENDORSEMENT_SATURATION_CAP,
        ]);

        Livewire::actingAs($admin)
            ->test(SkillsRelationManager::class, ['ownerRecord' => $target, 'pageClass' => EditUser::class])
            ->assertSuccessful()
            ->assertSee('تأییدشده')
            ->assertSee('تأیید 4 همکار');
    }

    public function test_skills_relation_manager_shows_dash_tier_for_pending_row_not_a_misleading_unused_badge()
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active']);
        $skill = Skill::create(['name' => 'Tier Pending Skill ' . uniqid(), 'is_active' => true, 'is_ghost' => false]);
        SkillUser::create(['user_id' => $target->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        Livewire::actingAs($admin)
            ->test(SkillsRelationManager::class, ['ownerRecord' => $target, 'pageClass' => EditUser::class])
            ->assertSuccessful()
            ->assertDontSee('آماده مشارکت');
    }

    public function test_tickets_relation_manager_assigning_a_ticket_syncs_linked_taskboard_task()
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);

        $dept = 'T' . random_int(1000, 9999);
        Department::firstOrCreate(['code' => $dept], ['name' => $dept, 'description' => $dept]);
        Profile::factory()->create(['user_id' => $assignee->id, 'department_id' => $dept, 'position' => 'employee']);

        $ticket = Ticket::factory()->create([
            'requester_id' => $target->id,
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $dept],
        ]);

        $this->assertNull(Task::where('ticket_id', $ticket->id)->first());

        Livewire::actingAs($admin)
            ->test(TicketsRelationManager::class, ['ownerRecord' => $target, 'pageClass' => EditUser::class])
            ->callTableAction(EditAction::class, $ticket, data: ['assigned_to' => $assignee->id])
            ->assertHasNoTableActionErrors();

        $this->assertSame($assignee->id, $ticket->fresh()->assigned_to);

        $task = Task::where('ticket_id', $ticket->id)->first();
        $this->assertNotNull($task, 'assigning a ticket from the user profile Tickets relation manager must spawn the linked taskboard task');
        $this->assertSame($target->id, $task->user_id);
        $this->assertSame($assignee->id, $task->assigned_to);
        $this->assertSame('todo', $task->status, 'a freshly spawned mirror task starts at todo; the assignee moves it');
    }

    public function test_tickets_relation_manager_reassigning_updates_existing_linked_task_not_duplicate()
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active']);
        $first = User::factory()->create(['status' => 'active']);
        $second = User::factory()->create(['status' => 'active']);

        $dept = 'T' . random_int(1000, 9999);
        Department::firstOrCreate(['code' => $dept], ['name' => $dept, 'description' => $dept]);
        Profile::factory()->create(['user_id' => $second->id, 'department_id' => $dept, 'position' => 'employee']);

        $ticket = Ticket::factory()->create([
            'requester_id' => $target->id,
            'status' => 'in-progress',
            'assigned_to' => $first->id,
            'extra' => ['target_department' => $dept],
        ]);

        Task::create([
            'title' => 'seed',
            'description' => 'seed',
            'status' => 'todo',
            'user_id' => $target->id,
            'assigned_to' => $first->id,
            'ticket_id' => $ticket->id,
        ]);

        Livewire::actingAs($admin)
            ->test(TicketsRelationManager::class, ['ownerRecord' => $target, 'pageClass' => EditUser::class])
            ->callTableAction(EditAction::class, $ticket, data: ['assigned_to' => $second->id])
            ->assertHasNoTableActionErrors();

        $this->assertSame(1, Task::where('ticket_id', $ticket->id)->count(), 'reassignment via the relation manager must update the existing linked task, not duplicate');
        $this->assertSame($second->id, Task::where('ticket_id', $ticket->id)->first()->assigned_to);
    }

    public function test_normalize_booking_state_returns_empty_for_blank_input(): void
    {
        $this->assertSame([], UserFormPresenter::normalizeBookingState(null));
        $this->assertSame([], UserFormPresenter::normalizeBookingState(''));
        $this->assertSame([], UserFormPresenter::normalizeBookingState([]));
    }

    public function test_normalize_booking_state_normalizes_assoc_pairs_casts_to_bool_and_sorts_all_first(): void
    {
        $input = [
            ['key' => 'meeting', 'value' => 1],
            ['key' => 'all', 'value' => 0],
            ['key' => 'desk', 'value' => true],
        ];

        $result = UserFormPresenter::normalizeBookingState($input);

        $this->assertSame('all', $result[0]['key']);
        $this->assertFalse($result[0]['value']);
        $this->assertSame('meeting', $result[1]['key']);
        $this->assertTrue($result[1]['value']);
        $this->assertSame('desk', $result[2]['key']);
        $this->assertTrue($result[2]['value']);
    }

    public function test_normalize_booking_state_normalizes_scalar_key_value_pairs(): void
    {
        $input = ['desk' => 1, 'all' => 0];

        $result = UserFormPresenter::normalizeBookingState($input);

        $this->assertSame('all', $result[0]['key']);
        $this->assertFalse($result[0]['value']);
        $this->assertSame('desk', $result[1]['key']);
        $this->assertTrue($result[1]['value']);
    }

    public function test_normalize_booking_state_defaults_missing_value_to_false(): void
    {
        $result = UserFormPresenter::normalizeBookingState([['key' => 'meeting']]);

        $this->assertSame('meeting', $result[0]['key']);
        $this->assertFalse($result[0]['value']);
    }

    public function test_normalize_booking_state_appends_missing_registry_types_as_off(): void
    {
        $result = collect(UserFormPresenter::normalizeBookingState([['key' => 'meeting', 'value' => true]]))->pluck('value', 'key');

        foreach (\App\Enums\ResourceType::cases() as $type) {
            $this->assertTrue($result->has($type->value));
        }

        $this->assertTrue($result['meeting']);
        $this->assertFalse($result['seat']);
    }

    public function test_edit_form_round_trip_keeps_per_type_booking_max(): void
    {
        $admin = $this->developer();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee', 'role' => 'user', 'booking' => [['key' => 'seat', 'value' => true, 'max' => 5]]]);

        Livewire::actingAs($admin)->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(5, $user->fresh()->bookingMax('seat'));
    }

    public function test_edit_form_booking_all_toggle_cascades_and_unticking_a_type_clears_all(): void
    {
        $admin = $this->developer();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee', 'role' => 'user', 'booking' => [['key' => 'all', 'value' => false], ['key' => 'seat', 'value' => false], ['key' => 'spot', 'value' => false]]]);

        $component = Livewire::actingAs($admin)->test(EditUser::class, ['record' => $user->getRouteKey()]);
        $rows = fn() => collect($component->get('data.booking'))->pluck('value', 'key');
        $uuid = fn(string $key) => collect($component->get('data.booking'))->search(fn($row) => $row['key'] === $key);

        $component->set('data.booking.' . $uuid('all') . '.value', true);
        $this->assertTrue($rows()['seat'] && $rows()['spot'] && $rows()['all']);

        $component->set('data.booking.' . $uuid('seat') . '.value', false);
        $this->assertFalse($rows()['all']);
        $this->assertTrue($rows()['spot']);
    }

    public function test_edit_form_hydrates_defaults_for_a_user_without_booking_data(): void
    {
        $admin = $this->developer();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee', 'role' => 'user']);
        $user->forceFill(['booking' => null])->saveQuietly();

        $keys = collect(Livewire::actingAs($admin)->test(EditUser::class, ['record' => $user->getRouteKey()])->get('data.booking'))->pluck('key');

        $this->assertTrue($keys->contains('all'));
        foreach (\App\Enums\ResourceType::cases() as $type) {
            $this->assertTrue($keys->contains($type->value));
        }
    }

    public function test_normalize_booking_state_keeps_per_type_max_and_nulls_blank(): void
    {
        $result = collect(UserFormPresenter::normalizeBookingState([
            ['key' => 'seat', 'value' => true, 'max' => '5'],
            ['key' => 'spot', 'value' => true, 'max' => ''],
            ['key' => 'car', 'value' => true],
        ]))->keyBy('key');

        $this->assertSame(5, $result['seat']['max']);
        $this->assertNull($result['spot']['max']);
        $this->assertNull($result['car']['max']);
    }

    public function test_tasksheet_action_url_targets_own_id(): void
    {
        $admin = $this->developer();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($admin)->test(ListUsers::class)
            ->assertTableActionVisible('tasksheet', $user)
            ->assertTableActionHasUrl('tasksheet', route('tasksheet', ['user' => $user->id]), $user);
    }

    public function test_share_tasksheet_action_takes_recipient_and_invokes_service(): void
    {
        $admin = $this->developer();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $this->mock(\App\Services\ProjectTask\TasksheetShareService::class, function ($mock) use ($user, $admin) {
            $mock->shouldReceive('shareWithManager')
                ->once()
                ->with(
                    Mockery::on(fn ($s) => $s->is($user)),
                    Mockery::on(fn ($r) => $r->is($admin)),
                    Mockery::on(fn ($by) => $by->is($admin)),
                )
                ->andReturn(['success' => true, 'manager' => null, 'message' => 'ok']);
        });

        $component = Livewire::actingAs($admin)->test(ListUsers::class)
            ->assertTableActionExists('shareTasksheet');

        $component->call('mountTableAction', 'shareTasksheet', $user->getKey())
            ->set('mountedActions.0.data.target', 'other')
            ->set('mountedActions.0.data.recipient', $admin->id)
            ->call('callMountedTableAction')
            ->assertNotified('ok');
    }

    public function test_workflows_relation_manager_shows_both_templates_and_project_instances_the_user_owns(): void
    {
        $admin = $this->developer();
        $user = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $user->id, 'member_ids' => [], 'departments' => []]);
        $template = Workflow::factory()->asTemplate()->create(['owner_id' => $user->id]);
        $instance = Workflow::factory()->create(['owner_id' => $user->id, 'project_id' => $project->id]);
        $foreignOwner = User::factory()->create(['status' => 'active']);
        $foreign = Workflow::factory()->asTemplate()->create(['owner_id' => $foreignOwner->id]);

        Livewire::actingAs($admin)
            ->test(WorkflowsRelationManager::class, ['ownerRecord' => $user, 'pageClass' => \App\Filament\Resources\UserResource\Pages\EditUser::class])
            ->assertCanSeeTableRecords([$template, $instance])
            ->assertCanNotSeeTableRecords([$foreign]);
    }

    public function test_workflows_relation_manager_has_no_apply_template_header_action(): void
    {
        $admin = $this->developer();
        $user = User::factory()->create(['status' => 'active']);
        Workflow::factory()->asTemplate()->create(['owner_id' => $user->id]);

        Livewire::actingAs($admin)
            ->test(WorkflowsRelationManager::class, ['ownerRecord' => $user, 'pageClass' => \App\Filament\Resources\UserResource\Pages\EditUser::class])
            ->assertTableActionDoesNotExist('applyTemplate')
            ->assertTableActionDoesNotExist('create');
    }

    public function test_workflows_relation_manager_cancel_action_bypasses_the_owner_gate_for_admins(): void
    {
        $admin = $this->developer();
        $user = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $user->id, 'member_ids' => [], 'departments' => []]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $user->id, 'project_id' => $project->id]);

        \Filament\Facades\Filament::setCurrentPanel('admin');

        Livewire::actingAs($admin)
            ->test(WorkflowsRelationManager::class, ['ownerRecord' => $user, 'pageClass' => \App\Filament\Resources\UserResource\Pages\EditUser::class])
            ->callTableAction('cancel', $workflow);

        $this->assertSame(Workflow::STATUS_CANCELLED, $workflow->fresh()->status);
    }

    public function test_reservations_relation_manager_edit_navigates_to_the_full_reservation_edit_page(): void
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active']);
        $reservation = Reservation::factory()->create(['user_id' => $target->id]);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\UserResource\RelationManagers\ReservationsRelationManager::class, [
                'ownerRecord' => $target,
                'pageClass' => EditUser::class,
            ])
            ->assertSuccessful()
            ->assertTableActionHasUrl('edit', ReservationResource::getUrl('edit', ['record' => $reservation], shouldGuessMissingParameters: true), $reservation);
    }

    public function test_reservations_relation_manager_strips_the_inherited_bulk_actions(): void
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active']);

        $page = Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\UserResource\RelationManagers\ReservationsRelationManager::class, [
                'ownerRecord' => $target,
                'pageClass' => EditUser::class,
            ])
            ->assertSuccessful();

        $this->assertSame([], $page->instance()->getTable()->getToolbarActions(),
            'the رزروها tab must stay a plain list/edit tab — no bulk delete/exporter inherited from ReservationResource');
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $component = Livewire::actingAs($admin)->test(ListUsers::class)->assertSuccessful();

        foreach (['presence', 'role', 'status'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }
    }

    public function test_sorting_by_id_name_email_and_created_at_produces_correct_order()
    {
        $admin = $this->developer();
        $first = User::factory()->create(['status' => 'active', 'name' => 'AAA-sort-user', 'email' => 'aaa-sort-' . uniqid() . '@example.com']);
        $second = User::factory()->create(['status' => 'active', 'name' => 'ZZZ-sort-user', 'email' => 'zzz-sort-' . uniqid() . '@example.com']);

        $component = Livewire::actingAs($admin)->test(ListUsers::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('id')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('name')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('email')->assertSuccessful();
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

    public function test_sorting_by_last_seen_role_status_and_type_produces_correct_order()
    {
        $admin = $this->developer();
        $first = User::factory()->create(['status' => 'active', 'role' => 'admin', 'type' => 'customer']);
        $first->forceFill(['last_seen' => now()->subYears(5)])->saveQuietly();
        $second = User::factory()->create(['status' => 'inactive', 'role' => 'user', 'type' => 'employee']);
        $second->forceFill(['last_seen' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListUsers::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('last_seen')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('last_seen', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('role')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('status')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('type')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\UserResource\Pages\CreateUser::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\UserResource\Pages\EditUser::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\UserResource\Pages\ListUsers::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\UserResource\Pages\CreateUser::class, \App\Filament\Resources\UserResource\Pages\EditUser::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }

        $managers = [\App\Filament\Resources\UserResource\RelationManagers\CredentialRelationManager::class, \App\Filament\Resources\UserResource\RelationManagers\EnergyTestsRelationManager::class, \App\Filament\Resources\UserResource\RelationManagers\PermissionsRelationManager::class, \App\Filament\Resources\UserResource\RelationManagers\ProfileRelationManager::class, \App\Filament\Resources\UserResource\RelationManagers\ReportsRelationManager::class, \App\Filament\Resources\UserResource\RelationManagers\ReservationsRelationManager::class, \App\Filament\Resources\UserResource\RelationManagers\SkillsRelationManager::class, \App\Filament\Resources\UserResource\RelationManagers\SuggestionsRelationManager::class, \App\Filament\Resources\UserResource\RelationManagers\TasksRelationManager::class, \App\Filament\Resources\UserResource\RelationManagers\TicketsRelationManager::class, \App\Filament\Resources\UserResource\RelationManagers\WorkflowsRelationManager::class];

        foreach ($managers as $manager) {
            $this->assertSame(\App\Filament\RelationManagers\RelationManager::class, get_parent_class($manager));
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, class_uses_recursive($manager));
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/UserResource/Schemas/UserTablePresenter.php'),
        ];

        $recordMethods = ['getTitleFromRecordUsing', 'getKeyFromRecordUsing', 'getDescriptionFromRecordUsing'];
        $queryMethods = ['modifyQueryUsing', 'groupQueryUsing', 'orderQueryUsing', 'scopeQueryByKeyUsing', 'scopeQueryUsing', 'query'];

        $offenders = [];

        foreach ([[$recordMethods, 'record'], [$queryMethods, 'query']] as [$methods, $expectedName]) {
            $pattern = '/\\b(' . implode('|', $methods) . ')\\s*\\(\\s*(?:static\\s+)?(?:function|fn)\\s*\\(([^)]*)\\)/s';

            foreach ($files as $path) {
                $contents = file_get_contents($path);

                if (!preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                foreach ($matches as $match) {
                    $method = $match[1][0];
                    $paramList = $match[2][0];
                    $offset = $match[0][1];

                    $firstParam = trim(explode(',', $paramList)[0] ?? '');

                    if ($firstParam === '') {
                        continue;
                    }

                    if (!preg_match('/^(?<type>[^$]*)\\$(?<name>[A-Za-z_][A-Za-z0-9_]*)/', $firstParam, $m)) {
                        continue;
                    }

                    $hasType = trim($m['type']) !== '';
                    $name = $m['name'];

                    if ($name === $expectedName || $hasType) {
                        continue;
                    }

                    $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
                    $offenders[] = basename($path) . ":{$line} {$method}(fn(\${$name}) => ...)";
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Closure param must be named \$record/\$query (or carry an explicit type hint) so Filament's by-name evaluate() injection binds correctly:\n" . implode("\n", $offenders),
        );
    }
}

class ThrowingListEditUser extends EditUser
{
    protected function getAllRelationManagers(): array
    {
        throw new \RuntimeException('boom');
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}

class ThrowingManagerEditUser extends EditUser
{
    protected function getAllRelationManagers(): array
    {
        return [ThrowingRelationManager::class];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}

class EmptyListEditUser extends EditUser
{
    protected function getAllRelationManagers(): array
    {
        return [];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}

class ThrowingRelationManager extends \App\Filament\RelationManagers\RelationManager
{
    protected static string $relationship = 'tasks';

    public static function deniedByPermission(\Illuminate\Database\Eloquent\Model $ownerRecord): ?string
    {
        throw new \RuntimeException('boom');
    }
}
