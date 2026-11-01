<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Enums\TaskActivityType;
use App\Filament\Resources\TaskResource\Enums\TaskPriority;
use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Jobs\ReconcileNudge;
use App\Livewire\Dashboard\Project\Actions\AdvanceWorkflowStepAction;
use App\Livewire\Dashboard\Project\Actions\ApplyTemplateAction;
use App\Livewire\Dashboard\Project\Actions\CancelWorkflowAction;
use App\Livewire\Dashboard\Project\Actions\ReassignWorkflowStepAction;
use App\Livewire\Dashboard\Project\Actions\SaveAsTemplateAction;
use App\Livewire\Dashboard\Project\Actions\SaveWorkflowAction;
use App\Livewire\Dashboard\Project\Actions\StartWorkflowAction;
use App\Livewire\Dashboard\Project\Activity;
use App\Livewire\Dashboard\Project\Analytics;
use App\Livewire\Dashboard\Project\Calendar;
use App\Livewire\Dashboard\Project\Kanban;
use App\Livewire\Dashboard\Project\Main;
use App\Livewire\Dashboard\Project\Presentation\ProjectAnalyticsPresenter;
use App\Livewire\Dashboard\Project\Presentation\ProjectPresenter;
use App\Livewire\Dashboard\Project\Presentation\WorkflowPresenter;
use App\Livewire\Dashboard\Project\Report;
use App\Livewire\Dashboard\Project\TeamChat;
use App\Livewire\Dashboard\Project\Workflow as WorkflowTab;
use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Livewire\Dashboard\TaskBoard\Main as TaskBoardMain;
use App\Models\ChannelMessage;
use App\Models\Department;
use App\Models\Project;
use App\Models\Reply;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use App\Models\Workflow;
use App\Services\Cache\ModelCacheVersion;
use App\Services\Menu\Notifications\ProjectNudge;
use App\Services\ProjectTask\ActivityLogger;
use App\Services\ProjectTask\ApproveTaskAction;
use App\Services\ProjectTask\ChannelProvisioner;
use App\Services\ProjectTask\CreateProjectAction;
use App\Services\ProjectTask\ProjectHeartbeat;
use App\Values\CalendarRange;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Renderless;
use Livewire\Livewire;
use Mockery;
use Morilog\Jalali\Jalalian;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::flush();
        Livewire::withoutLazyLoading();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::flush();
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

    private function ownerWithProject(array $memberIds = []): array
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => $memberIds, 'departments' => []]);

        return [$owner, $project];
    }

    public function test_my_projects_flags_pending_invite_rows(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $member = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$member->id], 'departments' => []]);
        app(ChannelProvisioner::class)->resolve($project->fresh());

        $rows = Livewire::actingAs($member)->test(Main::class)->instance()->myProjects()['rows'];
        $row = collect($rows)->firstWhere('id', $project->id);

        $this->assertNotNull($row);
        $this->assertTrue($row['pending']);
    }

    public function test_my_projects_percent_never_rounds_up_to_one_hundred_before_completion(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $done = Task::factory()->ownedBy($owner)->withStatus('done')->create(['project_id' => $project->id]);
        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        $copy = (array) DB::table('tasks')->where('id', $done->id)->first();
        unset($copy['id']);
        DB::table('tasks')->insert(array_fill(0, 198, $copy));

        $rows = Livewire::actingAs($owner)->test(Main::class)->instance()->myProjects()['rows'];

        $this->assertSame(99, collect($rows)->firstWhere('id', $project->id)['percent']);
    }

    public function test_calendar_bucket_cache_key_rotates_when_the_project_changes(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id])->instance();
        $suffix = new \ReflectionMethod($component, 'calendarBucketsCacheSuffix');
        $before = $suffix->invoke($component, $project->id, '1405-01-10');

        $project->update(['settings' => ['deadline' => now()->addDays(30)->toDateString()]]);

        $this->assertNotSame($before, $suffix->invoke($component, $project->id, '1405-01-10'));
    }

    public function test_my_projects_computes_percent_complete_per_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Task::factory()->ownedBy($owner)->withStatus('done')->create(['project_id' => $project->id]);
        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        $emptyProject = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $rows = Livewire::actingAs($owner)->test(Main::class)->instance()->myProjects()['rows'];

        $this->assertSame(50, collect($rows)->firstWhere('id', $project->id)['percent']);
        $this->assertSame(0, collect($rows)->firstWhere('id', $emptyProject->id)['percent']);
    }

    public function test_my_projects_surfaces_active_workflow_progress_singly_and_as_a_count_when_multiple(): void
    {
        [$owner, $singleCycleProject] = $this->ownerWithProject();
        Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $singleCycleProject->id, 'current_step' => 1, 'steps' => [
            $this->workflowStep(), $this->workflowStep(), $this->workflowStep(),
        ]]);
        $multiCycleProject = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $multiCycleProject->id]);
        Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $multiCycleProject->id]);
        $noWorkflowProject = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $rows = Livewire::actingAs($owner)->test(Main::class)->instance()->myProjects()['rows'];

        $singleRow = collect($rows)->firstWhere('id', $singleCycleProject->id);
        $this->assertSame(1, $singleRow['workflowCount']);
        $this->assertSame(2, $singleRow['workflowStep']);
        $this->assertSame(3, $singleRow['workflowTotal']);

        $multiRow = collect($rows)->firstWhere('id', $multiCycleProject->id);
        $this->assertSame(2, $multiRow['workflowCount']);
        $this->assertNull($multiRow['workflowStep']);
        $this->assertNull($multiRow['workflowTotal']);

        $noneRow = collect($rows)->firstWhere('id', $noWorkflowProject->id);
        $this->assertSame(0, $noneRow['workflowCount']);
    }

    public function test_close_project_clears_the_active_project_and_its_workspace(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->assertSet('activeProjectId', $project->id);

        $component->call('closeProject')
            ->assertSet('activeProjectId', null);

        $this->assertNull($component->instance()->activeProject);
    }

    public function test_switch_tab_clears_that_tabs_dirty_flag(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('markTabsDirtyExcept', 'report')
            ->assertSet('tabDirty.activity', true)
            ->assertSet('tabDirty.teamChat', true)
            ->assertSet('tabDirty.projectCalendar', true);

        $component->call('switchTab', 'activity')
            ->assertSet('tabDirty.activity', false)
            ->assertSet('tabDirty.teamChat', true);
    }

    public function test_a_bogus_tab_query_param_is_sanitized_to_workflow(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->withQueryParams(['tab' => 'bogus'])
            ->test(Main::class)
            ->assertSet('activeTab', 'workflow');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->withQueryParams(['tab' => 'kanban'])
            ->test(Main::class)
            ->assertSet('activeTab', 'kanban');
    }

    public function test_mark_tabs_dirty_except_leaves_the_active_tab_untouched(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('switchTab', 'teamChat')
            ->call('markTabsDirtyExcept', 'teamChat')
            ->assertSet('tabDirty.teamChat', false)
            ->assertSet('tabDirty.activity', true)
            ->assertSet('tabDirty.projectCalendar', true);
    }

    public function test_mention_candidates_includes_owner_and_members(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);

        $component = Livewire::actingAs($owner)->test(Main::class)->call('selectProject', $project->id);
        $names = $component->instance()->mentionCandidates()->pluck('id')->all();

        $this->assertContains($owner->id, $names);
        $this->assertContains($member->id, $names);
    }

    public function test_switch_tab_to_teamchat_syncs_channel_membership(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);
        $channel = app(ChannelProvisioner::class)->resolve($project);
        $channel->memberUsers()->detach($member->id);

        $this->assertFalse($channel->memberUsers()->where('users.id', $member->id)->exists());

        Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('switchTab', 'teamChat');

        $this->assertTrue($channel->memberUsers()->where('users.id', $member->id)->exists(), 'switching to the teamChat tab must sync the project audience into the channel membership pivot');
    }

    public function test_selecting_a_project_does_not_sync_channel_membership_without_visiting_teamchat(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);
        $channel = app(ChannelProvisioner::class)->resolve($project);
        $channel->memberUsers()->detach($member->id);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id);

        $this->assertFalse(
            $channel->memberUsers()->where('users.id', $member->id)->exists(),
            'opening a project must not eagerly sync channel membership for a user who never visits the teamChat tab — that work is deferred until switchTab(\'teamChat\') actually happens'
        );
    }

    public function test_heartbeat_returns_gone_when_no_longer_a_channel_member(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);
        $channel = app(ChannelProvisioner::class)->resolve($project);

        Livewire::withoutLazyLoading();
        $component = Livewire::withQueryParams(['open' => $project->id, 'tab' => 'teamChat'])
            ->actingAs($member)
            ->test(Main::class);

        $channel->memberUsers()->detach($member->id);

        $this->assertSame('gone', $component->instance()->heartbeat(), 'a mid-session removal must be detected via instance() without a fresh render re-triggering switchTab()\'s membership sync');
    }

    public function test_select_project_defaults_to_the_workflow_tab(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->assertSet('activeTab', 'workflow');
    }

    public function test_heartbeat_returns_version_string_when_not_on_team_chat_tab(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->set('activeTab', 'activity');

        $this->assertSame(\App\Services\ProjectTask\ProjectHeartbeat::version($project->id), $component->instance()->heartbeat());
    }

    public function test_refresh_sidebar_is_a_safe_island_refresh_target(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('refreshSidebar')
            ->assertHasNoErrors()
            ->assertSet('activeProjectId', $project->id);
    }

    public function test_workspace_island_branch_roots_are_keyed_contents_wrappers(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(Main::class)
            ->assertSeeHtml('wire:key="pane-empty"')
            ->call('selectProject', $project->id)
            ->assertSeeHtml('wire:key="pane-active"')
            ->assertDontSeeHtml('wire:key="pane-empty"');
    }

    public function test_active_project_eager_loads_owner_for_the_header(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Main::class)->call('selectProject', $project->id);
        $active = $component->instance()->activeProject();

        $this->assertTrue($active->relationLoaded('owner'), 'header renders owner->name without triggering a lazy load');
    }

    public function test_header_and_sidebar_render_the_same_deterministic_project_badge(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $badge = (new ProjectPresenter())->badgeColors($project->id);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->assertSeeHtml($badge['bg'])
            ->assertSee(mb_substr($project->name, 0, 1));
    }

    public function test_header_renders_a_chip_per_other_setting_and_nothing_when_none_exist(): void
    {
        [$owner, $withExtra] = $this->ownerWithProject();
        $withExtra->update(['settings' => ['webhook_url' => 'https://example.test/hook']]);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $withExtra->id)
            ->assertSeeHtml('webhook_url: https://example.test/hook');

        [$plainOwner, $plain] = $this->ownerWithProject();

        Livewire::actingAs($plainOwner)->test(Main::class)
            ->call('selectProject', $plain->id)
            ->assertDontSeeHtml('webhook_url');
    }

    public function test_update_project_rejects_stale_save_when_changed_since_form_opened(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('openEdit');

        $this->travel(2)->seconds();
        $project->touch();

        $component
            ->set('projectForm.name', 'updated while stale')
            ->call('updateProject')
            ->assertHasErrors(['form']);

        $this->assertNotSame('updated while stale', $project->fresh()->name);
    }

    public function test_update_project_persists_unchecked_requires_approval_and_cleared_sla(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['requires_approval' => true, 'sla' => 48],
        ]);

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('openEdit');

        $this->assertTrue($component->instance()->projectForm->requiresApproval);
        $this->assertSame(48, $component->instance()->projectForm->slaHours);

        $component
            ->set('projectForm.requiresApproval', false)
            ->set('projectForm.slaHours', null)
            ->call('updateProject')
            ->assertHasNoErrors();

        $this->assertNull($project->fresh()->settings);
    }

    public function test_create_and_edit_project_forms_render_rtl(): void
    {
        [$owner] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(Main::class)
            ->call('openCreate')
            ->assertSeeHtml('modal-inner-card !w-full !max-w-none !p-5 md:!p-6" dir="rtl"');
    }

    public function test_shared_row_actions_component_scopes_pin_mute_tag_stores_by_id_and_scope(): void
    {
        $html = view('components.ui.row-actions', [
            'id' => 42,
            'scope' => 'project',
            'pinNoun' => 'پروژه',
            'muteNoun' => 'پروژه',
        ])->render();

        $this->assertStringContainsString("\$store.pinned.togglePin(42, 'project')", $html);
        $this->assertStringContainsString("\$store.sound.toggleMute(42, 'project')", $html);
        $this->assertStringContainsString("\$store.tagged.tagBg(42, 'project')", $html);
    }

    public function test_search_filters_the_sidebar_project_list_by_name_case_insensitively(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        Project::factory()->create(['owner_id' => $owner->id, 'name' => 'Website Revamp', 'member_ids' => [], 'departments' => []]);
        Project::factory()->create(['owner_id' => $owner->id, 'name' => 'Mobile App', 'member_ids' => [], 'departments' => []]);

        $component = Livewire::actingAs($owner)->test(Main::class);

        $names = collect($component->instance()->myProjects()['rows'])->pluck('name')->all();
        $this->assertCount(2, $names);

        $component->set('search', 'website');
        $names = collect($component->instance()->myProjects()['rows'])->pluck('name')->all();
        $this->assertSame(['Website Revamp'], $names);

        $component->set('search', '');
        $names = collect($component->instance()->myProjects()['rows'])->pluck('name')->all();
        $this->assertCount(2, $names);
    }

    public function test_search_with_no_matches_returns_an_empty_project_list(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        Project::factory()->create(['owner_id' => $owner->id, 'name' => 'Website Revamp', 'member_ids' => [], 'departments' => []]);

        $result = Livewire::actingAs($owner)->test(Main::class)
            ->set('search', 'nonexistent project name')
            ->instance()->myProjects();

        $this->assertSame([], $result['rows']);
        $this->assertSame(0, $result['total']);
        $this->assertFalse($result['hasMore']);
    }

    public function test_load_more_projects_extends_the_sidebar_page_size(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        Project::factory()->count(35)->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $component = Livewire::actingAs($owner)->test(Main::class);

        $result = $component->instance()->myProjects();
        $this->assertCount(30, $result['rows']);
        $this->assertTrue($result['hasMore']);
        $this->assertSame(35, $result['total']);

        $component->call('loadMoreProjects');
        $result = $component->instance()->myProjects();
        $this->assertCount(35, $result['rows']);
        $this->assertFalse($result['hasMore']);
    }

    public function test_searching_resets_the_sidebar_page_size(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        Project::factory()->count(35)->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('loadMoreProjects')
            ->assertSet('projectsLimit', 60)
            ->set('search', 'x')
            ->assertSet('projectsLimit', 30);
    }

    public function test_search_does_not_filter_pending_invite_projects(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $member = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$member->id]]);
        $channel = app(ChannelProvisioner::class)->resolve($project);
        $channel->memberUsers()->updateExistingPivot($member->id, ['entered_at' => null]);

        $component = Livewire::actingAs($member)->test(Main::class)
            ->set('search', 'a name that matches nothing');

        $pending = collect($component->instance()->pendingInviteProjects())->pluck('id')->all();
        $this->assertContains($project->id, $pending, 'search must scope the visible sidebar list only, never the invite-toast queue');
    }

    public function test_project_members_lists_owner_and_members_with_presence(): void
    {
        $member = User::factory()->create(['status' => 'active', 'name' => 'Sara Ahmadi', 'presence' => 'onsite']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);

        $component = Livewire::actingAs($owner)->test(Main::class)->call('selectProject', $project->id);
        $members = $component->instance()->projectMembers();

        $this->assertTrue($members[0]['is_owner'], 'owner must be sorted first');
        $this->assertSame($owner->id, $members[0]['id']);

        $memberRow = collect($members)->firstWhere('id', $member->id);
        $this->assertNotNull($memberRow);
        $this->assertFalse($memberRow['is_owner']);
        $this->assertNotNull($memberRow['presence_label']);
    }

    public function test_mention_candidates_reuses_project_members_query(): void
    {
        $activeMember = User::factory()->create(['status' => 'active', 'name' => 'Sara Ahmadi']);
        $inactiveMember = User::factory()->create(['status' => 'inactive', 'name' => 'Reza Karimi']);
        [$owner, $project] = $this->ownerWithProject([$activeMember->id, $inactiveMember->id]);

        $component = null;
        try {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $component = Livewire::actingAs($owner)->test(Main::class)->call('selectProject', $project->id);

            $userQueries = collect(DB::getQueryLog())->pluck('query')
                ->filter(fn($q) => str_starts_with($q, 'select * from `users` where `users`.`status`'));
            $this->assertCount(1, $userQueries, 'projectMembers and mentionCandidates must share one underlying users query per component instance — just Main\'s own (projectMembers+mentionCandidates), since Activity is now a #[Defer]-swapped sibling whose real mount/query is deferred to a later lazy-load round-trip, not paid synchronously inside selectProject() the way the old always-mounted Activity used to');
        } finally {
            DB::disableQueryLog();
        }

        $members = $component->instance()->projectMembers();
        $candidates = $component->instance()->mentionCandidates();

        $memberIds = collect($members)->pluck('id')->all();
        $this->assertNotContains($inactiveMember->id, $memberIds, 'the header roster is the assignable roster: active people only');

        $candidateNames = $candidates->pluck('name')->all();
        $this->assertContains('Sara Ahmadi', $candidateNames);
        $this->assertNotContains('Reza Karimi', $candidateNames, 'inactive users must stay excluded from mention candidates');
    }

    public function test_project_presenter_lifecycle_legend_returns_six_marker_types_with_distinct_colors(): void
    {
        $legend = (new ProjectPresenter())->lifecycleLegend();

        $this->assertSame(['start', 'change', 'deadline', 'completed', 'projectDeadline', 'cycle-start'], array_keys($legend));
        $colors = collect($legend)->pluck('colorClass');
        $this->assertSame($colors->count(), $colors->unique()->count());
    }

    public function test_project_presenter_lifecycle_event_data_formats_start_and_status_change_lines(): void
    {
        $presenter = new ProjectPresenter();

        $start = $presenter->lifecycleEventData(['marker' => 'start', 'task_id' => 12, 'title' => 'دمو', 'time' => '10:00']);
        $this->assertSame('وظیفه ایجاد شد.', $start['line']);
        $this->assertSame('#۱۲', $start['badge']);

        $change = $presenter->lifecycleEventData(['marker' => 'change', 'task_id' => 1, 'title' => 'دمو', 'time' => '10:00', 'from' => 'todo', 'to' => 'in-progress']);
        $this->assertStringContainsString(TaskStatus::from('todo')->getLabel(), $change['line']);
        $this->assertStringContainsString(TaskStatus::from('in-progress')->getLabel(), $change['line']);
    }

    public function test_project_presenter_lifecycle_event_data_greys_out_resolved_deadline_only(): void
    {
        $presenter = new ProjectPresenter();
        $legend = $presenter->lifecycleLegend();

        $open = $presenter->lifecycleEventData(['marker' => 'deadline', 'task_id' => 1, 'title' => 'x', 'time' => '10:00', 'isResolved' => false]);
        $this->assertSame($legend['deadline']['colorClass'], $open['colorClass']);

        $resolved = $presenter->lifecycleEventData(['marker' => 'deadline', 'task_id' => 1, 'title' => 'x', 'time' => '10:00', 'isResolved' => true]);
        $this->assertSame('bg-[var(--md-sys-color-outline)]', $resolved['colorClass']);
        $this->assertNotSame($legend['deadline']['colorClass'], $resolved['colorClass']);
    }

    public function test_project_presenter_lifecycle_event_data_includes_paused_text_only_when_minutes_present(): void
    {
        $presenter = new ProjectPresenter();

        $withoutPause = $presenter->lifecycleEventData(['marker' => 'start', 'task_id' => 1, 'title' => 'x', 'time' => '10:00']);
        $this->assertNull($withoutPause['pausedText']);

        $withPause = $presenter->lifecycleEventData(['marker' => 'change', 'task_id' => 1, 'title' => 'x', 'time' => '10:00', 'from' => 'pending', 'to' => 'todo', 'pausedMinutes' => 90]);
        $this->assertSame('۱ ساعت', $withPause['pausedText']);
    }

    public function test_project_presenter_form_tab_methods_still_work_after_trait_addition(): void
    {
        $presenter = new ProjectPresenter();
        $tabs = $presenter->projectFormTabs();

        $this->assertSame(['details', 'settings'], collect($tabs)->pluck('key')->all());
        $this->assertContains('projectForm.departments', collect($tabs)->firstWhere('key', 'details')['errors']);

        $errors = new \Illuminate\Support\ViewErrorBag();
        $this->assertSame('details', $presenter->defaultProjectFormTab($tabs, $errors));
    }

    public function test_project_focus_record_honors_tab_query_param_when_present(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['open' => $project->id, 'tab' => 'teamChat'])
            ->actingAs($owner)
            ->test(Main::class)
            ->assertSet('activeTab', 'teamChat');
    }

    public function test_project_focus_record_defaults_to_workflow_tab_when_tab_param_absent(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['open' => $project->id])
            ->actingAs($owner)
            ->test(Main::class)
            ->assertSet('activeTab', 'workflow');
    }

    public function test_select_project_updates_active_project_id_without_disturbing_open(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->assertSet('activeProjectId', $project->id)
            ->assertSet('open', null)
            ->assertDontSeeHtml('focus-chip');
    }

    public function test_selecting_a_second_project_ordinarily_still_leaves_open_untouched(): void
    {
        [$owner, $projectA] = $this->ownerWithProject();
        $projectB = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $projectA->id)
            ->assertSet('activeProjectId', $projectA->id)
            ->assertSet('open', null);

        $component->call('selectProject', $projectB->id)
            ->assertSet('activeProjectId', $projectB->id)
            ->assertSet('open', null)
            ->assertDontSeeHtml('focus-chip');
    }

    public function test_arriving_via_a_genuine_search_deep_link_still_shows_the_focus_chip(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::withQueryParams(['open' => $project->id])
            ->actingAs($owner)->test(Main::class)
            ->assertSet('open', $project->id)
            ->assertSeeHtml('focus-chip');
    }

    public function test_select_project_does_not_set_open_for_a_project_not_visible_to_actor(): void
    {
        $actor = User::factory()->create(['status' => 'active']);
        [, $foreignProject] = $this->ownerWithProject();

        $component = Livewire::actingAs($actor)->test(Main::class);

        $this->assertFalse($component->instance()->selectProject($foreignProject->id));

        $component->assertSet('open', null);
        $this->assertNull($component->instance()->activeProjectId);
    }

    public function test_tab_row_renders_quick_access_link_to_full_channel_in_new_tab(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('switchTab', 'teamChat');

        $channel = $component->instance()->activeChannel();

        $component->assertSeeHtml(route('channels', ['open' => $channel->id]))
            ->assertSeeHtml('target="_blank"');
    }

    public function test_quick_access_channel_link_is_hidden_outside_team_chat_tab(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id);

        $channel = $component->instance()->activeChannel();

        $component->assertDontSeeHtml(route('channels', ['open' => $channel->id]));
    }

    public function test_active_tab_property_is_bound_to_tab_query_string_parameter(): void
    {
        $property = new \ReflectionProperty(Main::class, 'activeTab');
        $attributes = $property->getAttributes(\Livewire\Attributes\Url::class);

        $this->assertNotEmpty($attributes, 'activeTab must carry #[Url] per this project\'s page-level tab-selector convention');
        $this->assertSame('tab', $attributes[0]->newInstance()->as);
    }

    public function test_focus_record_honors_focus_entry_query_param(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $logger = app(ActivityLogger::class);
        $old = $logger->comment($task, $owner, 'old comment');
        foreach (range(1, 55) as $i) {
            $logger->comment($task, $owner, "newer comment $i");
        }

        Livewire::withoutLazyLoading();
        $component = Livewire::withQueryParams(['open' => $project->id, 'tab' => 'activity', 'focus_entry' => $old->id])
            ->actingAs($owner)
            ->test(Main::class);

        $component->assertSeeHtml('data-rf="activity-' . $old->id . '"');
    }

    public function test_focus_record_falls_back_to_project_focus_when_focus_entry_invalid(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['open' => $project->id, 'focus_entry' => 999999999])
            ->actingAs($owner)
            ->test(Main::class)
            ->assertDispatched('record-focus', type: 'project', id: $project->id);
    }

    public function test_focus_record_falls_back_to_project_focus_when_focus_entry_belongs_to_a_different_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        [$otherOwner, $otherProject] = $this->ownerWithProject();
        $otherTask = Task::factory()->ownedBy($otherOwner)->create(['project_id' => $otherProject->id]);
        $foreignReply = app(ActivityLogger::class)->comment($otherTask, $otherOwner, 'foreign project comment');

        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['open' => $project->id, 'focus_entry' => $foreignReply->id])
            ->actingAs($owner)
            ->test(Main::class)
            ->assertDispatched('record-focus', type: 'project', id: $project->id);
    }

    public function test_open_project_task_opens_editable_for_the_owner(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);

        $component = Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('openProjectTask', $task->id);

        $this->assertSame($task->id, $component->get('editingTaskId'));
        $this->assertTrue($component->get('isEditMode'));
        $this->assertFalse($component->get('isReadOnly'));
        $this->assertTrue($component->get('isModalOpen'));
    }

    public function test_open_project_task_opens_read_only_for_an_assignee_when_the_task_is_approved_and_frozen(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $member = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$member->id], 'settings' => ['requires_approval' => true]]);
        $task = Task::factory()->forProject($project)->withStatus('done')->create(['user_id' => $owner->id, 'assigned_to' => $member->id, 'approved_at' => now(), 'approved_by' => $owner->id]);

        $component = Livewire::actingAs($member)
            ->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('openProjectTask', $task->id);

        $this->assertFalse($component->get('isEditMode'));
        $this->assertTrue($component->get('isReadOnly'));
    }

    public function test_kanban_reopen_works_for_the_owner_and_is_refused_for_a_member(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $member = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$member->id], 'settings' => ['requires_approval' => true]]);
        $task = Task::factory()->forProject($project)->withStatus('done')->create(['user_id' => $owner->id, 'assigned_to' => $member->id, 'approved_at' => now(), 'approved_by' => $owner->id]);

        Livewire::actingAs($member)->test(Kanban::class, ['activeProjectId' => $project->id])->call('reopenTask', $task->id);
        $this->assertSame('done', Task::find($task->id)->status);

        Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])->call('reopenTask', $task->id);
        $this->assertSame('in-progress', Task::find($task->id)->status);
    }

    public function test_open_project_task_opens_read_only_for_a_fellow_project_member(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $member = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$member->id]]);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);

        $component = Livewire::actingAs($member)
            ->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('openProjectTask', $task->id);

        $this->assertSame($task->id, $component->get('editingTaskId'));
        $this->assertFalse($component->get('isEditMode'));
        $this->assertTrue($component->get('isReadOnly'));
        $this->assertTrue($component->get('isModalOpen'));
    }

    public function test_open_project_task_rejects_a_task_outside_the_active_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $otherProject = Project::factory()->create(['owner_id' => $owner->id]);
        $foreign = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $otherProject->id]);

        $component = Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('openProjectTask', $foreign->id);

        $this->assertNull($component->get('editingTaskId'));
        $this->assertFalse($component->get('isModalOpen'));
    }

    public function test_open_create_modal_from_project_defaults_the_task_to_the_active_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('openCreateModal')
            ->assertOk();

        $this->assertSame($project->id, $component->instance()->form->projectId);
    }

    public function test_duplicate_task_via_kanban_bridge_rejects_a_task_the_caller_does_not_own_or_is_not_assigned_to(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $stranger = User::factory()->create(['status' => 'active']);
        $otherOwner = User::factory()->create(['status' => 'active']);
        $otherProject = Project::factory()->create(['owner_id' => $otherOwner->id, 'member_ids' => [], 'departments' => []]);
        $foreignTask = Task::factory()->ownedBy($otherOwner)->create(['project_id' => $otherProject->id, 'title' => 'Foreign Task']);

        $countBefore = Task::count();

        Livewire::actingAs($stranger)
            ->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('duplicateTask', $foreignTask->id)
            ->assertOk();

        $this->assertSame($countBefore, Task::count(), 'a spoofed taskId from a task the caller neither owns nor is assigned to must not be duplicated');
    }

    public function test_duplicate_task_via_kanban_bridge_lands_in_the_tasks_own_real_project_not_the_active_one(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $otherProject = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $ownTask = Task::factory()->ownedBy($owner)->create(['project_id' => $otherProject->id, 'title' => 'Own Task In Other Project']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('duplicateTask', $ownTask->id)
            ->assertOk();

        $duplicate = Task::where('title', 'Own Task In Other Project (کپی)')->first();
        $this->assertNotNull($duplicate);
        $this->assertSame($otherProject->id, $duplicate->project_id, 'a duplicated task must land in its source task\'s own project, never the currently-active one');
    }

    public function test_project_task_modal_shows_edit_title_when_opening_an_editable_task(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('openProjectTask', $task->id)
            ->assertSeeHtml('ویرایش وظیفه')
            ->assertDontSeeHtml('ایجاد وظیفه جدید');
    }

    public function test_project_task_modal_shows_view_only_title_when_opening_a_read_only_task(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $member = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$member->id]]);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);

        Livewire::actingAs($member)
            ->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('openProjectTask', $task->id)
            ->assertSeeHtml('مشاهده وظیفه')
            ->assertDontSeeHtml('ایجاد وظیفه جدید');
    }

    public function test_project_task_modal_shows_create_title_when_opening_create_modal(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('openCreateModal')
            ->assertSeeHtml('ایجاد وظیفه جدید');
    }

    public function test_project_member_who_is_not_a_task_collaborator_can_still_reply(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $member = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$member->id]]);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);

        $component = Livewire::actingAs($member)
            ->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('openProjectTask', $task->id);

        $this->assertTrue($component->instance()->canReplyToTask());
    }

    public function test_task_collaborator_can_reply_via_project_kanban(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $collaborator = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$collaborator->id]]);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        TaskDetail::factory()->create(['task_id' => $task->id, 'collaborators' => [$collaborator->id]]);

        Livewire::actingAs($collaborator)
            ->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('openProjectTask', $task->id)
            ->set('taskReplyForm.body', 'یادداشت همکار پروژه')
            ->call('postTaskReply')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertSame(2, $task->fresh()->replies()->count());
    }

    public function test_analytics_tab_is_reachable_and_renders(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Task::factory()->ownedBy($owner)->withStatus('done')->create(['project_id' => $project->id]);

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('switchTab', 'analytics');

        $component->assertSet('activeTab', 'analytics');
        $this->assertStringContainsString('tab-analytics-' . $project->id, $component->html());
    }

    public function test_report_tab_dynamic_component_actually_mounts_inside_the_island_scoped_render(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Task::factory()->ownedBy($owner)->create(['project_id' => $project->id, 'deadline' => null]);

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('switchTab', 'report');

        $this->assertStringContainsString(
            'tab-report-' . $project->id,
            $component->html(),
            'The report dynamic-component must actually mount during switchTab()\'s island-scoped re-render, ' .
            'not just set $activeTab. A regression here (e.g. reintroducing a plain view()-data variable like ' .
            '$tabs instead of a #[Computed] Main::tabs()) silently drops the tag with no error — the island\'s ' .
            'own scoped render context only carries the component\'s public properties/#[Computed] methods, ' .
            'not render()\'s manually-passed extra view data.'
        );
    }

    public function test_project_calendar_sibling_component_actually_mounts_after_switch_tab(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('switchTab', 'projectCalendar');

        $this->assertStringContainsString(
            'tab-projectCalendar-' . $project->id,
            $component->html(),
            'The Project\Calendar dynamic-component must actually mount during switchTab()\'s island-scoped ' .
            're-render, not just set $activeTab — a regression here (e.g. a stale $tabs config) would silently ' .
            'drop the tag with no error.'
        );
    }

    public function test_project_kanban_sibling_component_actually_mounts_after_switch_tab(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('switchTab', 'kanban');

        $this->assertStringContainsString(
            'tab-kanban-' . $project->id,
            $component->html(),
            'The Project\Kanban dynamic-component must actually mount during switchTab()\'s island-scoped ' .
            're-render, not just set $activeTab — a regression here (e.g. a stale $tabs config) would silently ' .
            'drop the tag with no error.'
        );
    }

    public function test_project_activity_sibling_component_actually_mounts_after_switch_tab(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('switchTab', 'kanban')
            ->call('switchTab', 'activity');

        $this->assertStringContainsString(
            'tab-activity-' . $project->id,
            $component->html(),
            'The Project\Activity dynamic-component must actually mount during switchTab()\'s island-scoped ' .
            're-render, not just set $activeTab — a regression here (e.g. a stale $tabs config) would silently ' .
            'drop the tag with no error.'
        );
    }

    public function test_project_teamchat_sibling_component_actually_mounts_after_switch_tab(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('switchTab', 'teamChat');

        $this->assertStringContainsString(
            'team-chat-' . $project->id,
            $component->html(),
            'The always-mounted Project\TeamChat sibling component must actually render inside Main\'s HTML ' .
            'after switchTab() — this is a hidden-via-CSS always-rendered child, not an island-scoped fragment ' .
            'anymore, so a regression here (e.g. a bad wire:key, a missing mount param) would silently drop the ' .
            'tag or desync it from the active project.'
        );
    }

    public function test_warm_is_renderless(): void
    {
        $method = new \ReflectionMethod(Main::class, 'warm');

        $this->assertNotEmpty($method->getAttributes(Renderless::class), 'Main::warm() must carry #[Renderless] so a hover-triggered warm never forces a full re-render');
    }

    public function test_warm_rejects_a_tab_outside_the_switch_tab_whitelist(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Main::class)->call('selectProject', $project->id);

        $component->call('warm', 'not-a-real-tab');

        $key = sprintf('report:status_breakdown:%d:t%s', $project->id, ModelCacheVersion::version(Task::class));
        $this->assertFalse(Cache::has($key), 'an invalid tab must not fall through to the analytics warm branch — Main\'s own ambient render never touches this key, unlike task-report:{id}:{id} which header.blade.php\'s reportSummary already populates on every selectProject() call regardless of warm()');
    }

    public function test_warm_does_nothing_without_an_active_project(): void
    {
        $owner = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($owner)->test(Main::class)->call('warm', 'report');

        $this->assertFalse(Cache::has('task-report:0:' . $owner->id));
    }

    public function test_warm_report_populates_the_report_summary_cache(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('warm', 'report');

        $this->assertTrue(Cache::has(ModelCacheVersion::key(Task::class, sprintf('task-report:%d:p%s:d%s:%s', $project->id, ModelCacheVersion::version(Project::class), ModelCacheVersion::version(TaskDetail::class), now()->toDateString()))));
    }

    public function test_warm_analytics_populates_the_insights_cache(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', $project->id)
            ->call('warm', 'analytics');

        $taskVersion = ModelCacheVersion::version(Task::class);
        $detailVersion = ModelCacheVersion::version(TaskDetail::class);

        $this->assertTrue(Cache::has(sprintf('report:insights:%d:t%s:d%s:%s', $project->id, $taskVersion, $detailVersion, now()->toDateString())));
    }

    public function test_warm_is_a_safe_no_op_for_kanban_calendar_activity_and_teamchat(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Main::class)->call('selectProject', $project->id);

        foreach (['kanban', 'projectCalendar', 'activity', 'teamChat'] as $tab) {
            $component->call('warm', $tab)->assertHasNoErrors();
        }
    }

    public function test_create_modal_renders_without_error_when_no_project_is_active(): void
    {
        $owner = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('openCreateModal')
            ->assertOk()
            ->assertSet('form.projectId', null);
    }

    public function test_level_up_form_is_usable_from_the_project_module_shared_modal(): void
    {
        $owner = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('openCreateModal')
            ->set('form.newTitle', 'Standalone task title')
            ->call('toggleLevelUpForm')
            ->assertOk()
            ->assertSet('showLevelUpForm', true)
            ->assertSet('levelUpForm.name', 'Standalone task title');
    }

    public function test_stashing_a_pending_project_from_the_project_module_modal_creates_no_orphan_row(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $countBefore = Project::count();

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('openCreateModal')
            ->call('toggleLevelUpForm')
            ->set('levelUpForm.name', 'From Project Modal')
            ->call('createProjectFromTask')
            ->assertOk();

        $this->assertSame($countBefore, Project::count(), 'stashing must not create a Project row when triggered from the Project module\'s shared modal either');
    }

    private function presenter(): ProjectPresenter
    {
        return new ProjectPresenter();
    }

    private function projectWithDeadline(?string $deadline): Project
    {
        $project = new Project();
        $project->settings = $deadline === null ? [] : ['deadline' => $deadline];

        return $project;
    }

    private function chipsFor(?string $deadline, array $summary = ['overdue' => 0, 'at_risk' => 0]): array
    {
        return $this->presenter()->headerRiskChips($this->projectWithDeadline($deadline), $summary);
    }

    public function test_header_risk_chips_render_overdue_at_risk_and_countdown_in_order(): void
    {
        $chips = $this->chipsFor(now()->addDays(10)->toDateString(), ['overdue' => 3, 'at_risk' => 2]);

        $this->assertSame(['schedule', 'warning', 'event_busy'], array_column($chips, 'icon'));
        $this->assertSame('۳ دیرکرد', $chips[0]['text']);
        $this->assertSame('۲ نقض SLA یا مهلت', $chips[1]['text']);
        $this->assertSame('۱۰ روز تا مهلت پروژه', $chips[2]['text']);
    }

    public function test_header_risk_chips_empty_when_nothing_applies(): void
    {
        $this->assertSame([], $this->chipsFor(null));
        $this->assertSame([], $this->chipsFor(null, ['overdue' => 0, 'at_risk' => 0]));
    }

    public function test_deadline_countdown_chip_escalates_by_proximity(): void
    {
        $past = $this->chipsFor(now()->subDays(2)->toDateString());
        $this->assertSame('۲ روز از مهلت پروژه گذشت', $past[0]['text']);
        $this->assertStringContainsString('error-container', $past[0]['classes']);

        $today = $this->chipsFor(now()->toDateString());
        $this->assertSame('امروز مهلت پروژه', $today[0]['text']);

        $soon = $this->chipsFor(now()->addDays(5)->toDateString());
        $this->assertSame('۵ روز تا مهلت پروژه', $soon[0]['text']);
        $this->assertStringContainsString('warning-container', $soon[0]['classes']);

        $far = $this->chipsFor(now()->addDays(20)->toDateString());
        $this->assertSame('۲۰ روز تا مهلت پروژه', $far[0]['text']);
        $this->assertStringContainsString('success-container', $far[0]['classes']);
    }
    public function test_deadline_chip_disappears_once_every_task_is_done_or_the_project_is_archived(): void
    {
        $past = now()->subDays(7)->toDateString();

        $this->assertNotEmpty($this->chipsFor($past, ['total' => 4, 'done' => 3, 'overdue' => 0, 'at_risk' => 0]));
        $this->assertSame([], $this->chipsFor($past, ['total' => 4, 'done' => 4, 'overdue' => 0, 'at_risk' => 0]));

        $archived = $this->projectWithDeadline($past);
        $archived->archived_at = now();
        $this->assertSame([], $this->presenter()->headerRiskChips($archived, ['total' => 4, 'done' => 1, 'overdue' => 0, 'at_risk' => 0]));
    }

    public function test_activity_feed_paginates_with_load_older(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $logger = app(ActivityLogger::class);
        foreach (range(1, 55) as $i) {
            $logger->comment($task, $owner, "comment $i");
        }

        $component = Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id]);

        $feed = $component->instance()->activityFeed();
        $this->assertCount(50, $feed['rows']);
        $this->assertTrue($feed['hasMore']);

        $component->call('loadOlderActivity');
        $feed = $component->instance()->activityFeed();
        $this->assertCount(57, $feed['rows']);
        $this->assertFalse($feed['hasMore']);
    }

    public function test_activity_state_resets_via_full_remount_between_projects(): void
    {
        [$owner, $projectA] = $this->ownerWithProject();
        $projectB = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $projectA->id])
            ->call('loadOlderActivity')
            ->assertSet('activityLimit', 100);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $projectB->id])
            ->assertSet('activityLimit', 50);
    }

    public function test_grouped_activity_feed_groups_by_date_and_sets_label(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $logger = app(ActivityLogger::class);
        $logger->comment($task, $owner, 'today one');
        $logger->comment($task, $owner, 'today two');
        $yesterday = $logger->comment($task, $owner, 'yesterday one');
        $yesterday->forceFill(['created_at' => now()->subDay()])->save();

        $component = Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id]);
        $groups = $component->instance()->groupedActivityFeed();

        $this->assertCount(2, $groups);
        $labels = collect($groups)->pluck('label')->all();
        $this->assertContains('امروز', $labels);
        $this->assertContains('دیروز', $labels);

        $todayGroup = collect($groups)->firstWhere('label', 'امروز');
        $yesterdayGroup = collect($groups)->firstWhere('label', 'دیروز');
        $this->assertCount(4, $todayGroup['entries']);
        $this->assertCount(1, $yesterdayGroup['entries']);
    }

    public function test_activity_feed_marks_can_modify_for_own_recent_comment_only(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $logger = app(ActivityLogger::class);
        $ownReply = $logger->comment($task, $owner, 'my comment');
        $othersReply = $logger->comment($task, $member, 'their comment');
        $oldReply = $logger->comment($task, $owner, 'old comment');
        $oldReply->forceFill(['created_at' => now()->subSeconds(700)])->save();

        $rows = Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->instance()->activityFeed()['rows'];

        $byId = collect($rows)->keyBy('id');
        $this->assertTrue($byId[$ownReply->id]['can_modify']);
        $this->assertFalse($byId[$othersReply->id]['can_modify'], 'a viewer must not be able to modify another user\'s comment');
        $this->assertFalse($byId[$oldReply->id]['can_modify'], 'a comment past the edit time window must not be modifiable');
    }

    public function test_toggle_reaction_adds_and_removes(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $reply = app(ActivityLogger::class)->comment($task, $owner, 'hello');

        $component = Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id]);

        $component->call('toggleReaction', $reply->id, '👍');
        $this->assertCount(1, $reply->fresh()->reactions);

        $component->call('toggleReaction', $reply->id, '👍');
        $this->assertCount(0, $reply->fresh()->reactions);
    }

    public function test_toggle_reaction_with_disallowed_emoji_does_not_persist(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $reply = app(ActivityLogger::class)->comment($task, $owner, 'hello');

        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->call('toggleReaction', $reply->id, 'not-an-emoji');

        $this->assertEmpty($reply->fresh()->reactions, 'the emoji allowlist must be enforced through the Livewire call path too, not just the Action directly');
    }

    public function test_comment_mentioning_a_member_marks_mentions_you_for_that_member(): void
    {
        $member = User::factory()->create(['status' => 'active', 'name' => 'Sara Ahmadi']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        app(ActivityLogger::class)->comment($task, $owner, 'hey @Sara Ahmadi please review');

        $memberFeed = Livewire::actingAs($member)->test(Activity::class, ['activeProjectId' => $project->id])
            ->instance()->activityFeed();

        $row = collect($memberFeed['rows'])->first(fn($r) => str_contains((string) ($r['body_html'] ?? ''), 'please review'));
        $this->assertTrue($row['mentions_you']);
        $this->assertStringContainsString('@Sara Ahmadi</span>', $row['body_html']);
    }

    public function test_comment_not_mentioning_a_member_does_not_mark_mentions_you(): void
    {
        $member = User::factory()->create(['status' => 'active', 'name' => 'Sara Ahmadi']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        app(ActivityLogger::class)->comment($task, $owner, 'general update, no mentions');

        $memberFeed = Livewire::actingAs($member)->test(Activity::class, ['activeProjectId' => $project->id])
            ->instance()->activityFeed();

        $this->assertFalse($memberFeed['rows'][0]['mentions_you']);
    }

    public function test_activity_feed_never_marks_system_entries_as_modifiable(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        app(ActivityLogger::class)->system($task, $owner, \App\Enums\TaskActivityType::StatusChange);

        $rows = Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->instance()->activityFeed()['rows'];

        $this->assertFalse($rows[0]['can_modify'], 'system-generated audit entries must never be user-editable, even for the actor who triggered them');
    }

    public function test_save_edited_comment_updates_body_and_marks_is_edited(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $reply = app(ActivityLogger::class)->comment($task, $owner, 'original text');

        $component = Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->call('startEditComment', $reply->id, 'original text')
            ->assertSet('editingReplyId', $reply->id)
            ->set('editingReplyBody', 'edited text')
            ->call('saveEditedComment');

        $this->assertSame('edited text', $reply->fresh()->body);
        $this->assertNull($component->instance()->editingReplyId);

        $row = collect($component->instance()->activityFeed()['rows'])->firstWhere('id', $reply->id);
        $this->assertTrue($row['is_edited']);
    }

    public function test_comment_edit_toggle_branches_are_keyed_for_morph_safety(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $reply = app(ActivityLogger::class)->comment($task, $owner, 'original text');

        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->assertSeeHtml('wire:key="activity-comment-view"')
            ->assertDontSeeHtml('wire:key="activity-comment-edit"')
            ->call('startEditComment', $reply->id, 'original text')
            ->assertSeeHtml('wire:key="activity-comment-edit"')
            ->assertDontSeeHtml('wire:key="activity-comment-view"')
            ->call('cancelEditComment')
            ->assertSeeHtml('wire:key="activity-comment-view"')
            ->assertDontSeeHtml('wire:key="activity-comment-edit"');
    }

    public function test_reacting_to_a_comment_never_falsely_flips_is_edited(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $untouched = app(ActivityLogger::class)->comment($task, $owner, 'never edited');
        $edited = app(ActivityLogger::class)->comment($task, $owner, 'will be edited');

        $component = Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->set('editingReplyId', $edited->id)
            ->set('editingReplyBody', 'edited text')
            ->call('saveEditedComment')
            ->call('toggleReaction', $untouched->id, '👍')
            ->call('toggleReaction', $edited->id, '🎉');

        $rows = collect($component->instance()->activityFeed()['rows'])->keyBy('id');
        $this->assertFalse($rows[$untouched->id]['is_edited'], 'reacting to a never-edited comment must not falsely mark it as edited');
        $this->assertTrue($rows[$edited->id]['is_edited'], 'a genuinely edited comment must stay marked as edited after an unrelated reaction toggle');
    }

    public function test_save_edited_comment_rejects_editing_someone_elses_comment(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $reply = app(ActivityLogger::class)->comment($task, $owner, 'owner comment');

        Livewire::actingAs($member)->test(Activity::class, ['activeProjectId' => $project->id])
            ->set('editingReplyId', $reply->id)
            ->set('editingReplyBody', 'hijacked')
            ->call('saveEditedComment');

        $this->assertSame('owner comment', $reply->fresh()->body);
    }

    public function test_save_edited_comment_rejects_member_removed_from_project(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $reply = app(ActivityLogger::class)->comment($task, $member, 'about to lose access');
        $project->update(['member_ids' => []]);

        Livewire::actingAs($member)->test(Activity::class, ['activeProjectId' => $project->id])
            ->set('editingReplyId', $reply->id)
            ->set('editingReplyBody', 'hijacked after removal')
            ->call('saveEditedComment');

        $this->assertSame('about to lose access', $reply->fresh()->body);
    }

    public function test_save_edited_comment_rejects_after_edit_time_window(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $reply = app(ActivityLogger::class)->comment($task, $owner, 'original text');
        $reply->forceFill(['created_at' => now()->subSeconds(700)])->save();

        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->set('editingReplyId', $reply->id)
            ->set('editingReplyBody', 'too late')
            ->call('saveEditedComment');

        $this->assertSame('original text', $reply->fresh()->body);
    }

    public function test_delete_comment_removes_own_recent_comment(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $reply = app(ActivityLogger::class)->comment($task, $owner, 'delete me');

        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->call('deleteComment', $reply->id);

        $this->assertNull(Reply::find($reply->id));
    }

    public function test_delete_comment_rejects_deleting_someone_elses_comment(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $reply = app(ActivityLogger::class)->comment($task, $owner, 'owner comment');

        Livewire::actingAs($member)->test(Activity::class, ['activeProjectId' => $project->id])
            ->call('deleteComment', $reply->id);

        $this->assertNotNull(Reply::find($reply->id));
    }

    public function test_delete_comment_rejects_member_removed_from_project(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $reply = app(ActivityLogger::class)->comment($task, $member, 'about to lose access');
        $project->update(['member_ids' => []]);

        Livewire::actingAs($member)->test(Activity::class, ['activeProjectId' => $project->id])
            ->call('deleteComment', $reply->id);

        $this->assertNotNull(Reply::find($reply->id));
    }

    public function test_delete_comment_rejects_system_entries(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $entry = app(ActivityLogger::class)->system($task, $owner, \App\Enums\TaskActivityType::StatusChange);

        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->call('deleteComment', $entry->id);

        $this->assertNotNull(Reply::find($entry->id), 'system-generated audit entries must never be user-deletable');
    }

    public function test_activity_composer_accepts_a_file_attachment(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $file = UploadedFile::fake()->image('scan.jpg');

        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->set('activityComposer.body', 'see attached')
            ->set('activityComposer.files', [$file])
            ->call('postComment');

        $reply = Reply::where('repliable_type', Project::class)->where('repliable_id', $project->id)->where('body', 'see attached')->first();
        $this->assertNotNull($reply);
        $meta = $reply->files[0];
        $this->assertSame('scan.jpg', $meta['name'], 'original filename must survive the upload');
        $this->assertNotEmpty($meta['path'], 'stored disk path must be recorded, not just the display name');
        $this->assertStringStartsWith('image/', $meta['mime'], 'mime type must be captured from the actual uploaded file');
        $this->assertGreaterThan(0, $meta['size'], 'file size must be captured, not left null/zero');
    }

    public function test_activity_composer_staged_attachment_preview_renders_after_upload(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $file = UploadedFile::fake()->image('scan.jpg');

        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->set('activityComposer.files', [$file])
            ->assertSeeHtml('scan.jpg');
    }

    public function test_activity_composer_draft_survives_a_refresh_bridge_call(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->set('activityComposer.body', 'work in progress draft')
            ->call('refreshActivity')
            ->assertSet('activityComposer.body', 'work in progress draft');
    }

    public function test_remove_activity_attachment_drops_entry_by_index(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $fileA = UploadedFile::fake()->image('a.jpg');
        $fileB = UploadedFile::fake()->image('b.jpg');

        $component = Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->set('activityComposer.files', [$fileA, $fileB])
            ->call('removeActivityAttachment', 0);

        $files = $component->instance()->activityComposer->files;
        $this->assertCount(1, $files);
        $this->assertSame('b.jpg', $files[0]->getClientOriginalName());
    }

    public function test_activity_view_renders_attachment_link_for_reply_with_files(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        app(ActivityLogger::class)->comment($task, $owner, 'see attached', [UploadedFile::fake()->image('proof.jpg')]);

        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->assertSeeHtml('proof.jpg');
    }

    public function test_activity_view_renders_search_pin_and_export_controls(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        app(ActivityLogger::class)->comment($task, $owner, 'a comment');

        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->assertSeeHtml('activitySearch')
            ->assertSeeHtml('activityPinnedOnly')
            ->assertSeeHtml('exportActivity')
            ->assertSeeHtml('togglePin');
    }

    public function test_activity_view_renders_edit_and_delete_only_for_own_modifiable_comment(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        app(ActivityLogger::class)->comment($task, $owner, 'owner comment');

        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->assertSeeHtml('startEditComment')
            ->assertSeeHtml('confirmDeleteComment');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($member)->test(Activity::class, ['activeProjectId' => $project->id])
            ->assertDontSeeHtml('startEditComment')
            ->assertDontSeeHtml('confirmDeleteComment');
    }

    public function test_activity_entry_renders_data_rf_attribute_for_deep_link_focus(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $reply = app(ActivityLogger::class)->comment($task, $owner, 'find me');

        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->assertSeeHtml('data-rf="activity-' . $reply->id . '"');
    }

    public function test_focus_activity_entry_bumps_limit_to_include_an_older_entry(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $logger = app(ActivityLogger::class);
        $old = $logger->comment($task, $owner, 'old comment');
        foreach (range(1, 55) as $i) {
            $logger->comment($task, $owner, "newer comment $i");
        }

        $component = Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->assertSet('activityLimit', 50)
            ->call('focusActivityEntry', $old->id);

        $this->assertGreaterThan(50, $component->instance()->activityLimit);
        $ids = collect($component->instance()->activityFeed()['rows'])->pluck('id')->all();
        $this->assertContains($old->id, $ids, 'the focused entry must be included in the reloaded feed window');
    }

    public function test_focus_activity_entry_returns_false_for_unknown_entry(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $result = Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->call('focusActivityEntry', 999999999)
            ->instance();

        $this->assertSame(50, $result->activityLimit, 'an unknown entry id must not perturb the normal pagination state');
    }

    public function test_focus_activity_entry_rejects_a_reply_belonging_to_a_different_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        [$otherOwner, $otherProject] = $this->ownerWithProject();
        $otherTask = Task::factory()->ownedBy($otherOwner)->create(['project_id' => $otherProject->id]);
        $foreignReply = app(ActivityLogger::class)->comment($otherTask, $otherOwner, 'foreign project comment');

        $component = Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->call('focusActivityEntry', $foreignReply->id);

        $component->assertNotDispatched('record-focus');
        $this->assertSame(50, $component->instance()->activityLimit, 'a reply from a project the caller is not viewing must not perturb pagination state');
    }

    public function test_mount_honors_focus_entry_query_param_and_dispatches_nested_focus(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $logger = app(ActivityLogger::class);
        $old = $logger->comment($task, $owner, 'old comment');
        foreach (range(1, 55) as $i) {
            $logger->comment($task, $owner, "newer comment $i");
        }

        $component = Livewire::withQueryParams(['focus_entry' => $old->id])
            ->actingAs($owner)
            ->test(Activity::class, ['activeProjectId' => $project->id]);

        $this->assertGreaterThan(50, $component->instance()->activityLimit);
        $component->assertSeeHtml('data-rf="activity-' . $old->id . '"')
            ->assertDispatched('record-focus', type: 'activity', id: $old->id);
    }

    public function test_mount_honors_focus_entry_via_referer_fallback_when_the_query_string_is_empty(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $logger = app(ActivityLogger::class);
        $old = $logger->comment($task, $owner, 'old comment');
        foreach (range(1, 55) as $i) {
            $logger->comment($task, $owner, "newer comment $i");
        }

        $component = Livewire::withHeaders(['referer' => 'http://localhost/projects?open=' . $project->id . '&focus_entry=' . $old->id])
            ->actingAs($owner)
            ->test(Activity::class, ['activeProjectId' => $project->id]);

        $this->assertGreaterThan(50, $component->instance()->activityLimit);
    }

    public function test_activity_active_project_id_is_locked_against_client_tampering(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $otherOwner = User::factory()->create(['status' => 'active']);
        $otherProject = Project::factory()->create(['owner_id' => $otherOwner->id, 'member_ids' => [], 'departments' => []]);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($owner)
            ->test(Activity::class, ['activeProjectId' => $project->id])
            ->set('activeProjectId', $otherProject->id);
    }

    public function test_activity_limit_is_locked_against_client_tampering(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($owner)
            ->test(Activity::class, ['activeProjectId' => $project->id])
            ->set('activityLimit', 5000);
    }

    public function test_activity_renders_a_placeholder_when_lazy_loading_is_active(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        \Livewire\Features\SupportLazyLoading\SupportLazyLoading::$disableWhileTesting = false;

        try {
            Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
                ->assertSeeHtml('در حال بارگذاری فعالیت‌ها و نظرات')
                ->assertDontSeeHtml('postComment');
        } finally {
            Livewire::withoutLazyLoading();
        }
    }

    public function test_activity_placeholder_root_carries_the_refresh_bridge_listener(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->assertSeeHtml('x-on:project-activity-refresh.window="$wire.refreshActivity()"');

        \Livewire\Features\SupportLazyLoading\SupportLazyLoading::$disableWhileTesting = false;

        try {
            Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
                ->assertSeeHtml('x-on:project-activity-refresh.window="$wire.refreshActivity()"');
        } finally {
            Livewire::withoutLazyLoading();
        }
    }

    private function logDoneReply(Task $task, string $relativeCreated = '-3 days'): void
    {
        $reply = Reply::factory()->create([
            'repliable_type' => Task::class,
            'repliable_id' => $task->id,
            'user_id' => $task->user_id,
            'body' => null,
            'files' => [],
            'type' => TaskActivityType::StatusChange,
            'payload' => ['from' => 'in-progress', 'to' => 'done'],
        ]);
        DB::table('replies')->where('id', $reply->id)->update(['created_at' => now()->modify($relativeCreated)]);
        Task::whereKey($task->id)->toBase()->update(['completed_at' => now()->modify($relativeCreated)]);
    }

    public function test_analytics_chart_data_reflects_flow_risk_people_shape_with_department_population(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $eng = Department::create(['code' => 'ENG', 'name' => 'Engineering', 'description' => '']);
        $ops = Department::create(['code' => 'OPS', 'name' => 'Operations', 'description' => '']);

        $doneUrgentEng = Task::factory()->ownedBy($owner)->withStatus('done')->create(['project_id' => $project->id, 'priority' => 'urgent', 'deadline' => null]);
        TaskDetail::factory()->create(['task_id' => $doneUrgentEng->id, 'department_id' => $eng->code]);

        $todoLowEng = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'priority' => 'low', 'deadline' => null]);
        TaskDetail::factory()->create(['task_id' => $todoLowEng->id, 'department_id' => $eng->code]);

        $pendingHighOps = Task::factory()->ownedBy($owner)->withStatus('pending')->create(['project_id' => $project->id, 'priority' => 'high', 'deadline' => null]);
        TaskDetail::factory()->create(['task_id' => $pendingHighOps->id, 'department_id' => $ops->code]);

        Task::factory()->ownedBy($owner)->withStatus('in-progress')->create(['project_id' => $project->id, 'priority' => 'medium', 'deadline' => null]);

        $data = Livewire::actingAs($owner)->test(Analytics::class, ['activeProjectId' => $project->id])
            ->get('analyticsChartData');

        $this->assertSame(['flow', 'risk', 'people', 'meta'], array_keys($data));
        $this->assertSame(4, $data['meta']['total']);
        $this->assertSame(['labels' => false, 'department' => true], $data['meta']['populated']);

        $matrix = $data['risk']['matrix'];
        $this->assertCount(4, $matrix['priorities']);
        $this->assertCount(4, $matrix['statuses']);
        $this->assertSame(4, array_sum(array_map('array_sum', $matrix['cells'])));

        $department = $data['people']['departmentCompletion'];
        $this->assertNotNull($department);
        $this->assertSame(['Engineering', 'Operations'], $department['labels']);
        $this->assertSame([1, 0], $department['datasets'][0]['data']);
        $this->assertSame([1, 1], $department['datasets'][1]['data']);

        $this->assertNull($data['people']['labelsDistribution']);
        $this->assertSame(0, $data['risk']['atRiskCount']);
        $this->assertNull($data['flow']['medianCycle']);
        $this->assertNull($data['flow']['throughput']);
    }

    public function test_analytics_empty_state_shape_for_project_with_zero_tasks(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Analytics::class, ['activeProjectId' => $project->id]);

        $summary = $component->get('reportSummary');
        $this->assertSame(0, $summary['total']);

        $data = $component->get('analyticsChartData');
        $this->assertSame(0, $data['meta']['total']);
        $this->assertSame(['labels' => false, 'department' => false], $data['meta']['populated']);
        $this->assertNull($data['flow']['throughput']);
        $this->assertNull($data['flow']['medianCycle']);
        $this->assertNull($data['risk']['adherence']);
        $this->assertNull($data['people']['labelsDistribution']);
        $this->assertNull($data['people']['departmentCompletion']);
        $this->assertNull($data['flow']['staleDistribution']);
        $this->assertNull($data['flow']['cycleDistribution']);
        $this->assertNull($data['people']['doneByAssignee']);
        $this->assertSame(0, array_sum(array_map('array_sum', $data['risk']['matrix']['cells'])));
    }

    public function test_cycle_time_computed_from_status_change_replies(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        for ($i = 0; $i < 3; $i++) {
            $task = Task::factory()->ownedBy($owner)->withStatus('done')->updatedAt('-10 days')->create(['project_id' => $project->id, 'deadline' => null]);
            $this->logDoneReply($task, '-3 days');
        }

        $data = Livewire::actingAs($owner)->test(Analytics::class, ['activeProjectId' => $project->id])
            ->get('analyticsChartData');

        $this->assertSame(7.0, $data['flow']['medianCycle']);
    }

    public function test_stale_distribution_buckets_incomplete_tasks_by_idle_age(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        Task::factory()->ownedBy($owner)->withStatus('todo')->updatedAt('-10 days')->create(['project_id' => $project->id]);
        Task::factory()->ownedBy($owner)->withStatus('in-progress')->updatedAt('-20 days')->create(['project_id' => $project->id]);
        Task::factory()->ownedBy($owner)->withStatus('todo')->updatedAt('-45 days')->create(['project_id' => $project->id]);
        Task::factory()->ownedBy($owner)->withStatus('done')->updatedAt('-40 days')->create(['project_id' => $project->id]);

        $data = Livewire::actingAs($owner)->test(Analytics::class, ['activeProjectId' => $project->id])
            ->get('analyticsChartData');

        $stale = $data['flow']['staleDistribution'];
        $this->assertSame([1, 1, 1, 1], $stale['datasets'][0]['data']);
        $this->assertSame('فعال (۷ روز اخیر)', $stale['labels'][0]);
        $this->assertSame('بی‌تغیر +۳۰ روز', $stale['labels'][3]);
    }

    public function test_cycle_distribution_histograms_done_tasks_by_completion_duration(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        foreach (['-1 days', '-5 days', '-10 days', '-20 days'] as $createdAt) {
            $task = Task::factory()->ownedBy($owner)->withStatus('done')->create(['project_id' => $project->id, 'deadline' => null, 'created_at' => now()->modify($createdAt)]);
            $this->logDoneReply($task, 'now');
        }

        $data = Livewire::actingAs($owner)->test(Analytics::class, ['activeProjectId' => $project->id])
            ->get('analyticsChartData');

        $cycle = $data['flow']['cycleDistribution'];
        $this->assertSame([1, 1, 1, 1], $cycle['datasets'][0]['data']);
        $this->assertSame(['۰ تا ۳ روز', '۳ تا ۷ روز', '۷ تا ۱۴ روز', 'بیش از ۱۴ روز'], $cycle['labels']);
    }

    public function test_done_by_assignee_counts_done_tasks_per_assignee_in_desc_order(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $busy = User::factory()->create(['status' => 'active']);
        $light = User::factory()->create(['status' => 'active']);

        Task::factory()->ownedBy($owner)->withStatus('done')->count(3)->create(['project_id' => $project->id, 'assigned_to' => $busy->id]);
        Task::factory()->ownedBy($owner)->withStatus('done')->create(['project_id' => $project->id, 'assigned_to' => $light->id]);
        Task::factory()->ownedBy($owner)->withStatus('todo')->count(2)->create(['project_id' => $project->id, 'assigned_to' => $light->id]);

        $data = Livewire::actingAs($owner)->test(Analytics::class, ['activeProjectId' => $project->id])
            ->get('analyticsChartData');

        $done = $data['people']['doneByAssignee'];
        $this->assertSame([$busy->name, $light->name], $done['labels']);
        $this->assertSame([(string) $busy->id, (string) $light->id], $done['values']);
        $this->assertSame([3, 1], $done['datasets'][0]['data']);
    }

    public function test_gates_hide_metrics_below_threshold(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $task = Task::factory()->ownedBy($owner)->withStatus('done')->create(['project_id' => $project->id, 'deadline' => now()->subDay()]);
        $this->logDoneReply($task, '-1 days');

        $data = Livewire::actingAs($owner)->test(Analytics::class, ['activeProjectId' => $project->id])
            ->get('analyticsChartData');

        $this->assertNull($data['flow']['medianCycle']);
        $this->assertNull($data['flow']['throughput']);
        $this->assertNull($data['risk']['adherence']);
    }

    public function test_empty_enrichment_dimensions_emit_no_widget(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'deadline' => null]);
        Task::factory()->ownedBy($owner)->withStatus('in-progress')->create(['project_id' => $project->id, 'deadline' => null]);

        $data = Livewire::actingAs($owner)->test(Analytics::class, ['activeProjectId' => $project->id])
            ->get('analyticsChartData');

        $this->assertNull($data['people']['labelsDistribution']);
        $this->assertNull($data['people']['departmentCompletion']);
        $this->assertSame(['labels' => false, 'department' => false], $data['meta']['populated']);
    }

    public function test_analytics_active_project_id_is_locked_against_client_tampering(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $otherOwner = User::factory()->create(['status' => 'active']);
        $otherProject = Project::factory()->create(['owner_id' => $otherOwner->id, 'member_ids' => [], 'departments' => []]);
        Task::factory()->ownedBy($otherOwner)->create(['project_id' => $otherProject->id]);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($owner)
            ->test(Analytics::class, ['activeProjectId' => $project->id])
            ->set('activeProjectId', $otherProject->id);
    }

    public function test_analytics_renders_a_placeholder_when_lazy_loading_is_active(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        \Livewire\Features\SupportLazyLoading\SupportLazyLoading::$disableWhileTesting = false;

        try {
            Livewire::actingAs($owner)->test(Analytics::class, ['activeProjectId' => $project->id])
                ->assertSeeHtml('در حال بارگذاری تحلیل‌ها')
                ->assertDontSeeHtml('کل وظایف');
        } finally {
            Livewire::withoutLazyLoading();
        }
    }

    public function test_tab_charts_have_config_and_drill_map_is_pinned_to_clickable_charts(): void
    {
        $presenter = new ProjectAnalyticsPresenter();
        $configs = $presenter->chartConfigMap();
        $drills = $presenter->drillParamMap();

        foreach ($presenter->tabs() as $tab) {
            $this->assertNotSame([], $tab['charts']);
            foreach ($tab['charts'] as $chartKey) {
                $this->assertArrayHasKey($chartKey, $configs, "missing chartConfig for {$chartKey}");
                $this->assertNotSame('', $configs[$chartKey]['label']);
                $this->assertNotSame('', $configs[$chartKey]['description']);
                $this->assertNotSame([], $configs[$chartKey]['options']);
            }
        }

        $this->assertSame(['atRiskByPriority', 'horizon', 'perAssignee', 'doneByAssignee', 'labelsDistribution', 'departmentCompletion'], array_keys($drills));
        foreach (array_keys($drills) as $chartKey) {
            $this->assertArrayHasKey($chartKey, $configs, "drill param without chartConfig for {$chartKey}");
        }

        $this->assertSame('assignee', $drills['doneByAssignee']);
    }

    public function test_chart_data_routes_each_tab_chart_to_its_insights_bucket(): void
    {
        $presenter = new ProjectAnalyticsPresenter();

        foreach ($presenter->tabs() as $tabKey => $tab) {
            foreach ($tab['charts'] as $chartKey) {
                $payload = ['labels' => ['probe']];
                $insights = [$tabKey => [$chartKey => $payload]];

                $this->assertSame($payload, $presenter->chartData($insights, $chartKey), "chartData routing broken for {$chartKey}");
            }
        }

        $this->assertNull($presenter->chartData([], 'throughput'));
    }

    public function test_calendar_days_month_grid_shape(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Task::factory()->ownedBy($owner)->create(['project_id' => $project->id, 'deadline' => now()]);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $cal = $component->instance()->calendarDays();

        $today = Jalalian::now()->format('Y-m-d');
        $range = CalendarRange::fromNavigation($today, 'month');
        $leadingNulls = array_filter($cal['days'], fn($d) => $d === null);
        $this->assertCount($range->weekdayOffset(), $leadingNulls);

        $todayCell = collect($cal['days'])->filter()->firstWhere('date', $today);
        $this->assertNotNull($todayCell);
        $this->assertTrue($todayCell['isToday']);
        $this->assertTrue($todayCell['isSelected']);
        $this->assertTrue($todayCell['hasDeadline']);
        $this->assertSame(1, $todayCell['deadlineCount']);
    }

    public function test_calendar_prev_and_next_month_step_one_jalali_month(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $today = Jalalian::now()->format('Y-m-d');
        $expectedNext = Jalalian::fromFormat('Y-m-d', $today)->addMonths(1)->format('Y-m-d');

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id])
            ->assertSet('calendarNavDate', $today);

        $component->call('nextMonth')->assertSet('calendarNavDate', $expectedNext);
        $component->call('prevMonth')->assertSet('calendarNavDate', $today);
    }

    public function test_calendar_today_resets_navigation_and_selected(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $today = Jalalian::now()->format('Y-m-d');
        $otherDay = Jalalian::fromFormat('Y-m-d', $today)->addMonths(1)->format('Y-m-d');

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id])
            ->call('nextMonth')
            ->call('selectCalendarDay', $otherDay);

        $component->call('calendarToday');

        $component->assertSet('calendarNavDate', $today)->assertSet('selectedCalendarDay', $today);
    }

    public function test_calendar_select_day_sets_selected_without_flipping_navigation(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $today = Jalalian::now()->format('Y-m-d');
        $otherDay = Jalalian::fromFormat('Y-m-d', $today)->subDays(5)->format('Y-m-d');

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id])
            ->call('selectCalendarDay', $otherDay);

        $component->assertSet('calendarNavDate', $today)->assertSet('selectedCalendarDay', $otherDay);
    }

    public function test_calendar_selected_day_deadlines_scope_to_selected_date(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $todayJ = Jalalian::now();
        $day1 = sprintf('%04d-%02d-01', $todayJ->getYear(), $todayJ->getMonth());
        $day2 = sprintf('%04d-%02d-02', $todayJ->getYear(), $todayJ->getMonth());
        $task1 = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id, 'deadline' => Jalalian::fromFormat('Y-m-d', $day1)->toCarbon()->addHours(9)]);
        $task2 = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id, 'deadline' => Jalalian::fromFormat('Y-m-d', $day2)->toCarbon()->addHours(9)]);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id])
            ->call('selectCalendarDay', $day1);

        $deadlines = $component->instance()->selectedDayDeadlines();
        $this->assertCount(1, $deadlines);
        $this->assertSame($task1->id, $deadlines[0]['task_id']);

        $component->call('selectCalendarDay', $day2);
        $deadlines2 = $component->instance()->selectedDayDeadlines();
        $this->assertCount(1, $deadlines2);
        $this->assertSame($task2->id, $deadlines2[0]['task_id']);
    }

    public function test_calendar_current_month_name_formats_jalali_F_Y(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $cal = $component->instance()->calendarDays();

        $this->assertSame(Jalalian::now()->format('F Y'), $cal['label']);
    }

    public function test_calendar_view_dispatches_open_task_event_on_deadline_click(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id, 'deadline' => now()]);

        Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id])
            ->assertSeeHtml("Livewire.dispatch('project-open-task', { taskId: " . $task->id . ' })');
    }

    public function test_calendar_days_empty_when_no_project_selected(): void
    {
        $owner = User::factory()->create(['status' => 'active']);

        $result = Livewire::actingAs($owner)->test(Calendar::class)->instance()->calendarDays();

        $this->assertSame(['days' => [], 'label' => '', 'weekLabels' => CalendarRange::WEEK_DAY_LABELS], $result);
    }

    public function test_calendar_computeds_recover_from_invalid_nav_date_instead_of_crashing(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Task::factory()->ownedBy($owner)->create(['project_id' => $project->id, 'deadline' => now()]);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $instance = $component->instance();
        $instance->calendarNavDate = 'not-a-real-date';

        $this->assertSame(
            ['days' => [], 'label' => '', 'weekLabels' => CalendarRange::WEEK_DAY_LABELS],
            $instance->calendarDays()
        );
        $this->assertSame([], $instance->monthAgenda());
        $this->assertSame([], $instance->selectedDayDeadlines());
        $this->assertSame([], $instance->selectedDayLifecycle());
    }

    public function test_calendar_deadline_events_cache_scoped_by_project(): void
    {
        [$owner, $projectA] = $this->ownerWithProject();
        Task::factory()->ownedBy($owner)->create(['project_id' => $projectA->id, 'deadline' => now()]);
        $projectB = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $componentA = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $projectA->id]);
        $this->assertNotEmpty($componentA->instance()->selectedDayDeadlines());

        Livewire::withoutLazyLoading();
        $componentB = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $projectB->id]);
        $this->assertEmpty($componentB->instance()->selectedDayDeadlines());
    }

    public function test_calendar_state_resets_via_full_remount_between_projects(): void
    {
        [$owner, $projectA] = $this->ownerWithProject();
        $projectB = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $today = Jalalian::now()->format('Y-m-d');
        $otherMonth = Jalalian::fromFormat('Y-m-d', $today)->addMonths(1)->format('Y-m-d');

        Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $projectA->id])
            ->call('nextMonth')
            ->call('selectCalendarDay', $otherMonth)
            ->assertSet('calendarNavDate', $otherMonth);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $projectB->id])
            ->assertSet('calendarNavDate', $today)
            ->assertSet('selectedCalendarDay', $today);
    }

    public function test_refresh_calendar_clears_event_cache(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Task::factory()->ownedBy($owner)->create(['project_id' => $project->id, 'deadline' => now()]);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $this->assertCount(1, $component->instance()->selectedDayDeadlines());

        Task::factory()->ownedBy($owner)->create(['project_id' => $project->id, 'deadline' => now()]);

        $component->call('refreshCalendar');
        $this->assertCount(2, $component->instance()->selectedDayDeadlines());
    }

    public function test_calendar_days_grid_marks_start_change_completed_and_deadline(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $this->actingAs($owner);

        Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);

        $changeTask = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        Task::find($changeTask->id)->update(['status' => 'in-progress']);

        $doneTask = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        Task::find($doneTask->id)->update(['status' => 'done']);

        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'deadline' => now()]);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $cal = $component->instance()->calendarDays();

        $today = Jalalian::now()->format('Y-m-d');
        $todayCell = collect($cal['days'])->filter()->firstWhere('date', $today);

        $this->assertTrue($todayCell['hasStart']);
        $this->assertTrue($todayCell['hasChange']);
        $this->assertTrue($todayCell['hasCompleted']);
        $this->assertTrue($todayCell['hasDeadline']);
        $this->assertTrue($todayCell['hasOpenDeadline']);
    }

    public function test_calendar_resolved_deadline_marks_grey_not_open_when_task_done(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $this->actingAs($owner);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'deadline' => now()]);
        Task::find($task->id)->update(['status' => 'done']);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $cal = $component->instance()->calendarDays();
        $today = Jalalian::now()->format('Y-m-d');
        $todayCell = collect($cal['days'])->filter()->firstWhere('date', $today);

        $this->assertTrue($todayCell['hasDeadline']);
        $this->assertFalse($todayCell['hasOpenDeadline']);

        $deadlines = $component->instance()->selectedDayDeadlines();
        $this->assertTrue($deadlines[0]['isResolved']);
    }

    public function test_calendar_resolved_deadline_via_archive_without_done_status(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $this->actingAs($owner);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'deadline' => now()]);
        Task::find($task->id)->update(['archived_at' => now()]);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $deadlines = $component->instance()->selectedDayDeadlines();

        $this->assertTrue($deadlines[0]['isResolved']);
        $this->assertSame('todo', $task->fresh()->status);
    }

    public function test_calendar_selected_day_lifecycle_includes_start_and_status_change_events(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $this->actingAs($owner);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        Task::find($task->id)->update(['status' => 'in-progress']);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $events = $component->instance()->selectedDayLifecycle();
        $markers = collect($events)->pluck('marker')->all();

        $this->assertContains('start', $markers);
        $this->assertContains('change', $markers);

        $change = collect($events)->firstWhere('marker', 'change');
        $this->assertSame($task->id, $change['task_id']);
        $this->assertSame('todo', $change['from']);
        $this->assertSame('in-progress', $change['to']);
    }

    public function test_calendar_selected_day_lifecycle_marks_done_transition_as_completed(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $this->actingAs($owner);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        Task::find($task->id)->update(['status' => 'done']);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $completed = collect($component->instance()->selectedDayLifecycle())->firstWhere('marker', 'completed');

        $this->assertNotNull($completed);
        $this->assertSame($task->id, $completed['task_id']);
        $this->assertSame('done', $completed['to']);
    }

    public function test_calendar_selected_day_lifecycle_computes_paused_duration_since_entering_pending(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $this->actingAs($owner);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);

        Task::find($task->id)->update(['status' => 'pending']);
        $pendingReplyId = Reply::where('repliable_type', Task::class)
            ->where('repliable_id', $task->id)
            ->where('type', TaskActivityType::StatusChange)
            ->latest('id')
            ->value('id');
        Reply::where('id', $pendingReplyId)->update(['created_at' => now()->subHours(3)]);
        Task::find($task->id)->update(['status' => 'in-progress']);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $resumed = collect($component->instance()->selectedDayLifecycle())
            ->first(fn($e) => $e['marker'] === 'change' && $e['from'] === 'pending');

        $this->assertNotNull($resumed);
        $this->assertArrayHasKey('pausedMinutes', $resumed);
        $this->assertGreaterThanOrEqual(179, $resumed['pausedMinutes']);
        $this->assertLessThanOrEqual(181, $resumed['pausedMinutes']);
    }

    public function test_calendar_selected_day_lifecycle_omits_paused_minutes_when_not_resuming_from_pending(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $this->actingAs($owner);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        Task::find($task->id)->update(['status' => 'in-progress']);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $change = collect($component->instance()->selectedDayLifecycle())->firstWhere('marker', 'change');

        $this->assertArrayNotHasKey('pausedMinutes', $change);
    }

    public function test_calendar_month_agenda_includes_full_lifecycle_timeline_not_only_deadlines(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $this->actingAs($owner);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        Task::find($task->id)->update(['status' => 'in-progress']);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $markers = collect($component->instance()->monthAgenda())->pluck('marker')->unique()->all();

        $this->assertContains('start', $markers);
        $this->assertContains('change', $markers);
    }

    public function test_calendar_lifecycle_events_cache_scoped_by_project(): void
    {
        [$owner, $projectA] = $this->ownerWithProject();
        Task::factory()->ownedBy($owner)->create(['project_id' => $projectA->id]);
        $projectB = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $componentA = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $projectA->id]);
        $this->assertNotEmpty($componentA->instance()->selectedDayLifecycle());

        Livewire::withoutLazyLoading();
        $componentB = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $projectB->id]);
        $this->assertEmpty($componentB->instance()->selectedDayLifecycle());
    }

    public function test_calendar_days_grid_marks_cycle_start_on_workflow_start_day(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $cal = $component->instance()->calendarDays();

        $today = Jalalian::now()->format('Y-m-d');
        $todayCell = collect($cal['days'])->filter()->firstWhere('date', $today);

        $this->assertTrue($todayCell['hasCycleStart']);
        $this->assertFalse($todayCell['hasCycleEnd']);
        $this->assertSame(1, $todayCell['cycleCount']);
    }

    public function test_calendar_days_grid_cycle_count_reflects_multiple_markers_same_day(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);
        Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'status' => Workflow::STATUS_COMPLETED, 'started_at' => now(), 'completed_at' => now()]);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $cal = $component->instance()->calendarDays();

        $today = Jalalian::now()->format('Y-m-d');
        $todayCell = collect($cal['days'])->filter()->firstWhere('date', $today);

        $this->assertTrue($todayCell['hasCycleStart']);
        $this->assertTrue($todayCell['hasCycleEnd']);
        $this->assertSame(3, $todayCell['cycleCount']);
    }

    public function test_calendar_cycle_end_marker_distinguishes_cancelled_status(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'status' => Workflow::STATUS_CANCELLED, 'completed_at' => now()]);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $cycle = collect($component->instance()->monthAgenda())->firstWhere('marker', 'cycle-end');

        $this->assertNotNull($cycle);
        $this->assertSame(Workflow::STATUS_CANCELLED, $cycle['status']);
    }

    public function test_calendar_days_grid_no_cycle_marker_when_workflow_dates_outside_viewed_month(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'started_at' => now()->addMonths(2)]);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $cal = $component->instance()->calendarDays();

        $hasCycleStart = collect($cal['days'])->filter()->contains(fn($d) => $d['hasCycleStart']);
        $this->assertFalse($hasCycleStart);
    }

    public function test_calendar_month_agenda_includes_cycle_start_event(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'name' => 'چرخه پرداخت']);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $event = collect($component->instance()->monthAgenda())->firstWhere('marker', 'cycle-start');

        $this->assertNotNull($event);
        $this->assertSame($workflow->id, $event['workflow_id']);
        $this->assertSame('چرخه پرداخت', $event['name']);
    }

    public function test_calendar_agenda_lists_cycle_start_before_a_task_created_in_the_same_minute(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $minute = now()->startOfDay()->setTime(10, 0);
        Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'started_at' => $minute->copy()->setSecond(50)]);
        Task::factory()->ownedBy($owner)->create(['project_id' => $project->id, 'created_at' => $minute->copy()->setSecond(10)]);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $markers = collect($component->instance()->monthAgenda())->whereIn('marker', ['cycle-start', 'start'])->pluck('marker')->values()->all();

        $this->assertSame(['cycle-start', 'start'], $markers);
    }

    public function test_calendar_cycle_cache_invalidates_when_workflow_created_after_initial_render(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $this->assertEmpty($component->instance()->selectedDayCycle());

        Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        Livewire::withoutLazyLoading();
        $fresh = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $this->assertNotEmpty($fresh->instance()->selectedDayCycle());
    }

    public function test_calendar_cycle_cache_invalidates_when_workflow_completed_after_initial_render(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $this->assertNull(collect($component->instance()->monthAgenda())->firstWhere('marker', 'cycle-end'));

        $workflow->update(['status' => Workflow::STATUS_COMPLETED, 'completed_at' => now()]);

        Livewire::withoutLazyLoading();
        $fresh = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $cycleEnd = collect($fresh->instance()->monthAgenda())->firstWhere('marker', 'cycle-end');

        $this->assertNotNull($cycleEnd);
        $this->assertSame($workflow->id, $cycleEnd['workflow_id']);
    }

    public function test_calendar_active_project_id_is_locked_against_client_tampering(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $otherOwner = User::factory()->create(['status' => 'active']);
        $otherProject = Project::factory()->create(['owner_id' => $otherOwner->id, 'member_ids' => [], 'departments' => []]);
        Task::factory()->ownedBy($otherOwner)->create(['project_id' => $otherProject->id, 'deadline' => now()]);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($owner)
            ->test(Calendar::class, ['activeProjectId' => $project->id])
            ->set('activeProjectId', $otherProject->id);
    }

    public function test_calendar_nav_date_is_locked_against_client_tampering(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($owner)
            ->test(Calendar::class, ['activeProjectId' => $project->id])
            ->set('calendarNavDate', '1405-01-01');
    }

    public function test_calendar_selected_day_is_locked_against_client_tampering(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($owner)
            ->test(Calendar::class, ['activeProjectId' => $project->id])
            ->set('selectedCalendarDay', '1405-01-01');
    }

    public function test_calendar_renders_a_placeholder_when_lazy_loading_is_active(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        \Livewire\Features\SupportLazyLoading\SupportLazyLoading::$disableWhileTesting = false;

        try {
            Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id])
                ->assertSeeHtml('در حال بارگذاری تقویم')
                ->assertDontSeeHtml('wire:click="prevMonth"');
        } finally {
            Livewire::withoutLazyLoading();
        }
    }

    public function test_calendar_placeholder_root_x_data_matches_real_content_root(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id])
            ->assertSeeHtml('x-data="{ calView: \'grid\' }"');

        \Livewire\Features\SupportLazyLoading\SupportLazyLoading::$disableWhileTesting = false;

        try {
            Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id])
                ->assertSeeHtml('x-data="{ calView: \'grid\' }"');
        } finally {
            Livewire::withoutLazyLoading();
        }
    }

    public function test_calendar_placeholder_root_carries_the_refresh_bridge_listener(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id])
            ->assertSeeHtml('@project-calendar-refresh.window="$wire.refreshCalendar()"');

        \Livewire\Features\SupportLazyLoading\SupportLazyLoading::$disableWhileTesting = false;

        try {
            Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id])
                ->assertSeeHtml('@project-calendar-refresh.window="$wire.refreshCalendar()"');
        } finally {
            Livewire::withoutLazyLoading();
        }
    }

    private function ganttFor(User $owner, Project $project): array
    {
        return Livewire::actingAs($owner)
            ->test(Calendar::class, ['activeProjectId' => $project->id])
            ->instance()
            ->ganttRows();
    }

    public function test_calendar_gantt_bucket_shape_and_geometry(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $today = Jalalian::now()->format('Y-m-d');
        $range = CalendarRange::fromNavigation($today, 'month');
        $daysCount = count($range->days());
        $rangeStart = $range->start->copy()->startOfDay();
        $todayIdx = (int) round($rangeStart->diffInDays(now()->startOfDay(), false));
        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'deadline' => now()]);

        $gantt = $this->ganttFor($owner, $project);

        $this->assertSame($daysCount, $gantt['daysCount']);
        $this->assertSame($today, $gantt['today']);
        $this->assertSame($todayIdx, $gantt['todayIndex']);
        $this->assertCount($daysCount, $gantt['dayNumbers']);
        $this->assertCount(1, $gantt['rows']);

        $row = $gantt['rows'][0];
        $this->assertSame($todayIdx === 0, $row['leftClipped']);
        $this->assertFalse($row['rightClipped']);
        $this->assertFalse($row['isDone']);
        $this->assertTrue($row['hasDeadline']);
        $this->assertNull($row['tailStartPct']);
        $this->assertNull($row['tailWidthPct']);
        $this->assertEqualsWithDelta($todayIdx / $daysCount * 100, $row['startPct'], 0.05);
        $this->assertEqualsWithDelta(100 / $daysCount, $row['widthPct'], 0.05);
        $this->assertArrayHasKey('progressPct', $row);
        $this->assertArrayHasKey('slipCount', $row);
        $this->assertArrayHasKey('slipFrom', $row);
    }

    public function test_calendar_overdue_carry_lists_open_tasks_deadline_before_range(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $past = Jalalian::now()->subMonths(1)->toCarbon()->startOfDay();
        $open = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'deadline' => $past]);
        $done = Task::factory()->ownedBy($owner)->withStatus('done')->create(['project_id' => $project->id, 'deadline' => $past]);
        Task::whereKey($done->id)->toBase()->update(['completed_at' => $past]);

        $carry = Livewire::actingAs($owner)
            ->test(Calendar::class, ['activeProjectId' => $project->id])
            ->instance()
            ->overdueCarry();

        $this->assertCount(1, $carry);
        $item = $carry[0];
        $this->assertSame($open->id, $item['task_id']);
        $this->assertFalse($item['isResolved']);
        $this->assertSame(Jalalian::fromCarbon($past)->format('Y-m-d'), $item['date']);

        Livewire::withoutLazyLoading();
        $gantt = $this->ganttFor($owner, $project);
        $row = collect($gantt['rows'])->firstWhere('task_id', $open->id);
        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(0, $row['startPct'], 0.01);
        $this->assertNotNull($row['tailStartPct']);
        $this->assertEqualsWithDelta(0, $row['tailStartPct'], 0.01);
        $this->assertTrue($row['tailWidthPct'] > 0);

        $this->assertNull(collect($gantt['rows'])->first(fn(array $r) => $r['isDone']));
    }

    public function test_calendar_slip_counts_from_deadline_change_replies(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $original = Jalalian::now()->addDays(2)->toCarbon()->startOfDay();
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'deadline' => $original]);

        Task::find($task->id)->update(['deadline' => now()->addDays(5)]);
        Task::find($task->id)->update(['deadline' => now()->addDays(8)]);

        $row = collect($this->ganttFor($owner, $project)['rows'])->firstWhere('task_id', $task->id);

        $this->assertSame(2, $row['slipCount']);
        $this->assertSame(Jalalian::fromCarbon($original)->format('Y-m-d'), $row['slipFrom']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id]);
        $finalJ = Jalalian::fromCarbon(now()->addDays(8));
        $todayJ = Jalalian::now();
        $delta = ($finalJ->getYear() - $todayJ->getYear()) * 12 + ($finalJ->getMonth() - $todayJ->getMonth());
        for ($i = 0; $i < $delta; $i++) {
            $component->call('nextMonth');
        }

        $deadlineAgenda = collect($component->instance()->monthAgenda())->firstWhere('marker', 'deadline');
        $this->assertNotNull($deadlineAgenda);
        $this->assertSame(2, $deadlineAgenda['slipCount']);
        $this->assertSame(Jalalian::fromCarbon($original)->format('Y-m-d'), $deadlineAgenda['slipFrom']);
    }

    public function test_calendar_project_deadline_marker_and_cap_index(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $range = CalendarRange::fromNavigation(Jalalian::now()->format('Y-m-d'), 'month');
        $deadline = $range->start->copy()->addDays(3);
        $project->update(['settings' => ['deadline' => $deadline->toDateString()]]);

        $gantt = $this->ganttFor($owner, $project);

        $this->assertSame(Jalalian::fromCarbon($deadline)->format('Y-m-d'), $gantt['projectDeadline']);
        $this->assertSame(3, $gantt['projectDeadlineIndex']);

        Livewire::withoutLazyLoading();
        $cal = Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id])->instance()->calendarDays();
        $marked = collect($cal['days'])->filter()->firstWhere('hasProjectDeadline', true);
        $this->assertNotNull($marked);
        $this->assertSame(Jalalian::fromCarbon($deadline)->format('Y-m-d'), $marked['date']);
    }

    public function test_calendar_project_deadline_outside_range_has_no_cap_index(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $outside = Jalalian::now()->subMonths(2)->toCarbon()->startOfDay();
        $project->update(['settings' => ['deadline' => $outside->toDateString()]]);

        $gantt = $this->ganttFor($owner, $project);

        $this->assertSame(Jalalian::fromCarbon($outside)->format('Y-m-d'), $gantt['projectDeadline']);
        $this->assertNull($gantt['projectDeadlineIndex']);
    }

    public function test_calendar_gantt_pane_renders_with_rows_and_mode_toggle(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'deadline' => now()]);

        Livewire::actingAs($owner)->test(Calendar::class, ['activeProjectId' => $project->id])
            ->assertSeeHtml('نمای گانت')
            ->assertSeeHtml('view_timeline')
            ->assertSeeHtml('خط زمانی وظایف')
            ->assertSeeHtml('wire:key="gantt-row-' . $task->id . '"');
    }

    public function test_kanban_board_scopes_to_the_active_project_and_groups_by_status(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $other = User::factory()->create(['status' => 'active']);
        $otherProject = Project::factory()->create(['owner_id' => $owner->id]);

        $todo = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        $done = Task::factory()->ownedBy($other)->withStatus('done')->create(['project_id' => $project->id]);
        $foreign = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $otherProject->id]);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id]);

        $todoIds = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $doneIds = collect($component->instance()->kanbanBoard['tasks']['done'])->pluck('id')->all();

        $this->assertContains($todo->id, $todoIds);
        $this->assertContains($done->id, $doneIds);
        $this->assertNotContains($foreign->id, $todoIds);
        $this->assertSame(1, $component->instance()->kanbanBoard['totalCount']['todo']);
        $this->assertSame(1, $component->instance()->kanbanBoard['totalCount']['done']);
    }

    public function test_kanban_board_excludes_archived_tasks(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $archived = Task::factory()->ownedBy($owner)->withStatus('done')->create([
            'project_id' => $project->id,
            'archived_at' => now(),
        ]);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id]);

        $doneIds = collect($component->instance()->kanbanBoard['tasks']['done'])->pluck('id')->all();
        $this->assertNotContains($archived->id, $doneIds);
    }

    public function test_archived_tasks_lists_archived_done_tasks_with_restore_permission_flag(): void
    {
        $other = User::factory()->create(['status' => 'active']);
        [$owner, $project] = $this->ownerWithProject([$other->id]);
        $ownTask = Task::factory()->ownedBy($owner)->withStatus('done')->create([
            'project_id' => $project->id,
            'archived_at' => now(),
        ]);
        $othersTask = Task::factory()->ownedBy($other)->withStatus('done')->create([
            'project_id' => $project->id,
            'archived_at' => now(),
        ]);

        $asOwner = collect(Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])->instance()->archivedTasks());
        $this->assertTrue($asOwner->firstWhere('id', $ownTask->id)['can_restore']);
        $this->assertTrue($asOwner->firstWhere('id', $othersTask->id)['can_restore'], 'the project owner can restore any archived task');

        $asMember = collect(Livewire::actingAs($other)->test(Kanban::class, ['activeProjectId' => $project->id])->instance()->archivedTasks());
        $this->assertTrue($asMember->firstWhere('id', $othersTask->id)['can_restore']);
        $this->assertFalse($asMember->firstWhere('id', $ownTask->id)['can_restore']);
    }

    public function test_unarchive_task_restores_only_when_caller_is_the_tasks_creator_or_the_project_owner(): void
    {
        $other = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        [$owner, $project] = $this->ownerWithProject([$other->id, $stranger->id]);
        $othersTask = Task::factory()->ownedBy($other)->withStatus('done')->create([
            'project_id' => $project->id,
            'archived_at' => now(),
        ]);

        Livewire::actingAs($stranger)
            ->test(Kanban::class, ['activeProjectId' => $project->id])
            ->call('unarchiveTask', $othersTask->id);

        $this->assertNotNull($othersTask->fresh()->archived_at);

        Livewire::actingAs($owner)
            ->test(Kanban::class, ['activeProjectId' => $project->id])
            ->call('unarchiveTask', $othersTask->id);

        $this->assertNull($othersTask->fresh()->archived_at);
    }

    public function test_unarchive_task_restores_the_callers_own_archived_task(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $ownTask = Task::factory()->ownedBy($owner)->withStatus('done')->create([
            'project_id' => $project->id,
            'archived_at' => now(),
        ]);

        Livewire::actingAs($owner)
            ->test(Kanban::class, ['activeProjectId' => $project->id])
            ->call('unarchiveTask', $ownTask->id);

        $this->assertNull($ownTask->fresh()->archived_at);
    }

    public function test_unarchive_task_rejects_a_task_outside_the_active_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $otherProject = Project::factory()->create(['owner_id' => $owner->id]);
        $foreign = Task::factory()->ownedBy($owner)->withStatus('done')->create([
            'project_id' => $otherProject->id,
            'archived_at' => now(),
        ]);

        Livewire::actingAs($owner)
            ->test(Kanban::class, ['activeProjectId' => $project->id])
            ->call('unarchiveTask', $foreign->id);

        $this->assertNotNull($foreign->fresh()->archived_at);
    }

    public function test_kanban_board_eager_load_surfaces_action_source_columns_on_the_detail_array(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        \App\Models\TaskDetail::factory()->create([
            'task_id' => $task->id,
            'action_source' => 'ایمیل مدیر',
            'action_source_domain' => 'ایمیل',
        ]);

        $tasks = Livewire::actingAs($owner)
            ->test(Kanban::class, ['activeProjectId' => $project->id])
            ->instance()
            ->kanbanBoard['tasks']['todo'];

        $this->assertSame('ایمیل مدیر', $tasks[0]['detail']['action_source']);
        $this->assertSame('ایمیل', $tasks[0]['detail']['action_source_domain']);
    }

    public function test_update_task_status_rejects_a_task_outside_the_active_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $otherProject = Project::factory()->create(['owner_id' => $owner->id]);
        $foreign = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $otherProject->id]);

        Livewire::actingAs($owner)
            ->test(Kanban::class, ['activeProjectId' => $project->id])
            ->call('updateTaskStatus', $foreign->id, 'done');

        $this->assertSame('todo', $foreign->fresh()->status);
    }

    public function test_reorder_task_rejects_a_task_outside_the_active_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $otherProject = Project::factory()->create(['owner_id' => $owner->id]);
        $foreign = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $otherProject->id, 'rank' => 'a']);

        Livewire::actingAs($owner)
            ->test(Kanban::class, ['activeProjectId' => $project->id])
            ->call('reorderTask', $foreign->id, null, 'done');

        $this->assertSame('todo', $foreign->fresh()->status);
        $this->assertSame('a', $foreign->fresh()->rank);
    }

    public function test_update_task_status_to_done_without_state_is_blocked(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);

        $component = Livewire::actingAs($owner)
            ->test(Kanban::class, ['activeProjectId' => $project->id])
            ->call('updateTaskStatus', $task->id, 'done');

        $component->assertDispatched('toast');
        $this->assertSame('todo', $task->fresh()->status);
    }

    public function test_update_task_status_to_done_with_state_set_succeeds(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        TaskDetail::factory()->create(['task_id' => $task->id, 'state' => TaskState::Completion->value]);

        Livewire::actingAs($owner)
            ->test(Kanban::class, ['activeProjectId' => $project->id])
            ->call('updateTaskStatus', $task->id, 'done');

        $this->assertSame('done', $task->fresh()->status);
    }

    public function test_reorder_task_to_done_without_state_is_blocked(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);

        $component = Livewire::actingAs($owner)
            ->test(Kanban::class, ['activeProjectId' => $project->id])
            ->call('reorderTask', $task->id, null, 'done');

        $component->assertDispatched('toast');
        $this->assertSame('todo', $task->fresh()->status);
    }

    public function test_kanban_search_filters_by_title_or_description(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $matching = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'title' => 'Rare Needle']);
        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'title' => 'Something else']);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->set('kanbanSearch', 'Rare Needle');

        $ids = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $this->assertSame([$matching->id], $ids);
    }

    public function test_kanban_deadline_filter_scopes_to_matching_tasks(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $overdue = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'deadline' => now()->subDays(2)]);
        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'deadline' => now()->addDays(10)]);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->set('kanbanDeadlineFilter', 'overdue');

        $ids = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $this->assertSame([$overdue->id], $ids);
    }

    public function test_kanban_priority_filter_scopes_to_matching_tasks(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $urgent = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'priority' => 'urgent']);
        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'priority' => 'low']);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->set('kanbanPriorityFilter', 'urgent');

        $ids = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $this->assertSame([$urgent->id], $ids);
    }

    public function test_kanban_active_filter_count_reflects_only_currently_set_filters(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id]);
        $this->assertSame(0, $component->instance()->kanbanActiveFilterCount());

        $component->set('kanbanPriorityFilter', 'urgent')->set('kanbanDeadlineFilter', 'overdue');
        $this->assertSame(2, $component->instance()->kanbanActiveFilterCount());

        $component->set('kanbanPriorityFilter', '');
        $this->assertSame(1, $component->instance()->kanbanActiveFilterCount());
    }

    public function test_clear_kanban_filters_resets_every_filter_and_reloads_the_board(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $urgent = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'priority' => 'urgent']);
        $low = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'priority' => 'low']);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->set('kanbanPriorityFilter', 'urgent');

        $ids = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $this->assertSame([$urgent->id], $ids);

        $component->call('clearKanbanFilters')->assertSet('kanbanPriorityFilter', '');

        $ids = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$urgent->id, $low->id], $ids);
        $this->assertSame(0, $component->instance()->kanbanActiveFilterCount());
    }

    public function test_kanban_label_filter_is_an_and_match_across_selected_labels(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $both = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'labels' => ['urgent', 'backend']]);
        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'labels' => ['urgent']]);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->set('kanbanLabelFilter', ['urgent', 'backend']);

        $ids = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $this->assertSame([$both->id], $ids);
    }

    public function test_kanban_label_filter_matches_arabic_and_persian_script_variants_of_the_same_tag(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $persian = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'labels' => ['خودکارسازی']]);
        $arabic = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'labels' => ['خودكارسازي']]);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->set('kanbanLabelFilter', ['خودکارسازی']);

        $ids = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$persian->id, $arabic->id], $ids);
    }

    public function test_kanban_label_options_collapse_arabic_and_persian_script_variants_into_one(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'labels' => ['خودکارسازی']]);
        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'labels' => ['خودكارسازي']]);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id]);

        $this->assertSame(['خودکارسازی'], $component->instance()->kanbanLabelOptions());
    }

    public function test_add_kanban_label_filter_appends_without_overwriting_existing_selection(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->set('kanbanLabelFilter', ['urgent'])
            ->call('addKanbanLabelFilter', 'backend');

        $this->assertSame(['urgent', 'backend'], $component->get('kanbanLabelFilter'));
    }

    public function test_toggle_kanban_label_filter_adds_and_removes_labels(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->call('toggleKanbanLabelFilter', 'urgent');

        $this->assertSame(['urgent'], $component->get('kanbanLabelFilter'));

        $component->call('toggleKanbanLabelFilter', 'backend');
        $this->assertSame(['urgent', 'backend'], $component->get('kanbanLabelFilter'));

        $component->call('toggleKanbanLabelFilter', 'urgent');
        $this->assertSame(['backend'], $component->get('kanbanLabelFilter'));
    }

    public function test_kanban_scheme_filter_scopes_to_matching_tasks(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $matching = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        TaskDetail::factory()->create(['task_id' => $matching->id, 'scheme' => 'Scheme A']);
        $other = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        TaskDetail::factory()->create(['task_id' => $other->id, 'scheme' => 'Scheme B']);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->set('kanbanSchemeFilter', 'Scheme A');

        $ids = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $this->assertSame([$matching->id], $ids);
    }

    public function test_kanban_unit_filter_scopes_to_matching_tasks(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $matching = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        TaskDetail::factory()->create(['task_id' => $matching->id, 'unit' => 'Unit A']);
        $other = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        TaskDetail::factory()->create(['task_id' => $other->id, 'unit' => 'Unit B']);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->set('kanbanUnitFilter', 'Unit A');

        $ids = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $this->assertSame([$matching->id], $ids);
    }

    public function test_kanban_section_filter_scopes_to_matching_tasks(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $matching = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        TaskDetail::factory()->create(['task_id' => $matching->id, 'section' => 'Section A']);
        $other = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        TaskDetail::factory()->create(['task_id' => $other->id, 'section' => 'Section B']);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->set('kanbanSectionFilter', 'Section A');

        $ids = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $this->assertSame([$matching->id], $ids);
    }

    public function test_kanban_department_filter_scopes_to_matching_tasks(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $dept = Department::create(['code' => 'ENG', 'name' => 'Engineering', 'description' => '']);
        $matching = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        TaskDetail::factory()->create(['task_id' => $matching->id, 'department_id' => $dept->code]);
        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->set('kanbanDepartmentFilter', $dept->code);

        $ids = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $this->assertSame([$matching->id], $ids);
    }

    public function test_kanban_responsible_filter_scopes_to_matching_tasks(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $responsible = User::factory()->create(['status' => 'active']);
        $matching = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        TaskDetail::factory()->create(['task_id' => $matching->id, 'responsible_user_id' => $responsible->id]);
        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->set('kanbanResponsibleFilter', $responsible->id);

        $ids = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $this->assertSame([$matching->id], $ids);
    }

    public function test_kanban_assignee_filter_scopes_to_matching_tasks(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $matching = Task::factory()->create(['user_id' => $owner->id, 'assigned_to' => $assignee->id, 'status' => 'todo', 'project_id' => $project->id]);
        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->set('kanbanAssigneeFilter', $assignee->id);

        $ids = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $this->assertSame([$matching->id], $ids);
    }

    public function test_toggle_kanban_mine_scopes_board_to_my_assigned_tasks_and_toggles_off(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $colleague = User::factory()->create(['status' => 'active']);
        $mine = Task::factory()->create(['user_id' => $owner->id, 'assigned_to' => $owner->id, 'status' => 'todo', 'project_id' => $project->id]);
        $theirs = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'assigned_to' => $colleague->id]);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->call('toggleKanbanMine');

        $this->assertSame($owner->id, $component->instance()->kanbanAssigneeFilter);

        $ids = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $this->assertSame([$mine->id], $ids);

        $component->call('toggleKanbanMine');
        $this->assertNull($component->instance()->kanbanAssigneeFilter);

        $ids = collect($component->instance()->kanbanBoard['tasks']['todo'])->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$mine->id, $theirs->id], $ids);
    }

    public function test_kanban_state_resets_via_full_remount_between_projects(): void
    {
        [$owner, $projectA] = $this->ownerWithProject();
        $projectB = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $projectA->id])
            ->set('kanbanSearch', 'anything')
            ->set('kanbanPriorityFilter', 'high')
            ->assertSet('kanbanSearch', 'anything')
            ->assertSet('kanbanPriorityFilter', 'high');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $projectB->id])
            ->assertSet('kanbanSearch', '')
            ->assertSet('kanbanPriorityFilter', '');
    }

    public function test_kanban_card_popover_data_present_for_attachments_checklist_department_collaborator_and_responsible(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $responsible = User::factory()->create(['status' => 'active', 'name' => 'Responsible Person']);
        $collaborator = User::factory()->create(['status' => 'active', 'name' => 'Collaborator Person']);
        $dept = Department::create(['code' => 'ENG', 'name' => 'Engineering', 'description' => '']);
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        TaskDetail::factory()->create([
            'task_id' => $task->id,
            'responsible_user_id' => $responsible->id,
            'department_id' => $dept->code,
            'collaborators' => [$collaborator->id],
            'checklist' => [['text' => 'step 1', 'done' => true, 'weight' => 100]],
            'attachments' => [['path' => 'task/attachments/a.png', 'name' => 'a.png', 'mime' => 'image/png', 'size' => 10]],
        ]);

        $component = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id]);

        $loaded = collect($component->instance()->kanbanBoard['tasks']['todo'])->firstWhere('id', $task->id);

        $this->assertSame('ENG', $loaded['detail']['department_id']);
        $this->assertSame('Responsible Person', $loaded['detail']['responsible_user']['name']);
        $this->assertCount(1, $loaded['detail']['collaborator_users']);
        $this->assertSame('Collaborator Person', $loaded['detail']['collaborator_users'][0]['name']);
        $this->assertSame('step 1', $loaded['detail']['checklist'][0]['text']);
        $this->assertSame('a.png', $loaded['detail']['attachments'][0]['name']);

        $component->assertSeeHtml('Engineering')
            ->assertSeeHtml('Responsible Person')
            ->assertSeeHtml('step 1')
            ->assertSeeHtml('a.png');
    }

    public function test_cycle_priority_from_project_kanban_advances_priority(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id, 'priority' => null]);

        Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->call('cyclePriority', $task->id)
            ->assertHasNoErrors();

        $this->assertSame(TaskPriority::Low, $task->fresh()->priority);
    }

    public function test_cycle_priority_from_project_kanban_rejects_a_task_outside_the_active_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $otherProject = Project::factory()->create(['owner_id' => $owner->id]);
        $foreign = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $otherProject->id, 'priority' => 'low']);

        Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->call('cyclePriority', $foreign->id);

        $this->assertSame(TaskPriority::Low, $foreign->fresh()->priority);
    }

    public function test_kanban_board_groups_tasks_by_status_and_excludes_archived(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        Task::factory()->ownedBy($owner)->withStatus('in-progress')->create(['project_id' => $project->id]);
        Task::factory()->ownedBy($owner)->withStatus('pending')->create(['project_id' => $project->id]);
        Task::factory()->ownedBy($owner)->withStatus('pending')->create(['project_id' => $project->id]);
        Task::factory()->ownedBy($owner)->withStatus('pending')->create(['project_id' => $project->id]);
        Task::factory()->ownedBy($owner)->withStatus('done')->create(['project_id' => $project->id]);
        Task::factory()->ownedBy($owner)->withStatus('done')->archived()->create(['project_id' => $project->id]);

        $instance = Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->call('loadKanbanBoard')
            ->instance();

        $this->assertSame(['todo', 'in-progress', 'pending', 'done'], array_keys($instance->kanbanBoard['tasks']));
        $this->assertCount(2, $instance->kanbanBoard['tasks']['todo']);
        $this->assertCount(1, $instance->kanbanBoard['tasks']['in-progress']);
        $this->assertCount(3, $instance->kanbanBoard['tasks']['pending']);
        $this->assertCount(1, $instance->kanbanBoard['tasks']['done'], 'the archived done task must be excluded');

        $this->assertSame(2, $instance->kanbanBoard['totalCount']['todo']);
        $this->assertSame(1, $instance->kanbanBoard['totalCount']['in-progress']);
        $this->assertSame(3, $instance->kanbanBoard['totalCount']['pending']);
        $this->assertSame(1, $instance->kanbanBoard['totalCount']['done']);
    }

    public function test_kanban_active_project_id_is_locked_against_client_tampering(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $otherOwner = User::factory()->create(['status' => 'active']);
        $otherProject = Project::factory()->create(['owner_id' => $otherOwner->id, 'member_ids' => [], 'departments' => []]);
        Task::factory()->ownedBy($otherOwner)->create(['project_id' => $otherProject->id]);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($owner)
            ->test(Kanban::class, ['activeProjectId' => $project->id])
            ->set('activeProjectId', $otherProject->id);
    }

    public function test_kanban_renders_a_placeholder_when_lazy_loading_is_active(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        \Livewire\Features\SupportLazyLoading\SupportLazyLoading::$disableWhileTesting = false;

        try {
            Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
                ->assertSeeHtml('در حال بارگذاری برد وظایف')
                ->assertDontSeeHtml('جستجو در برد');
        } finally {
            Livewire::withoutLazyLoading();
        }
    }

    public function test_kanban_placeholder_root_x_data_matches_real_content_root(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->assertSeeHtml('x-data="{ moreOpen: false }"');

        \Livewire\Features\SupportLazyLoading\SupportLazyLoading::$disableWhileTesting = false;

        try {
            Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
                ->assertSeeHtml('x-data="{ moreOpen: false }"');
        } finally {
            Livewire::withoutLazyLoading();
        }
    }

    public function test_kanban_placeholder_root_carries_the_refresh_bridge_listener(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
            ->assertSeeHtml('@project-kanban-refresh.window="$wire.loadKanbanBoard()"');

        \Livewire\Features\SupportLazyLoading\SupportLazyLoading::$disableWhileTesting = false;

        try {
            Livewire::actingAs($owner)->test(Kanban::class, ['activeProjectId' => $project->id])
                ->assertSeeHtml('@project-kanban-refresh.window="$wire.loadKanbanBoard()"');
        } finally {
            Livewire::withoutLazyLoading();
        }
    }

    public function test_approve_task_is_blocked_for_a_non_owner_member(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $member = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [$member->id],
            'departments' => [],
            'settings' => ['requires_approval' => true],
        ]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create([
            'approved_at' => null,
            'assigned_to' => $member->id,
        ]);

        Livewire::actingAs($member)
            ->test(Kanban::class, ['activeProjectId' => $project->id])
            ->call('approveTask', $task->id)
            ->assertHasNoErrors();

        $this->assertNull($task->fresh()->approved_at);
        $this->assertFalse(app(\App\Services\ProjectTask\ApproveTaskAction::class)->execute($task->fresh(), $member));
        $this->assertNull($task->fresh()->approved_at);
    }

    public function test_set_report_sort_toggles_direction_and_resets_on_new_field(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
            ->assertSet('reportSort', ['field' => 'deadline', 'dir' => 'asc'])
            ->call('setReportSort', 'priority')
            ->assertSet('reportSort', ['field' => 'priority', 'dir' => 'asc'])
            ->call('setReportSort', 'priority')
            ->assertSet('reportSort', ['field' => 'priority', 'dir' => 'desc'])
            ->call('setReportSort', 'deadline')
            ->assertSet('reportSort', ['field' => 'deadline', 'dir' => 'asc']);
    }

    public function test_set_report_filter_sets_and_clears_each_field(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
            ->call('setReportFilter', 'assignee', $assignee->id)
            ->assertSet('reportAssigneeFilter', $assignee->id)
            ->call('setReportFilter', 'priority', 'urgent')
            ->assertSet('reportPriorityFilter', 'urgent')
            ->call('setReportFilter', 'assignee', '')
            ->assertSet('reportAssigneeFilter', null);
    }

    public function test_clear_report_filters_resets_all_state_and_limit(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
            ->set('reportStatusFilter', 'todo')
            ->set('reportSearch', 'foo')
            ->set('reportPriorityFilter', 'urgent')
            ->call('loadMoreReport')
            ->assertSet('reportLimit', 50)
            ->call('clearReportFilters')
            ->assertSet('reportStatusFilter', '')
            ->assertSet('reportSearch', '')
            ->assertSet('reportPriorityFilter', null)
            ->assertSet('reportSort', ['field' => 'deadline', 'dir' => 'asc'])
            ->assertSet('reportLimit', 25);
    }

    public function test_report_data_returns_empty_without_active_project(): void
    {
        $owner = User::factory()->create(['status' => 'active']);

        $data = Livewire::actingAs($owner)->test(Report::class)->instance()->reportData();

        $this->assertSame(['rows' => [], 'hasMore' => false], $data);
    }

    public function test_report_data_applies_priority_filter(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $urgent = Task::factory()->forProject($project)->ownedBy($owner)->create(['priority' => 'urgent', 'deadline' => null]);
        Task::factory()->forProject($project)->ownedBy($owner)->create(['priority' => 'low', 'deadline' => null]);

        $component = Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
            ->call('setReportFilter', 'priority', 'urgent');

        $rows = $component->instance()->reportData()['rows'];
        $this->assertSame([$urgent->id], array_column($rows, 'id'));
    }

    public function test_report_filter_options_returns_project_scoped_assignees(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        Task::factory()->forProject($project)->delegatedBy($owner, $assignee)->create(['labels' => ['design', 'backend']]);
        Task::factory()->forProject($project)->ownedBy($owner)->create(['labels' => ['design']]);

        $options = Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
            ->instance()
            ->reportFilterOptions();

        $this->assertEqualsCanonicalizing([$owner->id => $owner->name, $assignee->id => $assignee->name], $options['assignees']);
    }

    public function test_refresh_report_runs_without_error_and_preserves_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Task::factory()->forProject($project)->ownedBy($owner)->create(['deadline' => null]);

        $component = Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id]);
        $this->assertCount(1, $component->instance()->reportData()['rows']);

        Task::factory()->forProject($project)->ownedBy($owner)->create(['deadline' => null]);
        $component->call('refreshReport')->assertHasNoErrors()->assertSet('activeProjectId', $project->id);
        $this->assertCount(2, $component->instance()->reportData()['rows']);
    }

    public function test_report_state_resets_via_full_remount_between_projects(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $projectB = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
            ->set('reportSearch', 'stale')
            ->set('reportPriorityFilter', 'urgent')
            ->set('reportSort', ['field' => 'priority', 'dir' => 'desc'])
            ->assertSet('reportSearch', 'stale');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $projectB->id])
            ->assertSet('reportSearch', '')
            ->assertSet('reportPriorityFilter', null)
            ->assertSet('reportSort', ['field' => 'deadline', 'dir' => 'asc']);
    }

    public function test_report_data_includes_department_label_for_grouping(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $dept = Department::create(['code' => 'ENG', 'name' => 'Engineering', 'description' => '']);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        TaskDetail::factory()->create(['task_id' => $task->id, 'department_id' => $dept->code]);
        $ungrouped = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);

        $rows = collect(
            Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
                ->get('reportData')['rows']
        )->keyBy('id');

        $this->assertSame('Engineering', $rows[$task->id]['department_label']);
        $this->assertNull($rows[$ungrouped->id]['department_label']);
    }

    public function test_report_filter_options_lists_only_departments_used_in_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $usedDept = Department::create(['code' => 'ENG', 'name' => 'Engineering', 'description' => '']);
        Department::create(['code' => 'OPS', 'name' => 'Operations', 'description' => '']);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        TaskDetail::factory()->create(['task_id' => $task->id, 'department_id' => $usedDept->code]);

        $options = Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
            ->get('reportFilterOptions');

        $this->assertSame(['ENG' => 'Engineering'], $options['departments']);
    }

    public function test_set_report_filter_department_scopes_rows_and_clear_resets_it(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $dept = Department::create(['code' => 'ENG', 'name' => 'Engineering', 'description' => 'probe dept']);
        $matching = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        TaskDetail::factory()->create(['task_id' => $matching->id, 'department_id' => $dept->code]);
        Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);

        $component = Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
            ->call('setReportFilter', 'department', $dept->code);

        $rows = $component->get('reportData')['rows'];

        $this->assertCount(1, $rows);
        $this->assertSame($matching->id, $rows[0]['id']);

        $component->call('clearReportFilters');
        $this->assertNull($component->get('reportDepartmentFilter'));
    }

    public function test_report_scheme_progress_computes_done_over_total_per_scheme(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $doneA = Task::factory()->ownedBy($owner)->withStatus('done')->create(['project_id' => $project->id]);
        TaskDetail::factory()->create(['task_id' => $doneA->id, 'scheme' => 'Scheme A']);
        $todoA = Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);
        TaskDetail::factory()->create(['task_id' => $todoA->id, 'scheme' => 'Scheme A']);

        $progress = Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
            ->get('reportSchemeProgress');

        $this->assertSame([['scheme' => 'Scheme A', 'done' => 1, 'total' => 2]], $progress);
    }

    public function test_report_attachments_respects_active_filters(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $matching = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id, 'priority' => 'high']);
        TaskDetail::factory()->create(['task_id' => $matching->id, 'attachments' => [
            ['path' => 'task/attachments/a.png', 'name' => 'a.png', 'mime' => 'image/png', 'size' => 10],
        ]]);
        $filteredOut = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id, 'priority' => 'low']);
        TaskDetail::factory()->create(['task_id' => $filteredOut->id, 'attachments' => [
            ['path' => 'task/attachments/b.pdf', 'name' => 'b.pdf', 'mime' => 'application/pdf', 'size' => 20],
        ]]);
        $noAttachments = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id, 'priority' => 'high']);
        TaskDetail::factory()->create(['task_id' => $noAttachments->id]);

        $component = Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
            ->call('setReportFilter', 'priority', 'high');

        $attachments = $component->get('reportAttachments');

        $this->assertCount(1, $attachments);
        $this->assertSame($matching->id, $attachments[0]['task_id']);
        $this->assertSame('a.png', $attachments[0]['attachments'][0]['name']);

        $component->assertSeeHtml('a.png')->assertDontSeeHtml('b.pdf');
    }

    public function test_report_filters_reflow_renders_search_and_dropdowns_together(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Task::factory()->ownedBy($owner)->withStatus('todo')->create(['project_id' => $project->id]);

        Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
            ->assertSeeHtml('wire:model.live.debounce.300ms="reportSearch"')
            ->assertSeeHtml("setReportStatusFilter('todo')")
            ->assertSeeHtml("setReportFilter('assignee'");
    }

    public function test_report_active_project_id_is_locked_against_client_tampering(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $otherOwner = User::factory()->create(['status' => 'active']);
        $otherProject = Project::factory()->create(['owner_id' => $otherOwner->id, 'member_ids' => [], 'departments' => []]);
        Task::factory()->ownedBy($otherOwner)->create(['project_id' => $otherProject->id, 'deadline' => null]);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($owner)
            ->test(Report::class, ['activeProjectId' => $project->id])
            ->set('activeProjectId', $otherProject->id);
    }

    public function test_report_renders_a_placeholder_when_lazy_loading_is_active(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        \Livewire\Features\SupportLazyLoading\SupportLazyLoading::$disableWhileTesting = false;

        try {
            Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
                ->assertSeeHtml('در حال بارگذاری گزارش')
                ->assertDontSeeHtml('setReportStatusFilter');
        } finally {
            Livewire::withoutLazyLoading();
        }
    }

    public function test_report_placeholder_root_carries_the_refresh_bridge_listener(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
            ->assertSeeHtml('@project-report-refresh.window="$wire.refreshReport()"');

        \Livewire\Features\SupportLazyLoading\SupportLazyLoading::$disableWhileTesting = false;

        try {
            Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
                ->assertSeeHtml('@project-report-refresh.window="$wire.refreshReport()"');
        } finally {
            Livewire::withoutLazyLoading();
        }
    }

    public function test_report_expanded_row_renders_meta_chips_with_schema_label_and_raw_key_fallback(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['custom_schema' => ['vendor' => ['label' => 'تأمین‌کننده']]],
        ]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id, 'deadline' => null]);
        TaskDetail::factory()->create(['task_id' => $task->id, 'meta' => ['vendor' => 'آلفا', 'cost_center' => 'CC-9']]);

        $component = Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id]);

        $chips = collect($component->get('reportData')['rows'][0]['meta_chips'])->keyBy('key');
        $this->assertSame('تأمین‌کننده', $chips['vendor']['label']);
        $this->assertSame('آلفا', $chips['vendor']['value']);
        $this->assertSame('cost_center', $chips['cost_center']['label']);
        $this->assertSame('CC-9', $chips['cost_center']['value']);

        $component->assertSeeHtml('دیتای سفارشی')
            ->assertSeeHtml('تأمین‌کننده')
            ->assertSeeHtml('cost_center')
            ->assertSeeHtml('CC-9');
    }

    public function test_active_workflow_count_returns_zero_without_active_workflows(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $count = Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
            ->instance()
            ->activeWorkflowCount();

        $this->assertSame(0, $count);
    }

    public function test_active_workflow_count_counts_single_active_cycle(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        $count = Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
            ->instance()
            ->activeWorkflowCount();

        $this->assertSame(1, $count);
    }

    public function test_active_workflow_count_counts_multiple_active_cycles_and_excludes_other_statuses(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);
        Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);
        Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);
        Workflow::factory()->completed()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);
        Workflow::factory()->cancelled()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        $count = Livewire::actingAs($owner)->test(Report::class, ['activeProjectId' => $project->id])
            ->instance()
            ->activeWorkflowCount();

        $this->assertSame(2, $count);
    }

    public function test_active_workflow_count_returns_zero_without_active_project(): void
    {
        $owner = User::factory()->create(['status' => 'active']);

        $count = Livewire::actingAs($owner)->test(Report::class)->instance()->activeWorkflowCount();

        $this->assertSame(0, $count);
    }

    public function test_team_chat_messages_paginates_with_load_older(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $channel = app(ChannelProvisioner::class)->resolve($project);
        foreach (range(1, 35) as $i) {
            ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => "msg $i"]);
        }

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id]);

        $messages = $component->instance()->teamChatMessages();
        $this->assertCount(30, $messages['rows']);
        $this->assertTrue($messages['hasMore']);

        $component->call('loadOlderTeamChat');
        $messages = $component->instance()->teamChatMessages();
        $this->assertCount(35, $messages['rows']);
        $this->assertFalse($messages['hasMore']);
    }

    public function test_team_chat_state_resets_via_full_remount_between_projects(): void
    {
        [$owner, $projectA] = $this->ownerWithProject();
        $projectB = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        app(ChannelProvisioner::class)->resolve($projectA);

        Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $projectA->id])
            ->call('loadOlderTeamChat')
            ->assertSet('teamChatLimit', 60);

        Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $projectB->id])
            ->assertSet('teamChatLimit', 30);
    }

    public function test_team_chat_grouped_messages_attachments_resolved_via_presenter_trait(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $channel = app(ChannelProvisioner::class)->resolve($project);
        ChannelMessage::create([
            'channel_id' => $channel->id,
            'sender_id' => $owner->id,
            'body' => 'see attached',
            'attachments' => [['path' => 'channel_messages/x/y.jpg', 'name' => 'y.jpg', 'mime' => 'image/jpeg', 'size' => 2048]],
        ]);

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id]);
        $groups = $component->instance()->groupedTeamChatMessages();
        $attachment = $groups[0]['messages'][0]['attachments'][0];

        $this->assertTrue($attachment['is_image']);
        $this->assertSame('2.0 KB', $attachment['size_label']);
        $this->assertNotEmpty($attachment['url']);
    }

    public function test_team_chat_grouped_messages_type_flags_text_image_and_pdf(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $channel = app(ChannelProvisioner::class)->resolve($project);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'text only']);
        ChannelMessage::create([
            'channel_id' => $channel->id,
            'sender_id' => $owner->id,
            'body' => 'see attached',
            'attachments' => [['path' => 'channel_messages/x/y.png', 'name' => 'y.png', 'mime' => 'image/png', 'size' => 2048]],
        ]);
        ChannelMessage::create([
            'channel_id' => $channel->id,
            'sender_id' => $owner->id,
            'body' => '',
            'attachments' => [['path' => 'channel_messages/x/y.pdf', 'name' => 'y.pdf', 'mime' => 'application/pdf', 'size' => 2048]],
        ]);

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id]);
        $messages = $component->instance()->groupedTeamChatMessages()[0]['messages'];

        $this->assertSame('t', $messages[0]['type_flags']);
        $this->assertSame('t i', $messages[1]['type_flags']);
        $this->assertSame('p', $messages[2]['type_flags']);
    }

    public function test_send_chat_message_persists_the_message_and_bumps_the_chat_heartbeat_directly(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $before = ProjectHeartbeat::version($project->id, 'chat');

        Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id])
            ->set('chatComposer.body', 'hi team')
            ->call('sendChatMessage')
            ->assertHasNoErrors();

        $channel = app(ChannelProvisioner::class)->resolve($project->fresh());
        $this->assertTrue(ChannelMessage::where('channel_id', $channel->id)->where('body', 'hi team')->exists());

        ProjectHeartbeat::bump($project->id, 'chat');
        $this->assertNotSame($before, ProjectHeartbeat::version($project->id, 'chat'));
    }

    public function test_team_chat_grouped_messages_group_by_date_and_set_label(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $channel = app(ChannelProvisioner::class)->resolve($project);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'today one']);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'today two']);
        $yesterday = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'yesterday one']);
        $yesterday->forceFill(['created_at' => now()->subDay()])->save();

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id]);
        $groups = $component->instance()->groupedTeamChatMessages();

        $this->assertCount(2, $groups);
        $labels = collect($groups)->pluck('label')->all();
        $this->assertContains('امروز', $labels);
        $this->assertContains('دیروز', $labels);

        $todayGroup = collect($groups)->firstWhere('label', 'امروز');
        $yesterdayGroup = collect($groups)->firstWhere('label', 'دیروز');
        $this->assertCount(2, $todayGroup['messages']);
        $this->assertCount(1, $yesterdayGroup['messages']);
    }

    public function test_team_chat_grouped_messages_avatar_appears_on_is_last(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $channel = app(ChannelProvisioner::class)->resolve($project);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'one']);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'two']);

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id]);
        $messages = $component->instance()->groupedTeamChatMessages()[0]['messages'];

        $this->assertTrue($messages[0]['is_first']);
        $this->assertFalse($messages[0]['is_last']);
        $this->assertFalse($messages[1]['is_first']);
        $this->assertTrue($messages[1]['is_last']);
    }

    public function test_team_chat_grouped_messages_bubble_radius_and_gap_class(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $channel = app(ChannelProvisioner::class)->resolve($project);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'one']);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'two']);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'three']);

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id]);
        $messages = $component->instance()->groupedTeamChatMessages()[0]['messages'];

        $this->assertSame('rounded-2xl rounded-bl-md', $messages[0]['bubble_radius']);
        $this->assertSame('rounded-2xl rounded-l-md', $messages[1]['bubble_radius']);
        $this->assertSame('rounded-2xl rounded-tl-md', $messages[2]['bubble_radius']);
        $this->assertSame('mt-4', $messages[0]['gap_class']);
        $this->assertSame('mt-1', $messages[1]['gap_class']);
        $this->assertSame('mt-1', $messages[2]['gap_class']);
    }

    public function test_team_chat_mention_member_map_keyed_by_name_with_ids(): void
    {
        $member = User::factory()->create(['status' => 'active', 'name' => 'Sara Ahmadi']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id]);

        $map = $component->instance()->mentionMemberMap();

        $this->assertSame([$owner->id], $map[$owner->name]);
        $this->assertSame([$member->id], $map['Sara Ahmadi']);
    }

    public function test_mention_member_presence_reuses_mention_member_map_query(): void
    {
        $member = User::factory()->create(['status' => 'active', 'name' => 'Sara Ahmadi', 'presence' => 'onsite']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id]);

        try {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $map = $component->instance()->mentionMemberMap();
            $presence = $component->instance()->mentionMemberPresence();

            $memberQueries = collect(DB::getQueryLog())->pluck('query')
                ->filter(fn($q) => str_contains($q, 'select `users`.`id`, `users`.`name`, `users`.`presence`'));
            $this->assertCount(1, $memberQueries, 'mentionMemberMap and mentionMemberPresence must share one underlying query');
        } finally {
            DB::disableQueryLog();
        }

        $this->assertSame([$owner->id], $map[$owner->name]);
        $this->assertSame([$member->id], $map['Sara Ahmadi']);
        $this->assertNotNull($presence['Sara Ahmadi']['presence_label']);
        $this->assertNotNull($presence['Sara Ahmadi']['presence_class']);
        $this->assertTrue($presence['Sara Ahmadi']['reachable']);
    }

    public function test_team_chat_mention_member_names_empty_until_explicitly_loaded(): void
    {
        $member = User::factory()->create(['status' => 'active', 'name' => 'Sara Ahmadi']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id])
            ->assertSet('mentionMemberNames', []);

        $component->call('loadMentionMemberNames');

        $names = $component->get('mentionMemberNames');
        $this->assertContains($owner->name, $names);
        $this->assertContains('Sara Ahmadi', $names);
    }

    public function test_team_chat_remove_attachment_drops_entry_by_index(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $fileA = UploadedFile::fake()->image('a.jpg');
        $fileB = UploadedFile::fake()->image('b.jpg');
        $fileC = UploadedFile::fake()->image('c.jpg');

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id])
            ->set('chatComposer.attachments', [$fileA, $fileB, $fileC])
            ->call('removeAttachment', 1);

        $names = collect($component->instance()->chatComposer->attachments)->map->getClientOriginalName()->all();
        $this->assertSame(['a.jpg', 'c.jpg'], $names);
    }

    public function test_team_chat_composer_accepts_a_real_file_attachment(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $file = UploadedFile::fake()->image('scan.jpg');

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id])
            ->set('chatComposer.attachments', [$file])
            ->assertSeeHtml('scan.jpg');

        $attachments = $component->instance()->chatComposer->attachments;
        $this->assertCount(1, $attachments);
        $this->assertSame('scan.jpg', $attachments[0]->getClientOriginalName());
    }

    public function test_team_chat_composer_draft_survives_a_refresh_bridge_call(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id])
            ->set('chatComposer.body', 'work in progress draft')
            ->call('refreshTeamChat')
            ->assertSet('chatComposer.body', 'work in progress draft');
    }

    public function test_team_chat_send_creates_channel_message_and_resets_composer(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id])
            ->set('chatComposer.body', 'hello team')
            ->call('sendChatMessage')
            ->assertHasNoErrors()
            ->assertSet('chatComposer.body', '');

        $channelId = $project->fresh()->channel_id;
        $this->assertSame(1, ChannelMessage::where('channel_id', $channelId)->where('body', 'hello team')->count());
    }

    public function test_team_chat_grouped_messages_mentions_you_flag_when_member_mentioned(): void
    {
        $member = User::factory()->create(['status' => 'active', 'name' => 'Sara Ahmadi']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);
        $channel = app(ChannelProvisioner::class)->resolve($project);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'hey @Sara Ahmadi please review']);

        $component = Livewire::actingAs($member)->test(TeamChat::class, ['activeProjectId' => $project->id]);

        $msg = $component->instance()->groupedTeamChatMessages()[0]['messages'][0];

        $this->assertTrue($msg['mentions_you']);
        $this->assertStringContainsString('@Sara Ahmadi</span>', $msg['body_html']);
    }

    public function test_team_chat_messages_row_map_has_nested_sender_and_raw_attachments(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $channel = app(ChannelProvisioner::class)->resolve($project);
        ChannelMessage::create([
            'channel_id' => $channel->id,
            'sender_id' => $owner->id,
            'body' => 'raw shape',
            'attachments' => [['path' => 'channel_messages/x/y.jpg', 'name' => 'y.jpg', 'mime' => 'image/jpeg', 'size' => 2048]],
        ]);

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id]);
        $row = $component->instance()->teamChatMessages()['rows'][0];

        $this->assertSame($owner->name, $row['sender']['name']);
        $this->assertSame($owner->getProfileImageUrl() ?? $owner->getInitialsAvatarUrl(), $row['sender']['avatar']);
        $this->assertFalse($row['sender']['inactive']);
        $this->assertEquals(['path' => 'channel_messages/x/y.jpg', 'name' => 'y.jpg', 'mime' => 'image/jpeg', 'size' => 2048], $row['attachments'][0]);
    }

    public function test_project_chat_presenter_is_project_presenter_not_channel_presenter(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id]);
        $presenter = $component->instance()->chatPresenter();

        $this->assertInstanceOf(ProjectPresenter::class, $presenter);
        $this->assertNotInstanceOf(\App\Livewire\Dashboard\Channel\Presentation\ChannelPresenter::class, $presenter);
    }

    public function test_team_chat_active_project_id_is_locked_against_client_tampering(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $otherOwner = User::factory()->create(['status' => 'active']);
        $otherProject = Project::factory()->create(['owner_id' => $otherOwner->id, 'member_ids' => [], 'departments' => []]);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($owner)
            ->test(TeamChat::class, ['activeProjectId' => $project->id])
            ->set('activeProjectId', $otherProject->id);
    }

    public function test_team_chat_limit_is_locked_against_client_tampering(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($owner)
            ->test(TeamChat::class, ['activeProjectId' => $project->id])
            ->set('teamChatLimit', 5000);
    }

    public function test_activated_defaults_false_and_true_only_when_initial_active_is_passed(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)
            ->test(TeamChat::class, ['activeProjectId' => $project->id])
            ->assertSet('activated', false);

        Livewire::actingAs($owner)
            ->test(TeamChat::class, ['activeProjectId' => $project->id, 'initialActive' => true])
            ->assertSet('activated', true);
    }

    public function test_activate_flips_activated_to_true(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)
            ->test(TeamChat::class, ['activeProjectId' => $project->id])
            ->assertSet('activated', false)
            ->call('activate')
            ->assertSet('activated', true);
    }

    public function test_message_history_is_hidden_but_composer_stays_usable_before_activation(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $channel = app(ChannelProvisioner::class)->resolve($project);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'not yet visible message']);

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id]);

        $component->assertDontSeeHtml('not yet visible message');
        $component->assertSeeHtml('team-chat-ta');

        $component->call('activate')->assertSeeHtml('not yet visible message');
    }

    private function taskBoardForm(User $owner): TaskForm
    {
        $this->actingAs($owner);

        return Livewire::test(TaskBoardMain::class)->instance()->form;
    }

    public function test_create_project_action_trims_the_name_generates_a_unique_slug_and_normalizes_the_payload(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $member = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->actingAs($owner);

        $project = app(CreateProjectAction::class)->create(
            '  Padded Launch Name  ',
            [$member->id, 999999999],
            ['eng', 'ops', 'eng'],
        );

        $this->assertSame('Padded Launch Name', $project->name);
        $this->assertStringStartsWith('padded-launch-name-', $project->slug);
        $this->assertSame($owner->id, $project->owner_id);

        $fresh = $project->fresh();
        $this->assertSame([$member->id], $fresh->member_ids, 'non-existent member ids must be filtered out of member_ids');
        $this->assertSame(['eng', 'ops'], $fresh->departments, 'departments must be de-duplicated and re-indexed');
        $this->assertNull($fresh->settings, 'an empty settings bag must persist as null');

        $twin = app(CreateProjectAction::class)->create('  Padded Launch Name  ');
        $this->assertNotSame($project->slug, $twin->slug, 'identical names must still yield distinct slugs');
    }

    public function test_resolve_pending_project_returns_null_for_blank_or_whitespace_pending_name(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $action = app(CreateProjectAction::class);
        $form = $this->taskBoardForm($owner);

        $form->pendingProjectName = null;
        $this->assertNull($action->resolvePendingProject($form));

        $form->pendingProjectName = '   ';
        $this->assertNull($action->resolvePendingProject($form));

        $this->assertSame(0, Project::where('owner_id', $owner->id)->count(), 'a blank pending name must never create a project row');
    }

    public function test_resolve_pending_project_creates_the_pending_project_for_the_authenticated_actor(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $member = User::factory()->create(['status' => 'active', 'role' => 'user']);

        $form = $this->taskBoardForm($owner);
        $form->pendingProjectName = 'Pending Launch';
        $form->pendingProjectMemberIds = [$member->id, 999999999];
        $form->pendingProjectDepartments = ['eng', 'eng'];

        $projectId = app(CreateProjectAction::class)->resolvePendingProject($form);

        $project = Project::find($projectId);
        $this->assertNotNull($project);
        $this->assertSame('Pending Launch', $project->name);
        $this->assertSame($owner->id, $project->owner_id);
        $this->assertSame([$member->id], $project->member_ids);
        $this->assertSame(['eng'], $project->departments);
    }

    private function workflowStep(array $overrides = []): array
    {
        return array_merge([
            'label' => 'مرحله',
            'assignee_user_ids' => [],
            'type' => 'action',
            'reject_to_step' => null,
            'populate_task' => false,
            'task_title' => null,
            'task_description' => null,
            'deadline_days' => null,
        ], $overrides);
    }

    private function assertHttpAbort(int $status, \Closure $callback): void
    {
        try {
            $callback();
            $this->fail("Expected an HTTP {$status} abort");
        } catch (HttpException $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }

    public function test_non_owner_is_rejected_from_every_owner_gated_workflow_action(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $stranger = User::factory()->create(['status' => 'active']);
        $this->actingAs($stranger);
        $step = $this->workflowStep(['assignee_user_ids' => [$stranger->id]]);

        $this->assertHttpAbort(403, fn () => app(SaveWorkflowAction::class)->execute($project, [$step], 'چرخه', null));

        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$step]]);
        $this->assertHttpAbort(403, fn () => app(SaveWorkflowAction::class)->execute($project, [$step], 'ویرایش', $draft));
        $this->assertHttpAbort(403, fn () => app(StartWorkflowAction::class)->execute($draft));
        $this->assertHttpAbort(403, fn () => app(SaveAsTemplateAction::class)->execute($draft));

        $template = Workflow::factory()->asTemplate()->create(['owner_id' => $owner->id, 'steps' => [$step]]);
        $this->assertHttpAbort(403, fn () => app(ApplyTemplateAction::class)->execute($template, $project));

        $active = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$step]]);
        $this->assertHttpAbort(403, fn () => app(CancelWorkflowAction::class)->execute($active));
        $this->assertHttpAbort(403, fn () => app(AdvanceWorkflowStepAction::class)->execute($active->id, $stranger->id, 0, true, true));
    }

    public function test_start_workflow_rejects_empty_steps(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $this->actingAs($owner);
        $workflow = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => []]);

        $this->expectException(ValidationException::class);
        app(StartWorkflowAction::class)->execute($workflow);
    }

    public function test_start_workflow_rejects_a_step_with_zero_assignees(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $this->actingAs($owner);
        $workflow = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => []])]]);

        $this->expectException(ValidationException::class);
        app(StartWorkflowAction::class)->execute($workflow);
    }

    public function test_start_workflow_rejects_an_assignee_not_visible_to_the_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $stranger = User::factory()->create(['status' => 'active']);
        $this->actingAs($owner);
        $workflow = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$stranger->id]])]]);

        $this->expectException(ValidationException::class);
        app(StartWorkflowAction::class)->execute($workflow);
    }

    public function test_start_workflow_rejects_starting_on_a_soft_deleted_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $this->actingAs($owner);
        $workflow = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);
        $project->delete();

        $this->assertHttpAbort(403, fn () => app(StartWorkflowAction::class)->execute($workflow));
    }

    public function test_start_workflow_allows_multiple_simultaneous_active_cycles_on_the_same_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $this->actingAs($owner);
        $alreadyActive = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);

        $started = app(StartWorkflowAction::class)->execute($draft);

        $this->assertSame(Workflow::STATUS_ACTIVE, $started->fresh()->status);
        $this->assertSame(Workflow::STATUS_ACTIVE, $alreadyActive->fresh()->status);
    }

    public function test_save_workflow_rejects_any_edit_once_active(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $this->actingAs($owner);
        $active = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);

        $this->assertHttpAbort(403, fn () => app(SaveWorkflowAction::class)->execute($project, [$this->workflowStep(['assignee_user_ids' => [$assignee->id], 'label' => 'تغییر یافته'])], 'چرخه', $active));
    }

    public function test_save_workflow_filters_out_phantom_assignee_ids_silently(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $real = User::factory()->create(['status' => 'active']);
        $this->actingAs($owner);

        $workflow = app(SaveWorkflowAction::class)->execute($project, [$this->workflowStep(['assignee_user_ids' => [$real->id, 999999999]])], 'چرخه', null);

        $this->assertSame([$real->id], $workflow->fresh()->steps[0]['assignee_user_ids']);
    }

    public function test_save_workflow_rejects_populate_task_combined_with_approval_type(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $this->actingAs($owner);
        $steps = [
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
            $this->workflowStep(['assignee_user_ids' => [$assignee->id], 'type' => 'approval', 'populate_task' => true, 'task_title' => 'x']),
        ];

        $this->expectException(ValidationException::class);
        app(SaveWorkflowAction::class)->execute($project, $steps, 'چرخه', null);
    }

    public function test_save_workflow_stamps_owner_id_to_the_creating_actor(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $this->actingAs($owner);

        $workflow = app(SaveWorkflowAction::class)->execute($project, [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])], 'چرخه', null);

        $this->assertSame($owner->id, $workflow->owner_id);
    }

    public function test_save_workflow_persists_the_one_step_back_default_reject_to_step(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $this->actingAs($owner);
        $steps = [
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
            $this->workflowStep(['assignee_user_ids' => [$assignee->id], 'type' => 'approval']),
        ];

        $workflow = app(SaveWorkflowAction::class)->execute($project, $steps, 'چرخه', null);

        $this->assertSame(0, $workflow->fresh()->steps[1]['reject_to_step']);
    }

    public function test_save_workflow_rejects_a_reject_to_step_at_or_beyond_the_defining_steps_own_index(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $this->actingAs($owner);
        $steps = [
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
            $this->workflowStep(['assignee_user_ids' => [$assignee->id], 'type' => 'approval', 'reject_to_step' => 1]),
        ];

        $this->expectException(ValidationException::class);
        app(SaveWorkflowAction::class)->execute($project, $steps, 'چرخه', null);
    }

    public function test_save_workflow_accepts_deadline_days_boundary_values_zero_and_365(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $this->actingAs($owner);

        $low = app(SaveWorkflowAction::class)->execute($project, [$this->workflowStep(['assignee_user_ids' => [$assignee->id], 'populate_task' => true, 'task_title' => 'x', 'deadline_days' => 0])], 'چرخه', null);
        $high = app(SaveWorkflowAction::class)->execute($project, [$this->workflowStep(['assignee_user_ids' => [$assignee->id], 'populate_task' => true, 'task_title' => 'x', 'deadline_days' => 365])], 'چرخه', null);

        $this->assertSame(0, $low->fresh()->steps[0]['deadline_days']);
        $this->assertSame(365, $high->fresh()->steps[0]['deadline_days']);
    }

    public function test_save_workflow_rejects_deadline_days_below_zero(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $this->actingAs($owner);

        $this->expectException(ValidationException::class);
        app(SaveWorkflowAction::class)->execute($project, [$this->workflowStep(['assignee_user_ids' => [$assignee->id], 'populate_task' => true, 'task_title' => 'x', 'deadline_days' => -1])], 'چرخه', null);
    }

    public function test_save_workflow_rejects_deadline_days_above_365(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $this->actingAs($owner);

        $this->expectException(ValidationException::class);
        app(SaveWorkflowAction::class)->execute($project, [$this->workflowStep(['assignee_user_ids' => [$assignee->id], 'populate_task' => true, 'task_title' => 'x', 'deadline_days' => 366])], 'چرخه', null);
    }

    public function test_save_workflow_accepts_task_title_at_191_characters(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $this->actingAs($owner);
        $title = str_repeat('ا', 191);

        $workflow = app(SaveWorkflowAction::class)->execute($project, [$this->workflowStep(['assignee_user_ids' => [$assignee->id], 'populate_task' => true, 'task_title' => $title])], 'چرخه', null);

        $this->assertSame(191, mb_strlen($workflow->fresh()->steps[0]['task_title']));
    }

    public function test_save_workflow_rejects_task_title_at_192_characters(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $this->actingAs($owner);
        $title = str_repeat('ا', 192);

        $this->expectException(ValidationException::class);
        app(SaveWorkflowAction::class)->execute($project, [$this->workflowStep(['assignee_user_ids' => [$assignee->id], 'populate_task' => true, 'task_title' => $title])], 'چرخه', null);
    }

    public function test_advance_workflow_step_happy_path_advances_and_logs(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
        ]]);

        $advanced = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assignee->id, 0);

        $this->assertTrue($advanced);
        $fresh = $workflow->fresh();
        $this->assertSame(1, $fresh->current_step);
        $this->assertSame(Workflow::STATUS_ACTIVE, $fresh->status);
        $this->assertCount(1, $fresh->step_log);
        $this->assertSame($assignee->id, $fresh->step_log[0]['completed_by']);
    }

    public function test_advance_workflow_step_completes_the_cycle_on_the_last_step(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $workflow = Workflow::factory()->active()->create([
            'owner_id' => $owner->id,
            'project_id' => $project->id,
            'current_step' => 1,
            'steps' => [
                $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
                $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
            ],
        ]);

        $advanced = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assignee->id, 1);

        $this->assertTrue($advanced);
        $fresh = $workflow->fresh();
        $this->assertSame(Workflow::STATUS_COMPLETED, $fresh->status);
        $this->assertSame(1, $fresh->current_step);
        $this->assertNotNull($fresh->completed_at);
    }

    public function test_advance_workflow_step_one_step_cycle_completes_immediately_on_first_advance(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);

        $advanced = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assignee->id, 0);

        $this->assertTrue($advanced);
        $this->assertSame(Workflow::STATUS_COMPLETED, $workflow->fresh()->status);
    }

    public function test_advance_workflow_step_duplicate_call_with_a_stale_expected_step_no_ops(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
        ]]);

        $first = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assignee->id, 0);
        $second = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assignee->id, 0);

        $this->assertTrue($first);
        $this->assertFalse($second);
        $fresh = $workflow->fresh();
        $this->assertSame(1, $fresh->current_step);
        $this->assertCount(1, $fresh->step_log);
    }

    public function test_advance_workflow_step_concurrent_racing_calls_against_the_same_expected_step_result_in_only_one_net_advance(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assigneeA = User::factory()->create(['status' => 'active']);
        $assigneeB = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assigneeA->id, $assigneeB->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$assigneeA->id, $assigneeB->id]]),
            $this->workflowStep(['assignee_user_ids' => [$assigneeA->id, $assigneeB->id]]),
        ]]);

        $resultA = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assigneeA->id, 0);
        $resultB = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assigneeB->id, 0);

        $this->assertSame(1, (int) $resultA + (int) $resultB, 'exactly one of the two racing calls against the same stale expectedStep must succeed');
        $fresh = $workflow->fresh();
        $this->assertSame(1, $fresh->current_step);
        $this->assertCount(1, $fresh->step_log);
    }

    public function test_advance_workflow_step_no_ops_after_the_cycle_is_already_completed(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);
        app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assignee->id, 0);
        $this->assertSame(Workflow::STATUS_COMPLETED, $workflow->fresh()->status);

        $again = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assignee->id, 0);

        $this->assertFalse($again);
    }

    public function test_advance_workflow_step_rejects_a_non_assignee_non_owner(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id, $stranger->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
        ]]);

        $this->assertHttpAbort(403, fn () => app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $stranger->id, 0));
    }

    public function test_advance_workflow_step_owner_can_advance_without_being_an_assignee(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
        ]]);

        $advanced = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $owner->id, 0);

        $this->assertTrue($advanced);
    }

    public function test_advance_workflow_step_populated_step_manual_complete_no_ops_while_task_is_not_genuinely_done(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $driver = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$driver->id]]);
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$driver->id], 'populate_task' => true, 'task_title' => 'وظیفه']),
            $this->workflowStep(['assignee_user_ids' => [$driver->id]]),
        ]]);
        $workflow = app(StartWorkflowAction::class)->execute($draft);

        $advanced = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $driver->id, 0);

        $this->assertFalse($advanced);
        $this->assertSame(0, $workflow->fresh()->current_step);
    }

    public function test_advance_workflow_step_owner_force_advances_a_stuck_populated_step_and_logs_forced(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $driver = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$driver->id]]);
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$driver->id], 'populate_task' => true, 'task_title' => 'وظیفه']),
            $this->workflowStep(['assignee_user_ids' => [$driver->id]]),
        ]]);
        $workflow = app(StartWorkflowAction::class)->execute($draft);

        $forced = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $owner->id, 0, true, true);

        $this->assertTrue($forced);
        $fresh = $workflow->fresh();
        $this->assertSame(1, $fresh->current_step);
        $this->assertTrue($fresh->step_log[0]['forced']);
    }

    public function test_is_stuck_with_zero_deadline_days_is_not_instantly_stuck(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $driver = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$driver->id]]);
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$driver->id], 'populate_task' => true, 'task_title' => 'وظیفه', 'deadline_days' => 0]),
        ]]);
        $workflow = app(StartWorkflowAction::class)->execute($draft);

        Livewire::actingAs($owner)->test(WorkflowTab::class, ['activeProjectId' => $project->id])
            ->call('isStuck', $workflow->fresh())
            ->assertReturned(false);

        $this->travel(2)->days();

        Livewire::actingAs($owner)->test(WorkflowTab::class, ['activeProjectId' => $project->id])
            ->call('isStuck', $workflow->fresh())
            ->assertReturned(true);
    }

    public function test_workflow_presenter_can_act_is_populated_and_populated_task_branches(): void
    {
        $presenter = new WorkflowPresenter();
        $owner = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create();

        $step = ['assignee_user_ids' => [$assignee->id], 'populate_task' => true, 'task_id' => $task->id];
        $batchedTasks = collect([$task->id => $task]);

        $this->assertTrue($presenter->canAct($step, false, $assignee->id));
        $this->assertTrue($presenter->canAct($step, true, $stranger->id));
        $this->assertFalse($presenter->canAct($step, false, $stranger->id));
        $this->assertFalse($presenter->canAct(null, true, $owner->id));

        $this->assertTrue($presenter->isPopulated($step));
        $this->assertFalse($presenter->isPopulated(['populate_task' => false]));
        $this->assertFalse($presenter->isPopulated(null));

        $this->assertSame($task->id, $presenter->populatedTask($step, $batchedTasks)?->id);
        $this->assertNull($presenter->populatedTask(['populate_task' => false, 'task_id' => $task->id], $batchedTasks));
        $this->assertNull($presenter->populatedTask(['populate_task' => true], $batchedTasks));
    }

    public function test_advance_workflow_step_writes_the_note_into_the_step_log_entry(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
        ]]);

        app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assignee->id, 0, true, false, 'یادداشت من');

        $this->assertSame('یادداشت من', $workflow->fresh()->step_log[0]['note']);
    }

    public function test_advance_workflow_step_approval_rejected_sends_current_step_back_and_stays_active(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'current_step' => 1, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
            $this->workflowStep(['assignee_user_ids' => [$assignee->id], 'type' => 'approval', 'reject_to_step' => 0]),
        ]]);

        $result = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assignee->id, 1, false);

        $this->assertTrue($result);
        $fresh = $workflow->fresh();
        $this->assertSame(0, $fresh->current_step);
        $this->assertSame(Workflow::STATUS_ACTIVE, $fresh->status);
        $this->assertFalse($fresh->step_log[0]['approved']);
        $this->assertNotNull($fresh->steps[0]['activated_at']);
    }

    public function test_advance_workflow_step_approval_approved_behaves_like_an_action_advance(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'current_step' => 1, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
            $this->workflowStep(['assignee_user_ids' => [$assignee->id], 'type' => 'approval', 'reject_to_step' => 0]),
        ]]);

        $result = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assignee->id, 1, true);

        $this->assertTrue($result);
        $fresh = $workflow->fresh();
        $this->assertSame(Workflow::STATUS_COMPLETED, $fresh->status);
        $this->assertTrue($fresh->step_log[0]['approved']);
    }

    public function test_activation_timestamp_refreshes_on_a_forward_move_into_a_step(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
        ]]);
        $this->assertArrayNotHasKey('activated_at', $workflow->steps[1]);

        app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assignee->id, 0);

        $this->assertNotNull($workflow->fresh()->steps[1]['activated_at']);
    }

    public function test_activation_timestamp_refreshes_on_a_reject_back_move_into_a_step(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $oldTimestamp = now()->subDays(10)->toISOString();
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'current_step' => 1, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$assignee->id], 'activated_at' => $oldTimestamp]),
            $this->workflowStep(['assignee_user_ids' => [$assignee->id], 'type' => 'approval', 'reject_to_step' => 0]),
        ]]);

        app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assignee->id, 1, false);

        $refreshed = $workflow->fresh()->steps[0]['activated_at'];
        $this->assertNotSame($oldTimestamp, $refreshed);
        $this->assertTrue(Carbon::parse($refreshed)->isToday());
    }

    public function test_save_as_template_produces_a_project_id_null_row(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $source = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);
        $this->actingAs($owner);

        $template = app(SaveAsTemplateAction::class)->execute($source);

        $this->assertNull($template->project_id);
    }

    public function test_save_as_template_stamps_the_saving_actor_not_the_sources_owner_id(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $otherAuthor = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $source = Workflow::factory()->create(['owner_id' => $otherAuthor->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);
        $this->actingAs($owner);

        $template = app(SaveAsTemplateAction::class)->execute($source);

        $this->assertSame($owner->id, $template->owner_id);
        $this->assertNotSame($otherAuthor->id, $template->owner_id);
    }

    public function test_apply_template_clones_steps_verbatim_and_strips_task_id_without_mutating_the_source(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $template = Workflow::factory()->asTemplate()->create([
            'owner_id' => $owner->id,
            'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id], 'populate_task' => true, 'task_title' => 'وظیفه الگو', 'deadline_days' => 5, 'task_id' => 999])],
        ]);
        $this->actingAs($owner);

        $applied = app(ApplyTemplateAction::class)->execute($template, $project);

        $this->assertSame($project->id, $applied->project_id);
        $this->assertSame($template->id, $applied->template_id);
        $this->assertSame(Workflow::STATUS_DRAFT, $applied->status);
        $this->assertSame([$assignee->id], $applied->steps[0]['assignee_user_ids']);
        $this->assertTrue($applied->steps[0]['populate_task']);
        $this->assertSame('وظیفه الگو', $applied->steps[0]['task_title']);
        $this->assertSame(5, $applied->steps[0]['deadline_days']);
        $this->assertArrayNotHasKey('task_id', $applied->steps[0]);
        $this->assertSame(999, $template->fresh()->steps[0]['task_id'], 'applying a template must never mutate the source row');
    }

    public function test_apply_template_stamps_the_applying_actor_not_the_templates_own_owner_id(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $templateAuthor = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $template = Workflow::factory()->asTemplate()->create(['owner_id' => $templateAuthor->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);
        $this->actingAs($owner);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

        $applied = app(ApplyTemplateAction::class)->execute($template, $project);

        $this->assertSame($owner->id, $applied->owner_id);
        $this->assertNotSame($templateAuthor->id, $applied->owner_id);
    }

    public function test_template_with_populated_steps_applied_carries_no_task_id_and_creates_nothing_until_start(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $driver = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$driver->id]]);
        $template = Workflow::factory()->asTemplate()->create(['owner_id' => $owner->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$driver->id], 'populate_task' => true, 'task_title' => 'وظیفه الگو'])]]);
        $this->actingAs($owner);
        $countBefore = Task::count();

        $applied = app(ApplyTemplateAction::class)->execute($template, $project);

        $this->assertArrayNotHasKey('task_id', $applied->steps[0]);
        $this->assertSame($countBefore, Task::count());
        $this->assertSame(Workflow::STATUS_DRAFT, $applied->status);
    }

    public function test_reassign_workflow_step_changes_only_the_active_steps_assignees(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $current = User::factory()->create(['status' => 'active']);
        $newPerson = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$current->id, $newPerson->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$current->id]]),
            $this->workflowStep(['assignee_user_ids' => [$current->id]]),
        ]]);

        app(ReassignWorkflowStepAction::class)->execute($workflow, $owner->id, [$newPerson->id]);

        $fresh = $workflow->fresh();
        $this->assertSame([$newPerson->id], $fresh->steps[0]['assignee_user_ids']);
        $this->assertSame([$current->id], $fresh->steps[1]['assignee_user_ids']);
    }

    public function test_reassign_workflow_step_rejects_a_non_assignee_non_owner(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $current = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $newPerson = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$current->id, $stranger->id, $newPerson->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$current->id]])]]);

        $this->assertHttpAbort(403, fn () => app(ReassignWorkflowStepAction::class)->execute($workflow, $stranger->id, [$newPerson->id]));
    }

    public function test_reassign_workflow_step_no_ops_when_the_workflow_is_not_active(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $current = User::factory()->create(['status' => 'active']);
        $newPerson = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$current->id, $newPerson->id]]);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$current->id]])]]);

        app(ReassignWorkflowStepAction::class)->execute($draft, $owner->id, [$newPerson->id]);

        $this->assertSame([$current->id], $draft->fresh()->steps[0]['assignee_user_ids']);
    }

    public function test_reassign_workflow_step_rejects_an_empty_new_ids_array(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $current = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$current->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$current->id]])]]);

        $this->expectException(ValidationException::class);
        app(ReassignWorkflowStepAction::class)->execute($workflow, $owner->id, []);
    }

    public function test_reassign_workflow_step_filters_out_an_assignee_not_visible_to_the_project(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $current = User::factory()->create(['status' => 'active']);
        $visible = User::factory()->create(['status' => 'active']);
        $invisible = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$current->id, $visible->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$current->id]])]]);

        app(ReassignWorkflowStepAction::class)->execute($workflow, $owner->id, [$visible->id, $invisible->id]);

        $this->assertSame([$visible->id], $workflow->fresh()->steps[0]['assignee_user_ids']);
    }

    public function test_reassign_workflow_step_rejects_more_than_twenty_ids(): void
    {
        $this->expectException(ValidationException::class);

        [$owner, $project] = $this->ownerWithProject();
        $current = User::factory()->create(['status' => 'active']);
        $members = User::factory()->count(21)->create(['status' => 'active']);
        $project->update(['member_ids' => [$current->id, ...$members->pluck('id')->all()]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$current->id]])]]);

        app(ReassignWorkflowStepAction::class)->execute($workflow, $owner->id, $members->pluck('id')->all());
    }

    public function test_reassign_workflow_step_counts_the_twenty_cap_after_visibility_filtering_not_before(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $current = User::factory()->create(['status' => 'active']);
        $visibleMembers = User::factory()->count(15)->create(['status' => 'active']);
        $invisibleStrangers = User::factory()->count(10)->create(['status' => 'active']);
        $project->update(['member_ids' => [$current->id, ...$visibleMembers->pluck('id')->all()]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$current->id]])]]);

        $submitted = [...$visibleMembers->pluck('id')->all(), ...$invisibleStrangers->pluck('id')->all()];
        app(ReassignWorkflowStepAction::class)->execute($workflow, $owner->id, $submitted);

        $this->assertCount(15, $workflow->fresh()->steps[0]['assignee_user_ids']);
    }

    public function test_reassign_workflow_step_accepts_exactly_twenty_ids(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $current = User::factory()->create(['status' => 'active']);
        $members = User::factory()->count(20)->create(['status' => 'active']);
        $project->update(['member_ids' => [$current->id, ...$members->pluck('id')->all()]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$current->id]])]]);

        app(ReassignWorkflowStepAction::class)->execute($workflow, $owner->id, $members->pluck('id')->all());

        $this->assertCount(20, $workflow->fresh()->steps[0]['assignee_user_ids']);
    }

    public function test_reassign_workflow_step_populated_step_syncs_the_linked_tasks_assignment(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $driver = User::factory()->create(['status' => 'active']);
        $collaborator = User::factory()->create(['status' => 'active']);
        $newDriver = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$driver->id, $collaborator->id, $newDriver->id]]);
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$driver->id, $collaborator->id], 'populate_task' => true, 'task_title' => 'وظیفه']),
        ]]);
        $workflow = app(StartWorkflowAction::class)->execute($draft);
        $taskId = $workflow->fresh()->steps[0]['task_id'];

        app(ReassignWorkflowStepAction::class)->execute($workflow, $owner->id, [$newDriver->id]);

        $task = Task::with('detail')->find($taskId);
        $this->assertSame($newDriver->id, $task->assigned_to);
        $this->assertNull($task->detail->collaborators);
    }

    public function test_reassign_workflow_step_sends_a_database_notification_to_newly_added_people(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $current = User::factory()->create(['status' => 'active']);
        $newPerson = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$current->id, $newPerson->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$current->id]])]]);

        app(ReassignWorkflowStepAction::class)->execute($workflow, $owner->id, [$newPerson->id]);
        \App\Services\Menu\NudgeService::reconcile('workflow:nudge', Workflow::class, $workflow->id);

        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $newPerson->id)->count());
    }

    public function test_reassign_workflow_step_short_circuits_when_the_normalized_set_is_unchanged(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $current = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$current->id, $other->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$current->id, $other->id]])]]);
        $originalUpdatedAt = $workflow->updated_at;
        $notificationsBefore = DB::table('notifications')->count();
        $this->travel(2)->seconds();

        app(ReassignWorkflowStepAction::class)->execute($workflow, $owner->id, [$other->id, $current->id, $current->id]);

        $fresh = $workflow->fresh();
        $this->assertEquals($originalUpdatedAt->toDateTimeString(), $fresh->updated_at->toDateTimeString());
        $this->assertSame($notificationsBefore, DB::table('notifications')->count());
    }

    public function test_project_force_deletion_cascades_workflow_instances_and_leaves_templates_untouched(): void
    {
        if (!DB::selectOne("select 1 from information_schema.REFERENTIAL_CONSTRAINTS where CONSTRAINT_SCHEMA = database() and TABLE_NAME = 'workflows' and REFERENCED_TABLE_NAME = 'projects' and DELETE_RULE = 'CASCADE'")) {
            $this->markTestSkipped('dev DB lacks the workflows.project_id ON DELETE CASCADE foreign key declared in the migration');
        }

        [$owner, $project] = $this->ownerWithProject();
        $instance = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);
        $template = Workflow::factory()->asTemplate()->create(['owner_id' => $owner->id]);

        $project->forceDelete();

        $this->assertNull(Workflow::find($instance->id));
        $this->assertNotNull(Workflow::find($template->id));
    }

    public function test_project_soft_deletion_cancels_live_and_draft_workflows_without_notifying_assignees(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $active = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);

        $project->delete();

        $this->assertSame(Workflow::STATUS_CANCELLED, $active->fresh()->status);
        $this->assertSame(Workflow::STATUS_CANCELLED, $draft->fresh()->status, 'archiving a project cancels its live and draft instances alike');
        \App\Services\Menu\NudgeService::reconcile('workflow:nudge', Workflow::class, $active->id);
        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $assignee->id)->count(), 'a project_deleted cancellation never notifies the step assignees');
    }

    public function test_cancel_workflow_action_mirrors_to_activity_unlike_the_archive_driven_mass_cancel(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $this->actingAs($owner);
        $active = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);

        app(CancelWorkflowAction::class)->execute($active);

        $this->assertSame(Workflow::STATUS_CANCELLED, $active->fresh()->status);
        $this->assertSame(1, Reply::where('repliable_type', Project::class)->where('repliable_id', $project->id)->where('type', TaskActivityType::WorkflowStep->value)->where('payload->event', 'cancelled')->count());
    }

    public function test_workflow_step_nudge_for_returns_the_current_step_assignees_when_the_cycle_starts(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $driver = User::factory()->create(['status' => 'active']);
        $collaborator = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$driver->id, $collaborator->id]]);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$driver->id, $collaborator->id]])]]);
        $this->actingAs($owner);

        $workflow = app(StartWorkflowAction::class)->execute($draft);

        $recipients = (new ProjectNudge('step'))->for($workflow->fresh())->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$driver->id, $collaborator->id], $recipients);
    }

    public function test_workflow_step_nudge_for_returns_only_the_owner_when_completed(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);
        $this->actingAs($assignee);

        app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assignee->id, 0);

        $recipients = (new ProjectNudge('step'))->for($workflow->fresh())->pluck('id')->all();

        $this->assertSame([$owner->id], $recipients);
    }

    public function test_workflow_step_nudge_trigger_does_not_fire_for_a_reply_that_is_not_a_workflow_step(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $workflow = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);
        $trigger = (new ProjectNudge('step'))->triggers()[1]['subject'];

        $comment = new Reply(['repliable_type' => Project::class, 'type' => TaskActivityType::Comment, 'payload' => ['workflow' => $workflow->id]]);
        $taskScoped = new Reply(['repliable_type' => Task::class, 'type' => TaskActivityType::WorkflowStep, 'payload' => ['workflow' => $workflow->id]]);

        $this->assertNull($trigger($comment));
        $this->assertNull($trigger($taskScoped));
    }

    public function test_workflow_step_nudge_trigger_fires_on_a_backward_reject_move(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);
        $trigger = (new ProjectNudge('step'))->triggers()[1]['subject'];

        $reply = new Reply(['repliable_type' => Project::class, 'type' => TaskActivityType::WorkflowStep, 'payload' => ['workflow' => $workflow->id, 'event' => 'rejected']]);

        $this->assertSame($workflow->id, $trigger($reply)->id);
    }

    public function test_workflow_step_nudge_trigger_fires_the_completion_variant(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $workflow = Workflow::factory()->completed()->create(['owner_id' => $owner->id, 'project_id' => $project->id]);
        $trigger = (new ProjectNudge('step'))->triggers()[1]['subject'];

        $reply = new Reply(['repliable_type' => Project::class, 'type' => TaskActivityType::WorkflowStep, 'payload' => ['workflow' => $workflow->id, 'event' => 'completed']]);

        $this->assertSame($workflow->id, $trigger($reply)->id);
    }

    public function test_workflow_step_nudge_trigger_resolves_nothing_for_a_workflow_that_no_longer_exists(): void
    {
        $trigger = (new ProjectNudge('step'))->triggers()[1]['subject'];

        $reply = new Reply(['repliable_type' => Project::class, 'type' => TaskActivityType::WorkflowStep, 'payload' => ['workflow' => 999999999, 'event' => 'cancelled']]);

        $this->assertNull($trigger($reply));
    }

    public function test_opening_the_new_workflow_editor_hides_the_empty_state_when_there_are_no_active_cycles(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(WorkflowTab::class, ['activeProjectId' => $project->id])
            ->assertSeeHtml('workflow-active-empty')
            ->call('openNewWorkflow')
            ->assertDontSeeHtml('workflow-active-empty')
            ->assertSeeHtml('workflow-editor');
    }

    public function test_workflow_tab_active_project_id_is_locked_against_client_tampering(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $otherOwner = User::factory()->create(['status' => 'active']);
        $otherProject = Project::factory()->create(['owner_id' => $otherOwner->id, 'member_ids' => [], 'departments' => []]);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($owner)
            ->test(WorkflowTab::class, ['activeProjectId' => $project->id])
            ->set('activeProjectId', $otherProject->id);
    }

    public function test_flipping_a_populated_steps_type_to_approval_clears_the_task_population_fields(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(WorkflowTab::class, ['activeProjectId' => $project->id])
            ->set('steps.0.populate_task', true)
            ->set('steps.0.task_title', 'وظیفه')
            ->set('steps.0.deadline_days', 3)
            ->set('steps.0.type', 'approval')
            ->assertSet('steps.0.populate_task', false)
            ->assertSet('steps.0.task_title', null)
            ->assertSet('steps.0.task_description', null)
            ->assertSet('steps.0.deadline_days', null);
    }

    public function test_save_workflow_validation_failure_shows_inline_errors_only_not_a_duplicate_toast(): void
    {
        [$owner, $project] = $this->ownerWithProject();

        Livewire::actingAs($owner)->test(WorkflowTab::class, ['activeProjectId' => $project->id])
            ->call('saveWorkflow')
            ->assertHasErrors(['name'])
            ->assertNotDispatched('toast');
    }

    public function test_open_reassign_only_allows_the_owner_or_the_current_steps_assignee(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id, $stranger->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);

        Livewire::actingAs($stranger)->test(WorkflowTab::class, ['activeProjectId' => $project->id])
            ->call('openReassign', $workflow->id)
            ->assertSet('reassigningWorkflowId', null);

        Livewire::actingAs($assignee)->test(WorkflowTab::class, ['activeProjectId' => $project->id])
            ->call('openReassign', $workflow->id)
            ->assertSet('reassigningWorkflowId', $workflow->id);
    }

    public function test_advance_step_toast_reflects_the_actions_real_boolean_result(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
        ]]);

        Livewire::actingAs($assignee)->test(WorkflowTab::class, ['activeProjectId' => $project->id])
            ->call('advanceStep', $workflow->id, 0)
            ->assertDispatched('toast', type: 'success');

        Livewire::actingAs($assignee)->test(WorkflowTab::class, ['activeProjectId' => $project->id])
            ->call('advanceStep', $workflow->id, 0)
            ->assertDispatched('toast', type: 'info');
    }

    public function test_force_advance_toast_reflects_the_actions_real_boolean_result(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $workflow = Workflow::factory()->active()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);
        app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $assignee->id, 0);
        $this->assertSame(Workflow::STATUS_COMPLETED, $workflow->fresh()->status);

        Livewire::actingAs($owner)->test(WorkflowTab::class, ['activeProjectId' => $project->id])
            ->call('forceAdvance', $workflow->id, $workflow->fresh()->current_step)
            ->assertDispatched('toast', type: 'info');
    }

    public function test_run_again_deletes_the_scratch_template_even_when_apply_template_throws(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $this->actingAs($owner);
        $source = Workflow::factory()->completed()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);

        $mock = Mockery::mock(ApplyTemplateAction::class);
        $mock->shouldReceive('execute')->once()->andThrow(ValidationException::withMessages(['x' => 'boom']));
        $this->app->instance(ApplyTemplateAction::class, $mock);

        $countBefore = Workflow::templates()->count();

        Livewire::actingAs($owner)->test(WorkflowTab::class, ['activeProjectId' => $project->id])
            ->call('runAgain', $source->id)
            ->assertDispatched('toast', type: 'error');

        $this->assertSame($countBefore, Workflow::templates()->count(), 'the scratch template created for runAgain must never be left orphaned when applying it fails');
    }

    public function test_run_again_points_the_new_cycles_template_id_at_the_original_source_workflow(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $assignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$assignee->id]]);
        $this->actingAs($owner);
        $source = Workflow::factory()->completed()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);
        $countBefore = Workflow::templates()->count();

        Livewire::actingAs($owner)->test(WorkflowTab::class, ['activeProjectId' => $project->id])
            ->call('runAgain', $source->id)
            ->assertDispatched('toast', type: 'success');

        $newCycle = Workflow::forProject($project->id)->where('id', '!=', $source->id)->first();
        $this->assertNotNull($newCycle);
        $this->assertSame($source->id, $newCycle->template_id);
        $this->assertSame($countBefore, Workflow::templates()->count(), 'the scratch template must not linger as a reusable template');
    }

    public function test_starting_a_workflow_creates_the_populated_steps_task_with_the_exact_mapped_fields(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $driver = User::factory()->create(['status' => 'active']);
        $collaborator = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$driver->id, $collaborator->id]]);
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'name' => 'چرخه استاندارد', 'steps' => [
            $this->workflowStep(['label' => 'اجرا', 'assignee_user_ids' => [$driver->id, $collaborator->id], 'populate_task' => true, 'task_title' => 'اجرای کار', 'task_description' => 'شرح', 'deadline_days' => 3]),
        ]]);

        $workflow = app(StartWorkflowAction::class)->execute($draft);
        $taskId = $workflow->fresh()->steps[0]['task_id'];
        $task = Task::with('detail')->find($taskId);

        $this->assertSame('اجرای کار', $task->title);
        $this->assertSame('شرح', $task->description);
        $this->assertSame($driver->id, $task->assigned_to);
        $this->assertSame([$collaborator->id], $task->detail->collaborators);
        $this->assertSame($owner->id, $task->user_id);
        $this->assertSame('12:00:00', $task->deadline->format('H:i:s'));
        $this->assertSame(TaskState::Completion->value, $task->detail->state);
    }

    public function test_complete_for_task_advances_even_when_the_task_was_reassigned_away_from_the_registered_driver(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $driver = User::factory()->create(['status' => 'active']);
        $newAssignee = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$driver->id, $newAssignee->id]]);
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$driver->id], 'populate_task' => true, 'task_title' => 'وظیفه چرخه']),
            $this->workflowStep(['assignee_user_ids' => [$driver->id]]),
        ]]);
        $workflow = app(StartWorkflowAction::class)->execute($draft);
        $taskId = $workflow->fresh()->steps[0]['task_id'];
        Task::find($taskId)->update(['assigned_to' => $newAssignee->id]);

        $this->actingAs($newAssignee);
        Task::find($taskId)->update(['status' => 'done']);

        $this->assertSame(1, $workflow->fresh()->current_step, 'a task-driven completion must advance the cycle even though the completer is not the workflow-registered driver');
    }

    public function test_requires_approval_off_advances_immediately_when_the_populated_task_is_marked_done(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $driver = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$driver->id]]);
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$driver->id], 'populate_task' => true, 'task_title' => 'وظیفه']),
            $this->workflowStep(['assignee_user_ids' => [$driver->id]]),
        ]]);
        $workflow = app(StartWorkflowAction::class)->execute($draft);
        $taskId = $workflow->fresh()->steps[0]['task_id'];

        $this->actingAs($driver);
        Task::find($taskId)->update(['status' => 'done']);

        $this->assertSame(1, $workflow->fresh()->current_step);
    }

    public function test_requires_approval_on_with_non_owner_completion_holds_until_approve_task_action_runs(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $driver = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$driver->id], 'departments' => [], 'settings' => ['requires_approval' => true]]);
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$driver->id], 'populate_task' => true, 'task_title' => 'وظیفه']),
            $this->workflowStep(['assignee_user_ids' => [$driver->id]]),
        ]]);
        $workflow = app(StartWorkflowAction::class)->execute($draft);
        $taskId = $workflow->fresh()->steps[0]['task_id'];

        $this->actingAs($driver);
        Task::find($taskId)->update(['status' => 'done']);
        $this->assertSame(0, $workflow->fresh()->current_step, 'must not advance until the owner approves it');

        $this->actingAs($owner);
        $approved = app(ApproveTaskAction::class)->execute(Task::find($taskId), $owner);

        $this->assertTrue($approved);
        $this->assertSame(1, $workflow->fresh()->current_step, 'the approval must trigger the advance');
    }

    public function test_a_done_task_belonging_to_no_active_workflow_step_no_ops_harmlessly(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id, 'status' => 'todo']);
        $this->actingAs($owner);

        Task::find($task->id)->update(['status' => 'done']);

        $this->assertSame('done', $task->fresh()->status);
        $this->assertSame(0, Workflow::where('project_id', $project->id)->count());
    }

    public function test_completed_or_cancelled_workflows_linked_task_reaching_done_does_not_resurrect_it(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $driver = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$driver->id]]);
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$driver->id], 'populate_task' => true, 'task_title' => 'وظیفه'])]]);
        $workflow = app(StartWorkflowAction::class)->execute($draft);
        $taskId = $workflow->fresh()->steps[0]['task_id'];
        app(CancelWorkflowAction::class)->execute($workflow);
        $this->assertSame(Workflow::STATUS_CANCELLED, $workflow->fresh()->status);

        $this->actingAs($driver);
        Task::find($taskId)->update(['status' => 'done']);

        $this->assertSame(Workflow::STATUS_CANCELLED, $workflow->fresh()->status, 'a cancelled cycle must never be resurrected by its orphaned tasks completion');
    }

    public function test_reject_back_after_a_forced_advance_stops_the_prior_open_task_and_creates_a_fresh_one(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $driver = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$driver->id]]);
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$driver->id], 'populate_task' => true, 'task_title' => 'وظیفه اول']),
            $this->workflowStep(['assignee_user_ids' => [$driver->id], 'type' => 'approval', 'reject_to_step' => 0]),
        ]]);
        $workflow = app(StartWorkflowAction::class)->execute($draft);
        $firstTaskId = $workflow->fresh()->steps[0]['task_id'];
        app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $owner->id, 0, true, true);
        $this->assertSame(1, $workflow->fresh()->current_step);
        $countBefore = Task::count();

        app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $driver->id, 1, false);

        $this->assertSame($countBefore + 1, Task::count(), 'a forced advance stops the open task, so the rejected step gets a fresh occurrence');
        $this->assertNotSame($firstTaskId, $workflow->fresh()->steps[0]['task_id']);
        $this->assertSame(\App\Filament\Resources\TaskResource\Enums\TaskState::Suspension->value, Task::find($firstTaskId)->detail->state);
    }

    public function test_reject_back_after_the_prior_task_is_genuinely_done_creates_a_fresh_occurrence(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $driver = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$driver->id]]);
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$driver->id], 'populate_task' => true, 'task_title' => 'وظیفه اول']),
            $this->workflowStep(['assignee_user_ids' => [$driver->id], 'type' => 'approval', 'reject_to_step' => 0]),
        ]]);
        $workflow = app(StartWorkflowAction::class)->execute($draft);
        $firstTaskId = $workflow->fresh()->steps[0]['task_id'];

        $this->actingAs($driver);
        Task::find($firstTaskId)->update(['status' => 'done']);
        $this->assertSame(1, $workflow->fresh()->current_step, 'a genuinely done populated-step task must auto-advance the cycle');
        $countBefore = Task::count();

        app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $driver->id, 1, false);

        $this->assertSame($countBefore + 1, Task::count(), 'a fresh task must be created since the prior occurrence is already genuinely done');
        $this->assertNotSame($firstTaskId, $workflow->fresh()->steps[0]['task_id']);
    }

    public function test_reject_back_to_a_populated_step_whose_task_moved_projects_creates_a_fresh_task_and_leaves_the_stale_one_untouched(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $driver = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$driver->id]]);
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$driver->id], 'populate_task' => true, 'task_title' => 'وظیفه اول']),
            $this->workflowStep(['assignee_user_ids' => [$driver->id], 'type' => 'approval', 'reject_to_step' => 0]),
        ]]);
        $workflow = app(StartWorkflowAction::class)->execute($draft);
        $firstTaskId = $workflow->fresh()->steps[0]['task_id'];
        app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $owner->id, 0, true, true);
        $this->assertSame(1, $workflow->fresh()->current_step);

        $otherProject = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        Task::find($firstTaskId)->update(['project_id' => $otherProject->id]);

        app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $driver->id, 1, false);

        $freshTaskId = $workflow->fresh()->steps[0]['task_id'];
        $this->assertNotSame($firstTaskId, $freshTaskId);
        $this->assertSame($otherProject->id, Task::find($firstTaskId)->project_id, 'the stale foreign task must be left untouched');
    }

    public function test_a_soft_deleted_linked_task_blocks_manual_advance_and_is_recoverable_only_via_owner_force(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $driver = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$driver->id]]);
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$driver->id], 'populate_task' => true, 'task_title' => 'وظیفه']),
            $this->workflowStep(['assignee_user_ids' => [$driver->id]]),
        ]]);
        $workflow = app(StartWorkflowAction::class)->execute($draft);
        $taskId = $workflow->fresh()->steps[0]['task_id'];
        Task::find($taskId)->delete();

        $blocked = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $driver->id, 0);
        $this->assertFalse($blocked);
        $this->assertSame(0, $workflow->fresh()->current_step);

        $forced = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $owner->id, 0, true, true);
        $this->assertTrue($forced);
        $this->assertSame(1, $workflow->fresh()->current_step);
    }

    public function test_a_force_deleted_linked_task_also_blocks_manual_advance_until_owner_force(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $driver = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$driver->id]]);
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$driver->id], 'populate_task' => true, 'task_title' => 'وظیفه']),
            $this->workflowStep(['assignee_user_ids' => [$driver->id]]),
        ]]);
        $workflow = app(StartWorkflowAction::class)->execute($draft);
        $taskId = $workflow->fresh()->steps[0]['task_id'];
        Task::find($taskId)->forceDelete();

        $blocked = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $driver->id, 0);
        $this->assertFalse($blocked);

        $forced = app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $owner->id, 0, true, true);
        $this->assertTrue($forced);
    }

    private function cycleWithTask(User $owner, Project $project, User $assignee): array
    {
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$assignee->id], 'populate_task' => true, 'task_title' => 'خروجی']),
            $this->workflowStep(['assignee_user_ids' => [$assignee->id]]),
        ]]);
        $workflow = app(StartWorkflowAction::class)->execute($draft);
        $task = Task::find($workflow->fresh()->steps[0]['task_id']);

        return [$workflow, $task];
    }

    public function test_only_the_owner_can_post_in_the_activity_box_while_members_can_read(): void
    {
        $member = User::factory()->create(['status' => 'active', 'role' => 'user']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);

        Livewire::actingAs($member)->test(Activity::class, ['activeProjectId' => $project->id])
            ->set('activityComposer.body', 'sneaky')
            ->call('postComment')
            ->assertForbidden();

        $this->assertSame(0, Reply::where('repliable_type', Project::class)->where('repliable_id', $project->id)->where('body', 'sneaky')->count());

        Livewire::actingAs($owner)->test(Activity::class, ['activeProjectId' => $project->id])
            ->set('activityComposer.body', 'official')
            ->call('postComment')
            ->assertSee('official');
    }

    public function test_cancelling_a_cycle_stops_its_open_task_and_clears_the_step_link(): void
    {
        $assignee = User::factory()->create(['status' => 'active', 'role' => 'user']);
        [$owner, $project] = $this->ownerWithProject([$assignee->id]);
        [$workflow, $task] = $this->cycleWithTask($owner, $project, $assignee);
        $this->assertNotNull($task);

        app(CancelWorkflowAction::class)->execute($workflow);

        $this->assertSame(TaskState::Suspension->value, TaskDetail::where('task_id', $task->id)->value('state'));
        $this->assertNull($workflow->fresh()->steps[0]['task_id']);
        $this->assertSame(Workflow::STATUS_CANCELLED, $workflow->fresh()->status);
    }

    public function test_force_advancing_a_populated_step_stops_its_unfinished_task(): void
    {
        $assignee = User::factory()->create(['status' => 'active', 'role' => 'user']);
        [$owner, $project] = $this->ownerWithProject([$assignee->id]);
        [$workflow, $task] = $this->cycleWithTask($owner, $project, $assignee);

        app(AdvanceWorkflowStepAction::class)->execute($workflow->id, $owner->id, 0, true, true);

        $this->assertSame(TaskState::Suspension->value, TaskDetail::where('task_id', $task->id)->value('state'));
        $this->assertSame(1, $workflow->fresh()->current_step);
        $this->assertNull($workflow->fresh()->steps[0]['task_id']);
    }

    public function test_a_started_cycle_cannot_be_started_twice(): void
    {
        $assignee = User::factory()->create(['status' => 'active', 'role' => 'user']);
        [$owner, $project] = $this->ownerWithProject([$assignee->id]);
        $this->actingAs($owner);
        $draft = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [$this->workflowStep(['assignee_user_ids' => [$assignee->id]])]]);
        $started = app(StartWorkflowAction::class)->execute($draft);
        $startedAt = $started->fresh()->started_at;

        $this->assertHttpAbort(422, fn() => app(StartWorkflowAction::class)->execute($draft));
        $this->assertEquals($startedAt, $draft->fresh()->started_at);
    }

    public function test_removing_a_step_in_the_editor_remaps_or_clears_reject_targets(): void
    {
        $assignee = User::factory()->create(['status' => 'active', 'role' => 'user']);
        [$owner, $project] = $this->ownerWithProject([$assignee->id]);
        $steps = [
            $this->workflowStep(['label' => 'a', 'assignee_user_ids' => [$assignee->id]]),
            $this->workflowStep(['label' => 'b', 'assignee_user_ids' => [$assignee->id]]),
            $this->workflowStep(['label' => 'c', 'assignee_user_ids' => [$assignee->id], 'type' => 'approval', 'reject_to_step' => 1]),
        ];

        $component = Livewire::actingAs($owner)->test(WorkflowTab::class, ['activeProjectId' => $project->id])
            ->set('steps', $steps)
            ->call('removeStep', 0);
        $this->assertSame(0, $component->get('steps')[1]['reject_to_step']);

        $component->call('removeStep', 0);
        $this->assertNull($component->get('steps')[0]['reject_to_step']);
        $this->assertCount(1, $component->get('steps'));
    }

    public function test_deleting_or_moving_a_cycle_task_detaches_it_from_its_step(): void
    {
        $assignee = User::factory()->create(['status' => 'active', 'role' => 'user']);
        [$owner, $project] = $this->ownerWithProject([$assignee->id]);
        $other = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$assignee->id], 'departments' => []]);
        [$workflow, $task] = $this->cycleWithTask($owner, $project, $assignee);

        Task::find($task->id)->update(['project_id' => $other->id]);

        $this->assertNull($workflow->fresh()->steps[0]['task_id']);
        $this->assertSame('task_detached', collect($workflow->fresh()->step_log)->last()['event']);

        $second = Workflow::factory()->create(['owner_id' => $owner->id, 'project_id' => $project->id, 'steps' => [
            $this->workflowStep(['assignee_user_ids' => [$assignee->id], 'populate_task' => true, 'task_title' => 'دوم']),
        ]]);
        $running = app(StartWorkflowAction::class)->execute($second);
        $taskId = $running->fresh()->steps[0]['task_id'];

        Task::find($taskId)->delete();

        $this->assertNull($running->fresh()->steps[0]['task_id']);
    }

    public function test_create_project_keeps_only_active_non_guest_members(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $active = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $inactive = User::factory()->create(['status' => 'inactive', 'role' => 'user']);
        $guest = User::factory()->create(['status' => 'active', 'role' => 'user', 'type' => 'guest']);
        $this->actingAs($owner);

        $project = app(CreateProjectAction::class)->create('Roster Check', [$active->id, $inactive->id, $guest->id]);

        $this->assertSame([$active->id], $project->fresh()->member_ids);
    }

    public function test_team_chat_flags_inactive_senders_and_drops_them_from_mention_candidates(): void
    {
        $leaver = User::factory()->create(['status' => 'active', 'role' => 'user', 'name' => 'Departed Member']);
        [$owner, $project] = $this->ownerWithProject([$leaver->id]);
        $channel = app(ChannelProvisioner::class)->resolve($project->fresh());
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $leaver->id, 'body' => 'farewell']);
        $leaver->forceFill(['status' => 'inactive'])->saveQuietly();

        $component = Livewire::actingAs($owner)->test(TeamChat::class, ['activeProjectId' => $project->id, 'initialActive' => true]);
        $rows = collect($component->instance()->teamChatMessages()['rows']);

        $this->assertTrue($rows->firstWhere('body', 'farewell')['sender']['inactive']);
        $this->assertArrayNotHasKey('Departed Member', $component->instance()->mentionMemberPresence());
    }

    public function test_team_chat_hides_the_conversation_from_non_members_and_blocks_inactive_posters(): void
    {
        [$owner, $project] = $this->ownerWithProject();
        $channel = app(ChannelProvisioner::class)->resolve($project->fresh());
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'internal talk']);
        $outsider = User::factory()->create(['status' => 'active', 'role' => 'user']);

        $rows = Livewire::actingAs($outsider)->test(TeamChat::class, ['activeProjectId' => $project->id])->instance()->teamChatMessages()['rows'];
        $this->assertSame([], $rows);

        $owner->forceFill(['status' => 'inactive'])->saveQuietly();
        Livewire::actingAs($owner->fresh())->test(TeamChat::class, ['activeProjectId' => $project->id])
            ->set('chatComposer.body', 'ghost post')
            ->call('sendChatMessage')
            ->assertForbidden();
        $this->assertSame(0, ChannelMessage::where('channel_id', $channel->id)->where('body', 'ghost post')->count());
    }

    public function test_opening_team_chat_clears_the_members_unread_count(): void
    {
        $member = User::factory()->create(['status' => 'active', 'role' => 'user']);
        [$owner, $project] = $this->ownerWithProject([$member->id]);
        $channel = app(ChannelProvisioner::class)->resolve($project->fresh());
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'one']);
        $last = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'two']);
        $pivot = fn() => \App\Models\ChannelMember::where(['channel_id' => $channel->id, 'user_id' => $member->id])->first();
        $this->assertNull($pivot()->last_read_message_id);

        Livewire::actingAs($member)->test(TeamChat::class, ['activeProjectId' => $project->id])->call('activate');

        $this->assertSame($last->id, (int) $pivot()->last_read_message_id);
        $this->assertNotNull($pivot()->entered_at);
    }
}
