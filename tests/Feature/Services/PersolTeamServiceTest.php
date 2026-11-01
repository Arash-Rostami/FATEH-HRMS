<?php

namespace Tests\Feature\Services;

use App\Models\Department;
use App\Models\Profile;
use App\Models\User;
use App\Services\PersolTeamService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PersolTeamServiceTest extends TestCase
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
            foreach (explode("\n", (string)file_get_contents($env)) as $line) {
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

    private function dept(string $code): Department
    {
        return Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => $code]);
    }

    private function viewer(string $deptCode, array $profileOverrides = [], array $userOverrides = []): User
    {
        $this->dept($deptCode);
        $user = User::factory()->create(array_merge([
            'status' => 'inactive',
            'type' => 'employee',
            'role' => 'user',
        ], $userOverrides));

        Profile::factory()->create(array_merge([
            'user_id' => $user->id,
            'department_id' => $deptCode,
            'position' => 'employee',
            'gender' => 'female',
        ], $profileOverrides));

        return $user->fresh('profile');
    }

    private function target(string $deptCode, string $position = 'employee', array $userOverrides = [], array $profileOverrides = []): User
    {
        $this->dept($deptCode);
        $user = User::factory()->create(array_merge([
            'status' => 'active',
            'type' => 'employee',
            'role' => 'user',
        ], $userOverrides));

        Profile::factory()->create(array_merge([
            'user_id' => $user->id,
            'department_id' => $deptCode,
            'position' => $position,
            'gender' => 'male',
        ], $profileOverrides));

        return $user;
    }

    private function runRules(User $viewer): array
    {
        $query = User::query();
        app(PersolTeamService::class)->applyRules($query, $viewer);

        return $query->pluck('id')->all();
    }

    public function test_apply_rules_short_circuits_to_no_rows_when_viewer_has_no_profile(): void
    {
        $viewer = User::factory()->create(['status' => 'active']);
        $this->target('MK', 'manager');

        $ids = $this->runRules($viewer);

        $this->assertSame([], $ids);
    }

    public function test_apply_rules_filters_targets_to_active_status_only(): void
    {
        $viewer = $this->viewer('MA', ['gender' => 'female']);
        $active = $this->target('MK', 'manager');
        $inactive = $this->target('MK', 'manager', ['status' => 'inactive']);

        $ids = $this->runRules($viewer);

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }

    public function test_non_persol_tenant_ignores_persol_department_rules_and_applies_generic_default(): void
    {
        $original = config('app.tenant');
        config(['app.tenant' => 'fateh']);

        try {
            $viewer = $this->viewer('MA', ['position' => 'manager']);
            $ownDeptMember = $this->target('MA', 'employee');
            $managerOutsideMa = $this->target('MK', 'manager');

            $ids = $this->runRules($viewer);

            $this->assertContains($ownDeptMember->id, $ids, 'a non-Persol tenant falls back to own-department-only, not applyMARules');
            $this->assertNotContains($managerOutsideMa->id, $ids, 'Persol\'s MA gender/position carve-out must not apply outside the Persol tenant');
        } finally {
            config(['app.tenant' => $original]);
        }
    }

    public function test_non_persol_tenant_top_executive_rule_still_applies(): void
    {
        $original = config('app.tenant');
        config(['app.tenant' => 'fateh']);

        try {
            $viewer = $this->viewer('MA', ['position' => 'chairman']);
            $managerElsewhere = $this->target('MK', 'manager');
            $employeeElsewhere = $this->target('MK', 'employee');

            $ids = $this->runRules($viewer);

            $this->assertContains($managerElsewhere->id, $ids, 'the top-executive company-wide rule is tenant-agnostic');
            $this->assertNotContains($employeeElsewhere->id, $ids);
        } finally {
            config(['app.tenant' => $original]);
        }
    }

    public function test_top_executive_chairman_sees_manager_rank_and_above_company_wide(): void
    {
        $viewer = $this->viewer('MA', ['position' => 'chairman']);
        $managerInOtherDept = $this->target('MK', 'manager');
        $cManagerInOtherDept = $this->target('CP', 'c-manager');
        $ceoElsewhere = $this->target('HR', 'ceo');
        $employeeInOtherDept = $this->target('MK', 'employee');
        $expertInOwnDept = $this->target('MA', 'expert');

        $ids = $this->runRules($viewer);

        $this->assertContains($managerInOtherDept->id, $ids);
        $this->assertContains($cManagerInOtherDept->id, $ids);
        $this->assertContains($ceoElsewhere->id, $ids);
        $this->assertNotContains($employeeInOtherDept->id, $ids);
        $this->assertNotContains($expertInOwnDept->id, $ids);
    }

    public function test_top_executive_ceo_bypasses_department_specific_rules(): void
    {
        $viewer = $this->viewer('MK', ['position' => 'ceo']);
        $managerInMk = $this->target('MK', 'manager');
        $expertInSo = $this->target('SO', 'expert');

        $ids = $this->runRules($viewer);

        $this->assertContains($managerInMk->id, $ids);
        $this->assertNotContains($expertInSo->id, $ids, 'a top executive is scoped to manager+ rank, not applyMKRules\' expert-in-SO/CH carve-out');
    }

    public function test_ma_female_viewer_sees_managers_outside_ma_and_cx_members(): void
    {
        $viewer = $this->viewer('MA', ['gender' => 'female']);
        $managerOutsideMa = $this->target('MK', 'manager');
        $managerInMa = $this->target('MA', 'manager');
        $cxMember = $this->target('CX', 'employee');
        $nonManagerOutsideMa = $this->target('MK', 'employee');

        $ids = $this->runRules($viewer);

        $this->assertContains($managerOutsideMa->id, $ids);
        $this->assertContains($cxMember->id, $ids);
        $this->assertNotContains($managerInMa->id, $ids);
        $this->assertNotContains($nonManagerOutsideMa->id, $ids);
    }

    public function test_ma_male_viewer_sees_managers_outside_cp_wp_ch_ma_and_seniors_in_so(): void
    {
        $viewer = $this->viewer('MA', ['gender' => 'male']);
        $managerInSo = $this->target('SO', 'manager');
        $managerInCp = $this->target('CP', 'manager');
        $seniorInSo = $this->target('SO', 'senior');
        $expertInSo = $this->target('SO', 'expert');

        $ids = $this->runRules($viewer);

        $this->assertContains($managerInSo->id, $ids);
        $this->assertContains($seniorInSo->id, $ids);
        $this->assertNotContains($managerInCp->id, $ids);
        $this->assertNotContains($expertInSo->id, $ids);
    }

    public function test_ma_viewer_with_a_gender_outside_female_male_applies_no_extra_position_filter(): void
    {
        $this->markTestSkipped('profiles.gender is enum("female","male") NOT NULL (2026_09_19_000031) — a third value can never reach applyMARules, so the no-else gender branch is unreachable.');
    }

    public function test_mk_manager_sees_own_dept_and_managers_in_cp_wp_ch_and_experts_in_ch_so(): void
    {
        $viewer = $this->viewer('MK', ['position' => 'manager']);
        $ownDeptMember = $this->target('MK', 'employee');
        $managerInCp = $this->target('CP', 'manager');
        $expertInCh = $this->target('CH', 'expert');
        $expertInSo = $this->target('SO', 'expert');
        $employeeInCp = $this->target('CP', 'employee');

        $ids = $this->runRules($viewer);

        $this->assertContains($ownDeptMember->id, $ids);
        $this->assertContains($managerInCp->id, $ids);
        $this->assertContains($expertInCh->id, $ids);
        $this->assertContains($expertInSo->id, $ids);
        $this->assertNotContains($employeeInCp->id, $ids);
    }

    public function test_mk_non_manager_applies_no_rows(): void
    {
        $viewer = $this->viewer('MK', ['position' => 'tester']);
        $this->target('MK', 'employee');

        $ids = $this->runRules($viewer);

        $this->assertSame([], $ids);
    }

    public function test_hc_manager_sees_hc_hr_and_as_departments(): void
    {
        $viewer = $this->viewer('HC', ['position' => 'manager']);
        $hcMember = $this->target('HC', 'employee');
        $hrMember = $this->target('HR', 'employee');
        $asMember = $this->target('AS', 'employee');
        $cpMember = $this->target('CP', 'employee');

        $ids = $this->runRules($viewer);

        $this->assertContains($hcMember->id, $ids);
        $this->assertContains($hrMember->id, $ids);
        $this->assertContains($asMember->id, $ids);
        $this->assertNotContains($cpMember->id, $ids);
    }

    public function test_hr_manager_sees_hc_hr_and_as_departments(): void
    {
        User::whereHas('profile', fn($q) => $q->where('department_id', 'HR')->whereIn('position', ['chairman', 'ceo', 'c-manager']))
            ->update(['status' => 'inactive']);

        $viewer = $this->viewer('HR', ['position' => 'manager']);
        $hrMember = $this->target('HR', 'employee');
        $hcMember = $this->target('HC', 'employee');
        $cpMember = $this->target('CP', 'employee');

        $ids = $this->runRules($viewer);

        $this->assertContains($hrMember->id, $ids);
        $this->assertContains($hcMember->id, $ids);
        $this->assertNotContains($cpMember->id, $ids);
    }

    public function test_hc_non_manager_applies_no_rows(): void
    {
        $viewer = $this->viewer('HC', ['position' => 'tester']);
        $this->target('HC', 'employee');

        $ids = $this->runRules($viewer);

        $this->assertSame([], $ids);
    }

    public function test_cp_viewer_named_rashidbeygi_sees_adami_named_users(): void
    {
        $viewer = $this->viewer('CP', ['position' => 'employee'], ['name' => 'Rashidbeygi Manager']);
        $adamiUser = $this->target('CP', 'employee', ['name' => 'Ali Adami']);
        $nafarUser = $this->target('CP', 'employee', ['name' => 'Reza Nafar']);

        $ids = $this->runRules($viewer);

        $this->assertContains($adamiUser->id, $ids);
        $this->assertNotContains($nafarUser->id, $ids);
    }

    public function test_cp_viewer_named_shirzadeh_sees_nafar_named_users(): void
    {
        $viewer = $this->viewer('CP', ['position' => 'employee'], ['name' => 'Shirzadeh Manager']);
        $nafarUser = $this->target('CP', 'employee', ['name' => 'Reza Nafar']);
        $adamiUser = $this->target('CP', 'employee', ['name' => 'Ali Adami']);

        $ids = $this->runRules($viewer);

        $this->assertContains($nafarUser->id, $ids);
        $this->assertNotContains($adamiUser->id, $ids);
    }

    public function test_cp_viewer_with_unmatched_name_applies_no_rows(): void
    {
        $viewer = $this->viewer('CP', ['position' => 'employee'], ['name' => 'Random Person']);
        $this->target('CP', 'employee', ['name' => 'Ali Adami']);

        $ids = $this->runRules($viewer);

        $this->assertSame([], $ids);
    }

    public function test_cp_rule_matches_persian_name_variant(): void
    {
        $viewer = $this->viewer('CP', ['position' => 'employee'], ['name' => 'رشیدبیگی کارمند']);
        $persianAdami = $this->target('CP', 'employee', ['name' => 'علی آدمی']);

        $ids = $this->runRules($viewer);

        $this->assertContains($persianAdami->id, $ids);
    }

    public function test_default_department_manager_sees_only_own_dept_members(): void
    {
        $viewer = $this->viewer('IT', ['position' => 'manager']);
        $ownDeptMember = $this->target('IT', 'employee');
        $otherDeptMember = $this->target('MK', 'employee');

        $ids = $this->runRules($viewer);

        $this->assertContains($ownDeptMember->id, $ids);
        $this->assertNotContains($otherDeptMember->id, $ids);
    }

    public function test_default_department_non_manager_applies_no_rows(): void
    {
        $viewer = $this->viewer('IT', ['position' => 'tester']);
        $this->target('IT', 'employee');

        $ids = $this->runRules($viewer);

        $this->assertSame([], $ids);
    }
}