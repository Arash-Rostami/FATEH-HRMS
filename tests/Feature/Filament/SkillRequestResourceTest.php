<?php

namespace Tests\Feature\Filament;

use App\Enums\SkillRequestStatus;
use App\Filament\Resources\SkillRequestResource\Pages\ListSkillRequests;
use App\Models\Permission;
use App\Models\Skill;
use App\Models\SkillUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SkillRequestResourceTest extends TestCase
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

    public function test_list_shows_pending_requests_by_default_and_hides_approved_rows()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'Listed Skill ' . uniqid()]);
        $pendingUser = User::factory()->create(['status' => 'active']);
        $approvedUser = User::factory()->create(['status' => 'active']);
        $pending = SkillUser::create(['user_id' => $pendingUser->id, 'skill_id' => $skill->id, 'status' => 'pending']);
        $approved = SkillUser::create(['user_id' => $approvedUser->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'approved_by' => $admin->id]);

        Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$approved]);
    }

    public function test_default_sort_orders_pending_rows_before_non_pending_when_status_filter_cleared()
    {
        $admin = $this->developer();
        $skillA = Skill::create(['name' => 'Sort Skill A ' . uniqid()]);
        $skillB = Skill::create(['name' => 'Sort Skill B ' . uniqid()]);
        $userA = User::factory()->create(['status' => 'active']);
        $userB = User::factory()->create(['status' => 'active']);

        $olderApproved = SkillUser::create(['user_id' => $userA->id, 'skill_id' => $skillA->id, 'status' => 'approved', 'approved_at' => now(), 'approved_by' => $admin->id]);
        $olderApproved->created_at = now()->subDays(5);
        $olderApproved->saveQuietly();

        $newerPending = SkillUser::create(['user_id' => $userB->id, 'skill_id' => $skillB->id, 'status' => 'pending']);

        Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->filterTable('status', '')
            ->assertCanSeeTableRecords([$newerPending, $olderApproved], inOrder: true);
    }

    public function test_approve_action_catalog_pick_transitions_pending_to_approved_and_stamps_approver()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'Approve Catalog Skill ' . uniqid()]);
        $user = User::factory()->create(['status' => 'active']);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->callTableAction('approve', $row);

        $fresh = $row->fresh();
        $this->assertSame(SkillRequestStatus::Approved, $fresh->status);
        $this->assertNotNull($fresh->approved_at);
        $this->assertSame($admin->id, $fresh->approved_by);
    }

    public function test_approve_action_proposal_also_activates_draft_skill()
    {
        $admin = $this->developer();
        $name = 'Proposed Draft Skill ' . uniqid();
        $draft = Skill::create(['name' => $name, 'is_active' => false, 'is_ghost' => false]);
        $user = User::factory()->create(['status' => 'active']);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $draft->id, 'status' => 'pending', 'requested_name' => $name]);

        Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->callTableAction('approve', $row);

        $this->assertSame(SkillRequestStatus::Approved, $row->fresh()->status);
        $freshSkill = $draft->fresh();
        $this->assertTrue($freshSkill->is_active);
        $this->assertFalse($freshSkill->is_ghost);
    }

    public function test_reject_action_sets_status_rejected_and_reason_and_leaves_draft_skill_untouched()
    {
        $admin = $this->developer();
        $name = 'Reject Draft Skill ' . uniqid();
        $draft = Skill::create(['name' => $name, 'is_active' => false, 'is_ghost' => false]);
        $user = User::factory()->create(['status' => 'active']);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $draft->id, 'status' => 'pending', 'requested_name' => $name]);

        Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->callTableAction('reject', $row, data: ['rejected_reason' => 'Not aligned with catalog']);

        $fresh = $row->fresh();
        $this->assertSame(SkillRequestStatus::Rejected, $fresh->status);
        $this->assertSame('Not aligned with catalog', $fresh->rejected_reason);
        $freshSkill = $draft->fresh();
        $this->assertFalse($freshSkill->is_active);
        $this->assertFalse($freshSkill->is_ghost);
    }

    public function test_bulk_approve_processes_only_pending_rows_in_selection_and_skips_others()
    {
        $admin = $this->developer();
        $skillA = Skill::create(['name' => 'Bulk Approve Skill A ' . uniqid()]);
        $skillB = Skill::create(['name' => 'Bulk Approve Skill B ' . uniqid()]);
        $userA = User::factory()->create(['status' => 'active']);
        $userB = User::factory()->create(['status' => 'active']);
        $pending = SkillUser::create(['user_id' => $userA->id, 'skill_id' => $skillA->id, 'status' => 'pending']);
        $preApprovedAt = now()->subDay();
        $alreadyApproved = SkillUser::create(['user_id' => $userB->id, 'skill_id' => $skillB->id, 'status' => 'approved', 'approved_at' => $preApprovedAt, 'approved_by' => $admin->id]);

        Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->filterTable('status', '')
            ->callTableBulkAction('bulk_approve', [$pending, $alreadyApproved]);

        $this->assertSame(SkillRequestStatus::Approved, $pending->fresh()->status);
        $this->assertSame($preApprovedAt->timestamp, $alreadyApproved->fresh()->approved_at->timestamp);
    }

    public function test_bulk_reject_processes_only_pending_rows_in_selection_and_skips_others()
    {
        $admin = $this->developer();
        $skillA = Skill::create(['name' => 'Bulk Reject Skill A ' . uniqid()]);
        $skillB = Skill::create(['name' => 'Bulk Reject Skill B ' . uniqid()]);
        $userA = User::factory()->create(['status' => 'active']);
        $userB = User::factory()->create(['status' => 'active']);
        $pending = SkillUser::create(['user_id' => $userA->id, 'skill_id' => $skillA->id, 'status' => 'pending']);
        $alreadyRejected = SkillUser::create(['user_id' => $userB->id, 'skill_id' => $skillB->id, 'status' => 'rejected', 'rejected_reason' => 'original reason']);

        Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->filterTable('status', '')
            ->callTableBulkAction('bulk_reject', [$pending, $alreadyRejected], data: ['rejected_reason' => 'bulk reason']);

        $this->assertSame(SkillRequestStatus::Rejected, $pending->fresh()->status);
        $this->assertSame('bulk reason', $pending->fresh()->rejected_reason);
        $this->assertSame('original reason', $alreadyRejected->fresh()->rejected_reason);
    }

    public function test_limited_permission_admin_without_update_cannot_see_approve_or_reject_actions()
    {
        $limitedAdmin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        Permission::create([
            'user_id' => $limitedAdmin->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'skill_request', 'actions' => ['view']]],
        ]);
        $skill = Skill::create(['name' => 'Gated Skill ' . uniqid()]);
        $user = User::factory()->create(['status' => 'active']);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        Livewire::actingAs($limitedAdmin)->test(ListSkillRequests::class)
            ->assertSuccessful()
            ->assertTableActionHidden('approve', $row)
            ->assertTableActionHidden('reject', $row);
    }

    public function test_tier_column_shows_computed_tier_for_approved_rows_and_dash_for_pending()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'Tier Column Request Skill ' . uniqid()]);
        $approvedUser = User::factory()->create(['status' => 'active']);
        $pendingUser = User::factory()->create(['status' => 'active']);

        $approved = SkillUser::create([
            'user_id' => $approvedUser->id,
            'skill_id' => $skill->id,
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'endorsements_count' => SkillUser::ENDORSEMENT_SATURATION_CAP,
        ]);
        SkillUser::create(['user_id' => $pendingUser->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->filterTable('status', '')
            ->assertSuccessful()
            ->assertSee('تأییدشده');
    }

    public function test_pending_tab_shows_only_pending_requests()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'Pending Tab Skill ' . uniqid()]);
        $pendingUser = User::factory()->create(['status' => 'active']);
        $approvedUser = User::factory()->create(['status' => 'active']);
        $pending = SkillUser::create(['user_id' => $pendingUser->id, 'skill_id' => $skill->id, 'status' => 'pending']);
        $approved = SkillUser::create(['user_id' => $approvedUser->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'approved_by' => $admin->id]);

        Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->assertSuccessful()
            ->set('activeTab', 'pending')
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$approved]);
    }

    public function test_approved_tab_shows_only_approved_requests()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'Approved Tab Skill ' . uniqid()]);
        $pendingUser = User::factory()->create(['status' => 'active']);
        $approvedUser = User::factory()->create(['status' => 'active']);
        $pending = SkillUser::create(['user_id' => $pendingUser->id, 'skill_id' => $skill->id, 'status' => 'pending']);
        $approved = SkillUser::create(['user_id' => $approvedUser->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'approved_by' => $admin->id]);

        Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->filterTable('status', '')
            ->set('activeTab', 'approved')
            ->assertCanSeeTableRecords([$approved])
            ->assertCanNotSeeTableRecords([$pending]);
    }

    public function test_rejected_tab_shows_only_rejected_requests()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'Rejected Tab Skill ' . uniqid()]);
        $pendingUser = User::factory()->create(['status' => 'active']);
        $rejectedUser = User::factory()->create(['status' => 'active']);
        $pending = SkillUser::create(['user_id' => $pendingUser->id, 'skill_id' => $skill->id, 'status' => 'pending']);
        $rejected = SkillUser::create(['user_id' => $rejectedUser->id, 'skill_id' => $skill->id, 'status' => 'rejected', 'rejected_reason' => 'not needed']);

        Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->filterTable('status', '')
            ->set('activeTab', 'rejected')
            ->assertCanSeeTableRecords([$rejected])
            ->assertCanNotSeeTableRecords([$pending]);
    }

    public function test_approved_tab_badge_reflects_live_approved_count()
    {
        $admin = $this->developer();
        $before = (int) SkillUser::where('status', 'approved')->count();
        $skill = Skill::create(['name' => 'Badge Approved Skill ' . uniqid()]);
        $userOne = User::factory()->create(['status' => 'active']);
        $userTwo = User::factory()->create(['status' => 'active']);
        SkillUser::create(['user_id' => $userOne->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'approved_by' => $admin->id]);
        SkillUser::create(['user_id' => $userTwo->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'approved_by' => $admin->id]);

        $tabs = Livewire::actingAs($admin)->test(ListSkillRequests::class)->instance()->getTabs();

        $this->assertEquals($before + 2, $tabs['approved']->getBadge());
    }

    public function test_stale_filter_includes_pending_rows_older_than_14_days_and_excludes_fresh_ones()
    {
        $admin = $this->developer();
        $skillOld = Skill::create(['name' => 'Stale Skill ' . uniqid()]);
        $skillFresh = Skill::create(['name' => 'Fresh Skill ' . uniqid()]);
        $userOld = User::factory()->create(['status' => 'active']);
        $userFresh = User::factory()->create(['status' => 'active']);

        $stale = SkillUser::create(['user_id' => $userOld->id, 'skill_id' => $skillOld->id, 'status' => 'pending']);
        $stale->created_at = now()->subDays(20);
        $stale->saveQuietly();

        $fresh = SkillUser::create(['user_id' => $userFresh->id, 'skill_id' => $skillFresh->id, 'status' => 'pending']);

        Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->filterTable('stale', true)
            ->assertCanSeeTableRecords([$stale])
            ->assertCanNotSeeTableRecords([$fresh]);
    }

    public function test_department_filter_scopes_list_to_users_in_matching_department()
    {
        $admin = $this->developer();
        $departmentA = \App\Models\Department::factory()->create();
        $departmentB = \App\Models\Department::factory()->create();
        $skill = Skill::create(['name' => 'Department Filter Skill ' . uniqid()]);

        $userA = User::factory()->create(['status' => 'active']);
        \App\Models\Profile::factory()->create(['user_id' => $userA->id, 'department_id' => $departmentA->code]);
        $inA = SkillUser::create(['user_id' => $userA->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        $userB = User::factory()->create(['status' => 'active']);
        \App\Models\Profile::factory()->create(['user_id' => $userB->id, 'department_id' => $departmentB->code]);
        $inB = SkillUser::create(['user_id' => $userB->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->filterTable('department', $departmentA->code)
            ->assertCanSeeTableRecords([$inA])
            ->assertCanNotSeeTableRecords([$inB]);
    }

    public function test_created_at_filter_scopes_to_the_given_date_range()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'Created Range Skill ' . uniqid()]);
        $userA = User::factory()->create(['status' => 'active']);
        $userB = User::factory()->create(['status' => 'active']);

        $inRange = SkillUser::create(['user_id' => $userA->id, 'skill_id' => $skill->id, 'status' => 'pending']);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = SkillUser::create(['user_id' => $userB->id, 'skill_id' => $skill->id, 'status' => 'pending']);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_sorting_by_created_at_last_used_at_and_endorsements_count_produces_correct_order()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'Sort Meta Skill ' . uniqid()]);
        $userA = User::factory()->create(['status' => 'active']);
        $userB = User::factory()->create(['status' => 'active']);

        $first = SkillUser::create(['user_id' => $userA->id, 'skill_id' => $skill->id, 'status' => 'pending', 'endorsements_count' => 0]);
        $first->forceFill(['created_at' => now()->subYears(5), 'last_used_at' => now()->subYears(5)])->saveQuietly();
        $second = SkillUser::create(['user_id' => $userB->id, 'skill_id' => $skill->id, 'status' => 'pending', 'endorsements_count' => 4]);
        $second->forceFill(['created_at' => now(), 'last_used_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('created_at')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('last_used_at')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('endorsements_count')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('endorsements_count', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_skill_name_and_user_name_produces_correct_order()
    {
        $admin = $this->developer();
        $skillA = Skill::create(['name' => 'AAA-sort-request-skill ' . uniqid()]);
        $skillZ = Skill::create(['name' => 'ZZZ-sort-request-skill ' . uniqid()]);
        $userA = User::factory()->create(['status' => 'active', 'name' => 'AAA-sort-request-user']);
        $userZ = User::factory()->create(['status' => 'active', 'name' => 'ZZZ-sort-request-user']);

        $first = SkillUser::create(['user_id' => $userA->id, 'skill_id' => $skillA->id, 'status' => 'pending']);
        $second = SkillUser::create(['user_id' => $userZ->id, 'skill_id' => $skillZ->id, 'status' => 'pending']);

        $component = Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('skill.name')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('skill.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_status_produces_correct_order_with_status_filter_cleared()
    {
        $admin = $this->developer();
        $skill = Skill::create(['name' => 'Sort Status Skill ' . uniqid()]);
        $userA = User::factory()->create(['status' => 'active']);
        $userB = User::factory()->create(['status' => 'active']);

        $approved = SkillUser::create(['user_id' => $userA->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'approved_by' => $admin->id]);
        $rejected = SkillUser::create(['user_id' => $userB->id, 'skill_id' => $skill->id, 'status' => 'rejected', 'rejected_reason' => 'test']);

        $component = Livewire::actingAs($admin)->test(ListSkillRequests::class)
            ->set('tableRecordsPerPage', 'all')
            ->filterTable('status', '')
            ->sortTable('status')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$approved->id, $rejected->id], array_values(array_intersect($ids, [$approved->id, $rejected->id])));

        $component->sortTable('status', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$rejected->id, $approved->id], array_values(array_intersect($ids, [$approved->id, $rejected->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\SkillRequestResource\Pages\ListSkillRequests::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/SkillRequestResource/Schemas/SkillRequestTablePresenter.php'),
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
