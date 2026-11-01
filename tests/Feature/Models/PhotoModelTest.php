<?php

namespace Tests\Feature\Models;

use App\Models\Department;
use App\Models\Photo;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Once;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Once::flush();
        Cache::flush();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Once::flush();
        Cache::flush();
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

    public function test_event_date_cast_returns_date_instance(): void
    {
        $photo = new Photo(['event_date' => '2026-07-30']);

        $this->assertInstanceOf(\DateTimeInterface::class, $photo->event_date);
        $this->assertSame('2026-07-30', $photo->event_date->format('Y-m-d'));
    }

    public function test_path_cast_round_trips_array_in_memory(): void
    {
        $photo = new Photo();
        $photo->path = ['a.jpg', 'b.jpg'];

        $this->assertSame(['a.jpg', 'b.jpg'], $photo->path);
    }

    public function test_departments_cast_round_trips_array_in_memory(): void
    {
        $photo = new Photo();
        $photo->departments = ['D1', 'D2'];

        $this->assertSame(['D1', 'D2'], $photo->departments);
    }

    public function test_all_departments_returns_empty_when_no_departments_and_no_department_id(): void
    {
        $photo = new Photo();

        $this->assertSame([], $photo->all_departments);
    }

    public function test_all_departments_returns_just_department_id_when_departments_empty(): void
    {
        $photo = new Photo(['department_id' => 'D1']);

        $this->assertSame(['D1'], $photo->all_departments);
    }

    public function test_all_departments_prepends_department_id_to_departments(): void
    {
        $photo = new Photo(['department_id' => 'D1', 'departments' => ['D2', 'D3']]);

        $this->assertSame(['D1', 'D2', 'D3'], $photo->all_departments);
    }

    public function test_all_departments_deduplicates_with_array_unique(): void
    {
        $photo = new Photo(['department_id' => 'D1', 'departments' => ['D1', 'D2']]);

        $this->assertSame(['D1', 'D2'], array_values($photo->all_departments));
    }

    public function test_all_department_models_returns_empty_collection_when_no_codes(): void
    {
        $photo = new Photo();

        $this->assertTrue($photo->all_department_models->isEmpty());
    }

    public function test_all_department_models_filters_cached_models_by_codes(): void
    {
        Department::create(['code' => 'ZZ-P1', 'name' => 'P One', 'description' => '']);
        Department::create(['code' => 'ZZ-P2', 'name' => 'P Two', 'description' => '']);
        Department::create(['code' => 'ZZ-P3', 'name' => 'P Three', 'description' => '']);

        $photo = new Photo(['department_id' => 'ZZ-P1', 'departments' => ['ZZ-P2']]);

        $models = $photo->all_department_models;

        $this->assertCount(2, $models);
        $this->assertSame(['ZZ-P1', 'ZZ-P2'], $models->pluck('code')->all());
    }

    public function test_image_urls_returns_empty_when_path_empty(): void
    {
        $photo = new Photo();

        $this->assertSame([], $photo->image_urls);
    }

    public function test_image_urls_filters_empty_path_entries(): void
    {
        $photo = new Photo(['path' => ['a.jpg', '', 'b.jpg']]);

        $this->assertCount(2, $photo->image_urls);
    }

    public function test_image_urls_passes_http_urls_through_unchanged(): void
    {
        $photo = new Photo(['path' => ['https://example.com/a.jpg']]);

        $this->assertSame(['https://example.com/a.jpg'], $photo->image_urls);
    }

    public function test_image_urls_resolves_relative_path_via_public_disk_fallback(): void
    {
        $photo = new Photo(['path' => ['photos/a.jpg']]);

        $urls = $photo->image_urls;

        $this->assertStringContainsString('photos/a.jpg', $urls[0]);
    }

    public function test_image_urls_caches_result_on_instance(): void
    {
        $photo = new Photo(['path' => ['https://example.com/a.jpg']]);
        $first = $photo->image_urls;
        $photo->path = ['https://example.com/b.jpg'];

        $this->assertSame($first, $photo->image_urls);
    }

    public function test_scope_by_department_filters_by_department_code(): void
    {
        Photo::factory()->create(['department_id' => 'ZZ-SCOPE-A']);
        Photo::factory()->create(['department_id' => 'ZZ-SCOPE-A']);
        Photo::factory()->create(['department_id' => 'ZZ-SCOPE-B']);

        $results = Photo::byDepartment('ZZ-SCOPE-A')->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn(Photo $photo) => $photo->department_id === 'ZZ-SCOPE-A'));
    }

    public function test_path_cast_round_trips_array_through_db(): void
    {
        $photo = Photo::factory()->create(['path' => ['a.jpg', 'b.jpg']]);

        $fresh = Photo::find($photo->id);

        $this->assertSame(['a.jpg', 'b.jpg'], $fresh->path);
    }

    public function test_departments_cast_round_trips_array_through_db(): void
    {
        $photo = Photo::factory()->create(['departments' => ['D1', 'D2']]);

        $fresh = Photo::find($photo->id);

        $this->assertSame(['D1', 'D2'], $fresh->departments);
    }

    public function test_for_viewer_counts_legacy_department_id_and_hides_inactive_and_other_department_photos(): void
    {
        foreach (['ZZ-GA', 'ZZ-GB'] as $code) {
            Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => '']);
        }
        $viewer = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $viewer->id, 'department_id' => 'ZZ-GA']);

        $public = Photo::factory()->create(['department_id' => null, 'departments' => null]);
        $legacyOwn = Photo::factory()->create(['department_id' => 'ZZ-GA', 'departments' => null]);
        $jsonOwn = Photo::factory()->create(['department_id' => null, 'departments' => ['ZZ-GA']]);
        $legacyForeign = Photo::factory()->create(['department_id' => 'ZZ-GB', 'departments' => null]);
        $inactive = Photo::factory()->create(['department_id' => null, 'departments' => null, 'active' => false]);

        $ids = Photo::forViewer($viewer)->pluck('id')->all();

        $this->assertContains($public->id, $ids);
        $this->assertContains($legacyOwn->id, $ids);
        $this->assertContains($jsonOwn->id, $ids);
        $this->assertNotContains($legacyForeign->id, $ids);
        $this->assertNotContains($inactive->id, $ids);

        foreach ([$public, $legacyOwn, $jsonOwn, $legacyForeign, $inactive] as $photo) {
            $this->assertSame(in_array($photo->id, $ids, true), Photo::find($photo->id)->isVisibleTo('ZZ-GA'));
        }
    }

    public function test_audience_users_include_the_legacy_department_id_members_only(): void
    {
        foreach (['ZZ-GA', 'ZZ-GB'] as $code) {
            Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => '']);
        }
        $inside = User::factory()->create(['status' => 'active']);
        $outside = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $inside->id, 'department_id' => 'ZZ-GA']);
        Profile::factory()->create(['user_id' => $outside->id, 'department_id' => 'ZZ-GB']);

        $photo = Photo::factory()->create(['department_id' => 'ZZ-GA', 'departments' => null]);

        $this->assertSame(['ZZ-GA'], $photo->audienceCodes());
        $ids = $photo->audienceUsers()->pluck('id')->all();
        $this->assertContains($inside->id, $ids);
        $this->assertNotContains($outside->id, $ids);
    }

    public function test_deleting_removes_path_files_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('photos/a.jpg', 'content');
        Storage::disk('public')->put('photos/b.mp4', 'content');
        $photo = Photo::factory()->create(['path' => ['photos/a.jpg', 'photos/b.mp4']]);

        $photo->delete();

        Storage::disk('public')->assertMissing('photos/a.jpg');
        Storage::disk('public')->assertMissing('photos/b.mp4');
    }

    public function test_deleting_with_no_path_does_not_error(): void
    {
        Storage::fake('public');
        $photo = Photo::factory()->create(['path' => []]);

        $photo->delete();

        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
    }
}