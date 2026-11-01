<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Authority\Main;
use App\Models\Authority;
use App\Models\Department;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AuthorityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::forget('authority-departments');
        Cache::forget('authority-global-count');
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

    private function dept(string $code): Department
    {
        return Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => $code]);
    }

    public function test_authority_render_and_filter()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        Authority::factory()->create(['department_id' => 'IT', 'user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertHasNoErrors()
            ->call('setDept', 'IT')
            ->assertHasNoErrors()
            ->call('switchTab', 'manager')
            ->assertHasNoErrors()
            ->call('loadMore')
            ->assertHasNoErrors();
    }

    public function test_mount_sets_active_dept_from_auth_user_profile_department_id(): void
    {
        $this->dept('AU1');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AU1']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('activeDept', 'AU1');
    }

    public function test_mount_defaults_active_dept_to_empty_string_when_user_has_no_profile(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('activeDept', '');
    }

    public function test_mount_with_open_param_bumps_per_page_to_50(): void
    {
        $this->dept('AU2');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AU2']);
        $authority = Authority::factory()->create([
            'department_id' => 'AU2',
            'user_id' => $user->id,
            'details' => ['duty' => 'FOCUS-DUTY-AU2'],
        ]);

        Livewire::actingAs($user)
            ->test(Main::class, ['open' => $authority->id])
            ->assertSet('open', $authority->id)
            ->assertSet('perPage', 50);
    }

    public function test_mount_with_open_param_dispatches_record_focus_event(): void
    {
        $this->dept('AU3');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AU3']);
        $authority = Authority::factory()->create(['department_id' => 'AU3', 'user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Main::class, ['open' => $authority->id])
            ->assertDispatched('record-focus', type: 'authority', id: $authority->id);
    }

    public function test_set_dept_changes_active_dept_clears_open_and_resets_page(): void
    {
        $this->dept('AU4');
        $this->dept('AU5');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AU4']);
        $authority = Authority::factory()->create(['department_id' => 'AU4', 'user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Main::class, ['open' => $authority->id])
            ->assertSet('open', $authority->id)
            ->call('setDept', 'AU5')
            ->assertSet('activeDept', 'AU5')
            ->assertSet('open', null);
    }

    public function test_switch_tab_changes_active_tab(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('activeTab', 'manager')
            ->call('switchTab', 'employee')
            ->assertSet('activeTab', 'employee');
    }

    public function test_load_more_increments_per_page_by_20(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('perPage', 20)
            ->call('loadMore')
            ->assertSet('perPage', 40)
            ->call('loadMore')
            ->assertSet('perPage', 60);
    }

    public function test_updated_search_clears_open_and_resets_page(): void
    {
        $this->dept('AU6');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AU6']);
        $authority = Authority::factory()->create(['department_id' => 'AU6', 'user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Main::class, ['open' => $authority->id])
            ->assertSet('open', $authority->id)
            ->set('search', 'budget')
            ->assertSet('open', null);
    }

    public function test_authorities_scoped_to_active_department_in_normal_mode(): void
    {
        $this->dept('AU7');
        $this->dept('AU8');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AU7']);
        Authority::factory()->create([
            'department_id' => 'AU7', 'user_id' => $user->id,
            'details' => ['duty' => 'IN-AU7-DUTY-TOKEN'],
        ]);
        Authority::factory()->create([
            'department_id' => 'AU8', 'user_id' => $user->id,
            'details' => ['duty' => 'IN-AU8-DUTY-TOKEN'],
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSee('IN-AU7-DUTY-TOKEN')
            ->assertDontSee('IN-AU8-DUTY-TOKEN');
    }

    public function test_search_filters_authorities_by_duty_details_json_key(): void
    {
        $this->dept('AU9');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AU9']);
        Authority::factory()->create([
            'department_id' => 'AU9', 'user_id' => $user->id,
            'details' => ['duty' => 'BUDGET-APPROVAL-TOKEN'],
        ]);
        Authority::factory()->create([
            'department_id' => 'AU9', 'user_id' => $user->id,
            'details' => ['duty' => 'HIRING-PLAN-TOKEN'],
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('search', 'BUDGET')
            ->assertSee('BUDGET-APPROVAL-TOKEN')
            ->assertDontSee('HIRING-PLAN-TOKEN');
    }

    public function test_search_matches_department_name_via_or_where_has_branch(): void
    {
        Department::firstOrCreate(['code' => 'FIN'], ['name' => 'Finance Directorate', 'description' => 'Finance Directorate']);
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'FIN']);
        Authority::factory()->create([
            'department_id' => 'FIN', 'user_id' => $user->id,
            'details' => ['duty' => 'EXPENSE-REVIEW-TOKEN'],
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('search', 'Finance')
            ->assertSee('EXPENSE-REVIEW-TOKEN');
    }

    public function test_search_with_single_zero_character_filters_to_matching_authority_only(): void
    {
        $this->dept('AUX');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AUX']);
        Authority::factory()->create([
            'department_id' => 'AUX', 'user_id' => $user->id,
            'details' => ['duty' => 'DUTY-0-MATCH'],
        ]);
        Authority::factory()->create([
            'department_id' => 'AUX', 'user_id' => $user->id,
            'details' => ['duty' => 'DUTY-NOMATCH'],
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('search', '0')
            ->assertSee('DUTY-0-MATCH')
            ->assertDontSee('DUTY-NOMATCH');
    }

    public function test_focus_mode_pins_listing_to_single_open_record(): void
    {
        $this->dept('AUF');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AUF']);
        Authority::factory()->create([
            'department_id' => 'AUF', 'user_id' => $user->id,
            'details' => ['duty' => 'OTHER-DUTY-A-TOKEN'],
        ]);
        Authority::factory()->create([
            'department_id' => 'AUF', 'user_id' => $user->id,
            'details' => ['duty' => 'OTHER-DUTY-B-TOKEN'],
        ]);
        $target = Authority::factory()->create([
            'department_id' => 'AUF', 'user_id' => $user->id,
            'details' => ['duty' => 'TARGET-DUTY-TOKEN'],
        ]);

        Livewire::actingAs($user)
            ->test(Main::class, ['open' => $target->id])
            ->assertSee('TARGET-DUTY-TOKEN')
            ->assertDontSee('OTHER-DUTY-A-TOKEN')
            ->assertDontSee('OTHER-DUTY-B-TOKEN');
    }

    public function test_clear_focus_exits_focus_mode_and_restores_department_list(): void
    {
        $this->dept('AUC');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AUC']);
        Authority::factory()->create([
            'department_id' => 'AUC', 'user_id' => $user->id,
            'details' => ['duty' => 'SIBLING-DUTY-TOKEN'],
        ]);
        $target = Authority::factory()->create([
            'department_id' => 'AUC', 'user_id' => $user->id,
            'details' => ['duty' => 'FOCUSED-DUTY-TOKEN'],
        ]);

        Livewire::actingAs($user)
            ->test(Main::class, ['open' => $target->id])
            ->assertSee('FOCUSED-DUTY-TOKEN')
            ->assertDontSee('SIBLING-DUTY-TOKEN')
            ->call('clearFocus')
            ->assertSet('open', null)
            ->assertSee('SIBLING-DUTY-TOKEN');
    }

    public function test_total_count_returns_global_authority_count_not_dept_scoped(): void
    {
        $this->dept('AUT1');
        $this->dept('AUT2');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AUT1']);
        $baseline = Authority::count();
        Authority::factory()->create(['department_id' => 'AUT1', 'user_id' => $user->id]);
        Authority::factory()->create(['department_id' => 'AUT2', 'user_id' => $user->id]);

        $component = Livewire::actingAs($user)->test(Main::class);

        $this->assertSame($baseline + 2, $component->instance()->totalCount);
    }

    public function test_departments_computed_only_returns_depts_that_have_authorities(): void
    {
        $this->dept('AUF1');
        $this->dept('AUF2');
        $this->dept('AUF3');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AUF1']);
        Authority::factory()->create(['department_id' => 'AUF1', 'user_id' => $user->id]);
        Authority::factory()->create(['department_id' => 'AUF2', 'user_id' => $user->id]);

        $component = Livewire::actingAs($user)->test(Main::class);

        $codes = $component->instance()->departments->pluck('code')->all();
        $this->assertContains('AUF1', $codes);
        $this->assertContains('AUF2', $codes);
        $this->assertNotContains('AUF3', $codes);
    }

    public function test_current_department_returns_dept_matching_active_dept(): void
    {
        $this->dept('AUCD');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AUCD']);
        Authority::factory()->create(['department_id' => 'AUCD', 'user_id' => $user->id]);

        $component = Livewire::actingAs($user)->test(Main::class);

        $this->assertNotNull($component->instance()->currentDepartment);
        $this->assertSame('AUCD', $component->instance()->currentDepartment->code);
    }

    public function test_current_department_is_null_when_active_dept_has_no_authorities(): void
    {
        $this->dept('AUCN');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AUCN']);

        $component = Livewire::actingAs($user)->test(Main::class);

        $this->assertNull($component->instance()->currentDepartment);
    }

    public function test_load_more_reveals_page_two_records_and_hides_load_more_button_when_all_fit(): void
    {
        $this->dept('AULP');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AULP']);
        for ($i = 0; $i < 21; $i++) {
            Authority::factory()->create([
                'department_id' => 'AULP', 'user_id' => $user->id,
                'details' => ['duty' => "LP-DUTY-$i"],
            ]);
        }
        Authority::factory()->create([
            'department_id' => 'AULP', 'user_id' => $user->id,
            'details' => ['duty' => 'LP-OLDEST-DUTY-TOKEN'],
            'created_at' => now()->subDays(2),
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertDontSee('LP-OLDEST-DUTY-TOKEN')
            ->assertSee('$wire.loadMore()', false)
            ->call('loadMore')
            ->assertSee('LP-OLDEST-DUTY-TOKEN')
            ->assertDontSee('$wire.loadMore()', false);
    }

    public function test_empty_state_shown_when_search_yields_no_matches(): void
    {
        $this->dept('AUEM');
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'AUEM']);
        Authority::factory()->create([
            'department_id' => 'AUEM', 'user_id' => $user->id,
            'details' => ['duty' => 'PRESENT-DUTY-TOKEN'],
        ]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('search', 'NONEXISTENT-SEARCH-TERM-XYZ')
            ->assertDontSee('PRESENT-DUTY-TOKEN')
            ->assertSee('هیچ اختیاری یافت نشد')
            ->assertSeeHtml('wire:key="authority-empty"');
    }

    public function test_row_index_renders_persian_digits(): void
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        Authority::factory()->create(['department_id' => 'IT', 'user_id' => $user->id]);

        $html = Livewire::actingAs($user)->test(Main::class)->html();

        $this->assertMatchesRegularExpression('/w-6 text-center">\s*۰۱/u', $html);
    }
}