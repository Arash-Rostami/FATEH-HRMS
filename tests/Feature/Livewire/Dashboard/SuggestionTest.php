<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Suggestion\Detail;
use App\Livewire\Dashboard\Suggestion\Main;
use App\Livewire\Dashboard\Suggestion\Presentation\SuggestionPresenter;
use App\Models\Department;
use App\Models\Profile;
use App\Models\Review;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SuggestionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Livewire::withoutLazyLoading();
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

    public function test_detail_view_sanitizes_description_html_but_preserves_safe_formatting()
    {
        $author = $this->deptUser('PS', 'expert');
        $suggestion = $this->makeSuggestion([
            'user_id' => $author->id,
            'description' => '<p>Safe <strong>bold</strong> text</p><script>alert(1)</script>',
        ]);

        Livewire::actingAs($author)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->assertSee('<strong>bold</strong>', false)
            ->assertDontSee('<script', false)
            ->assertDontSee('alert(1)', false);
    }

    public function test_suggestion_module_crud_lifecycle()
    {
        $author = $this->deptUser('PS', 'expert');
        $reviewer = $this->deptUser('TST', 'expert');
        $ceo = $this->deptUser('MA', 'ceo');

        Livewire::actingAs($author)
            ->test(Main::class)
            ->set('form.title', 'Tinker CRUD probe')
            ->set('form.descriptionSelf', 'Minimal description for automated probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->set('form.departments', ['TST'])
            ->set('form.selfFill', true)
            ->set('form.feedbackTeam', 'agree')
            ->call('submit')
            ->assertHasNoErrors();

        $suggestion = Suggestion::where('title', 'Tinker CRUD probe')->firstOrFail();
        $this->assertSame('dept_remarks', $suggestion->stage);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($reviewer)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->set('feedbackForm.feedback', 'agree')
            ->set('feedbackForm.comment', 'Looks fine from TST.')
            ->call('submitFeedback')
            ->assertHasNoErrors();

        $suggestion->refresh();
        $this->assertSame('awaiting_decision', $suggestion->stage);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($ceo)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->set('decisionForm.decision', 'accepted')
            ->set('decisionForm.decisionComment', 'Approved.')
            ->set('decisionForm.referralDepts', ['TST'])
            ->set('decisionForm.referralActions', 'Implement it.')
            ->call('submitDecision')
            ->assertHasNoErrors();

        $suggestion->refresh();
        $this->assertSame('accepted', $suggestion->stage);
        $this->assertSame('Approved.', $suggestion->comments);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($reviewer)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->call('markComplete')
            ->assertHasNoErrors();

        $suggestion->refresh();
        $this->assertSame('closed', $suggestion->stage);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($author)
            ->test(Main::class)
            ->set('search', 'Tinker CRUD probe')
            ->assertViewHas('rules');
    }

    public function test_create_suggestion_blocks_ma_injection_into_stakeholder_departments()
    {
        $this->uniqueDept('MA');
        $author = $this->deptUser('PS', 'expert');

        Livewire::actingAs($author)
            ->test(Main::class)
            ->set('form.title', 'MA injection probe')
            ->set('form.descriptionSelf', 'Minimal description for MA injection probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->set('form.departments', ['MA'])
            ->set('form.selfFill', true)
            ->set('form.feedback', ['MA' => 'agree'])
            ->call('submit')
            ->assertHasNoErrors();

        $suggestion = Suggestion::where('title', 'MA injection probe')->firstOrFail();
        $this->assertNotContains('MA', $suggestion->departments);
        $this->assertNull(
            Review::where('suggestion_id', $suggestion->id)->where('department_id', 'MA')->first()
        );
    }

    public function test_create_suggestion_rejects_unknown_department_code()
    {
        $author = $this->deptUser('PS', 'expert');

        Livewire::actingAs($author)
            ->test(Main::class)
            ->set('form.title', 'Fake dept probe')
            ->set('form.descriptionSelf', 'Minimal description for fake dept probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->set('form.departments', ['ZZ'])
            ->call('submit')
            ->assertHasErrors(['departments']);

        $this->assertSame(0, Suggestion::where('title', 'Fake dept probe')->count());
    }

    public function test_open_create_resets_form_and_sets_panel_to_create()
    {
        $user = $this->deptUser('TST', 'expert');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.title', 'Stale title')
            ->call('openCreate')
            ->assertSet('panel', 'create')
            ->assertSet('selectedId', null)
            ->assertSet('form.title', '');
    }

    public function test_select_suggestion_sets_selected_id_and_panel_to_detail()
    {
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['departments' => ['TST']]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('selectSuggestion', $suggestion->id)
            ->assertSet('selectedId', $suggestion->id)
            ->assertSet('panel', 'detail');
    }

    public function test_load_more_increments_per_page_by_ten()
    {
        $user = $this->deptUser('TST', 'expert');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('perPage', 5)
            ->call('loadMore')
            ->assertSet('perPage', 15)
            ->call('loadMore')
            ->assertSet('perPage', 25);
    }

    public function test_submit_creates_suggestion_and_dispatches_success_toast_and_resets_panel()
    {
        $user = $this->deptUser('TST', 'expert');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.title', 'Toast probe title')
            ->set('form.descriptionSelf', 'Valid description for the toast probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('panel', 'empty')
            ->assertDispatched('toast', message: 'پیشنهاد با موفقیت ثبت شد.', type: 'success');

        $this->assertNotNull(Suggestion::where('title', 'Toast probe title')->first());
    }

    public function test_submit_validates_title_required()
    {
        $user = $this->deptUser('TST', 'expert');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.title', '')
            ->set('form.descriptionSelf', 'Valid description for the validation probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->call('submit')
            ->assertHasErrors(['form.title']);
    }

    public function test_submit_validates_description_min_ten_chars()
    {
        $user = $this->deptUser('TST', 'expert');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.title', 'Valid title')
            ->set('form.descriptionSelf', 'short')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->call('submit')
            ->assertHasErrors(['form.descriptionSelf']);
    }

    public function test_submit_validates_purpose_required()
    {
        $user = $this->deptUser('TST', 'expert');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.title', 'Valid title')
            ->set('form.descriptionSelf', 'Valid description for the validation probe.')
            ->set('form.purpose', [])
            ->set('form.rule', ['simplify'])
            ->call('submit')
            ->assertHasErrors(['form.purpose']);
    }

    public function test_submit_validates_rule_required()
    {
        $user = $this->deptUser('TST', 'expert');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.title', 'Valid title')
            ->set('form.descriptionSelf', 'Valid description for the validation probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', [])
            ->call('submit')
            ->assertHasErrors(['form.rule']);
    }

    public function test_submit_merges_auth_department_into_departments()
    {
        $this->uniqueDept('OTHER');
        $user = $this->deptUser('TST', 'expert');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.title', 'Merge dept probe')
            ->set('form.descriptionSelf', 'Valid description for the merge probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->set('form.departments', ['OTHER'])
            ->call('submit')
            ->assertHasNoErrors();

        $suggestion = Suggestion::where('title', 'Merge dept probe')->firstOrFail();
        $this->assertSame(['TST', 'OTHER'], $suggestion->departments);
    }

    public function test_submit_creates_blank_home_dept_review_when_self_fill_false_and_not_manager()
    {
        $this->uniqueDept('TST');
        $manager = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $manager->id, 'department_id' => 'TST', 'position' => 'manager']);
        $expert = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $expert->id, 'department_id' => 'TST', 'position' => 'expert']);

        Livewire::actingAs($expert)
            ->test(Main::class)
            ->set('form.title', 'No reviews probe')
            ->set('form.descriptionSelf', 'Valid description for the no-reviews probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->call('submit')
            ->assertHasNoErrors();

        $suggestion = Suggestion::where('title', 'No reviews probe')->firstOrFail();
        $reviews = Review::where('suggestion_id', $suggestion->id)->get();

        $this->assertCount(1, $reviews);
        $this->assertSame('TST', $reviews->first()->department_id);
        $this->assertSame('unknown', $reviews->first()->feedback);
        $this->assertSame('team_remarks', $suggestion->stage);
    }

    public function test_submit_creates_manager_agree_review_when_dept_head()
    {
        $user = $this->deptUser('HEAD', 'expert');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.title', 'Manager self-create probe')
            ->set('form.descriptionSelf', 'Valid description for the manager probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->call('submit')
            ->assertHasNoErrors();

        $suggestion = Suggestion::where('title', 'Manager self-create probe')->firstOrFail();
        $review = Review::where('suggestion_id', $suggestion->id)->first();
        $this->assertNotNull($review);
        $this->assertSame('agree', $review->feedback);
    }

    public function test_search_filters_by_title()
    {
        $user = $this->deptUser('TST', 'expert');
        $match = $this->makeSuggestion(['title' => 'AlphaUniqueTokenProbe']);
        $other = $this->makeSuggestion(['title' => 'BetaUniqueTokenProbe']);

        $results = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('search', 'AlphaUniqueTokenProbe')
            ->instance()->suggestions->getCollection();

        $this->assertNotNull($results->firstWhere('id', $match->id));
        $this->assertNull($results->firstWhere('id', $other->id));
    }

    public function test_search_filters_by_serial_number()
    {
        $user = $this->deptUser('TST', 'expert');
        $match = $this->makeSuggestion(['title' => 'SerialMatchProbe']);
        $other = $this->makeSuggestion(['title' => 'SerialOtherProbe']);
        $serial = 'SN-' . $match->created_at->format('Ymd') . '-' . str_pad($match->id, 6, '0', STR_PAD_LEFT);

        $results = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('search', $serial)
            ->instance()->suggestions->getCollection();

        $this->assertNotNull($results->firstWhere('id', $match->id));
        $this->assertNull($results->firstWhere('id', $other->id));
    }

    public function test_search_by_full_serial_with_lpad_padded_id_finds_the_suggestion()
    {
        $user = $this->deptUser('TST', 'expert');
        $match = $this->makeSuggestion(['title' => 'FullSerialProbe']);
        $control = $this->makeSuggestion(['title' => 'FullSerialControlProbe']);
        DB::table('suggestions')->where('id', $match->id)->update(['created_at' => '2037-05-04 12:30:00']);

        $serial = 'SN-20370504-' . str_pad((string) $match->id, 6, '0', STR_PAD_LEFT);

        $ids = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('perPage', 200)
            ->set('search', $serial)
            ->instance()->suggestions->getCollection()->pluck('id')->all();

        $this->assertSame([$match->id], $ids);
        $this->assertNotContains($control->id, $ids);
    }

    public function test_search_by_partial_date_prefix_finds_the_suggestion()
    {
        $user = $this->deptUser('TST', 'expert');
        $match = $this->makeSuggestion(['title' => 'PartialDateProbe']);
        $control = $this->makeSuggestion(['title' => 'PartialDateControlProbe']);
        DB::table('suggestions')->where('id', $match->id)->update(['created_at' => '2037-05-04 12:30:00']);

        $ids = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('perPage', 200)
            ->set('search', 'SN-2037')
            ->instance()->suggestions->getCollection()->pluck('id')->all();

        $this->assertSame([$match->id], $ids);
        $this->assertNotContains($control->id, $ids);
    }

    public function test_search_sn_without_digits_still_matches_via_fallback_branch()
    {
        $user = $this->deptUser('TST', 'expert');
        $match = $this->makeSuggestion(['title' => 'FallbackSerialProbe']);

        $ids = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('perPage', 200)
            ->set('search', 'SN-')
            ->instance()->suggestions->getCollection()->pluck('id')->all();

        $this->assertContains($match->id, $ids);
    }

    public function test_search_by_title_substring_still_matches()
    {
        $user = $this->deptUser('TST', 'expert');
        $match = $this->makeSuggestion(['title' => 'SubstringTokenProbeZQ']);
        $control = $this->makeSuggestion(['title' => 'UnrelatedControlProbe']);

        $ids = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('perPage', 200)
            ->set('search', 'stringTokenProbe')
            ->instance()->suggestions->getCollection()->pluck('id')->all();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($control->id, $ids);
    }

    public function test_search_with_single_zero_character_does_not_filter_due_to_falsy_truthiness_check()
    {
        $user = $this->deptUser('TST', 'expert');
        $withZero = $this->makeSuggestion(['title' => 'Has0TokenProbe']);
        $withoutZero = $this->makeSuggestion(['title' => 'NoZeroMatchProbe']);

        $results = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('perPage', 50)
            ->set('search', '0')
            ->instance()->suggestions->getCollection();

        $this->assertNotNull($results->firstWhere('id', $withoutZero->id), 'PHP falsy "0" skips the when() guard at Main.php:140 so the title filter is a no-op (masked here by serials always containing "0")');
        $this->assertNotNull($results->firstWhere('id', $withZero->id));
    }

    public function test_updated_search_resets_page_to_one()
    {
        $user = $this->deptUser('TST', 'expert');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('nextPage')
            ->assertSet('paginators.page', 2)
            ->set('search', 'anything')
            ->assertSet('paginators.page', 1);
    }

    public function test_focus_record_selects_existing_suggestion()
    {
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['departments' => ['TST']]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('focusRecord', $suggestion->id)
            ->assertSet('selectedId', $suggestion->id)
            ->assertSet('panel', 'detail');
    }

    public function test_focus_record_ignores_nonexistent_suggestion()
    {
        $user = $this->deptUser('TST', 'expert');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('focusRecord', 99999999)
            ->assertSet('selectedId', null)
            ->assertSet('panel', 'empty');
    }

    public function test_available_departments_excludes_auth_dept_and_ma()
    {
        $this->uniqueDept('TST2');
        $user = $this->deptUser('TST', 'expert');

        $available = Livewire::actingAs($user)
            ->test(Main::class)
            ->instance()->availableDepartments;

        $this->assertArrayNotHasKey('TST', $available);
        $this->assertArrayNotHasKey('MA', $available);
        $this->assertArrayHasKey('TST2', $available);
    }

    public function test_top_contributors_ranks_by_accepted_then_total()
    {
        $author = User::factory()->create(['status' => 'active']);
        $this->makeSuggestion(['user_id' => $author->id, 'stage' => 'accepted']);
        $this->makeSuggestion(['user_id' => $author->id, 'stage' => 'accepted']);
        $this->makeSuggestion(['user_id' => $author->id, 'stage' => 'accepted']);
        $viewer = User::factory()->create(['status' => 'active']);

        $top = Livewire::actingAs($viewer)
            ->test(Main::class)
            ->instance()->topContributors;

        $this->assertSame($author->id, $top->first()->user_id);
        $this->assertSame(3, (int) $top->first()->accepted_suggestions);
    }

    public function test_suggestions_aggregates_review_counts()
    {
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['title' => 'CountAggregatorProbe', 'departments' => ['TST']]);
        Review::factory()->create(['suggestion_id' => $suggestion->id, 'department_id' => 'TST', 'feedback' => 'agree', 'user_id' => $user->id]);
        Review::factory()->create(['suggestion_id' => $suggestion->id, 'department_id' => 'IT', 'feedback' => 'agree', 'user_id' => $user->id]);
        Review::factory()->create(['suggestion_id' => $suggestion->id, 'department_id' => 'HR', 'feedback' => 'disagree', 'user_id' => $user->id]);

        $item = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('search', 'CountAggregatorProbe')
            ->instance()->suggestions->getCollection()->firstWhere('id', $suggestion->id);

        $this->assertNotNull($item);
        $this->assertSame(2, (int) $item->agree_count);
        $this->assertSame(1, (int) $item->disagree_count);
        $this->assertSame(0, (int) $item->neutral_count);
    }

    public function test_empty_state_when_search_has_no_matches()
    {
        $user = $this->deptUser('TST', 'expert');

        $results = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('search', 'ZZNoMatchTokenXYZ999')
            ->assertSeeHtml('wire:key="suggestion-list-empty"')
            ->instance()->suggestions->getCollection();

        $this->assertTrue($results->isEmpty());
    }

    public function test_can_give_feedback_true_for_dept_head_in_dept_remarks_without_review()
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => ['TST']]);

        $can = Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canGiveFeedback;

        $this->assertTrue($can);
    }

    public function test_can_give_feedback_false_for_ceo()
    {
        $ceo = $this->deptUser('MA', 'ceo');
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => ['MA']]);

        $can = Livewire::actingAs($ceo)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canGiveFeedback;

        $this->assertFalse($can);
    }

    public function test_can_give_feedback_false_when_stage_is_not_dept_remarks()
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'pending', 'departments' => ['TST']]);

        $can = Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canGiveFeedback;

        $this->assertFalse($can);
    }

    public function test_can_give_feedback_false_when_dept_not_listed()
    {
        $this->uniqueDept('TST');
        $this->uniqueDept('OTHER');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => ['OTHER']]);

        $can = Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canGiveFeedback;

        $this->assertFalse($can);
    }

    public function test_can_decide_true_for_ceo_in_awaiting_decision()
    {
        $ceo = $this->deptUser('MA', 'ceo');
        $suggestion = $this->makeSuggestion(['stage' => 'awaiting_decision', 'departments' => ['TST']]);

        $can = Livewire::actingAs($ceo)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canDecide;

        $this->assertTrue($can);
    }

    public function test_can_decide_false_for_non_ceo()
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'awaiting_decision', 'departments' => ['TST']]);

        $can = Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canDecide;

        $this->assertFalse($can);
    }

    public function test_can_decide_false_when_stage_not_awaiting_decision()
    {
        $ceo = $this->deptUser('MA', 'ceo');
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => ['TST']]);

        $can = Livewire::actingAs($ceo)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canDecide;

        $this->assertFalse($can);
    }

    public function test_can_mark_complete_true_for_referred_dept_with_incomplete_review()
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'accepted', 'departments' => ['TST']]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'MA',
            'feedback' => 'agree', 'referral' => ['TST'], 'complete' => false, 'user_id' => $user->id,
        ]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'TST',
            'feedback' => 'agree', 'complete' => false, 'user_id' => $user->id,
        ]);

        $can = Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canMarkComplete;

        $this->assertTrue($can);
    }

    public function test_can_mark_complete_false_for_non_referred_dept()
    {
        $this->uniqueDept('TST');
        $this->uniqueDept('OTHER');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'accepted', 'departments' => ['TST']]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'MA',
            'feedback' => 'agree', 'referral' => ['OTHER'], 'complete' => false, 'user_id' => $user->id,
        ]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'TST',
            'feedback' => 'agree', 'complete' => false, 'user_id' => $user->id,
        ]);

        $can = Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canMarkComplete;

        $this->assertFalse($can);
    }

    public function test_can_mark_complete_false_when_review_already_complete()
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'accepted', 'departments' => ['TST']]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'MA',
            'feedback' => 'agree', 'referral' => ['TST'], 'complete' => false, 'user_id' => $user->id,
        ]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'TST',
            'feedback' => 'agree', 'complete' => true, 'user_id' => $user->id,
        ]);

        $can = Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canMarkComplete;

        $this->assertFalse($can);
    }

    public function test_submit_feedback_creates_review_and_syncs_stage_to_awaiting_decision()
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => ['TST']]);

        Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->set('feedbackForm.feedback', 'agree')
            ->set('feedbackForm.comment', 'Agreed from TST.')
            ->call('submitFeedback')
            ->assertHasNoErrors();

        $review = Review::where('suggestion_id', $suggestion->id)->first();
        $this->assertNotNull($review);
        $this->assertSame('agree', $review->feedback);
        $this->assertSame('awaiting_decision', $suggestion->refresh()->stage);
    }

    public function test_submit_decision_accepted_creates_ma_review_with_referral_and_syncs_stage()
    {
        $this->uniqueDept('TST');
        $ceo = $this->deptUser('MA', 'ceo');
        $suggestion = $this->makeSuggestion(['stage' => 'awaiting_decision', 'departments' => ['TST']]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'TST',
            'feedback' => 'agree', 'complete' => false, 'user_id' => $ceo->id,
        ]);

        Livewire::actingAs($ceo)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->set('decisionForm.decision', 'accepted')
            ->set('decisionForm.decisionComment', 'Approved by CEO.')
            ->set('decisionForm.referralDepts', ['TST'])
            ->set('decisionForm.referralActions', 'Implement it.')
            ->call('submitDecision')
            ->assertHasNoErrors();

        $maReview = Review::where('suggestion_id', $suggestion->id)->where('department_id', 'MA')->first();
        $this->assertNotNull($maReview);
        $this->assertSame(['TST'], $maReview->referral);
        $this->assertSame('accepted', $suggestion->refresh()->stage);
    }

    public function test_submit_decision_rejected_creates_ma_review_and_syncs_stage_to_rejected()
    {
        $this->uniqueDept('TST');
        $ceo = $this->deptUser('MA', 'ceo');
        $suggestion = $this->makeSuggestion(['stage' => 'awaiting_decision', 'departments' => ['TST']]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'TST',
            'feedback' => 'agree', 'complete' => false, 'user_id' => $ceo->id,
        ]);

        Livewire::actingAs($ceo)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->set('decisionForm.decision', 'rejected')
            ->set('decisionForm.decisionComment', 'Rejected by CEO.')
            ->call('submitDecision')
            ->assertHasNoErrors();

        $this->assertSame('Rejected by CEO.', $suggestion->refresh()->comments);

        $maReview = Review::where('suggestion_id', $suggestion->id)->where('department_id', 'MA')->first();
        $this->assertNotNull($maReview);
        $this->assertSame('disagree', $maReview->feedback);
        $this->assertNull($maReview->referral);
        $this->assertSame('rejected', $suggestion->refresh()->stage);
    }

    public function test_submit_decision_under_review_creates_ma_review_and_syncs_stage_to_under_review()
    {
        $this->uniqueDept('TST');
        $ceo = $this->deptUser('MA', 'ceo');
        $suggestion = $this->makeSuggestion(['stage' => 'awaiting_decision', 'departments' => ['TST']]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'TST',
            'feedback' => 'agree', 'complete' => false, 'user_id' => $ceo->id,
        ]);

        Livewire::actingAs($ceo)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->set('decisionForm.decision', 'under_review')
            ->set('decisionForm.decisionComment', 'Needs more info.')
            ->call('submitDecision')
            ->assertHasNoErrors();

        $maReview = Review::where('suggestion_id', $suggestion->id)->where('department_id', 'MA')->first();
        $this->assertNotNull($maReview);
        $this->assertSame('incomplete', $maReview->feedback);
        $this->assertSame('under_review', $suggestion->refresh()->stage);
    }

    public function test_submit_decision_aborts_403_for_non_ceo()
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'manager');
        $suggestion = $this->makeSuggestion(['stage' => 'awaiting_decision', 'departments' => ['TST']]);

        Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->set('decisionForm.decision', 'rejected')
            ->call('submitDecision')
            ->assertStatus(403);

        $this->assertNull(Review::where('suggestion_id', $suggestion->id)->where('department_id', 'MA')->first());
    }

    public function test_submit_feedback_aborts_403_when_stage_not_dept_remarks()
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'awaiting_decision', 'departments' => ['TST']]);

        Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->set('feedbackForm.feedback', 'agree')
            ->call('submitFeedback')
            ->assertStatus(403);
    }

    public function test_mark_complete_sets_review_complete_and_syncs_stage_to_closed()
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'accepted', 'departments' => ['TST']]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'MA',
            'feedback' => 'agree', 'referral' => ['TST'], 'complete' => false, 'user_id' => $user->id,
        ]);
        $deptReview = Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'TST',
            'feedback' => 'agree', 'complete' => false, 'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->call('markComplete')
            ->assertHasNoErrors();

        $this->assertTrue($deptReview->refresh()->complete);
        $this->assertSame('closed', $suggestion->refresh()->stage);
    }

    public function test_mark_complete_silently_refuses_non_referred_dept()
    {
        $this->uniqueDept('TST');
        $this->uniqueDept('OTHER');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'accepted', 'departments' => ['TST']]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'MA',
            'feedback' => 'agree', 'referral' => ['OTHER'], 'complete' => false, 'user_id' => $user->id,
        ]);
        $deptReview = Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'TST',
            'feedback' => 'agree', 'complete' => false, 'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->call('markComplete')
            ->assertHasNoErrors();

        $this->assertFalse($deptReview->refresh()->complete);
    }

    public function test_my_review_returns_review_for_auth_user_department()
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => ['TST']]);
        $review = Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'TST',
            'feedback' => 'agree', 'user_id' => $user->id,
        ]);

        $my = Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->myReview;

        $this->assertNotNull($my);
        $this->assertSame($review->id, $my->id);
    }

    public function test_my_review_does_not_crash_when_profile_department_is_orphaned()
    {
        $code = 'GHOST' . substr(uniqid(), -4);
        $this->uniqueDept($code);
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'department_id' => $code,
            'position' => 'expert',
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('departments')->where('code', $code)->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => [$code]]);

        $my = Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->myReview;

        $this->assertNull($my);
    }

    public function test_can_give_feedback_true_for_dept_head_in_team_remarks_for_home_department()
    {
        $this->uniqueDept('OTHER');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'team_remarks', 'departments' => ['TST', 'OTHER']]);

        $can = Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canGiveFeedback;

        $this->assertTrue($can);
    }

    public function test_can_give_feedback_false_for_non_home_dept_at_team_remarks()
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('OTHER', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'team_remarks', 'departments' => ['TST', 'OTHER']]);

        $can = Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canGiveFeedback;

        $this->assertFalse($can);
    }

    public function test_submit_feedback_at_team_remarks_advances_stage_to_dept_remarks()
    {
        $this->uniqueDept('OTHER');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'team_remarks', 'departments' => ['TST', 'OTHER']]);

        Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->set('feedbackForm.feedback', 'agree')
            ->set('feedbackForm.comment', 'Home dept agrees.')
            ->call('submitFeedback')
            ->assertHasNoErrors();

        $this->assertSame('dept_remarks', $suggestion->refresh()->stage);
    }

    public function test_can_give_feedback_false_for_chairman_position()
    {
        $this->uniqueDept('TST');
        $chairman = $this->deptUser('TST', 'chairman');
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => ['TST']]);

        $can = Livewire::actingAs($chairman)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canGiveFeedback;

        $this->assertFalse($can);
    }

    public function test_can_decide_true_for_chairman_position()
    {
        $chairman = $this->deptUser('TST', 'chairman');
        $suggestion = $this->makeSuggestion(['stage' => 'awaiting_decision', 'departments' => ['TST']]);

        $can = Livewire::actingAs($chairman)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canDecide;

        $this->assertTrue($can);
    }

    public function test_can_decide_false_for_ma_manager_when_chairman_exists()
    {
        $this->deptUser('OTHER', 'chairman');
        $maManager = $this->deptUser('MA', 'manager');
        $suggestion = $this->makeSuggestion(['stage' => 'awaiting_decision', 'departments' => ['TST']]);

        $can = Livewire::actingAs($maManager)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canDecide;

        $this->assertFalse($can);
    }

    public function test_can_decide_true_for_ma_manager_fallback_when_no_active_chairman_or_ceo_exists()
    {
        User::whereHas('profile', fn($q) => $q->whereIn('position', ['chairman', 'ceo']))
            ->update(['status' => 'inactive']);

        $maManager = $this->deptUser('MA', 'manager');
        $suggestion = $this->makeSuggestion(['stage' => 'awaiting_decision', 'departments' => ['TST']]);

        $can = Livewire::actingAs($maManager)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canDecide;

        $this->assertTrue($can);
    }

    public function test_sync_stage_works_without_authenticated_user()
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'team_remarks', 'departments' => ['TST']]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'TST',
            'feedback' => 'agree', 'user_id' => $user->id,
        ]);

        $this->assertNull(Auth::user());
        $suggestion->load('reviews')->syncStage();

        $this->assertSame('awaiting_decision', $suggestion->refresh()->stage);
    }

    public function test_attention_required_true_for_home_dept_head_at_team_remarks_false_for_other_dept()
    {
        $home = $this->deptUser('TST', 'expert');
        $other = $this->deptUser('OTHER', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'team_remarks', 'departments' => ['TST', 'OTHER']]);

        $this->assertTrue(Suggestion::requiresAttentionFor($suggestion, $home));
        $this->assertFalse(Suggestion::requiresAttentionFor($suggestion, $other));
    }

    public function test_attention_required_false_for_chairman_at_team_remarks()
    {
        $chairman = $this->deptUser('TST', 'chairman');
        $suggestion = $this->makeSuggestion(['stage' => 'team_remarks', 'departments' => ['TST']]);

        $this->assertFalse(Suggestion::requiresAttentionFor($suggestion, $chairman));
    }

    public function test_attention_required_true_for_ceo_at_awaiting_decision()
    {
        $this->uniqueDept('TST');
        $ceo = $this->deptUser('MA', 'ceo');
        $suggestion = $this->makeSuggestion(['stage' => 'awaiting_decision', 'departments' => ['TST']]);

        $this->assertTrue(Suggestion::requiresAttentionFor($suggestion, $ceo));
    }

    public function test_auto_resolve_stale_command_neutrals_home_dept_at_team_remarks()
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'team_remarks', 'departments' => ['TST'], 'user_id' => $user->id]);
        DB::table('suggestions')->where('id', $suggestion->id)->update(['updated_at' => now()->subHours(50)]);

        Artisan::call('suggestions:auto-resolve-stale');

        $review = Review::where('suggestion_id', $suggestion->id)->where('department_id', 'TST')->first();
        $this->assertNotNull($review);
        $this->assertSame('neutral', $review->feedback);
        $this->assertSame('awaiting_decision', $suggestion->refresh()->stage);
    }

    public function test_auto_resolve_stale_command_ignores_fresh_team_remarks_suggestions()
    {
        $this->uniqueDept('TST');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'team_remarks', 'departments' => ['TST'], 'user_id' => $user->id]);

        Artisan::call('suggestions:auto-resolve-stale');

        $this->assertNull(Review::where('suggestion_id', $suggestion->id)->first());
        $this->assertSame('team_remarks', $suggestion->refresh()->stage);
    }

    public function test_auto_resolve_stale_command_neutrals_unanswered_depts_at_dept_remarks()
    {
        $this->uniqueDept('TST');
        $this->uniqueDept('OTHER');
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'dept_remarks', 'departments' => ['TST', 'OTHER'], 'user_id' => $user->id]);
        Review::factory()->create(['suggestion_id' => $suggestion->id, 'department_id' => 'TST', 'feedback' => 'agree', 'user_id' => $user->id]);
        DB::table('suggestions')->where('id', $suggestion->id)->update(['updated_at' => now()->subHours(50)]);

        Artisan::call('suggestions:auto-resolve-stale');

        $otherReview = Review::where('suggestion_id', $suggestion->id)->where('department_id', 'OTHER')->first();
        $this->assertNotNull($otherReview);
        $this->assertSame('neutral', $otherReview->feedback);
        $this->assertSame('awaiting_decision', $suggestion->refresh()->stage);
    }

    public function test_can_mark_complete_false_for_referred_dept_when_not_dept_head()
    {
        $this->uniqueDept('TST');
        $manager = $this->deptUser('TST', 'manager');
        $expert = $this->deptUser('TST', 'expert');

        $suggestion = $this->makeSuggestion(['stage' => 'accepted', 'departments' => ['TST']]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'MA',
            'feedback' => 'agree', 'referral' => ['TST'], 'complete' => false, 'user_id' => $manager->id,
        ]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'TST',
            'feedback' => 'agree', 'complete' => false, 'user_id' => $manager->id,
        ]);

        $can = Livewire::actingAs($expert)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canMarkComplete;

        $this->assertFalse($can);
    }

    public function test_can_mark_complete_false_for_chairman_position()
    {
        $this->uniqueDept('TST');
        $chairman = $this->deptUser('TST', 'chairman');

        $suggestion = $this->makeSuggestion(['stage' => 'accepted', 'departments' => ['TST']]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'MA',
            'feedback' => 'agree', 'referral' => ['TST'], 'complete' => false, 'user_id' => $chairman->id,
        ]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'TST',
            'feedback' => 'agree', 'complete' => false, 'user_id' => $chairman->id,
        ]);

        $can = Livewire::actingAs($chairman)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canMarkComplete;

        $this->assertFalse($can);
    }

    public function test_self_fill_creates_review_row_for_every_department()
    {
        $this->uniqueDept('OTHER');
        $author = $this->deptUser('TST', 'expert');

        Livewire::actingAs($author)
            ->test(Main::class)
            ->set('form.title', 'Self fill full rows probe')
            ->set('form.descriptionSelf', 'Valid description for the self-fill probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->set('form.departments', ['OTHER'])
            ->set('form.selfFill', true)
            ->set('form.feedback', ['OTHER' => 'agree'])
            ->set('form.descriptionDepts', ['OTHER' => 'Pre-filled by submitter.'])
            ->call('submit')
            ->assertHasNoErrors();

        $suggestion = Suggestion::where('title', 'Self fill full rows probe')->firstOrFail();
        $reviews = Review::where('suggestion_id', $suggestion->id)->get()->keyBy('department_id');

        $this->assertCount(2, $reviews);
        $this->assertSame('agree', $reviews['OTHER']->feedback);
        $this->assertSame('awaiting_decision', $suggestion->refresh()->stage);
    }

    public function test_submit_self_fill_rejects_invalid_department_feedback_value()
    {
        $this->uniqueDept('OTHER');
        $author = $this->deptUser('TST', 'expert');

        Livewire::actingAs($author)
            ->test(Main::class)
            ->set('form.title', 'Invalid feedback probe')
            ->set('form.descriptionSelf', 'Valid description for the invalid feedback probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->set('form.departments', ['OTHER'])
            ->set('form.selfFill', true)
            ->set('form.feedback', ['OTHER' => 'hacked'])
            ->call('submit')
            ->assertHasErrors(['form.feedback.OTHER']);

        $this->assertSame(0, Suggestion::where('title', 'Invalid feedback probe')->count());
    }

    public function test_submit_rejects_ma_department_submitter()
    {
        $user = $this->deptUser('MA', 'manager');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.title', 'MA restricted probe')
            ->set('form.descriptionSelf', 'Valid description for the MA restriction probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->call('submit')
            ->assertHasErrors(['form.title']);

        $this->assertSame(0, Suggestion::where('title', 'MA restricted probe')->count());
    }

    public function test_submit_rejects_mg_department_submitter()
    {
        $user = $this->deptUser('MG', 'manager');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.title', 'MG restricted probe')
            ->set('form.descriptionSelf', 'Valid description for the MG restriction probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->call('submit')
            ->assertHasErrors(['form.title']);

        $this->assertSame(0, Suggestion::where('title', 'MG restricted probe')->count());
    }

    public function test_create_button_hidden_for_ma_department_user()
    {
        $user = $this->deptUser('MA', 'manager');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertDontSee('glow-sm', false)
            ->assertDontSee('ثبت پیشنهاد جدید');
    }

    public function test_create_button_hidden_for_mg_department_user()
    {
        $user = $this->deptUser('MG', 'manager');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertDontSee('glow-sm', false)
            ->assertDontSee('ثبت پیشنهاد جدید');
    }

    public function test_create_button_visible_for_non_ma_department_user()
    {
        $user = $this->deptUser('TST', 'expert');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSee('wire:click="openCreate"', false);
    }

    public function test_submit_rejects_invalid_feedback_team_value()
    {
        $user = $this->deptUser('TST', 'expert');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.title', 'Invalid team feedback probe')
            ->set('form.descriptionSelf', 'Valid description for the invalid team feedback probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->set('form.feedbackTeam', 'hacked')
            ->call('submit')
            ->assertHasErrors(['form.feedbackTeam']);

        $this->assertSame(0, Suggestion::where('title', 'Invalid team feedback probe')->count());
    }

    public function test_submit_persists_user_chosen_priority()
    {
        $user = $this->deptUser('TST', 'expert');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.title', 'Priority probe high')
            ->set('form.descriptionSelf', 'Valid description for the priority probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->set('form.priority', 'high')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('high', Suggestion::where('title', 'Priority probe high')->value('priority'));

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.title', 'Priority probe invalid')
            ->set('form.descriptionSelf', 'Valid description for the priority probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->set('form.priority', 'urgent')
            ->call('submit')
            ->assertHasErrors(['form.priority']);

        $this->assertSame(0, Suggestion::where('title', 'Priority probe invalid')->count());
    }

    public function test_list_orders_by_priority_then_recency()
    {
        $author = $this->deptUser('TST', 'expert');
        $low = $this->makeSuggestion(['priority' => 'low', 'user_id' => $author->id, 'title' => 'Low newest ' . uniqid()]);
        $highOld = $this->makeSuggestion(['priority' => 'high', 'user_id' => $author->id, 'title' => 'High old ' . uniqid()]);
        $mediumOlder = $this->makeSuggestion(['priority' => 'medium', 'user_id' => $author->id, 'title' => 'Medium older ' . uniqid(), 'created_at' => now()->addMinute()]);

        Livewire::actingAs($author)
            ->test(Main::class)
            ->set('perPage', 200)
            ->assertSet('suggestions', function ($p) use ($low, $highOld, $mediumOlder) {
                $titles = $p->getCollection()->pluck('title')->values();
                $pos = fn($t) => $titles->search($t);
                return $pos($highOld->title) < $pos($mediumOlder->title) && $pos($mediumOlder->title) < $pos($low->title);
            });
    }

    public function test_review_items_flag_only_auto_resolved_rows_as_system_generated(): void
    {
        $submitter = User::factory()->create(['status' => 'active']);
        $this->uniqueDept('HOME');
        $this->uniqueDept('OTHER');

        $suggestion = $this->makeSuggestion([
            'user_id' => $submitter->id,
            'departments' => ['HOME', 'OTHER'],
            'stage' => 'dept_remarks',
        ]);

        Review::factory()->create([
            'suggestion_id' => $suggestion->id,
            'department_id' => 'HOME',
            'feedback' => 'agree',
            'comments' => 'نظر واقعی مدیر',
            'referral' => [],
            'user_id' => $submitter->id,
        ]);

        Review::factory()->create([
            'suggestion_id' => $suggestion->id,
            'department_id' => 'OTHER',
            'feedback' => 'neutral',
            'comments' => Review::AUTO_RESOLVE_COMMENT,
            'referral' => [],
            'user_id' => $submitter->id,
        ]);

        $items = collect((new SuggestionPresenter($suggestion))->reviewItems())
            ->keyBy(fn($item) => $item['review']->department_id);

        $this->assertFalse($items['HOME']['is_system_generated']);
        $this->assertTrue($items['OTHER']['is_system_generated']);
    }

    public function test_self_filled_non_home_review_is_not_flagged_as_system_generated(): void
    {
        $submitter = User::factory()->create(['status' => 'active']);
        $this->uniqueDept('HOME');
        $this->uniqueDept('OTHER');

        $suggestion = $this->makeSuggestion([
            'user_id' => $submitter->id,
            'departments' => ['HOME', 'OTHER'],
            'stage' => 'dept_remarks',
            'self_fill' => true,
        ]);

        Review::factory()->create([
            'suggestion_id' => $suggestion->id,
            'department_id' => 'OTHER',
            'feedback' => 'neutral',
            'comments' => 'از طرف پیشنهاددهنده',
            'referral' => [],
            'user_id' => $submitter->id,
        ]);

        $items = collect((new SuggestionPresenter($suggestion))->reviewItems())
            ->keyBy(fn($item) => $item['review']->department_id);

        $this->assertFalse($items['OTHER']['is_system_generated']);
    }

    public function test_attention_only_filter_limits_listing_to_attention_required(): void
    {
        $home = $this->deptUser('TST', 'expert');
        $mine = $this->makeSuggestion(['stage' => 'team_remarks', 'departments' => ['TST'], 'title' => 'Mine ' . uniqid()]);
        $others = $this->makeSuggestion(['stage' => 'team_remarks', 'departments' => ['OTHER'], 'title' => 'Others ' . uniqid()]);

        Livewire::actingAs($home)
            ->test(Main::class)
            ->set('perPage', 200)
            ->assertSet('attentionOnly', false)
            ->assertSet('suggestions', fn($p) => $p->getCollection()->contains('title', $mine->title))
            ->call('setAttentionOnly', true)
            ->assertSet('attentionOnly', true)
            ->assertSet('suggestions', fn($p) => $p->getCollection()->contains('title', $mine->title)
                && ! $p->getCollection()->contains('title', $others->title))
            ->call('setAttentionOnly', false)
            ->assertSet('suggestions', fn($p) => $p->getCollection()->contains('title', $others->title));
    }

    public function test_attention_only_stays_scoped_when_searching_by_serial(): void
    {
        $home = $this->deptUser('TST', 'expert');
        $mine = $this->makeSuggestion(['stage' => 'team_remarks', 'departments' => ['TST'], 'user_id' => $home->id]);
        $others = $this->makeSuggestion(['stage' => 'team_remarks', 'departments' => ['OTHER'], 'user_id' => $home->id]);

        $serial = fn(Suggestion $s) => 'SN-' . $s->created_at->format('Ymd') . '-' . str_pad($s->id, 6, '0', STR_PAD_LEFT);

        Livewire::actingAs($home)
            ->test(Main::class)
            ->call('setAttentionOnly', true)
            ->set('search', $serial($others))
            ->assertSet('suggestions', fn($p) => $p->getCollection()->isEmpty())
            ->set('search', $serial($mine))
            ->assertSet('suggestions', fn($p) => $p->getCollection()->contains('title', $mine->title));
    }

    public function test_list_cards_render_priority_chip_from_priority_config(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $high = $this->makeSuggestion(['priority' => 'high', 'title' => 'High ' . uniqid()]);
        $low = $this->makeSuggestion(['priority' => 'low', 'title' => 'Low ' . uniqid()]);

        $testable = Livewire::actingAs($user)->test(Main::class)
            ->set('perPage', 200);
        $testable->assertSee($high->title)->assertSee($low->title)
            ->assertSee('اولویت: زیاد')
            ->assertSee('اولویت: کم')
            ->assertSee('priority_high')
            ->assertSee('low_priority');
    }

    public function test_under_review_decision_lets_home_dept_head_resubmit_and_returns_stage_to_awaiting_decision(): void
    {
        $this->uniqueDept('TST');
        $ceo = $this->deptUser('MA', 'ceo');
        $head = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['stage' => 'awaiting_decision', 'departments' => ['TST']]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'TST',
            'feedback' => 'agree', 'complete' => false, 'user_id' => $head->id,
        ]);

        Livewire::actingAs($ceo)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->set('decisionForm.decision', 'under_review')
            ->set('decisionForm.decisionComment', 'Needs more info.')
            ->call('submitDecision')
            ->assertHasNoErrors();

        $this->assertSame('under_review', $suggestion->refresh()->stage);

        $can = Livewire::actingAs($head)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canGiveFeedback;
        $this->assertTrue($can);

        Livewire::actingAs($head)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->set('feedbackForm.feedback', 'agree')
            ->set('feedbackForm.comment', 'Completed as requested.')
            ->call('submitFeedback')
            ->assertHasNoErrors();

        $this->assertSame('awaiting_decision', $suggestion->refresh()->stage);
        $this->assertSame('unknown', Review::where('suggestion_id', $suggestion->id)->where('department_id', 'MA')->value('feedback'));

        $canDecide = Livewire::actingAs($ceo)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->canDecide;
        $this->assertTrue($canDecide);
    }

    public function test_detail_action_events_refresh_the_main_list_suggestions(): void
    {
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['departments' => ['TST'], 'stage' => 'team_remarks', 'title' => 'EventRefreshProbe ' . uniqid()]);

        $main = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('search', $suggestion->title);
        $this->assertSame('team_remarks', $main->instance()->suggestions->firstWhere('id', $suggestion->id)->stage);

        foreach (['decision-made' => 'rejected', 'feedback-submitted' => 'dept_remarks', 'completion-marked' => 'closed'] as $event => $stage) {
            DB::table('suggestions')->where('id', $suggestion->id)->update(['stage' => $stage]);
            $main->dispatch($event);
            $this->assertSame($stage, $main->instance()->suggestions->firstWhere('id', $suggestion->id)->stage);
        }
    }

    public function test_top_contributors_counts_closed_suggestions_as_accepted(): void
    {
        $author = User::factory()->create(['status' => 'active']);
        $this->makeSuggestion(['user_id' => $author->id, 'stage' => 'accepted']);
        $this->makeSuggestion(['user_id' => $author->id, 'stage' => 'accepted']);
        $this->makeSuggestion(['user_id' => $author->id, 'stage' => 'closed']);
        $this->makeSuggestion(['user_id' => $author->id, 'stage' => 'closed']);
        $viewer = User::factory()->create(['status' => 'active']);

        $top = Livewire::actingAs($viewer)
            ->test(Main::class)
            ->instance()->topContributors;

        $this->assertSame($author->id, $top->first()->user_id);
        $this->assertSame(4, (int) $top->first()->accepted_suggestions);
    }

    public function test_detail_neutral_count_matches_the_list_count_for_a_suggestion_with_an_unknown_review(): void
    {
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['departments' => ['TST'], 'title' => 'NeutralParityProbe']);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'TST',
            'feedback' => 'unknown', 'user_id' => $user->id,
        ]);

        $list = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('search', 'NeutralParityProbe')
            ->instance()->suggestions->getCollection()
            ->firstWhere('id', $suggestion->id);

        $detail = Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->instance()->suggestion;

        $this->assertSame((int) $list->neutral_count, (int) $detail->neutral_count);
        $this->assertSame(0, (int) $detail->neutral_count);
    }

    public function test_submit_decision_rejects_invalid_referral_department_codes_with_a_visible_error(): void
    {
        $this->uniqueDept('TST');
        $ceo = $this->deptUser('MA', 'ceo');
        $suggestion = $this->makeSuggestion(['stage' => 'awaiting_decision', 'departments' => ['TST']]);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id, 'department_id' => 'TST',
            'feedback' => 'agree', 'complete' => false, 'user_id' => $ceo->id,
        ]);

        Livewire::actingAs($ceo)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->set('decisionForm.decision', 'accepted')
            ->set('decisionForm.decisionComment', 'Approved.')
            ->set('decisionForm.referralDepts', ['TST', 'QQ'])
            ->call('submitDecision')
            ->assertHasErrors(['decisionForm.referralDepts']);

        $this->assertNull(Review::where('suggestion_id', $suggestion->id)->where('department_id', 'MA')->first());
        $suggestion->refresh();
        $this->assertSame('awaiting_decision', $suggestion->stage);
    }

    public function test_deptless_submitter_creates_no_null_department_review_row(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.title', 'Deptless submitter probe')
            ->set('form.descriptionSelf', 'Valid description for the deptless submitter probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->call('submit')
            ->assertHasNoErrors();

        $suggestion = Suggestion::where('title', 'Deptless submitter probe')->firstOrFail();
        $this->assertSame(0, Review::where('suggestion_id', $suggestion->id)->whereNull('department_id')->count());
    }

    public function test_search_by_displayed_serial_with_short_unpadded_id_finds_the_suggestion(): void
    {
        $user = $this->deptUser('TST', 'expert');
        $match = $this->makeSuggestion(['title' => 'ShortSerialProbe']);
        $control = $this->makeSuggestion(['title' => 'ShortSerialControlProbe']);
        DB::table('suggestions')->where('id', $match->id)->update(['created_at' => '2037-05-04 12:30:00']);

        $ids = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('perPage', 200)
            ->set('search', 'SN-20370504-' . $match->id)
            ->instance()->suggestions->getCollection()->pluck('id')->all();

        $this->assertSame([$match->id], $ids);
        $this->assertNotContains($control->id, $ids);
    }

    public function test_search_with_literal_percent_does_not_wildcard_match(): void
    {
        $user = $this->deptUser('TST', 'expert');
        $match = $this->makeSuggestion(['title' => 'Has%PercentProbe']);
        $control = $this->makeSuggestion(['title' => 'NoPercentTokenProbe']);

        $ids = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('perPage', 200)
            ->set('search', '%')
            ->instance()->suggestions->getCollection()->pluck('id')->all();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($control->id, $ids);
    }

    public function test_submit_accepts_a_webp_attachment(): void
    {
        Storage::fake('public');
        $user = $this->deptUser('TST', 'expert');

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('form.title', 'Webp attachment probe')
            ->set('form.descriptionSelf', 'Valid description for the webp attachment probe.')
            ->set('form.purpose', ['cost'])
            ->set('form.rule', ['simplify'])
            ->set('form.attachment', UploadedFile::fake()->image('probe.webp'))
            ->call('submit')
            ->assertHasNoErrors();

        $suggestion = Suggestion::where('title', 'Webp attachment probe')->firstOrFail();
        $this->assertNotNull($suggestion->attachment);
    }

    public function test_attention_required_true_for_home_dept_head_at_under_review_false_for_other_dept_and_ceo(): void
    {
        $home = $this->deptUser('TST', 'expert');
        $other = $this->deptUser('OTHER', 'expert');
        $ceo = $this->deptUser('MA', 'ceo');
        $suggestion = $this->makeSuggestion(['stage' => 'under_review', 'departments' => ['TST', 'OTHER']]);

        $this->assertTrue(Suggestion::requiresAttentionFor($suggestion, $home));
        $this->assertFalse(Suggestion::requiresAttentionFor($suggestion, $other));
        $this->assertFalse(Suggestion::requiresAttentionFor($suggestion, $ceo));
    }

    public function test_detail_render_on_a_deleted_suggestion_shows_a_gone_notice_instead_of_an_infinite_loading_skeleton(): void
    {
        $user = $this->deptUser('TST', 'expert');
        $suggestion = $this->makeSuggestion(['departments' => ['TST']]);
        $suggestion->delete();

        Livewire::actingAs($user)
            ->test(Detail::class, ['suggestionId' => $suggestion->id])
            ->assertViewIs('livewire.dashboard.suggestion.detail.gone');
    }
}