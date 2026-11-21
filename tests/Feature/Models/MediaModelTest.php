<?php

namespace Tests\Feature\Models;

use App\Enums\MediaIcon;
use App\Models\Department;
use App\Models\Media;
use App\Models\Profile;
use App\Models\Report;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Services\ContentSanitizerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ReflectionProperty;
use Tests\TestCase;

class MediaModelTest extends TestCase
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

    public function test_description_mutator_strips_control_characters_and_keeps_null(): void
    {
        $media = new Media();
        $media->description = "hello\x00world";

        $this->assertSame('helloworld', $media->getAttributes()['description']);

        $media->description = null;

        $this->assertNull($media->getAttributes()['description']);
    }

    public function test_library_scope_lists_only_a_visible_released_parent(): void
    {
        foreach (['KMDA', 'KMDB'] as $code) {
            Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => '']);
        }

        $viewer = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $viewer->id, 'department_id' => 'KMDA']);

        $series = Media::factory()->series()->create(['departments' => null, 'active' => true]);
        $episode = Media::factory()->create(['parent_id' => $series->id, 'user_id' => $series->user_id]);
        $expired = Media::factory()->series()->expired()->create();
        $unreleased = Media::factory()->series()->locked()->create();
        $inactive = Media::factory()->series()->create(['active' => false]);
        $foreign = Media::factory()->series()->create(['departments' => ['KMDB']]);

        $ids = Media::library($viewer)->pluck('id')->all();

        $this->assertContains($series->id, $ids);
        $this->assertNotContains($episode->id, $ids);
        $this->assertNotContains($expired->id, $ids);
        $this->assertNotContains($unreleased->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
        $this->assertTrue($unreleased->fresh()->is_locked);
        $this->assertSame('سری', $series->kind_label);
    }

    public function test_file_url_is_a_public_url_not_the_storage_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/files/a.mp3', 'audio');

        $media = new Media();
        $media->file_path = 'media/files/a.mp3';

        $this->assertNotSame('media/files/a.mp3', $media->file_url);
        $this->assertStringStartsWith('http', $media->file_url);
    }

    public function test_series_delete_removes_episodes_and_their_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/files/ep.mp3', 'audio');
        Storage::disk('public')->put('media/covers/series.jpg', 'img');

        $series = Media::factory()->series()->create(['cover_image' => 'media/covers/series.jpg']);
        $episode = Media::factory()->create([
            'parent_id' => $series->id,
            'user_id' => $series->user_id,
            'file_path' => 'media/files/ep.mp3',
        ]);

        $series->delete();

        $this->assertNull(Media::find($series->id));
        $this->assertNull(Media::find($episode->id));
        Storage::disk('public')->assertMissing('media/files/ep.mp3');
        Storage::disk('public')->assertMissing('media/covers/series.jpg');
    }

    public function test_replacing_a_file_deletes_only_the_previous_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/files/old.mp3', 'old');
        Storage::disk('public')->put('media/files/new.mp3', 'new');

        $media = Media::factory()->create(['file_path' => 'media/files/old.mp3']);
        $media->update(['file_path' => 'media/files/new.mp3']);

        Storage::disk('public')->assertMissing('media/files/old.mp3');
        Storage::disk('public')->assertExists('media/files/new.mp3');
    }

    public function test_system_duration_write_skips_model_hooks(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/files/keep.mp3', 'audio');

        $media = Media::factory()->create([
            'file_path' => 'media/files/keep.mp3',
            'duration_seconds' => null,
        ]);
        $stamp = $media->updated_at->toDateTimeString();
        DB::table('media')->where('id', $media->id)->update(['description' => "hello\x00world"]);

        Media::writeSystemDurations([$media->id => 90]);
        Media::writePositions([$media->id => 3]);

        $row = DB::table('media')->where('id', $media->id)->first();

        $this->assertSame(90, (int) $row->duration_seconds);
        $this->assertSame(3, (int) $row->position);
        $this->assertSame("hello\x00world", $row->description);
        $this->assertSame($stamp, $row->updated_at);
        Storage::disk('public')->assertExists('media/files/keep.mp3');
    }

    public function test_probe_duration_returns_null_instead_of_throwing(): void
    {
        $this->assertNull(Media::probeDuration(''));
        $this->assertNull(Media::probeDuration(base_path('missing-media-file.mp3')));
    }

    public function test_catalog_cache_is_department_scoped_and_changes_with_the_day(): void
    {
        ModelCacheVersion::bump(Media::class);

        $this->assertSame(['d' => 'A'], Media::cachedCatalog('KMDA', fn () => ['d' => 'A']));
        $this->assertSame(['d' => 'B'], Media::cachedCatalog('KMDB', fn () => ['d' => 'B']));
        $this->assertSame(['d' => 'all'], Media::cachedCatalog(null, fn () => ['d' => 'all']));
        $this->assertSame(['d' => 'A'], Media::cachedCatalog('KMDA', fn () => ['d' => 'changed']));

        $this->travelTo(now()->addDay());

        $this->assertSame(['d' => 'next-day'], Media::cachedCatalog('KMDA', fn () => ['d' => 'next-day']));

        $this->travelBack();
    }

    public function test_catalog_cache_is_invalidated_by_a_media_save_and_a_dependent_model_version(): void
    {
        ModelCacheVersion::bump(Media::class);

        $this->assertSame(['v' => 1], Media::cachedCatalog('KMDA', fn () => ['v' => 1]));

        Media::factory()->series()->create();

        $this->assertSame(['v' => 2], Media::cachedCatalog('KMDA', fn () => ['v' => 2]));

        ModelCacheVersion::bump(Report::class);

        $this->assertSame(['v' => 3], Media::cachedCatalog('KMDA', fn () => ['v' => 3]));

        ModelCacheVersion::bump(User::class);

        $this->assertSame(['v' => 4], Media::cachedCatalog('KMDA', fn () => ['v' => 4]));
    }

    public function test_media_icon_has_a_heroicon_a_persian_label_and_a_material_name(): void
    {
        $this->assertGreaterThanOrEqual(30, count(MediaIcon::cases()));
        $this->assertContains(MediaIcon::default(), MediaIcon::cases());

        foreach (MediaIcon::cases() as $icon) {
            $this->assertStringStartsWith('heroicon-', $icon->value);
            $this->assertNotSame('', $icon->getLabel());
            $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $icon->getMaterialIcon());
        }

        $this->assertSame('headphones', MediaIcon::Headphones->getMaterialIcon());
        $this->assertSame('smart_display', MediaIcon::Video->getMaterialIcon());
    }
}
