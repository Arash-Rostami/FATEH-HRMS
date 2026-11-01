<?php

namespace Tests\Feature\Models;

use App\Models\Department;
use App\Models\Profile;
use App\Models\Report;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Services\ContentSanitizerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ReflectionProperty;
use Tests\TestCase;

class ReportModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->resetDbCharsetCache();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        $this->resetDbCharsetCache();
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

    private function resetDbCharsetCache(): void
    {
        $prop = new ReflectionProperty(ContentSanitizerService::class, 'dbIsUtf8mb4');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    public function test_description_set_mutator_strips_control_characters(): void
    {
        $report = new Report();
        $report->description = "hello\x00world";

        $this->assertSame('helloworld', $report->getAttributes()['description']);
    }

    public function test_description_set_mutator_passes_null_through_unchanged(): void
    {
        $report = new Report();
        $report->description = null;

        $this->assertNull($report->getAttributes()['description']);
    }

    public function test_description_get_accessor_returns_sanitized_value_unchanged(): void
    {
        $report = new Report();
        $report->description = 'hello';

        $this->assertSame('hello', $report->description);
    }

    public function test_file_type_accessor_returns_lowercased_extension(): void
    {
        $report = new Report();
        $report->file_path = '/path/to/FILE.PDF';

        $this->assertSame('pdf', $report->file_type);
    }

    public function test_file_type_accessor_returns_unknown_when_file_path_null(): void
    {
        $report = new Report();

        $this->assertSame('unknown', $report->file_type);
    }

    public function test_thumbnail_accessor_returns_resolved_cover_image_when_present(): void
    {
        $report = new Report();
        $report->cover_image = 'https://example.com/cover.jpg';

        $this->assertSame('https://example.com/cover.jpg', $report->thumbnail);
    }

    public function test_thumbnail_accessor_returns_pdf_asset_for_pdf_file_type(): void
    {
        $report = new Report();
        $report->file_path = 'doc.pdf';

        $this->assertStringContainsString('build/assets/img/pdf.png', $report->thumbnail);
    }

    public function test_thumbnail_accessor_returns_doc_asset_for_docx_file_type(): void
    {
        $report = new Report();
        $report->file_path = 'doc.docx';

        $this->assertStringContainsString('build/assets/img/doc.png', $report->thumbnail);
    }

    public function test_thumbnail_accessor_returns_doc_asset_for_doc_file_type(): void
    {
        $report = new Report();
        $report->file_path = 'doc.doc';

        $this->assertStringContainsString('build/assets/img/doc.png', $report->thumbnail);
    }

    public function test_thumbnail_accessor_returns_default_asset_for_other_file_types(): void
    {
        $report = new Report();
        $report->file_path = 'archive.zip';

        $this->assertStringContainsString('build/assets/img/report.png', $report->thumbnail);
    }

    public function test_thumbnail_accessor_returns_default_asset_when_no_file_path(): void
    {
        $report = new Report();

        $this->assertStringContainsString('build/assets/img/report.png', $report->thumbnail);
    }

    public function test_thumbnail_accessor_caches_result_on_instance(): void
    {
        $report = new Report();
        $report->file_path = 'doc.pdf';
        $first = $report->thumbnail;
        $report->file_path = 'archive.zip';

        $this->assertSame($first, $report->thumbnail);
    }

    public function test_active_cast_round_trips_boolean_through_db(): void
    {
        $report = Report::factory()->create(['active' => true]);

        $this->assertTrue($report->fresh()->active);

        $report->active = false;
        $report->save();

        $this->assertFalse($report->fresh()->active);
    }

    public function test_scope_active_filters_to_active_reports(): void
    {
        $baseline = Report::active()->count();
        $active = Report::factory()->create(['active' => true]);
        $inactive = Report::factory()->create(['active' => false]);

        $ids = Report::active()->pluck('id')->all();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
        $this->assertSame($baseline + 1, Report::active()->count());
    }

    public function test_scope_inactive_filters_to_inactive_reports(): void
    {
        $baseline = Report::inactive()->count();
        $active = Report::factory()->create(['active' => true]);
        $inactive = Report::factory()->create(['active' => false]);

        $ids = Report::inactive()->pluck('id')->all();

        $this->assertContains($inactive->id, $ids);
        $this->assertNotContains($active->id, $ids);
        $this->assertSame($baseline + 1, Report::inactive()->count());
    }

    public function test_scope_by_department_filters_by_department_id(): void
    {
        $baseline = Report::byDepartment('ZZ-T1')->count();
        $in = Report::factory()->create(['department_id' => 'ZZ-T1']);
        $out = Report::factory()->create(['department_id' => 'ZZ-T2']);

        $ids = Report::byDepartment('ZZ-T1')->pluck('id')->all();

        $this->assertContains($in->id, $ids);
        $this->assertNotContains($out->id, $ids);
        $this->assertSame($baseline + 1, Report::byDepartment('ZZ-T1')->count());
    }

    public function test_scope_by_user_filters_by_user_id(): void
    {
        $alice = User::factory()->create(['status' => 'active']);
        $bob = User::factory()->create(['status' => 'active']);
        $baseline = Report::byUser($alice->id)->count();
        $in = Report::factory()->create(['user_id' => $alice->id]);
        $out = Report::factory()->create(['user_id' => $bob->id]);

        $ids = Report::byUser($alice->id)->pluck('id')->all();

        $this->assertContains($in->id, $ids);
        $this->assertNotContains($out->id, $ids);
        $this->assertSame($baseline + 1, Report::byUser($alice->id)->count());
    }

    public function test_description_set_mutator_strips_control_characters_through_db(): void
    {
        $report = Report::factory()->create(['description' => "hello\x00world"]);

        $this->assertSame('helloworld', $report->fresh()->description);
    }

    public function test_deleting_removes_cover_image_and_file_path_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('reports/cover.jpg', 'content');
        Storage::disk('public')->put('reports/file.pdf', 'content');
        $report = Report::factory()->create([
            'cover_image' => 'reports/cover.jpg',
            'file_path' => 'reports/file.pdf',
        ]);

        $report->delete();

        Storage::disk('public')->assertMissing('reports/cover.jpg');
        Storage::disk('public')->assertMissing('reports/file.pdf');
    }

    public function test_deleting_with_no_files_does_not_error(): void
    {
        Storage::fake('public');
        $report = Report::factory()->create([
            'cover_image' => null,
            'file_path' => '',
        ]);

        $report->delete();

        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }

    public function test_pinned_cast_round_trips_boolean_through_db(): void
    {
        $report = Report::factory()->create(['pinned' => true]);

        $this->assertTrue($report->fresh()->pinned);

        $report->pinned = false;
        $report->save();

        $this->assertFalse($report->fresh()->pinned);
    }

    public function test_departments_cast_serializes_array_and_reads_back_as_array(): void
    {
        $report = Report::factory()->create(['departments' => ['IT', 'HR']]);

        $this->assertSame(['IT', 'HR'], $report->fresh()->departments);
    }

    public function test_scope_pinned_filters_to_pinned_reports(): void
    {
        $pinned = Report::factory()->create(['pinned' => true]);
        $unpinned = Report::factory()->create(['pinned' => false]);

        $ids = Report::pinned()->pluck('id')->all();

        $this->assertContains($pinned->id, $ids);
        $this->assertNotContains($unpinned->id, $ids);
    }

    public function test_scope_not_expired_excludes_past_expires_at(): void
    {
        $expired = Report::factory()->create(['expires_at' => '2020-01-01']);
        $future = Report::factory()->create(['expires_at' => now()->addMonth()->toDateString()]);
        $none = Report::factory()->create(['expires_at' => null]);

        $ids = Report::notExpired()->pluck('id')->all();

        $this->assertNotContains($expired->id, $ids);
        $this->assertContains($future->id, $ids);
        $this->assertContains($none->id, $ids);
    }

    public function test_scope_visible_to_includes_public_reports_for_any_department(): void
    {
        $public = Report::factory()->create(['departments' => null]);

        $this->assertContains($public->id, Report::visibleTo('IT')->pluck('id')->all());
        $this->assertContains($public->id, Report::visibleTo(null)->pluck('id')->all());
    }

    public function test_scope_visible_to_includes_report_when_users_department_is_in_audience(): void
    {
        $restricted = Report::factory()->create(['departments' => ['IT', 'HR']]);

        $this->assertContains($restricted->id, Report::visibleTo('IT')->pluck('id')->all());
        $this->assertContains($restricted->id, Report::visibleTo('HR')->pluck('id')->all());
    }

    public function test_scope_visible_to_excludes_report_when_users_department_is_not_in_audience(): void
    {
        $restricted = Report::factory()->create(['departments' => ['HR']]);

        $this->assertNotContains($restricted->id, Report::visibleTo('IT')->pluck('id')->all());
    }

    public function test_for_viewer_hides_inactive_and_other_department_reports_and_ignores_the_owner_department_id(): void
    {
        foreach (['ZZ-RA', 'ZZ-RB'] as $code) {
            Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => '']);
        }
        $viewer = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $viewer->id, 'department_id' => 'ZZ-RA']);

        $public = Report::factory()->create(['departments' => null, 'active' => true, 'department_id' => 'ZZ-RB']);
        $own = Report::factory()->create(['departments' => ['ZZ-RA'], 'active' => true]);
        $foreign = Report::factory()->create(['departments' => ['ZZ-RB'], 'active' => true, 'department_id' => 'ZZ-RA']);
        $inactive = Report::factory()->create(['departments' => null, 'active' => false]);

        $ids = Report::forViewer($viewer)->pluck('id')->all();

        $this->assertContains($public->id, $ids);
        $this->assertContains($own->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
        $this->assertNotContains($inactive->id, $ids);

        foreach ([$public, $own, $foreign, $inactive] as $report) {
            $this->assertSame(in_array($report->id, $ids, true), Report::find($report->id)->isVisibleTo('ZZ-RA'));
        }
    }

    public function test_audience_users_are_limited_to_the_restricted_departments(): void
    {
        foreach (['ZZ-RA', 'ZZ-RB'] as $code) {
            Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => '']);
        }
        $inside = User::factory()->create(['status' => 'active']);
        $outside = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $inside->id, 'department_id' => 'ZZ-RA']);
        Profile::factory()->create(['user_id' => $outside->id, 'department_id' => 'ZZ-RB']);

        $ids = Report::factory()->create(['departments' => ['ZZ-RA'], 'active' => true])->audienceUsers()->pluck('id')->all();

        $this->assertContains($inside->id, $ids);
        $this->assertNotContains($outside->id, $ids);
    }

    public function test_is_public_accessor_returns_true_when_departments_empty(): void
    {
        $report = Report::factory()->make(['departments' => null]);

        $this->assertTrue($report->is_public);

        $report->departments = [];

        $this->assertTrue($report->is_public);
    }

    public function test_is_public_accessor_returns_false_when_departments_filled(): void
    {
        $report = Report::factory()->make(['departments' => ['IT']]);

        $this->assertFalse($report->is_public);
    }

    public function test_audience_departments_accessor_returns_empty_collection_when_public(): void
    {
        $report = Report::factory()->make(['departments' => null]);

        $this->assertTrue($report->audience_departments->isEmpty());
    }

    public function test_audience_departments_accessor_returns_matching_department_models(): void
    {
        ModelCacheVersion::bump(Department::class);
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        Department::firstOrCreate(['code' => 'HR'], ['name' => 'HR']);
        ModelCacheVersion::bump(Department::class);

        $report = Report::factory()->make(['departments' => ['IT', 'HR']]);

        $codes = $report->audience_departments->pluck('code')->all();

        $this->assertContains('IT', $codes);
        $this->assertContains('HR', $codes);
        $this->assertCount(2, $codes);
    }
}