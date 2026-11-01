<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\CreateProject;
use App\Filament\Resources\ProjectResource\Pages\EditProject;
use App\Filament\Resources\ProjectResource\Pages\ListProjects;
use App\Filament\Resources\ProjectResource\RelationManagers\WorkflowsRelationManager;
use App\Models\Permission;
use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectResourceTest extends TestCase
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

    public function test_list_renders_and_shows_factory_project()
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-test-list-render', 'member_ids' => [], 'departments' => []]);

        Livewire::actingAs($admin)
            ->test(ListProjects::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$project]);
    }

    public function test_create_project_persists_name_owner_and_audience()
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $member = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($admin)
            ->test(CreateProject::class)
            ->fillForm([
                'name' => '0-test-create-project',
                'owner_id' => $owner->id,
                'member_ids' => [$member->id],
                'departments' => [],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::where('name', '0-test-create-project')->firstOrFail();
        $this->assertSame($owner->id, $project->owner_id);
        $this->assertSame([$member->id], $project->member_ids);
    }

    public function test_create_project_validates_required_name()
    {
        $admin = $this->developer();
        $owner = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateProject::class)
            ->fillForm([
                'name' => null,
                'owner_id' => $owner->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_create_project_validates_required_owner()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)
            ->test(CreateProject::class)
            ->fillForm([
                'name' => '0-test-missing-owner',
                'owner_id' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['owner_id' => 'required']);
    }

    public function test_edit_project_updates_name(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-test-edit-before', 'member_ids' => [], 'departments' => []]);

        Livewire::actingAs($admin)
            ->test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['name' => '0-test-edit-after'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('0-test-edit-after', $project->fresh()->name);
    }

    public function test_edit_project_deadline_picker_is_jalali_but_stores_a_gregorian_date(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-test-deadline', 'member_ids' => [], 'departments' => []]);
        $deadline = now()->addDays(20)->toDateString();

        $component = Livewire::actingAs($admin)
            ->test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['settings.deadline' => $deadline])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($deadline, substr((string) $project->fresh()->setting('deadline'), 0, 10));
        $field = $component->instance()->getSchema('form')->getFlatComponents()['settings.deadline'] ?? null;
        $this->assertNotNull($field);
        $this->assertSame('filament-jalali::jalali-date-time-picker', $field->getView());
        $this->assertFalse($field->isNative());
    }

    public function test_delete_action_soft_deletes_project(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-test-delete', 'member_ids' => [], 'departments' => []]);

        Livewire::actingAs($admin)
            ->test(ListProjects::class)
            ->callTableAction(DeleteAction::class, $project);

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_project_slug_is_generated_from_name_on_create(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateProject::class)
            ->fillForm([
                'name' => '0-test-slug-generation',
                'owner_id' => $owner->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::where('name', '0-test-slug-generation')->firstOrFail();
        $this->assertNotEmpty($project->slug);
    }

    public function test_pruning_soon_filter_scopes_to_projects_deleted_over_thirty_days_ago(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();

        $recentlyDeleted = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-test-recent-delete', 'member_ids' => [], 'departments' => []]);
        $recentlyDeleted->delete();

        $longDeleted = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-test-long-delete', 'member_ids' => [], 'departments' => []]);
        $longDeleted->delete();
        $longDeleted->forceFill(['deleted_at' => now()->subDays(45)])->saveQuietly();

        Livewire::actingAs($admin)
            ->test(ListProjects::class)
            ->filterTable('pruning_soon')
            ->assertCanSeeTableRecords([$longDeleted])
            ->assertCanNotSeeTableRecords([$recentlyDeleted]);
    }

    public function test_created_at_filter_scopes_to_the_given_date_range(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();

        $inRange = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-test-in-range', 'member_ids' => [], 'departments' => []]);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-test-out-of-range', 'member_ids' => [], 'departments' => []]);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)
            ->test(ListProjects::class)
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_tasks_relation_manager_shows_only_this_projects_tasks(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $projectA = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $projectB = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $taskInA = \App\Models\Task::factory()->create(['project_id' => $projectA->id, 'user_id' => $owner->id]);
        $taskInB = \App\Models\Task::factory()->create(['project_id' => $projectB->id, 'user_id' => $owner->id]);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\ProjectResource\RelationManagers\TasksRelationManager::class, [
                'ownerRecord' => $projectA,
                'pageClass' => EditProject::class,
            ])
            ->assertCanSeeTableRecords([$taskInA])
            ->assertCanNotSeeTableRecords([$taskInB]);
    }

    public function test_tasks_relation_manager_create_action_creates_a_task_linked_to_the_project(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\ProjectResource\RelationManagers\TasksRelationManager::class, [
                'ownerRecord' => $project,
                'pageClass' => EditProject::class,
            ])
            ->mountTableAction('create')
            ->setTableActionData([
                'user_id' => $owner->id,
                'title' => 'RM created task',
                'status' => 'todo',
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $task = \App\Models\Task::where('title', 'RM created task')->firstOrFail();
        $this->assertSame($project->id, $task->project_id);
        $this->assertSame($owner->id, $task->user_id);
    }

    public function test_tasks_relation_manager_edit_action_updates_the_task(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $task = \App\Models\Task::factory()->create(['project_id' => $project->id, 'user_id' => $owner->id, 'title' => 'original title']);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\ProjectResource\RelationManagers\TasksRelationManager::class, [
                'ownerRecord' => $project,
                'pageClass' => EditProject::class,
            ])
            ->callTableAction('edit', $task, data: [
                'user_id' => $owner->id,
                'title' => 'updated title',
                'status' => 'todo',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame('updated title', $task->fresh()->title);
    }

    public function test_tasks_relation_manager_delete_action_soft_deletes_the_task(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $task = \App\Models\Task::factory()->create(['project_id' => $project->id, 'user_id' => $owner->id]);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\ProjectResource\RelationManagers\TasksRelationManager::class, [
                'ownerRecord' => $project,
                'pageClass' => EditProject::class,
            ])
            ->callTableAction(DeleteAction::class, $task);

        $this->assertNull(\App\Models\Task::find($task->id));
        $this->assertNotNull(\App\Models\Task::withTrashed()->find($task->id));
    }

    public function test_tasksheet_action_url_targets_project_owner(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-test-tasksheet-owner', 'member_ids' => [], 'departments' => []]);

        Livewire::actingAs($admin)
            ->test(ListProjects::class)
            ->assertTableActionVisible('tasksheet', $project)
            ->assertTableActionHasUrl('tasksheet', route('tasksheet', ['user' => $owner->id]), $project);
    }

    public function test_restore_action_restores_soft_deleted_project(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-test-restore', 'member_ids' => [], 'departments' => []]);
        $project->delete();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);

        Livewire::actingAs($admin)
            ->test(ListProjects::class)
            ->callTableAction(RestoreAction::class, $project);

        $this->assertNotSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_project_can_never_be_force_deleted_even_by_a_developer(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-test-no-force-delete', 'member_ids' => [], 'departments' => []]);

        $this->assertFalse(ProjectResource::canForceDelete($project));
        $this->assertFalse(ProjectResource::canForceDeleteAny());
    }

    public function test_workflows_relation_manager_shows_only_this_projects_own_workflow_instances(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create(['status' => 'active']);
        $projectA = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $projectB = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $workflowInA = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $projectA->id]);
        $workflowInB = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $projectB->id]);

        Livewire::actingAs($admin)
            ->test(WorkflowsRelationManager::class, ['ownerRecord' => $projectA, 'pageClass' => EditProject::class])
            ->assertCanSeeTableRecords([$workflowInA])
            ->assertCanNotSeeTableRecords([$workflowInB]);
    }

    public function test_workflows_relation_manager_has_no_generic_create_edit_or_delete_actions(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $workflow = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        Livewire::actingAs($admin)
            ->test(WorkflowsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
            ->assertTableActionDoesNotExist('create')
            ->assertTableActionDoesNotExist('edit', null, $workflow)
            ->assertTableActionDoesNotExist('delete', null, $workflow);
    }

    public function test_workflows_relation_manager_apply_template_action_bypasses_the_owner_gate_for_admins(): void
    {
        $admin = $this->developer();
        $projectOwner = User::factory()->create(['status' => 'active']);
        $templateOwner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $projectOwner->id, 'member_ids' => [], 'departments' => []]);
        $template = Workflow::factory()->asTemplate()->create(['owner_id' => $templateOwner->id, 'name' => 'الگوی آزمایشی']);

        Filament::setCurrentPanel('admin');

        Livewire::actingAs($admin)
            ->test(WorkflowsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
            ->mountTableAction('applyTemplate')
            ->setTableActionData(['template_id' => $template->id])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $applied = Workflow::where('project_id', $project->id)->where('template_id', $template->id)->first();
        $this->assertNotNull($applied);
        $this->assertSame(Workflow::STATUS_DRAFT, $applied->status);
        $this->assertSame($admin->id, $applied->owner_id);
    }

    public function test_workflows_relation_manager_cancel_action_bypasses_the_owner_gate_for_admins(): void
    {
        $admin = $this->developer();
        $projectOwner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $projectOwner->id, 'member_ids' => [], 'departments' => []]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $projectOwner->id, 'project_id' => $project->id]);

        Filament::setCurrentPanel('admin');

        Livewire::actingAs($admin)
            ->test(WorkflowsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
            ->callTableAction('cancel', $workflow);

        $this->assertSame(Workflow::STATUS_CANCELLED, $workflow->fresh()->status);
    }

    public function test_workflows_relation_manager_cancel_action_is_hidden_for_a_non_active_workflow(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        Livewire::actingAs($admin)
            ->test(WorkflowsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
            ->assertTableActionHidden('cancel', $draft);
    }

    public function test_edit_project_saves_and_removes_free_form_extra_settings(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'name' => '0-test-extra-settings',
            'member_ids' => [],
            'departments' => [],
            'settings' => ['requires_approval' => true, 'sla' => 48, 'custom_key' => 'old-value'],
        ]);

        Livewire::actingAs($admin)
            ->test(EditProject::class, ['record' => $project->getRouteKey()])
            ->fillForm([
                'extraSettings' => [
                    ['key' => 'custom_key', 'value' => 'new-value'],
                    ['key' => 'added_key', 'value' => 'added-value'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = $project->fresh()->settings;
        $this->assertSame('new-value', $settings['custom_key']);
        $this->assertSame('added-value', $settings['added_key']);
        $this->assertTrue($settings['requires_approval']);
        $this->assertSame(48, $settings['sla']);
    }

    public function test_edit_project_removes_an_extra_setting_row_that_was_deleted(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'name' => '0-test-extra-removal',
            'member_ids' => [],
            'departments' => [],
            'settings' => ['requires_approval' => false, 'custom_key' => 'old-value', 'other_key' => 'keep-me'],
        ]);

        Livewire::actingAs($admin)
            ->test(EditProject::class, ['record' => $project->getRouteKey()])
            ->fillForm([
                'extraSettings' => [['key' => 'other_key', 'value' => 'keep-me']],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = $project->fresh()->settings;
        $this->assertArrayNotHasKey('custom_key', $settings);
        $this->assertSame('keep-me', $settings['other_key']);
    }

    public function test_edit_project_without_touching_settings_preserves_existing_extra_settings(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'name' => '0-test-extra-preserved',
            'member_ids' => [],
            'departments' => [],
            'settings' => ['custom_key' => 'untouched'],
        ]);

        Livewire::actingAs($admin)
            ->test(EditProject::class, ['record' => $project->getRouteKey()])
            ->fillForm(['name' => '0-test-extra-preserved-renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('untouched', $project->fresh()->settings['custom_key']);
    }

    public function test_create_project_persists_extra_settings_rows(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateProject::class)
            ->fillForm([
                'name' => '0-test-create-extra',
                'owner_id' => $owner->id,
                'extraSettings' => [['key' => 'budget', 'value' => '1200']],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::where('name', '0-test-create-extra')->firstOrFail();
        $this->assertSame('1200', $project->settings['budget']);
    }

    public function test_list_query_counts_only_overdue_open_tasks_per_project(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-test-overdue-count', 'member_ids' => [], 'departments' => []]);

        \App\Models\Task::factory()->create(['project_id' => $project->id, 'user_id' => $owner->id, 'status' => 'todo', 'deadline' => now()->subDay()]);
        \App\Models\Task::factory()->create(['project_id' => $project->id, 'user_id' => $owner->id, 'status' => 'in-progress', 'deadline' => now()->subDays(3)]);
        \App\Models\Task::factory()->create(['project_id' => $project->id, 'user_id' => $owner->id, 'status' => 'done', 'deadline' => now()->subDay()]);
        \App\Models\Task::factory()->create(['project_id' => $project->id, 'user_id' => $owner->id, 'status' => 'todo', 'deadline' => now()->addDays(3)]);

        Livewire::actingAs($admin)->test(ListProjects::class)->assertSuccessful();

        $this->assertSame(2, (int) ProjectResource::getEloquentQuery()->find($project->id)->overdue_tasks_count);
    }

    public function test_progress_column_computes_percent_from_done_over_total_tasks(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-test-progress-75', 'member_ids' => [], 'departments' => []]);

        \App\Models\Task::factory()->count(3)->create(['project_id' => $project->id, 'user_id' => $owner->id, 'status' => 'done']);
        \App\Models\Task::factory()->create(['project_id' => $project->id, 'user_id' => $owner->id, 'status' => 'todo']);

        Livewire::actingAs($admin)->test(ListProjects::class)
            ->assertSuccessful()
            ->assertSee('75٪');
    }

    public function test_progress_column_renders_zero_percent_without_division_by_zero_when_project_has_no_tasks(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-test-progress-zero', 'member_ids' => [], 'departments' => []]);

        Livewire::actingAs($admin)->test(ListProjects::class)
            ->assertSuccessful()
            ->assertSee('0٪');
    }

    public function test_tasks_relation_manager_archived_filter_scopes_to_archived_tasks(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $archived = \App\Models\Task::factory()->create(['project_id' => $project->id, 'user_id' => $owner->id, 'archived_at' => now()]);
        $active = \App\Models\Task::factory()->create(['project_id' => $project->id, 'user_id' => $owner->id]);

        $component = Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\ProjectResource\RelationManagers\TasksRelationManager::class, [
                'ownerRecord' => $project,
                'pageClass' => EditProject::class,
            ]);

        $component->filterTable('is_archived', true)
            ->assertCanSeeTableRecords([$archived])
            ->assertCanNotSeeTableRecords([$active]);

        $component->filterTable('is_archived', false)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$archived]);
    }

    public function test_sort_by_id_orders_records_ascending_and_descending(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $first = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-sort-id-a', 'member_ids' => [], 'departments' => []]);
        $second = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-sort-id-b', 'member_ids' => [], 'departments' => []]);

        $component = Livewire::actingAs($admin)->test(ListProjects::class)
            ->searchTable('0-sort-id-')
            ->sortTable('id')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sort_by_name_orders_records_ascending_and_descending(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $first = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-AAA-sort-name', 'member_ids' => [], 'departments' => []]);
        $second = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-ZZZ-sort-name', 'member_ids' => [], 'departments' => []]);

        $component = Livewire::actingAs($admin)->test(ListProjects::class)
            ->searchTable('sort-name')
            ->sortTable('name')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sort_by_created_at_orders_records_ascending_and_descending(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $older = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-sort-created-old', 'member_ids' => [], 'departments' => []]);
        $older->forceFill(['created_at' => '2020-01-15'])->saveQuietly();
        $newer = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-sort-created-new', 'member_ids' => [], 'departments' => []]);
        $newer->forceFill(['created_at' => '2030-01-15'])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListProjects::class)
            ->searchTable('0-sort-created-')
            ->sortTable('created_at')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_sort_by_deleted_at_orders_records_ascending_and_descending(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $earlier = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-sort-deleted-earlier', 'member_ids' => [], 'departments' => []]);
        $earlier->delete();
        $earlier->forceFill(['deleted_at' => '2020-01-15'])->saveQuietly();
        $later = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-sort-deleted-later', 'member_ids' => [], 'departments' => []]);
        $later->delete();
        $later->forceFill(['deleted_at' => '2030-01-15'])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListProjects::class)
            ->searchTable('0-sort-deleted-')
            ->sortTable('deleted_at')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$earlier->id, $later->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));

        $component->sortTable('deleted_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$later->id, $earlier->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));
    }

    public function test_sort_by_tasks_count_and_overdue_tasks_count_orders_records_ascending_and_descending(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();
        $few = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-sort-tasks-few', 'member_ids' => [], 'departments' => []]);
        $many = Project::factory()->create(['owner_id' => $owner->id, 'name' => '0-sort-tasks-many', 'member_ids' => [], 'departments' => []]);
        \App\Models\Task::factory()->create(['project_id' => $many->id, 'user_id' => $owner->id, 'status' => 'todo', 'deadline' => now()->subDay()]);
        \App\Models\Task::factory()->count(2)->create(['project_id' => $many->id, 'user_id' => $owner->id, 'status' => 'done']);

        $component = Livewire::actingAs($admin)->test(ListProjects::class)
            ->searchTable('0-sort-tasks-')
            ->sortTable('tasks_count')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$few->id, $many->id], array_values(array_intersect($ids, [$few->id, $many->id])));

        $component->sortTable('tasks_count', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$many->id, $few->id], array_values(array_intersect($ids, [$few->id, $many->id])));

        $component->sortTable('overdue_tasks_count')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$few->id, $many->id], array_values(array_intersect($ids, [$few->id, $many->id])));

        $component->sortTable('overdue_tasks_count', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$many->id, $few->id], array_values(array_intersect($ids, [$few->id, $many->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\ProjectResource\Pages\CreateProject::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\ProjectResource\Pages\EditProject::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\ProjectResource\Pages\ListProjects::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\ProjectResource\Pages\CreateProject::class, \App\Filament\Resources\ProjectResource\Pages\EditProject::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }

        $managers = [\App\Filament\Resources\ProjectResource\RelationManagers\TasksRelationManager::class, \App\Filament\Resources\ProjectResource\RelationManagers\WorkflowsRelationManager::class];

        foreach ($managers as $manager) {
            $this->assertSame(\App\Filament\RelationManagers\RelationManager::class, get_parent_class($manager));
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, class_uses_recursive($manager));
        }
    }

    public function test_create_project_save_failure_sends_the_presented_exception_notification(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateProject::class)
            ->fillForm([
                'name' => str_repeat('a', 200),
                'owner_id' => $owner->id,
            ])
            ->call('create')
            ->assertNotified(__('errors/strings.notifications.value_too_long_title'));

        $this->assertDatabaseMissing('projects', ['owner_id' => $owner->id]);
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/ProjectResource/Schemas/ProjectTablePresenter.php'),
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

    private function twoStepCycle(): array
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $member = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id], 'departments' => []]);
        $step = fn(array $people) => ['label' => 'مرحله', 'assignee_user_ids' => $people, 'type' => 'action', 'reject_to_step' => null, 'populate_task' => false];
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$step([$owner->id]), $step([$member->id])]]);

        return [$project, $workflow, $owner, $member];
    }

    private function viewOnlyProjectAdmin(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'admin']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => false, 'abilities' => [['module' => 'project', 'actions' => ['view']]]]);

        return $user;
    }

    public function test_force_advance_and_reassign_need_update_permission_and_an_active_cycle(): void
    {
        [$project, $workflow] = $this->twoStepCycle();
        $draft = Workflow::factory()->create(['owner_id' => $project->owner_id, 'project_id' => $project->id]);
        Filament::setCurrentPanel('admin');

        Livewire::actingAs($this->viewOnlyProjectAdmin())
            ->test(WorkflowsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
            ->assertTableActionHidden('forceAdvance', $workflow)
            ->assertTableActionHidden('reassignStep', $workflow);

        Livewire::actingAs($this->developer())
            ->test(WorkflowsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
            ->assertTableActionVisible('forceAdvance', $workflow)
            ->assertTableActionVisible('reassignStep', $workflow)
            ->assertTableActionHidden('forceAdvance', $draft)
            ->assertTableActionHidden('reassignStep', $draft);
    }

    public function test_admin_force_advance_moves_an_active_cycle_to_its_next_step(): void
    {
        [$project, $workflow] = $this->twoStepCycle();
        Filament::setCurrentPanel('admin');

        Livewire::actingAs($this->developer())
            ->test(WorkflowsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
            ->callTableAction('forceAdvance', $workflow);

        $this->assertSame(1, $workflow->fresh()->current_step);
        $this->assertTrue((bool) (collect($workflow->fresh()->step_log)->last()['forced'] ?? false));
    }

    public function test_admin_reassign_replaces_the_current_steps_assignees_from_the_project_roster(): void
    {
        [$project, $workflow, $owner, $member] = $this->twoStepCycle();
        Filament::setCurrentPanel('admin');

        Livewire::actingAs($this->developer())
            ->test(WorkflowsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
            ->callTableAction('reassignStep', $workflow, ['assignees' => [$member->id]]);

        $this->assertSame([$member->id], array_map('intval', $workflow->fresh()->steps[0]['assignee_user_ids']));
    }
}
