<?php

namespace Tests\Feature\Support;

use App\Models\Department;
use App\Models\Profile;
use App\Models\Review;
use App\Models\Suggestion;
use App\Models\User;
use App\Support\SuggestionAccessPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuggestionAccessPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        Auth::logout();
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

    private function deptUser(string $dept, string $position): User
    {
        Department::firstOrCreate(['code' => $dept], ['name' => $dept, 'description' => $dept]);
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'department_id' => $dept,
            'position' => $position,
        ]);

        return $user;
    }

    private function nonHeadDeptUser(string $dept): User
    {
        Department::firstOrCreate(['code' => $dept], ['name' => $dept, 'description' => $dept]);
        $this->deptUser($dept, 'manager');
        return $this->deptUser($dept, 'expert');
    }

    private function uniqueDept(string $code): Department
    {
        return Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => $code]);
    }

    private function makeSuggestion(array $overrides = []): Suggestion
    {
        $author = $overrides['user_id'] ?? User::factory()->create(['status' => 'active'])->id;

        return Suggestion::factory()->create(array_merge([
            'title' => 'Probe ' . uniqid(),
            'description' => 'Baseline description for the probe suggestion.',
            'departments' => [],
            'purpose' => ['cost'],
            'rule' => ['simplify'],
            'stage' => 'pending',
            'self_fill' => false,
            'abort' => false,
            'user_id' => $author,
        ], $overrides));
    }

    private function makeReview(Suggestion $suggestion, array $overrides = []): Review
    {
        return Review::factory()->create(array_merge([
            'suggestion_id' => $suggestion->id,
            'department_id' => 'TST',
            'feedback' => 'unknown',
            'complete' => false,
            'referral' => [],
            'user_id' => $suggestion->user_id,
        ], $overrides));
    }

    private function reload(Suggestion $suggestion): Suggestion
    {
        return Suggestion::with('reviews')->findOrFail($suggestion->id);
    }

    public function test_can_decide_returns_true_for_senior_decision_maker_at_awaiting_decision(): void
    {
        $ceo = $this->deptUser('MA', 'ceo');
        $suggestion = $this->makeSuggestion(['stage' => 'awaiting_decision', 'departments' => ['TST']]);
        $this->actingAs($ceo);

        $this->assertTrue(SuggestionAccessPolicy::canDecide($suggestion));
    }

    public function test_can_decide_returns_false_for_non_senior_user(): void
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'awaiting_decision', 'departments' => ['TST']]);
        $this->actingAs($user);

        $this->assertFalse(SuggestionAccessPolicy::canDecide($suggestion));
    }

    public function test_can_decide_returns_false_when_stage_is_not_awaiting_decision(): void
    {
        $ceo = $this->deptUser('MA', 'ceo');
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => ['TST']]);
        $this->actingAs($ceo);

        $this->assertFalse(SuggestionAccessPolicy::canDecide($suggestion));
    }

    public function test_can_decide_returns_false_for_null_suggestion(): void
    {
        $ceo = $this->deptUser('MA', 'ceo');
        $this->actingAs($ceo);

        $this->assertFalse(SuggestionAccessPolicy::canDecide(null));
    }

    public function test_can_give_feedback_true_for_dept_head_at_team_remarks_when_dept_is_home(): void
    {
        $this->uniqueDept('TST');
        $this->uniqueDept('OTHER');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'team_remarks', 'departments' => ['TST', 'OTHER']]);
        $this->actingAs($user);

        $this->assertTrue(SuggestionAccessPolicy::canGiveFeedback($suggestion));
    }

    public function test_can_give_feedback_false_at_team_remarks_when_dept_is_not_home(): void
    {
        $this->uniqueDept('TST');
        $this->uniqueDept('OTHER');
        $user = $this->deptUser('OTHER', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'team_remarks', 'departments' => ['TST', 'OTHER']]);
        $this->actingAs($user);

        $this->assertFalse(SuggestionAccessPolicy::canGiveFeedback($suggestion));
    }

    public function test_can_give_feedback_true_for_dept_head_at_dept_remarks_when_dept_is_listed(): void
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => ['TST']]);
        $this->actingAs($user);

        $this->assertTrue(SuggestionAccessPolicy::canGiveFeedback($suggestion));
    }

    public function test_can_give_feedback_false_when_dept_is_not_listed(): void
    {
        $this->uniqueDept('TST');
        $this->uniqueDept('OTHER');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => ['OTHER']]);
        $this->actingAs($user);

        $this->assertFalse(SuggestionAccessPolicy::canGiveFeedback($suggestion));
    }

    public function test_can_give_feedback_false_for_top_executive(): void
    {
        $ceo = $this->deptUser('MA', 'ceo');
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => ['MA']]);
        $this->actingAs($ceo);

        $this->assertFalse(SuggestionAccessPolicy::canGiveFeedback($suggestion));
    }

    public function test_can_give_feedback_false_for_non_dept_head(): void
    {
        $code = 'N' . Str::upper(Str::random(3));
        $user = $this->nonHeadDeptUser($code);
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => [$code]]);
        $this->actingAs($user);

        $this->assertFalse(SuggestionAccessPolicy::canGiveFeedback($suggestion));
    }

    public function test_can_give_feedback_false_when_stage_is_not_remarks(): void
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'pending', 'departments' => ['TST']]);
        $this->actingAs($user);

        $this->assertFalse(SuggestionAccessPolicy::canGiveFeedback($suggestion));
    }

    public function test_can_give_feedback_false_when_feedback_already_set(): void
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => ['TST']]);
        $this->makeReview($suggestion, ['department_id' => 'TST', 'feedback' => 'agree']);
        $this->actingAs($user);

        $this->assertFalse(SuggestionAccessPolicy::canGiveFeedback($this->reload($suggestion)));
    }

    public function test_can_give_feedback_true_when_feedback_is_unknown(): void
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => ['TST']]);
        $this->makeReview($suggestion, ['department_id' => 'TST', 'feedback' => 'unknown']);
        $this->actingAs($user);

        $this->assertTrue(SuggestionAccessPolicy::canGiveFeedback($this->reload($suggestion)));
    }

    public function test_can_give_feedback_true_when_feedback_is_null_or_empty_string(): void
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => ['TST']]);
        $this->actingAs($user);

        $emptyReview = new Review(['department_id' => 'TST', 'feedback' => '']);
        $suggestion->setRelation('reviews', collect([$emptyReview]));
        $this->assertTrue(SuggestionAccessPolicy::canGiveFeedback($suggestion));

        $nullReview = new Review(['department_id' => 'TST', 'feedback' => null]);
        $suggestion->setRelation('reviews', collect([$nullReview]));
        $this->assertTrue(SuggestionAccessPolicy::canGiveFeedback($suggestion));
    }

    public function test_can_give_feedback_false_for_null_suggestion(): void
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $this->actingAs($user);

        $this->assertFalse(SuggestionAccessPolicy::canGiveFeedback(null));
    }

    public function test_can_mark_complete_true_for_referred_dept_with_incomplete_review(): void
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'accepted', 'departments' => ['TST']]);
        $this->makeReview($suggestion, ['department_id' => 'MA', 'feedback' => 'agree', 'referral' => ['TST'], 'complete' => false]);
        $this->makeReview($suggestion, ['department_id' => 'TST', 'feedback' => 'agree', 'complete' => false]);
        $this->actingAs($user);

        $this->assertTrue(SuggestionAccessPolicy::canMarkComplete($this->reload($suggestion)));
    }

    public function test_can_mark_complete_false_when_dept_not_in_referral(): void
    {
        $this->uniqueDept('TST');
        $this->uniqueDept('OTHER');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'accepted', 'departments' => ['TST']]);
        $this->makeReview($suggestion, ['department_id' => 'MA', 'feedback' => 'agree', 'referral' => ['OTHER'], 'complete' => false]);
        $this->makeReview($suggestion, ['department_id' => 'TST', 'feedback' => 'agree', 'complete' => false]);
        $this->actingAs($user);

        $this->assertFalse(SuggestionAccessPolicy::canMarkComplete($this->reload($suggestion)));
    }

    public function test_can_mark_complete_false_when_review_missing(): void
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'accepted', 'departments' => ['TST']]);
        $this->makeReview($suggestion, ['department_id' => 'MA', 'feedback' => 'agree', 'referral' => ['TST'], 'complete' => false]);
        $this->actingAs($user);

        $this->assertFalse(SuggestionAccessPolicy::canMarkComplete($this->reload($suggestion)));
    }

    public function test_can_mark_complete_false_when_review_already_complete(): void
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'accepted', 'departments' => ['TST']]);
        $this->makeReview($suggestion, ['department_id' => 'MA', 'feedback' => 'agree', 'referral' => ['TST'], 'complete' => false]);
        $this->makeReview($suggestion, ['department_id' => 'TST', 'feedback' => 'agree', 'complete' => true]);
        $this->actingAs($user);

        $this->assertFalse(SuggestionAccessPolicy::canMarkComplete($this->reload($suggestion)));
    }

    public function test_ceo_review_returns_ma_department_review(): void
    {
        $suggestion = $this->makeSuggestion(['stage' => 'accepted', 'departments' => ['TST']]);
        $maReview = $this->makeReview($suggestion, ['department_id' => 'MA', 'feedback' => 'agree', 'referral' => ['TST']]);
        $this->makeReview($suggestion, ['department_id' => 'TST', 'feedback' => 'agree']);

        $this->assertTrue(SuggestionAccessPolicy::ceoReview($this->reload($suggestion))->is($maReview));
    }

    public function test_ceo_review_returns_null_when_no_ma_review_exists(): void
    {
        $suggestion = $this->makeSuggestion(['stage' => 'accepted', 'departments' => ['TST']]);
        $this->makeReview($suggestion, ['department_id' => 'TST', 'feedback' => 'agree']);

        $this->assertNull(SuggestionAccessPolicy::ceoReview($this->reload($suggestion)));
    }

    public function test_ceo_review_returns_null_for_null_suggestion(): void
    {
        $this->assertNull(SuggestionAccessPolicy::ceoReview(null));
    }

    public function test_department_review_returns_review_for_given_dept(): void
    {
        $suggestion = $this->makeSuggestion(['stage' => 'accepted', 'departments' => ['TST']]);
        $tstReview = $this->makeReview($suggestion, ['department_id' => 'TST', 'feedback' => 'agree']);

        $this->assertTrue(SuggestionAccessPolicy::departmentReview($this->reload($suggestion), 'TST')->is($tstReview));
    }

    public function test_department_review_returns_null_when_dept_not_found(): void
    {
        $suggestion = $this->makeSuggestion(['stage' => 'accepted', 'departments' => ['TST']]);
        $this->makeReview($suggestion, ['department_id' => 'TST', 'feedback' => 'agree']);

        $this->assertNull(SuggestionAccessPolicy::departmentReview($this->reload($suggestion), 'OTHER'));
    }

    public function test_department_review_returns_null_for_null_suggestion(): void
    {
        $this->assertNull(SuggestionAccessPolicy::departmentReview(null, 'TST'));
    }

    public function test_build_review_rows_manager_submitter_defaults_home_feedback_to_agree(): void
    {
        $rows = SuggestionAccessPolicy::buildReviewRows(
            suggestionId: 10,
            departments: ['TST', 'OTHER'],
            homeDept: 'TST',
            submitterUserId: 5,
            submitterIsManager: true,
            selfFill: false,
        );

        $this->assertCount(1, $rows);
        $this->assertSame('TST', $rows[0]['department_id']);
        $this->assertSame('agree', $rows[0]['feedback']);
        $this->assertSame('توجه به اینکه پیشنهاد دهنده هستم، نیاز به بررسی و اعلام نظر از سمت مدیریت', $rows[0]['comments']);
        $this->assertSame(5, $rows[0]['user_id']);
        $this->assertSame(10, $rows[0]['suggestion_id']);
        $this->assertFalse($rows[0]['complete']);
    }

    public function test_build_review_rows_non_manager_submitter_defaults_home_feedback_to_unknown(): void
    {
        $rows = SuggestionAccessPolicy::buildReviewRows(
            suggestionId: 10,
            departments: ['TST'],
            homeDept: 'TST',
            submitterUserId: 5,
            submitterIsManager: false,
            selfFill: false,
        );

        $this->assertSame('unknown', $rows[0]['feedback']);
        $this->assertNull($rows[0]['comments']);
    }

    public function test_build_review_rows_self_fill_emits_one_row_per_dept_excluding_home(): void
    {
        $rows = SuggestionAccessPolicy::buildReviewRows(
            suggestionId: 10,
            departments: ['TST', 'OTHER', 'IT'],
            homeDept: 'TST',
            submitterUserId: 5,
            submitterIsManager: false,
            selfFill: true,
            deptFeedback: ['OTHER' => 'agree'],
            deptComments: ['IT' => 'remark'],
        );

        $this->assertCount(3, $rows);
        $this->assertSame('TST', $rows[0]['department_id']);
        $this->assertSame('OTHER', $rows[1]['department_id']);
        $this->assertSame('agree', $rows[1]['feedback']);
        $this->assertSame('remark', $rows[2]['comments']);
        $this->assertSame('unknown', $rows[2]['feedback']);
        $this->assertSame('', $rows[1]['comments']);
    }

    public function test_build_review_rows_self_fill_false_emits_only_home_row(): void
    {
        $rows = SuggestionAccessPolicy::buildReviewRows(
            suggestionId: 10,
            departments: ['TST', 'OTHER', 'IT'],
            homeDept: 'TST',
            submitterUserId: 5,
            submitterIsManager: false,
            selfFill: false,
        );

        $this->assertCount(1, $rows);
        $this->assertSame('TST', $rows[0]['department_id']);
    }

    public function test_build_review_rows_explicit_home_feedback_and_comment_override_defaults(): void
    {
        $rows = SuggestionAccessPolicy::buildReviewRows(
            suggestionId: 10,
            departments: ['TST', 'OTHER'],
            homeDept: 'TST',
            submitterUserId: 5,
            submitterIsManager: true,
            selfFill: false,
            homeFeedback: 'neutral',
            homeComment: 'custom remark',
        );

        $this->assertSame('neutral', $rows[0]['feedback']);
        $this->assertSame('custom remark', $rows[0]['comments']);
    }
}