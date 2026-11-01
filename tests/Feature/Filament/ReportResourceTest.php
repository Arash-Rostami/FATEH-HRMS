<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ReportResource\Pages\CreateReport;
use App\Filament\Resources\ReportResource\Pages\EditReport;
use App\Filament\Resources\ReportResource\Pages\ListReports;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Report;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Filament\Actions\DeleteAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ReportResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Storage::fake('public');
        ModelCacheVersion::bump(Department::class);
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        ModelCacheVersion::bump(Department::class);
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

    public function test_list_renders_and_shows_factory_report_row()
    {
        $admin = $this->developer();

        $report = Report::factory()->create(['title' => 'List Render Probe']);

        Livewire::actingAs($admin)->test(ListReports::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$report]);
    }

    public function test_active_toggle_and_bulk_visibility_actions_set_active_and_leave_pinned_untouched()
    {
        $admin = $this->developer();
        $pinned = Report::factory()->create(['title' => 'Vis Pinned Probe', 'pinned' => true, 'active' => true]);
        $plain = Report::factory()->create(['title' => 'Vis Plain Probe', 'pinned' => false, 'active' => true]);

        $component = Livewire::actingAs($admin)->test(ListReports::class)
            ->call('updateTableColumnState', 'active', (string) $plain->getKey(), false);

        $this->assertFalse((bool) $plain->fresh()->active);
        $component->assertCanSeeTableRecords([$plain->fresh(), $pinned]);

        $component->callTableBulkAction('bulk_deactivate', [$pinned, $plain]);
        $this->assertFalse((bool) $pinned->fresh()->active);
        $this->assertTrue((bool) $pinned->fresh()->pinned);
        $this->assertFalse((bool) $plain->fresh()->pinned);

        Livewire::actingAs($admin)->test(ListReports::class)
            ->callTableBulkAction('bulk_activate', [$pinned, $plain]);
        $this->assertTrue((bool) $pinned->fresh()->active);
        $this->assertTrue((bool) $plain->fresh()->active);
        $this->assertTrue((bool) $pinned->fresh()->pinned);
    }

    public function test_create_report_persists_title_and_user()
    {
        $admin = $this->developer();
        $author = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($admin)->test(CreateReport::class)
            ->fillForm([
                'title' => 'Created Report Title',
                'user_id' => $author->id,
                'file_path' => UploadedFile::fake()->create('sample.pdf', 100, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('reports', [
            'title' => 'Created Report Title',
            'user_id' => $author->id,
        ]);
    }

    public function test_create_report_requires_a_title()
    {
        $admin = $this->developer();
        $author = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($admin)->test(CreateReport::class)
            ->fillForm([
                'title' => null,
                'user_id' => $author->id,
                'file_path' => UploadedFile::fake()->create('sample.pdf', 100, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    }

    public function test_edit_report_updates_title_and_persists()
    {
        $admin = $this->developer();

        Storage::disk('public')->put('reports/files/orig.pdf', 'x');
        $report = Report::factory()->create([
            'title' => 'Original Report Title',
            'file_path' => 'reports/files/orig.pdf',
        ]);

        Livewire::actingAs($admin)->test(EditReport::class, ['record' => $report->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['title' => 'Updated Report Title'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated Report Title', $report->fresh()->title);
    }

    public function test_delete_table_action_removes_report_row()
    {
        $admin = $this->developer();

        $report = Report::factory()->create(['title' => 'Delete Probe']);

        Livewire::actingAs($admin)->test(ListReports::class)
            ->callTableAction(DeleteAction::class, $report);

        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }

    public function test_active_filter_scopes_list_to_active_reports_only()
    {
        $admin = $this->developer();

        $active = Report::factory()->create(['title' => 'Active Probe', 'active' => true]);
        $inactive = Report::factory()->create(['title' => 'Inactive Probe', 'active' => false]);

        Livewire::actingAs($admin)->test(ListReports::class)
            ->assertSuccessful()
            ->filterTable('active', ['value' => 1])
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_visibility_filter_scopes_to_restricted_reports()
    {
        $admin = $this->developer();

        $restricted = Report::factory()->create(['title' => 'Restricted Probe', 'departments' => ['IT']]);
        $public = Report::factory()->create(['title' => 'Public Probe', 'departments' => null]);

        Livewire::actingAs($admin)->test(ListReports::class)
            ->assertSuccessful()
            ->filterTable('visibility', true)
            ->assertCanSeeTableRecords([$restricted])
            ->assertCanNotSeeTableRecords([$public]);
    }

    public function test_visibility_filter_scopes_to_public_reports()
    {
        $admin = $this->developer();

        $restricted = Report::factory()->create(['title' => 'Restricted Probe', 'departments' => ['IT']]);
        $public = Report::factory()->create(['title' => 'Public Probe', 'departments' => null]);

        Livewire::actingAs($admin)->test(ListReports::class)
            ->assertSuccessful()
            ->filterTable('visibility', '0')
            ->assertCanSeeTableRecords([$public])
            ->assertCanNotSeeTableRecords([$restricted]);
    }

    public function test_department_filter_scopes_list_to_matching_department()
    {
        $admin = $this->developer();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        Department::firstOrCreate(['code' => 'HR'], ['name' => 'HR', 'description' => 'HR']);
        ModelCacheVersion::bump(Department::class);

        $it = Report::factory()->create(['title' => 'IT Probe', 'department_id' => 'IT']);
        $hr = Report::factory()->create(['title' => 'HR Probe', 'department_id' => 'HR']);

        Livewire::actingAs($admin)->test(ListReports::class)
            ->assertSuccessful()
            ->filterTable('department_id', 'IT')
            ->assertCanSeeTableRecords([$it])
            ->assertCanNotSeeTableRecords([$hr]);
    }

    public function test_created_at_filter_scopes_by_from_until()
    {
        $admin = $this->developer();

        $old = Report::factory()->create(['title' => 'Old Created Probe', 'created_at' => '2026-01-15']);
        $recent = Report::factory()->create(['title' => 'Recent Created Probe', 'created_at' => '2026-03-20']);

        Livewire::actingAs($admin)->test(ListReports::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => '2026-02-01', 'until' => '2026-04-01'])
            ->assertCanSeeTableRecords([$recent])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_pinned_filter_scopes_to_pinned_reports()
    {
        $admin = $this->developer();

        $pinned = Report::factory()->create(['title' => 'Pinned Probe', 'pinned' => true]);
        $unpinned = Report::factory()->create(['title' => 'Unpinned Probe', 'pinned' => false]);

        Livewire::actingAs($admin)->test(ListReports::class)
            ->assertSuccessful()
            ->filterTable('pinned', true)
            ->assertCanSeeTableRecords([$pinned])
            ->assertCanNotSeeTableRecords([$unpinned]);
    }

    public function test_report_date_range_filter_scopes_by_from_until()
    {
        $admin = $this->developer();

        $jan = Report::factory()->create(['title' => 'January Probe', 'report_date' => '2026-01-15']);
        $mar = Report::factory()->create(['title' => 'March Probe', 'report_date' => '2026-03-20']);

        Livewire::actingAs($admin)->test(ListReports::class)
            ->assertSuccessful()
            ->filterTable('report_date_range', ['from' => '2026-02-01', 'until' => '2026-04-01'])
            ->assertCanSeeTableRecords([$mar])
            ->assertCanNotSeeTableRecords([$jan]);
    }

    public function test_share_with_departments_bulk_action_sets_audience_on_selected_reports()
    {
        $admin = $this->developer();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        Department::firstOrCreate(['code' => 'HR'], ['name' => 'HR', 'description' => 'HR']);
        ModelCacheVersion::bump(Department::class);

        $r1 = Report::factory()->create(['title' => 'Share A', 'departments' => null]);
        $r2 = Report::factory()->create(['title' => 'Share B', 'departments' => null]);

        Livewire::actingAs($admin)->test(ListReports::class)
            ->callTableBulkAction('share_with_departments', [$r1, $r2], data: ['departments' => ['IT', 'HR']]);

        $this->assertSame(['IT', 'HR'], $r1->fresh()->departments);
        $this->assertSame(['IT', 'HR'], $r2->fresh()->departments);
    }

    public function test_create_report_persists_departments_and_pinned()
    {
        $admin = $this->developer();
        $author = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        ModelCacheVersion::bump(Department::class);

        Livewire::actingAs($admin)->test(CreateReport::class)
            ->fillForm([
                'title' => 'Audience Report',
                'user_id' => $author->id,
                'departments' => ['IT'],
                'pinned' => true,
                'file_path' => UploadedFile::fake()->create('sample.pdf', 100, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $report = Report::where('title', 'Audience Report')->first();
        $this->assertNotNull($report);
        $this->assertSame(['IT'], $report->departments);
        $this->assertTrue($report->pinned);
    }

    public function test_edit_report_updates_departments_and_pinned()
    {
        $admin = $this->developer();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        ModelCacheVersion::bump(Department::class);

        Storage::disk('public')->put('reports/files/orig.pdf', 'x');
        $report = Report::factory()->create([
            'title' => 'Original Report Title',
            'file_path' => 'reports/files/orig.pdf',
            'departments' => null,
            'pinned' => false,
        ]);

        Livewire::actingAs($admin)->test(EditReport::class, ['record' => $report->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['departments' => ['IT'], 'pinned' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $report->fresh();
        $this->assertSame(['IT'], $fresh->departments);
        $this->assertTrue($fresh->pinned);
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Report::factory()->create(['title' => 'Group Render Probe']);

        $component = Livewire::actingAs($admin)->test(ListReports::class)->assertSuccessful();

        foreach (['active', 'department.description', 'pinned'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }
    }

    public function test_sort_by_id_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $first = Report::factory()->create(['title' => '0-sort-id-a']);
        $second = Report::factory()->create(['title' => '0-sort-id-b']);

        $component = Livewire::actingAs($admin)->test(ListReports::class)
            ->searchTable('0-sort-id-')
            ->sortTable('id')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sort_by_title_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $first = Report::factory()->create(['title' => '0-AAA-sort-title']);
        $second = Report::factory()->create(['title' => '0-ZZZ-sort-title']);

        $component = Livewire::actingAs($admin)->test(ListReports::class)
            ->searchTable('sort-title')
            ->sortTable('title')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('title', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sort_by_created_at_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $older = Report::factory()->create(['title' => '0-sort-created-old', 'created_at' => '2020-01-15']);
        $newer = Report::factory()->create(['title' => '0-sort-created-new', 'created_at' => '2030-01-15']);

        $component = Livewire::actingAs($admin)->test(ListReports::class)
            ->searchTable('0-sort-created-')
            ->sortTable('created_at')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_sort_by_report_date_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $older = Report::factory()->create(['title' => '0-sort-report-date-old', 'report_date' => '2020-01-15']);
        $newer = Report::factory()->create(['title' => '0-sort-report-date-new', 'report_date' => '2030-01-15']);

        $component = Livewire::actingAs($admin)->test(ListReports::class)
            ->searchTable('0-sort-report-date-')
            ->sortTable('report_date')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('report_date', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_sort_by_department_name_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $deptA = Department::firstOrCreate(['code' => 'ZZ-sort-a'], ['name' => 'AAA-sort-department', 'description' => 'x']);
        $deptZ = Department::firstOrCreate(['code' => 'ZZ-sort-z'], ['name' => 'ZZZ-sort-department', 'description' => 'x']);
        ModelCacheVersion::bump(Department::class);

        $first = Report::factory()->create(['title' => '0-sort-dept-a', 'department_id' => $deptA->code]);
        $second = Report::factory()->create(['title' => '0-sort-dept-z', 'department_id' => $deptZ->code]);

        $component = Livewire::actingAs($admin)->test(ListReports::class)
            ->searchTable('0-sort-dept-')
            ->sortTable('department.name')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('department.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sort_by_user_name_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $userA = User::factory()->create(['name' => '0-AAA-sort-user']);
        $userZ = User::factory()->create(['name' => '0-ZZZ-sort-user']);

        $first = Report::factory()->create(['title' => '0-sort-user-a', 'user_id' => $userA->id]);
        $second = Report::factory()->create(['title' => '0-sort-user-z', 'user_id' => $userZ->id]);

        $component = Livewire::actingAs($admin)->test(ListReports::class)
            ->searchTable('0-sort-user-')
            ->sortTable('user.name')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\ReportResource\Pages\CreateReport::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\ReportResource\Pages\EditReport::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\ReportResource\Pages\ListReports::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\ReportResource\Pages\CreateReport::class, \App\Filament\Resources\ReportResource\Pages\EditReport::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }

    public function test_edit_tracked_file_columns_delete_only_the_replaced_files(): void
    {
        $probe = new class { use \App\Filament\Traits\HandlesDroppedFiles { trackedFilePaths as public; deleteDroppedFiles as public; } };
        $tracked = (fn () => $this->trackedFileColumns())->call(new \App\Filament\Resources\ReportResource\Pages\EditReport());
        Storage::fake('public');
        foreach (['reports/covers/old.jpg', 'reports/covers/new.jpg', 'reports/files/kept.pdf'] as $path) {
            Storage::disk('public')->put($path, 'x');
        }
        $record = \App\Models\Report::factory()->create(['cover_image' => 'reports/covers/old.jpg', 'file_path' => 'reports/files/kept.pdf']);
        $before = $probe->trackedFilePaths($record, $tracked);
        $this->assertEqualsCanonicalizing(['reports/covers/old.jpg', 'reports/files/kept.pdf'], $before);

        $record->update(['cover_image' => 'reports/covers/new.jpg']);
        $probe->deleteDroppedFiles($record, $before, $tracked);

        Storage::disk('public')->assertMissing('reports/covers/old.jpg');
        Storage::disk('public')->assertExists('reports/files/kept.pdf');
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/ReportResource/Schemas/ReportTablePresenter.php'),
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
