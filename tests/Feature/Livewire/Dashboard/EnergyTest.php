<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Energy\Chart;
use App\Livewire\Dashboard\Energy\Main;
use App\Models\Department;
use App\Models\EnergyTest as EnergyModel;
use App\Models\Profile;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class EnergyTest extends TestCase
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

    public function test_energy_submit_creates_test()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('answers.mind.0', true)
            ->set('answers.physique.0', true)
            ->set('answers.emotion.0', true)
            ->set('answers.soul.0', true)
            ->call('submitTest')
            ->assertHasNoErrors();

        $test = EnergyModel::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->assertSame(1, (int) $test->mind_score);
    }

    public function test_active_tab_is_remembered_across_mounts(): void
    {
        Livewire::withoutLazyLoading();
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)->test(Main::class)->call('switchTab', 'chart')->assertSet('activeTab', 'chart');
        Livewire::actingAs($user)->test(Main::class)->assertSet('activeTab', 'chart');
    }

    public function test_mount_shows_survey_when_user_has_no_recent_test(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('showSurvey', true)
            ->assertSet('totalSteps', 4);
    }

    public function test_mount_hides_survey_when_user_completed_test_within_25_days(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        EnergyModel::factory()->forUser($user)->completedDaysAgo(10)->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('showSurvey', false)
            ->assertSet('totalSteps', 0);
    }

    public function test_mount_shows_survey_when_only_test_is_older_than_25_days(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        EnergyModel::factory()->forUser($user)->completedDaysAgo(30)->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('showSurvey', true)
            ->assertSet('totalSteps', 4);
    }

    public function test_mount_seeds_all_false_answers_and_disabled_state_when_survey_shown(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('totalSteps', 4)
            ->assertSet('step', 1)
            ->assertSet('canProceed', false)
            ->assertSet('canSubmit', false)
            ->assertSet('progress', 0);
    }

    public function test_user_test_count_reflects_total_tests_for_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        EnergyModel::factory()->forUser($user)->count(2)->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('userTestCount', 2);
    }

    public function test_next_step_advances_step_when_not_at_last_step(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('step', 1)
            ->call('nextStep')
            ->assertSet('step', 2);
    }

    public function test_next_step_clamps_at_total_steps(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->assertSet('step', 4)
            ->call('nextStep')
            ->assertSet('step', 4);
    }

    public function test_previous_step_decrements_step_when_not_at_first_step(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('nextStep')
            ->assertSet('step', 2)
            ->call('previousStep')
            ->assertSet('step', 1);
    }

    public function test_previous_step_clamps_at_first_step(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('step', 1)
            ->call('previousStep')
            ->assertSet('step', 1);
    }

    public function test_switch_tab_changes_active_tab(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('activeTab', 'survey')
            ->call('switchTab', 'chart')
            ->assertSet('activeTab', 'chart');
    }

    public function test_can_proceed_is_false_when_current_category_unanswered(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('canProceed', false);
    }

    public function test_can_proceed_is_true_when_current_category_has_a_checked_answer(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('answers.physique.0', true)
            ->assertSet('canProceed', true);
    }

    public function test_can_submit_is_false_when_not_on_last_step_even_if_all_categories_answered(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('answers.physique.0', true)
            ->set('answers.emotion.0', true)
            ->set('answers.mind.0', true)
            ->set('answers.soul.0', true)
            ->assertSet('step', 1)
            ->assertSet('canSubmit', false);
    }

    public function test_can_submit_is_true_on_last_step_when_every_category_answered(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('answers.physique.0', true)
            ->call('nextStep')
            ->set('answers.emotion.0', true)
            ->call('nextStep')
            ->set('answers.mind.0', true)
            ->call('nextStep')
            ->set('answers.soul.0', true)
            ->assertSet('step', 4)
            ->assertSet('canSubmit', true);
    }

    public function test_progress_reflects_current_step_percentage(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('progress', 0)
            ->call('nextStep')
            ->assertSet('progress', 25.0)
            ->call('nextStep')
            ->assertSet('progress', 50.0)
            ->call('nextStep')
            ->assertSet('progress', 75.0);
    }

    public function test_checking_normal_option_unchecks_none_of_the_above_in_same_category(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('answers.mind.4', true)
            ->assertSet('answers.mind.4', true)
            ->set('answers.mind.0', true)
            ->assertSet('answers.mind.0', true)
            ->assertSet('answers.mind.4', false);
    }

    public function test_checking_none_of_the_above_unchecks_all_normal_options_in_category(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('answers.mind.0', true)
            ->set('answers.mind.1', true)
            ->assertSet('answers.mind.0', true)
            ->assertSet('answers.mind.1', true)
            ->set('answers.mind.4', true)
            ->assertSet('answers.mind.0', false)
            ->assertSet('answers.mind.1', false)
            ->assertSet('answers.mind.4', true);
    }

    public function test_unchecking_an_option_does_not_uncheck_other_options(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('answers.mind.0', true)
            ->set('answers.mind.1', true)
            ->set('answers.mind.0', false)
            ->assertSet('answers.mind.0', false)
            ->assertSet('answers.mind.1', true);
    }

    public function test_updated_answers_does_not_throw_when_name_has_no_category_index_dot(): void
    {
        // regression guard: Main.php's updatedAnswers() destructures explode('.', $name) into
        // [$category, $index] — a $name with no dot (e.g. Livewire firing the hook for the bare
        // "answers" property itself, not a "category.index" leaf) used to throw "Undefined array
        // key 1"; it must now fall back to a plain state refresh instead of crashing.
        $user = User::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($user)->test(Main::class);
        $component->instance()->updatedAnswers(true, 'answers');

        $this->assertIsArray($component->instance()->answers);
    }

    public function test_submit_test_excludes_none_of_the_above_option_from_dimension_score(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('answers.mind.4', true)
            ->set('answers.physique.4', true)
            ->set('answers.emotion.4', true)
            ->set('answers.soul.4', true)
            ->call('submitTest')
            ->assertHasNoErrors();

        $test = EnergyModel::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->assertSame(0, (int) $test->mind_score);
        $this->assertSame(0, (int) $test->overall_score);
    }

    public function test_submit_test_overall_score_sums_all_dimension_scores(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('answers.physique.0', true)
            ->set('answers.emotion.0', true)
            ->set('answers.mind.0', true)
            ->set('answers.soul.0', true)
            ->call('submitTest')
            ->assertHasNoErrors();

        $test = EnergyModel::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->assertSame(1, (int) $test->mind_score);
        $this->assertSame(1, (int) $test->emotion_score);
        $this->assertSame(1, (int) $test->physique_score);
        $this->assertSame(1, (int) $test->soul_score);
        $this->assertSame(4, (int) $test->overall_score);
    }

    public function test_submit_test_hides_survey_and_dispatches_success_toast(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($user)
            ->test(Main::class)
            ->set('answers.mind.0', true)
            ->set('answers.physique.0', true)
            ->set('answers.emotion.0', true)
            ->set('answers.soul.0', true)
            ->call('submitTest')
            ->assertSet('showSurvey', false)
            ->assertDispatched('toast', message: 'پاسخ با موفقیت ذخیره شد.', type: 'success');

        $this->assertStringContainsString('wire:key="energy-after-test"', $component->html());
    }

    public function test_submit_test_does_not_persist_record_when_can_submit_is_false(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('canSubmit', false)
            ->call('submitTest')
            ->assertSet('showSurvey', true)
            ->assertDispatched('toast', message: 'پاسخ به همه دسته‌ها الزامی است.', type: 'error');

        $this->assertSame(0, EnergyModel::where('user_id', $user->id)->count());
    }

    public function test_submit_test_dispatches_energy_test_submitted_to_chart()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('answers.mind.0', true)
            ->set('answers.physique.0', true)
            ->set('answers.emotion.0', true)
            ->set('answers.soul.0', true)
            ->call('submitTest')
            ->assertDispatchedTo('dashboard.energy.chart', 'energy-test-submitted');
    }

    public function test_chart_refreshes_latest_test_and_history_when_event_received()
    {
        $user = User::factory()->create(['status' => 'active']);
        $old = EnergyModel::factory()->forUser($user)->create(['overall_score' => 4]);

        $chart = Livewire::actingAs($user)->test(Chart::class);
        $this->assertSame($old->overall_score, $chart->instance()->latestTest['overall_score']);
        $this->assertCount(1, $chart->instance()->history);
        $this->assertSame(toJalali($old->completed_at, 'Y/m/d'), $chart->instance()->history[0]['date']);

        $new = EnergyModel::factory()->forUser($user)->create(['overall_score' => 12, 'completed_at' => now()->addMinute()]);

        $chart->dispatch('energy-test-submitted');

        $this->assertSame($new->overall_score, $chart->instance()->latestTest['overall_score']);
        $this->assertCount(2, $chart->instance()->history);
    }

    public function test_chart_tab_renders_real_chart_content_when_switched_to(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        EnergyModel::factory()->forUser($user)->create();

        $main = Livewire::actingAs($user)->test(Main::class);

        Livewire::withoutLazyLoading();
        $component = $main->call('switchTab', 'chart');

        $this->assertStringContainsString('energyChart(', $component->html());
        $this->assertStringContainsString('wire:key="energy-chart"', $component->html());
    }

    public function test_chart_component_is_not_mounted_and_its_queries_are_not_run_when_survey_tab_is_active(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        EnergyModel::factory()->forUser($user)->create();

        try {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $component = Livewire::actingAs($user)->test(Main::class);

            $this->assertStringNotContainsString('energyChart(', $component->html());
            $this->assertStringNotContainsString('wire:key="energy-chart"', $component->html());

            $chartQueries = collect(DB::getQueryLog())->pluck('query')
                ->filter(fn($q) => str_contains($q, 'AVG(mind_score)'));
            $this->assertCount(0, $chartQueries, 'Chart::companyAverages() must not run while the chart tab is inactive — the component should not mount at all');
        } finally {
            DB::disableQueryLog();
        }
    }

    public function test_company_averages_excludes_guest_type_users(): void
    {
        $viewer = User::factory()->create(['status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        EnergyModel::factory()->forUser($employee)->create(['overall_score' => 8]);

        $baseline = Livewire::actingAs($viewer)->test(Chart::class)->instance()->companyAverages;

        $guest = User::factory()->create(['status' => 'active', 'type' => 'guest']);
        EnergyModel::factory()->forUser($guest)->create(['overall_score' => 20]);

        $afterGuest = Livewire::actingAs($viewer)->test(Chart::class)->instance()->companyAverages;

        $this->assertSame($baseline['overall'], $afterGuest['overall'], 'a guest-type energy test must not shift the company average');
    }

    public function test_company_averages_is_cached_under_the_model_cache_version_key(): void
    {
        $viewer = User::factory()->create(['status' => 'active']);
        EnergyModel::factory()->forUser(User::factory()->create(['status' => 'active']))->create(['overall_score' => 8]);

        Livewire::actingAs($viewer)->test(Chart::class)->instance()->companyAverages;

        $key = ModelCacheVersion::key(EnergyModel::class, 'company_averages:' . $viewer->id);

        $this->assertTrue(Cache::has($key));

        Cache::forget($key);
    }

    public function test_chart_team_members_data_shows_company_wide_managers_for_a_top_executive(): void
    {
        Department::firstOrCreate(['code' => 'MA'], ['name' => 'MA', 'description' => 'MA']);
        Department::firstOrCreate(['code' => 'MK'], ['name' => 'MK', 'description' => 'MK']);

        $viewer = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $viewer->id, 'department_id' => 'MA', 'position' => 'chairman']);

        $manager = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $manager->id, 'department_id' => 'MK', 'position' => 'manager']);
        EnergyModel::factory()->forUser($manager)->create(['overall_score' => 12]);

        $employee = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $employee->id, 'department_id' => 'MK', 'position' => 'employee']);
        EnergyModel::factory()->forUser($employee)->create(['overall_score' => 5]);

        $names = collect(Livewire::actingAs($viewer)->test(Chart::class)->instance()->teamMembersData)->pluck('name');

        $this->assertContains($manager->name, $names, 'a chairman must see a manager\'s energy results company-wide, outside their own department');
        $this->assertNotContains($employee->name, $names, 'a chairman only sees manager rank and above, not every employee');
    }

    public function test_cooldown_days_counts_remaining_days_until_survey_reopens(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->assertNull(Livewire::actingAs($user)->test(Main::class)->instance()->cooldownDays);

        EnergyModel::factory()->forUser($user)->completedDaysAgo(10)->create();

        Livewire::withoutLazyLoading();

        $this->assertSame(15, Livewire::actingAs($user)->test(Main::class)->instance()->cooldownDays);
    }

    public function test_layout_max_scope_is_tab_aware_so_the_survey_tab_never_locks_header_navbar_footer_hidden(): void
    {
        // regression guard: layouts/app.blade.php used to stamp data-max-scope="energy" for the
        // whole /energy route regardless of tab. energyChart.js's initMaximize() — the only thing
        // that clears the pre-paint "max-restore" class — only mounts on the chart tab, so a user
        // who maximized a chart widget then navigated back to the default survey tab (or reloaded
        // there) would see header/navbar/footer permanently hidden with no way to recover.
        $user = User::factory()->create(['status' => 'active']);

        $survey = $this->actingAs($user)->get('/energy')->getContent();
        $this->assertStringNotContainsString('data-max-scope="energy"', $survey);

        $chart = $this->actingAs($user)->get('/energy?tab=chart')->getContent();
        $this->assertStringContainsString('data-max-scope="energy"', $chart);
    }

    public function test_personal_history_maps_stored_answers_to_option_texts_per_month(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        EnergyModel::factory()->forUser($user)->create([
            'month_index' => 0,
            'answers' => [
                'physique' => [false, false, true, false, false],
                'emotion' => [false, false, false, false, true],
                'mind' => [true, false, false, false, false],
                'soul' => [true, false, false, false, false],
            ],
        ]);

        $bank = app(\App\Services\EnergyQuestionService::class)->getQuestionsForMonth(0)['questions'];
        $entry = Livewire::actingAs($user)
            ->test(Chart::class)
            ->instance()
            ->personalHistory[0];

        $this->assertSame($bank['mind'][0], $entry['choices']['mind']);
        $this->assertSame($bank['physique'][2], $entry['choices']['physique']);
        $this->assertSame($bank['emotion'][4], $entry['choices']['emotion']);
    }
}