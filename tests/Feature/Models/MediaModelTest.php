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
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ReflectionProperty;
use RuntimeException;
use Tests\TestCase;

class MediaModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->resetDbCharsetCache();
        DB::beginTransaction();
        DB::connection()->setTransactionManager(new class extends DatabaseTransactionsManager {
            public function afterCommitCallbacksShouldBeExecuted($level)
            {
                return $level <= 1;
            }
        });
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
        foreach ([false, null, '', 12, ['x'], base_path('missing-media-file.mp3')] as $path) {
            $this->assertNull(Media::probeDuration($path));
        }
    }

    public function test_a_rolled_back_update_or_delete_keeps_the_files_and_a_commit_removes_them(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/files/old.mp3', 'old');
        Storage::disk('public')->put('media/files/new.mp3', 'new');

        $media = Media::factory()->create(['file_path' => 'media/files/old.mp3']);

        try {
            DB::transaction(function () use ($media) {
                $media->update(['file_path' => 'media/files/new.mp3']);

                throw new RuntimeException('fail');
            });
        } catch (RuntimeException) {
        }

        Storage::disk('public')->assertExists('media/files/old.mp3');

        $media = Media::find($media->id);

        try {
            DB::transaction(function () use ($media) {
                $media->delete();

                throw new RuntimeException('fail');
            });
        } catch (RuntimeException) {
        }

        Storage::disk('public')->assertExists('media/files/old.mp3');

        DB::transaction(fn () => Media::find($media->id)->update(['file_path' => 'media/files/new.mp3']));

        Storage::disk('public')->assertMissing('media/files/old.mp3');
        Storage::disk('public')->assertExists('media/files/new.mp3');

        DB::transaction(fn () => Media::find($media->id)->delete());

        Storage::disk('public')->assertMissing('media/files/new.mp3');
    }

    public function test_a_series_delete_removes_episode_files_only_after_the_outer_commit(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/files/ep.mp3', 'ep');

        $series = Media::factory()->series()->create();
        $episode = Media::factory()->create(['parent_id' => $series->id, 'user_id' => $series->user_id, 'file_path' => 'media/files/ep.mp3']);

        try {
            DB::transaction(function () use ($series) {
                $series->delete();

                throw new RuntimeException('fail');
            });
        } catch (RuntimeException) {
        }

        Storage::disk('public')->assertExists('media/files/ep.mp3');
        $this->assertNotNull(Media::find($episode->id));

        DB::transaction(function () use ($series) {
            Media::find($series->id)->delete();

            Storage::disk('public')->assertExists('media/files/ep.mp3');
        });

        Storage::disk('public')->assertMissing('media/files/ep.mp3');
        $this->assertNull(Media::find($episode->id));
    }

    public function test_a_parent_must_be_a_different_root_series_and_a_series_cannot_have_a_parent(): void
    {
        $series = Media::factory()->series()->create();
        $other = Media::factory()->series()->create();
        $episode = Media::factory()->create(['parent_id' => $series->id, 'user_id' => $series->user_id]);
        $audio = Media::factory()->create();

        $this->assertValidationFails('data.parent_id', fn () => $other->update(['parent_id' => $series->id]));
        $this->assertValidationFails('data.parent_id', fn () => $series->update(['parent_id' => $series->id]));
        $this->assertValidationFails('data.parent_id', fn () => Media::factory()->series()->create(['parent_id' => $series->id]));
        $this->assertValidationFails('data.parent_id', fn () => Media::factory()->create(['parent_id' => $audio->id]));
        $this->assertValidationFails('data.parent_id', fn () => Media::factory()->create(['parent_id' => $episode->id]));
        $this->assertValidationFails('data.parent_id', fn () => $audio->update(['parent_id' => 999999999]));

        $audio->update(['parent_id' => $series->id]);

        $this->assertSame($series->id, $audio->fresh()->parent_id);
    }

    public function test_a_series_with_episodes_cannot_change_kind(): void
    {
        $series = Media::factory()->series()->create();
        $empty = Media::factory()->series()->create();
        Media::factory()->create(['parent_id' => $series->id, 'user_id' => $series->user_id]);

        $this->assertValidationFails('data.kind', fn () => $series->update(['kind' => Media::KIND_AUDIO]));

        $empty->update(['kind' => Media::KIND_AUDIO]);

        $this->assertSame(Media::KIND_AUDIO, $empty->fresh()->kind);
    }

    public function test_a_non_series_delete_runs_no_children_query(): void
    {
        $episode = Media::factory()->create();
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $episode->delete();

        $this->assertCount(0, array_filter($queries, fn (string $sql) => str_contains($sql, '`parent_id` = ?') && str_starts_with($sql, 'select')));
    }

    public function test_a_stored_zero_or_negative_duration_becomes_null(): void
    {
        $zero = Media::factory()->create(['duration_seconds' => 0]);
        $negative = Media::factory()->create(['duration_seconds' => 90]);
        $negative->update(['duration_seconds' => -5]);

        $this->assertNull($zero->fresh()->duration_seconds);
        $this->assertNull($negative->fresh()->duration_seconds);
    }

    private function assertValidationFails(string $key, callable $write): void
    {
        try {
            $write();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($key, $e->errors());

            return;
        }

        $this->fail('Expected a ValidationException for '.$key);
    }

    public function test_catalog_cache_is_department_scoped_and_changes_with_the_day(): void
    {
        ModelCacheVersion::bump(Media::class);
        $this->travelTo(today()->setTime(23, 59, 30));

        $this->assertSame(['d' => 'A'], Media::cachedCatalog('KMDA', fn () => ['d' => 'A']));
        $this->assertSame(['d' => 'B'], Media::cachedCatalog('KMDB', fn () => ['d' => 'B']));
        $this->assertSame(['d' => 'all'], Media::cachedCatalog(null, fn () => ['d' => 'all']));
        $this->assertSame(['d' => 'A'], Media::cachedCatalog('KMDA', fn () => ['d' => 'changed']));

        $this->travelTo(now()->addMinute());

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
