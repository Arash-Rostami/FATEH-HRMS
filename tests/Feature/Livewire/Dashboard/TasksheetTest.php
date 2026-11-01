<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Enums\TaskActivityType;
use App\Livewire\Dashboard\Tasksheet\Actions\ExportTasksheetAction;
use App\Livewire\Dashboard\Tasksheet\Main;
use App\Livewire\Dashboard\Tasksheet\Presentation\TasksheetPresenter;
use App\Models\Department;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

/**
 * Covers Tasksheet\Main's readOnly mode resolution and, most importantly, that every
 * guarded mutating method is inert for a recipient on a shared/signed report.
 */
class TasksheetTest extends TestCase
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
        Auth::logout();
        Cache::flush();
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

    public function test_mounting_self_shows_read_only_false_and_shared_route_shows_read_only_true(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $target = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Main::class)
            ->assertSet('readOnly', false)
            ->assertSet('subjectUserId', $user->id);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Main::class, ['subject' => $target->id])
            ->assertSet('readOnly', true)
            ->assertSet('subjectUserId', $target->id);
    }

    public function test_guarded_mutating_methods_are_inert_for_a_recipient_on_a_shared_report(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $subject = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create();

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($recipient)->test(Main::class, ['subject' => $subject->id])
            ->assertSet('readOnly', true);

        $component->call('setPreset', 'last_month')->assertSet('preset', 'this_month');
        $component->call('setCustomRange')->assertSet('preset', 'this_month');
        $component->call('scopeToProject', $project->id)->assertSet('scopeProjectId', null);
        $component->call('shareWithManager')->assertNotDispatched('toast');

        $component->assertSet('subjectUserId', $subject->id);
    }

    public function test_tampering_with_the_signed_urls_query_string_causes_a_403_before_mounting(): void
    {
        $viewer = User::factory()->create(['status' => 'active']);
        $target = User::factory()->create(['status' => 'active']);
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $signedUrl = URL::temporarySignedRoute('tasksheet.shared', now()->addDays(14), [
            'subject' => $target->id,
            'project' => $projectA->id,
        ]);

        $tamperedUrl = str_replace('project=' . $projectA->id, 'project=' . $projectB->id, $signedUrl);
        $this->assertNotSame($signedUrl, $tamperedUrl);

        $this->actingAs($viewer)->get($tamperedUrl)->assertStatus(403);
        $this->actingAs($viewer)->get($signedUrl)->assertOk();
    }

    public function test_baseline_window_toggle_shifts_back_one_equal_period_and_restores(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($user)->test(Main::class);
        $current = $component->instance()->window();

        $component->call('toggleBaselineWindow')->assertSet('viewingBaseline', true);

        $baseline = $component->instance()->window();
        $this->assertEquals($current['start']->copy()->subSecond()->toDateTimeString(), $baseline['end']->toDateTimeString());
        $this->assertEqualsWithDelta(
            $current['start']->diffInSeconds($current['end']),
            $baseline['start']->diffInSeconds($baseline['end']),
            2
        );

        $component->call('toggleBaselineWindow')->assertSet('viewingBaseline', false);

        $restored = $component->instance()->window();
        $this->assertEquals($current['start']->toDateTimeString(), $restored['start']->toDateTimeString());
        $this->assertEquals($current['end']->toDateTimeString(), $restored['end']->toDateTimeString());
    }

    public function test_toggle_baseline_window_is_inert_for_a_shared_report_recipient(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $subject = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($recipient)->test(Main::class, ['subject' => $subject->id])
            ->assertSet('readOnly', true)
            ->call('toggleBaselineWindow')
            ->assertSet('viewingBaseline', false);
    }

    public function test_read_only_recipient_cannot_tamper_with_viewing_baseline_via_raw_set(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $subject = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($recipient)->test(Main::class, ['subject' => $subject->id])
            ->assertSet('readOnly', true)
            ->set('viewingBaseline', true)
            ->assertStatus(403);
    }

    public function test_changing_the_preset_or_custom_range_exits_baseline_view(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($user)->test(Main::class)
            ->call('toggleBaselineWindow')
            ->assertSet('viewingBaseline', true)
            ->call('setPreset', 'last_month')
            ->assertSet('preset', 'last_month')
            ->assertSet('viewingBaseline', false);

        $component->call('toggleBaselineWindow')
            ->assertSet('viewingBaseline', true)
            ->set('fromYear', 1402)->set('fromMonth', 1)->set('fromDay', 1)
            ->set('toYear', 1402)->set('toMonth', 6)->set('toDay', 15)
            ->call('setCustomRange')
            ->assertSet('preset', 'custom')
            ->assertSet('viewingBaseline', false);
    }

    public function test_toggle_activity_and_load_more_activity_work_even_in_read_only_mode(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $subject = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($recipient)->test(Main::class, ['subject' => $subject->id])
            ->assertSet('readOnly', true)
            ->assertSet('activityOpen', false)
            ->call('toggleActivity')
            ->assertSet('activityOpen', true)
            ->assertSet('activityLimit', 50)
            ->call('loadMoreActivity')
            ->assertSet('activityLimit', 100);
    }

    public function test_share_with_manager_requires_a_recipient_and_sends_to_the_chosen_one(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($subject)->test(Main::class)
            ->assertSet('shareRecipientId', null);

        $component->call('shareWithManager')
            ->assertDispatched('toast', type: 'warning');

        $component->set('shareTarget', 'other')
            ->set('shareRecipientId', $recipient->id)
            ->call('shareWithManager')
            ->assertDispatched('toast', type: 'success');
    }

    public function test_share_link_carries_the_senders_window_params_not_the_default_month(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $subject->id]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($subject)->test(Main::class)
            ->set('fromYear', 1402)->set('fromMonth', 1)->set('fromDay', 1)
            ->set('toYear', 1402)->set('toMonth', 6)->set('toDay', 15)
            ->call('setCustomRange')
            ->call('scopeToProject', $project->id)
            ->set('shareTarget', 'other')
            ->set('shareRecipientId', $recipient->id)
            ->call('shareWithManager')
            ->assertDispatched('toast', type: 'success');

        $rows = DB::table('notifications')->where('notifiable_id', $recipient->id)->get();
        $this->assertCount(1, $rows);

        $url = json_decode($rows[0]->data, true)['actions'][0]['url'];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('custom', $query['preset'] ?? null);
        $this->assertStringContainsString('/tasksheet/shared/' . $subject->id, $url);
        $this->assertSame('1402', (string) ($query['fy'] ?? ''));
        $this->assertSame('1', (string) ($query['fm'] ?? ''));
        $this->assertSame('1', (string) ($query['fd'] ?? ''));
        $this->assertSame('1402', (string) ($query['ty'] ?? ''));
        $this->assertSame('6', (string) ($query['tm'] ?? ''));
        $this->assertSame('15', (string) ($query['td'] ?? ''));
        $this->assertSame((string) $project->id, (string) ($query['project'] ?? ''));
    }

    public function test_interactive_user_can_set_custom_date_range_via_wire_model_and_apply_it(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Main::class)
            ->assertSet('readOnly', false)
            ->set('fromYear', 1402)
            ->set('fromMonth', 1)
            ->set('fromDay', 1)
            ->set('toYear', 1402)
            ->set('toMonth', 6)
            ->set('toDay', 15)
            ->call('setCustomRange')
            ->assertHasNoErrors()
            ->assertSet('preset', 'custom');
    }

    public function test_custom_range_renders_through_the_shared_date_span_popover_and_presets_stay_visible(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Main::class)
            ->assertSeeHtml('title="فیلتر بازه تاریخ"')
            ->assertSeeHtml('$wire.setCustomRange()')
            ->assertDontSeeHtml('$wire.applyDateSpan()')
            ->assertDontSeeHtml('$wire.clearDateSpan()')
            ->assertDontSeeHtml('$wire.setDateBasis(')
            ->assertDontSeeHtml('bg-[var(--md-sys-color-error)]"></span>')
            ->assertSee('این ماه')
            ->assertSee('ماه گذشته')
            ->assertSee('این فصل');

        $component
            ->set('fromYear', 1402)->set('fromMonth', 1)->set('fromDay', 1)
            ->set('toYear', 1402)->set('toMonth', 6)->set('toDay', 15)
            ->call('setCustomRange')
            ->assertSet('preset', 'custom')
            ->assertSeeHtml('bg-[var(--md-sys-color-error)]"></span>')
            ->call('setPreset', 'last_month')
            ->assertSet('preset', 'last_month')
            ->assertDontSeeHtml('bg-[var(--md-sys-color-error)]"></span>');
    }

    public function test_read_only_recipient_cannot_tamper_with_date_window_properties_via_raw_set(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $subject = User::factory()->create(['status' => 'active']);

        foreach (['fromYear', 'fromMonth', 'fromDay', 'toYear', 'toMonth', 'toDay'] as $property) {
            Livewire::withoutLazyLoading();
            Livewire::actingAs($recipient)->test(Main::class, ['subject' => $subject->id])
                ->assertSet('readOnly', true)
                ->set($property, 1402)
                ->assertStatus(403);
        }
    }

    public function test_read_only_recipient_cannot_tamper_with_locked_preset_or_scope_via_raw_set(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $subject = User::factory()->create(['status' => 'active']);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($recipient)->test(Main::class, ['subject' => $subject->id])
            ->set('preset', 'custom');
    }

    public function test_read_only_recipient_cannot_tamper_with_locked_scope_via_raw_set(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $subject = User::factory()->create(['status' => 'active']);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($recipient)->test(Main::class, ['subject' => $subject->id])
            ->set('scopeProjectId', 1);
    }

    public function test_admin_entry_point_query_param_resolves_subject_and_does_not_set_read_only(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $target = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($admin)->withQueryParams(['user' => $target->id])->test(Main::class)
            ->assertSet('readOnly', false)
            ->assertSet('subjectUserId', $target->id);
    }

    public function test_plain_user_cannot_impersonate_another_subject_via_query_param(): void
    {
        $viewer = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $target = User::factory()->create(['status' => 'active']);

        $this->actingAs($viewer)->get(route('tasksheet', ['user' => $target->id]))
            ->assertStatus(403);
    }

    public function test_developer_can_view_another_subjects_report_via_query_param(): void
    {
        $developer = User::factory()->create(['status' => 'active', 'role' => 'developer']);
        $target = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($developer)->withQueryParams(['user' => $target->id])->test(Main::class)
            ->assertSet('readOnly', false)
            ->assertSet('subjectUserId', $target->id);
    }

    public function test_department_manager_can_view_a_subordinates_report_via_query_param(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Profile::factory()->for($manager)->create(['department_id' => $department->code, 'position' => 'manager']);
        $subordinate = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Profile::factory()->for($subordinate)->create(['department_id' => $department->code, 'position' => 'employee']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($manager)->withQueryParams(['user' => $subordinate->id])->test(Main::class)
            ->assertSet('readOnly', false)
            ->assertSet('subjectUserId', $subordinate->id);
    }

    public function test_viewing_a_deactivated_subjects_report_is_404_for_regular_viewers_and_open_for_admins(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $regular = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $target = User::factory()->create(['status' => 'inactive']);

        $this->actingAs($regular)->get(route('tasksheet', ['user' => $target->id]))
            ->assertStatus(404);

        $this->actingAs($admin)->get(route('tasksheet', ['user' => $target->id]))
            ->assertStatus(200);
    }

    public function test_scoping_to_a_project_filters_the_rendered_report_to_only_that_project(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $projectA = Project::factory()->create(['owner_id' => $user->id, 'name' => 'پروژه آلفای تست']);
        $projectB = Project::factory()->create(['owner_id' => $user->id, 'name' => 'پروژه بتای تست']);

        Auth::login($user);
        $taskA = Task::factory()->forProject($projectA)->create(['user_id' => $user->id, 'assigned_to' => $user->id, 'status' => 'todo']);
        Task::find($taskA->id)->update(['status' => 'done']);
        $taskB = Task::factory()->forProject($projectB)->create(['user_id' => $user->id, 'assigned_to' => $user->id, 'status' => 'todo']);
        Task::find($taskB->id)->update(['status' => 'done']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Main::class)
            ->call('scopeToProject', $projectA->id)
            ->assertSet('scopeProjectId', $projectA->id)
            ->assertSee('پروژه آلفای تست')
            ->assertDontSee('پروژه بتای تست');
    }

    public function test_scoping_to_a_project_with_no_activity_in_window_still_shows_a_clear_scope_escape_hatch(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $user->id]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Main::class)
            ->call('scopeToProject', $project->id)
            ->assertSet('scopeProjectId', $project->id)
            ->assertSee('نمایش همهٔ پروژه‌ها')
            ->assertSee('در این بازه، فعالیتی برای این پروژه ثبت نشده است.')
            ->assertSeeHtml('wire:key="tasksheet-projects-scoped-empty"');
    }

    public function test_custom_range_over_one_year_is_capped_with_a_toast_and_the_picker_fields_are_resynced(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Main::class)
            ->set('fromYear', 1400)->set('fromMonth', 1)->set('fromDay', 1)
            ->set('toYear', 1403)->set('toMonth', 1)->set('toDay', 1)
            ->call('setCustomRange')
            ->assertDispatched('toast', type: 'warning')
            ->assertSet('preset', 'custom');

        $this->assertNotSame(1403, $component->get('toYear'));

        $window = $component->instance()->window();
        $this->assertLessThanOrEqual(366, $window['start']->diffInDays($window['end']));
    }

    public function test_custom_range_over_one_year_loaded_from_url_caps_the_end_at_add_year_end_of_day(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)
            ->withQueryParams([
                'preset' => 'custom',
                'fy' => 1400, 'fm' => 1, 'fd' => 1,
                'ty' => 1403, 'tm' => 1, 'td' => 1,
            ])
            ->test(Main::class);

        $window = $component->instance()->window();

        $this->assertSame(
            $window['start']->copy()->addDays(364)->endOfDay()->toDateTimeString(),
            $window['end']->toDateTimeString()
        );
        $this->assertLessThanOrEqual(366, $window['start']->diffInDays($window['end']));
    }

    public function test_scorecard_tile_renders_a_sparkline_when_a_baseline_exists(): void
    {
        $subject = User::factory()->create(['status' => 'active']);

        $this->completeTaskAt($subject, now());
        $this->completeTaskAt($subject, now()->subDay()->setTime(1, 0));
        $this->completeTaskAt($subject, now()->subDay()->setTime(2, 0));
        $this->completeTaskAt($subject, now()->subDay()->setTime(3, 0));

        $today = Jalalian::now();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($subject)->test(Main::class)
            ->set('fromYear', $today->getYear())->set('fromMonth', $today->getMonth())->set('fromDay', $today->getDay())
            ->set('toYear', $today->getYear())->set('toMonth', $today->getMonth())->set('toDay', $today->getDay())
            ->call('setCustomRange')
            ->assertSeeHtml('width="20" height="14"');
    }

    public function test_delta_chip_suffix_is_empty_for_counts_and_days_for_cycle_time_but_percent_for_on_time(): void
    {
        $report = ['scorecard' => [
            'completed' => ['value' => 10, 'previous' => 8],
            'on_time_percent' => ['value' => 80.0, 'previous' => 60.0, 'delta_percent' => 20.0],
            'cycle_time_days' => ['median' => 5.0, 'previous_median' => 3.0],
            'approvals_received' => ['value' => 4, 'previous' => 2],
            'still_overdue' => 0,
            'in_progress' => 0,
        ], 'projects' => [], 'standalone' => null];

        $tiles = (new TasksheetPresenter())->scorecardBlocks($report)['tiles'];

        $this->assertSame('قبلی: ۸', $tiles[0]['chip']['text']);
        $this->assertStringStartsWith('قبلی: ۶۰%', $tiles[1]['chip']['text']);
        $this->assertSame('قبلی: ۳ روز', $tiles[2]['chip']['text']);
        $this->assertSame('قبلی: ۲', $tiles[3]['chip']['text']);
    }

    public function test_cycle_time_chip_tone_is_inverted_while_the_arrow_keeps_its_true_direction(): void
    {
        $presenter = new TasksheetPresenter();

        $longer = $presenter->deltaChip(5.0, 3.0, ' روز', true);
        $shorter = $presenter->deltaChip(2.0, 3.0, ' روز', true);
        $moreDone = $presenter->deltaChip(5.0, 3.0, '');
        $flat = $presenter->deltaChip(3.0, 3.0, ' روز', true);

        $this->assertSame(['up', 'bad'], [$longer['direction'], $longer['tone']]);
        $this->assertSame(['down', 'good'], [$shorter['direction'], $shorter['tone']]);
        $this->assertSame(['up', 'good'], [$moreDone['direction'], $moreDone['tone']]);
        $this->assertSame('flat', $flat['tone']);
    }

    public function test_scorecard_tile_omits_the_sparkline_without_a_baseline(): void
    {
        $subject = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($subject)->test(Main::class)
            ->assertDontSeeHtml('width="20" height="14"');
    }

    public function test_export_produces_a_full_report_not_just_top_line_statistics(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $subject->id]);

        $projectTask = Task::factory()->ownedBy($subject)->withStatus('todo')->create([
            'project_id' => $project->id,
            'title' => 'Export project task probe',
        ]);
        Auth::login($subject);
        Task::find($projectTask->id)->update(['status' => 'done']);

        $standaloneTask = Task::factory()->ownedBy($subject)->withStatus('todo')->create([
            'title' => 'Export standalone task probe',
        ]);
        Task::find($standaloneTask->id)->update(['status' => 'done']);

        $response = app(ExportTasksheetAction::class)->execute($subject->fresh(), now()->startOfDay(), now()->endOfDay());

        ob_start();
        ($response->getCallback())();
        $bytes = (string) ob_get_clean();

        $tmp = tempnam(sys_get_temp_dir(), 'fateh_tasksheet_export_test_');
        file_put_contents($tmp, $bytes);

        $cells = [];
        $reader = new Reader();
        $reader->open($tmp);
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                foreach ($row->toArray() as $cell) {
                    $cells[] = (string) $cell;
                }
            }
            break;
        }
        $reader->close();
        @unlink($tmp);

        $text = implode(' | ', $cells);

        $this->assertStringContainsString('خلاصه عملکرد', $text);
        $this->assertStringContainsString('خلاصه به تفکیک پروژه', $text);
        $this->assertStringContainsString('جزئیات وظایف', $text);
        $this->assertStringContainsString($project->name, $text);
        $this->assertStringContainsString('Export project task probe', $text);
        $this->assertStringContainsString('بدون پروژه', $text);
        $this->assertStringContainsString('Export standalone task probe', $text);
    }

    private function completeTaskAt(User $subject, \Carbon\Carbon $completedAt): void
    {
        $task = Task::factory()->ownedBy($subject)->withStatus('todo')->create();
        Auth::login($subject);
        Task::find($task->id)->update(['status' => 'done']);

        DB::table('replies')
            ->where('repliable_id', $task->id)
            ->where('repliable_type', Task::class)
            ->where('type', TaskActivityType::StatusChange->value)
            ->update(['created_at' => $completedAt]);
    }
}
