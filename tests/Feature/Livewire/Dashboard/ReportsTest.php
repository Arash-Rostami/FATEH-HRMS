<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Tab\Reports;
use App\Models\Department;
use App\Models\Profile;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsTest extends TestCase
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

    private function makeDepartment(string $code): Department
    {
        return Department::factory()->create(['code' => $code, 'name' => $code, 'description' => $code]);
    }

    public function test_reports_render_and_toggle()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Report::factory()->create([
            'user_id' => $user->id,
            'active' => true,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Reports::class)
            ->assertHasNoErrors()
            ->call('toggleView', 'list')
            ->assertHasNoErrors()
            ->call('loadMore')
            ->assertHasNoErrors();
    }

    public function test_report_thumbnail_resolves_cover_image_to_public_url()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $report = Report::factory()->create([
            'user_id' => $user->id,
            'cover_image' => 'reports/covers/cover.jpg',
            'file_path' => 'report.pdf',
        ]);

        $thumbnail = $report->thumbnail;

        $this->assertStringStartsWith('http', $thumbnail);
        $this->assertNotEquals('reports/covers/cover.jpg', $thumbnail);
    }

    public function test_report_thumbnail_falls_back_to_file_type_asset()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $report = Report::factory()->create([
            'user_id' => $user->id,
            'cover_image' => null,
            'file_path' => 'report.pdf',
        ]);

        $this->assertStringEndsWith('build/assets/img/pdf.png', $report->thumbnail);
    }

    public function test_mount_restores_view_mode_from_session()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Session::put('reports_view_mode', 'list');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Reports::class);

        $this->assertSame('list', $component->instance()->view);
    }

    public function test_mount_defaults_to_card_view_when_session_unset()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Reports::class);

        $this->assertSame('card', $component->instance()->view);
    }

    public function test_toggle_view_to_list_sets_view_and_persists_to_session()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Reports::class)
            ->call('toggleView', 'list');

        $this->assertSame('list', $component->instance()->view);
        $this->assertSame('list', session('reports_view_mode'));
    }

    public function test_toggle_view_to_card_sets_view_and_persists_to_session()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Reports::class)
            ->call('toggleView', 'card');

        $this->assertSame('card', $component->instance()->view);
        $this->assertSame('card', session('reports_view_mode'));
    }

    public function test_toggle_view_with_invalid_value_is_ignored()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Reports::class)
            ->call('toggleView', 'grid');

        $this->assertSame('card', $component->instance()->view);
        $this->assertNull(session('reports_view_mode'));
    }

    public function test_search_filters_reports_by_title()
    {
        $marker = 'TTL' . uniqid();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $match = Report::factory()->create(['user_id' => $user->id, 'active' => true, 'title' => 'گزارش ' . $marker]);
        $other = Report::factory()->create(['user_id' => $user->id, 'active' => true, 'title' => 'بدون نشانگر', 'description' => 'بدون نشانگر']);

        Livewire::withoutLazyLoading();
        $reports = Livewire::actingAs($user)->test(Reports::class)->set('search', $marker)->instance()->reports;

        $this->assertTrue($reports->contains('id', $match->id));
        $this->assertFalse($reports->contains('id', $other->id));
    }

    public function test_search_filters_reports_by_description()
    {
        $marker = 'DSC' . uniqid();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $match = Report::factory()->create(['user_id' => $user->id, 'active' => true, 'title' => 'بدون نشانگر', 'description' => 'تحلیل ' . $marker]);
        $other = Report::factory()->create(['user_id' => $user->id, 'active' => true, 'title' => 'بدون نشانگر', 'description' => 'بدون نشانگر']);

        Livewire::withoutLazyLoading();
        $reports = Livewire::actingAs($user)->test(Reports::class)->set('search', $marker)->instance()->reports;

        $this->assertTrue($reports->contains('id', $match->id));
        $this->assertFalse($reports->contains('id', $other->id));
    }

    public function test_search_with_single_zero_character_still_filters()
    {
        $marker = 'ZR' . uniqid();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $match = Report::factory()->create(['user_id' => $user->id, 'active' => true, 'title' => '0' . $marker, 'description' => 'بدون رقم']);
        $other = Report::factory()->create(['user_id' => $user->id, 'active' => true, 'title' => 'گزارش بدون رقم', 'description' => 'بدون رقم هیچ']);

        Livewire::withoutLazyLoading();
        $reports = Livewire::actingAs($user)->test(Reports::class)->set('search', '0')->instance()->reports;

        $this->assertTrue($reports->contains('id', $match->id));
        $this->assertFalse($reports->contains('id', $other->id));
    }

    public function test_empty_search_string_returns_all_active_reports()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $r1 = Report::factory()->create(['user_id' => $user->id, 'active' => true, 'title' => 'M1' . uniqid()]);
        $r2 = Report::factory()->create(['user_id' => $user->id, 'active' => true, 'title' => 'M2' . uniqid()]);

        Livewire::withoutLazyLoading();
        $reports = Livewire::actingAs($user)->test(Reports::class)
            ->set('search', '')
            ->call('loadMore')
            ->instance()->reports;

        $this->assertTrue($reports->contains('id', $r1->id));
        $this->assertTrue($reports->contains('id', $r2->id));
    }

    public function test_search_excludes_inactive_reports()
    {
        $marker = 'INV' . uniqid();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Report::factory()->create(['user_id' => $user->id, 'active' => false, 'title' => $marker, 'description' => $marker]);

        Livewire::withoutLazyLoading();
        $reports = Livewire::actingAs($user)->test(Reports::class)->set('search', $marker)->instance()->reports;

        $this->assertCount(0, $reports);
    }

    public function test_active_filter_scopes_reports_to_selected_department()
    {
        $codeA = 'DPA' . substr(uniqid(), -5);
        $codeB = 'DPB' . substr(uniqid(), -5);
        $this->makeDepartment($codeA);
        $this->makeDepartment($codeB);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $inDept = Report::factory()->create(['user_id' => $user->id, 'active' => true, 'department_id' => $codeA]);
        $outDept = Report::factory()->create(['user_id' => $user->id, 'active' => true, 'department_id' => $codeB]);

        Livewire::withoutLazyLoading();
        $reports = Livewire::actingAs($user)->test(Reports::class)->set('activeFilter', $codeA)->instance()->reports;

        $this->assertTrue($reports->contains('id', $inDept->id));
        $this->assertFalse($reports->contains('id', $outDept->id));
    }

    public function test_active_filter_all_returns_reports_from_every_department()
    {
        $codeA = 'DPA' . substr(uniqid(), -5);
        $codeB = 'DPB' . substr(uniqid(), -5);
        $this->makeDepartment($codeA);
        $this->makeDepartment($codeB);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $r1 = Report::factory()->create(['user_id' => $user->id, 'active' => true, 'department_id' => $codeA]);
        $r2 = Report::factory()->create(['user_id' => $user->id, 'active' => true, 'department_id' => $codeB]);

        Livewire::withoutLazyLoading();
        $reports = Livewire::actingAs($user)->test(Reports::class)
            ->set('activeFilter', 'all')
            ->call('loadMore')
            ->instance()->reports;

        $this->assertTrue($reports->contains('id', $r1->id));
        $this->assertTrue($reports->contains('id', $r2->id));
    }

    public function test_departments_computed_returns_only_depts_with_active_reports()
    {
        $withReports = 'DWA' . substr(uniqid(), -5);
        $withoutReports = 'DWO' . substr(uniqid(), -5);
        $this->makeDepartment($withReports);
        $this->makeDepartment($withoutReports);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Report::factory()->create(['user_id' => $user->id, 'active' => true, 'department_id' => $withReports]);
        Report::factory()->create(['user_id' => $user->id, 'active' => false, 'department_id' => $withoutReports]);

        Livewire::withoutLazyLoading();
        $codes = Livewire::actingAs($user)->test(Reports::class)->instance()->departments->pluck('code')->all();

        $this->assertContains($withReports, $codes);
        $this->assertNotContains($withoutReports, $codes);
    }

    public function test_total_reports_counts_only_active_reports()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Livewire::withoutLazyLoading();
        $baseline = Livewire::actingAs($user)->test(Reports::class)->instance()->totalReports;
        Report::factory()->count(3)->create(['user_id' => $user->id, 'active' => true, 'departments' => null, 'expires_at' => null]);
        Report::factory()->count(2)->create(['user_id' => $user->id, 'active' => false, 'departments' => null, 'expires_at' => null]);

        Livewire::withoutLazyLoading();
        $total = Livewire::actingAs($user)->test(Reports::class)->instance()->totalReports;

        $this->assertSame($baseline + 3, $total);
    }

    public function test_has_more_pages_true_when_count_exceeds_per_page()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Report::factory()->count(6)->create(['user_id' => $user->id, 'active' => true]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Reports::class);

        $this->assertTrue($component->instance()->hasMorePages);
    }

    public function test_has_more_pages_false_when_scoped_count_within_per_page()
    {
        $code = 'DLO' . substr(uniqid(), -5);
        $this->makeDepartment($code);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Report::factory()->count(2)->create(['user_id' => $user->id, 'active' => true, 'department_id' => $code]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Reports::class)->set('activeFilter', $code);

        $this->assertFalse($component->instance()->hasMorePages);
    }

    public function test_has_more_pages_reflects_search_scope()
    {
        $marker = 'SRC' . uniqid();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Report::factory()->count(6)->create(['user_id' => $user->id, 'active' => true, 'title' => $marker, 'description' => $marker]);
        $other = Report::factory()->create(['user_id' => $user->id, 'active' => true, 'title' => 'بدون نشانگر', 'description' => 'بدون نشانگر']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Reports::class);
        $this->assertTrue($component->instance()->hasMorePages);

        $component->set('search', $marker);
        $this->assertTrue($component->instance()->hasMorePages);

        $component->set('search', 'بدون نشانگر');
        $this->assertFalse($component->instance()->hasMorePages);
    }

    public function test_load_more_increases_per_page_and_reveals_more_reports()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $mine = Report::factory()->count(6)->create(['user_id' => $user->id, 'active' => true]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Reports::class);
        $this->assertSame(5, $component->instance()->perPage);
        $beforeCount = $component->instance()->reports->count();

        $component->call('loadMore');
        $this->assertSame(15, $component->instance()->perPage);
        $after = $component->instance()->reports;

        $this->assertGreaterThan($beforeCount, $after->count());
        foreach ($mine as $r) {
            $this->assertTrue($after->contains('id', $r->id), "Report {$r->id} missing after loadMore");
        }
    }

    public function test_focus_record_opens_modal_for_active_report()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $report = Report::factory()->create(['user_id' => $user->id, 'active' => true]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Reports::class)
            ->call('focusRecord', $report->id);

        $this->assertTrue($component->instance()->showModal);
        $this->assertSame($report->id, $component->instance()->activeReportId);
    }

    public function test_focus_record_ignores_inactive_report()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $report = Report::factory()->create(['user_id' => $user->id, 'active' => false]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Reports::class)
            ->call('focusRecord', $report->id);

        $this->assertFalse($component->instance()->showModal);
        $this->assertNull($component->instance()->activeReportId);
    }

    public function test_focus_record_ignores_nonexistent_id()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Reports::class)
            ->call('focusRecord', 99999999);

        $this->assertFalse($component->instance()->showModal);
        $this->assertNull($component->instance()->activeReportId);
    }

    public function test_open_url_param_focuses_active_report_on_mount()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $report = Report::factory()->create(['user_id' => $user->id, 'active' => true]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->withQueryParams(['open' => $report->id])->test(Reports::class);

        $this->assertTrue($component->instance()->showModal);
        $this->assertSame($report->id, $component->instance()->activeReportId);
    }

    public function test_open_url_param_ignored_for_inactive_report_on_mount()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $report = Report::factory()->create(['user_id' => $user->id, 'active' => false]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->withQueryParams(['open' => $report->id])->test(Reports::class);

        $this->assertFalse($component->instance()->showModal);
        $this->assertNull($component->instance()->activeReportId);
    }

    public function test_download_missing_file_dispatches_error_toast()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $report = Report::factory()->create([
            'user_id' => $user->id,
            'active' => true,
            'file_path' => 'reports/missing-' . uniqid() . '.pdf',
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Reports::class)
            ->call('download', $report->id)
            ->assertDispatched('toast', message: 'فایل موجود نیست!', type: 'error');
    }

    public function test_download_inactive_report_dispatches_error_toast()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $report = Report::factory()->create([
            'user_id' => $user->id,
            'active' => false,
            'file_path' => 'reports/inactive-' . uniqid() . '.pdf',
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Reports::class)
            ->call('download', $report->id)
            ->assertDispatched('toast', message: 'گزارش در دسترس نیست!', type: 'error');
    }

    public function test_public_report_visible_to_user_in_any_department()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        $public = Report::factory()->create(['active' => true, 'departments' => null, 'department_id' => null]);

        Livewire::withoutLazyLoading();
        $reports = Livewire::actingAs($user)->test(Reports::class)->instance()->reports;

        $this->assertTrue($reports->contains('id', $public->id));
    }

    public function test_restricted_report_hidden_from_non_audience_user()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        $restricted = Report::factory()->create(['active' => true, 'departments' => ['HR'], 'department_id' => null]);

        Livewire::withoutLazyLoading();
        $reports = Livewire::actingAs($user)->test(Reports::class)->instance()->reports;

        $this->assertFalse($reports->contains('id', $restricted->id));
    }

    public function test_restricted_report_deep_link_download_and_totals_follow_the_viewers_department()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        Department::firstOrCreate(['code' => 'HR'], ['name' => 'HR', 'description' => 'HR']);
        $it = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $hr = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $it->id, 'department_id' => 'IT']);
        Profile::factory()->create(['user_id' => $hr->id, 'department_id' => 'HR']);
        $itBefore = Report::forViewer($it)->notExpired()->count();
        $hrBefore = Report::forViewer($hr)->notExpired()->count();
        $restricted = Report::factory()->create(['active' => true, 'departments' => ['HR'], 'department_id' => null]);
        $itTotal = Report::forViewer($it)->notExpired()->count();
        $hrTotal = Report::forViewer($hr)->notExpired()->count();

        Livewire::withoutLazyLoading();
        $itPage = Livewire::actingAs($it)->withQueryParams(['open' => $restricted->id])->test(Reports::class);
        $this->assertFalse($itPage->instance()->showModal);
        $this->assertSame($itBefore, $itTotal);
        $this->assertSame($itTotal, $itPage->instance()->totalReports);
        $itPage->call('download', $restricted->id)->assertDispatched('toast', message: 'گزارش در دسترس نیست!', type: 'error');

        Livewire::withoutLazyLoading();
        $hrPage = Livewire::actingAs($hr)->withQueryParams(['open' => $restricted->id])->test(Reports::class);
        $this->assertTrue($hrPage->instance()->showModal);
        $this->assertSame($hrTotal, $hrPage->instance()->totalReports);
        $this->assertSame($hrBefore + 1, $hrTotal);
    }

    public function test_restricted_report_visible_to_audience_user()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        $restricted = Report::factory()->create(['active' => true, 'departments' => ['IT'], 'department_id' => null]);

        Livewire::withoutLazyLoading();
        $reports = Livewire::actingAs($user)->test(Reports::class)->instance()->reports;

        $this->assertTrue($reports->contains('id', $restricted->id));
    }

    public function test_expired_report_hidden_from_user_panel()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        $expired = Report::factory()->create(['active' => true, 'expires_at' => '2020-01-01', 'departments' => null, 'department_id' => null]);

        Livewire::withoutLazyLoading();
        $reports = Livewire::actingAs($user)->test(Reports::class)->instance()->reports;

        $this->assertFalse($reports->contains('id', $expired->id));
    }

    public function test_report_with_future_expires_at_remains_visible()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        $future = Report::factory()->create(['active' => true, 'expires_at' => now()->addMonth()->toDateString(), 'departments' => null, 'department_id' => null]);

        Livewire::withoutLazyLoading();
        $reports = Livewire::actingAs($user)->test(Reports::class)->instance()->reports;

        $this->assertTrue($reports->contains('id', $future->id));
    }

    public function test_pinned_reports_sort_to_top_of_list()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        Report::query()->delete();

        $pinned = Report::factory()->create(['active' => true, 'pinned' => true, 'departments' => null, 'department_id' => null]);
        $pinned->forceFill(['created_at' => now()->subDays(10)])->save();
        $unpinned = Report::factory()->create(['active' => true, 'pinned' => false, 'departments' => null, 'department_id' => null]);
        $unpinned->forceFill(['created_at' => now()])->save();

        Livewire::withoutLazyLoading();
        $reports = Livewire::actingAs($user)->test(Reports::class)->instance()->reports;

        $this->assertSame($pinned->id, $reports->first()->id);
        $this->assertSame($unpinned->id, $reports->last()->id);
    }

    public function test_download_restricted_report_dispatches_error_toast_for_non_audience_user()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        $report = Report::factory()->create([
            'active' => true,
            'departments' => ['HR'],
            'department_id' => null,
            'file_path' => 'reports/restricted-' . uniqid() . '.pdf',
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Reports::class)
            ->call('download', $report->id)
            ->assertDispatched('toast', message: 'گزارش در دسترس نیست!', type: 'error');
    }

    public function test_download_restricted_report_passes_guard_for_audience_user()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        $report = Report::factory()->create([
            'active' => true,
            'departments' => ['IT'],
            'department_id' => null,
            'file_path' => 'reports/audience-' . uniqid() . '.pdf',
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Reports::class)
            ->call('download', $report->id)
            ->assertDispatched('toast', message: 'فایل موجود نیست!', type: 'error');
    }
}
