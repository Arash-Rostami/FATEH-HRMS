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
        $this->assertStringStartsWith('/storage/', $media->file_url);
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

        $row = DB::table('media')->where('id', $media->id)->first();

        $this->assertSame(90, (int) $row->duration_seconds);
        $this->assertSame("hello\x00world", $row->description);
        $this->assertSame($stamp, $row->updated_at);
        Storage::disk('public')->assertExists('media/files/keep.mp3');
    }

    public function test_a_new_file_without_a_duration_is_probed_on_the_server_when_the_item_is_saved(): void
    {
        Storage::fake('public');
        $data = str_repeat("\x80", 24000);
        $wav = 'RIFF'.pack('V', 36 + strlen($data)).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 8000, 8000, 1, 8).'data'.pack('V', strlen($data)).$data;
        Storage::disk('public')->put('media/files/probe.wav', $wav);

        $media = Media::factory()->create(['file_path' => 'media/files/probe.wav', 'duration_seconds' => null]);

        $this->assertSame(3, (int) $media->fresh()->duration_seconds);
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

    public function test_a_series_never_keeps_a_file_duration_or_tracks(): void
    {
        $created = Media::factory()->series()->create([
            'file_path' => 'media/files/stray.mp3',
            'duration_seconds' => 90,
            'transcript_path' => 'media/tracks/stray.vtt',
            'chapters_path' => 'media/tracks/stray-chapters.vtt',
        ]);

        $created->update(['file_path' => 'media/files/stray-again.mp3', 'duration_seconds' => 45, 'title' => '0-Series Probe']);

        $single = Media::factory()->create(['kind' => Media::KIND_AUDIO, 'file_path' => 'media/files/single.mp3']);
        $this->assertValidationFails('data.kind', fn () => $single->update(['kind' => Media::KIND_SERIES]));
        $this->assertSame(Media::KIND_AUDIO, $single->fresh()->kind);

        $single->refresh()->update(['file_path' => null]);
        $single->update(['kind' => Media::KIND_SERIES]);

        foreach ([$created->fresh(), $single->fresh()] as $series) {
            $this->assertNull($series->getRawOriginal('file_path'));
            $this->assertNull($series->duration_seconds);
            $this->assertNull($series->transcript_path);
            $this->assertNull($series->chapters_path);
        }
    }

    public function test_an_episode_added_or_moved_under_a_series_is_appended_last(): void
    {
        $series = Media::factory()->series()->create();
        $other = Media::factory()->series()->create();
        $first = Media::factory()->create(['parent_id' => $series->id, 'user_id' => $series->user_id]);
        $second = Media::factory()->create(['parent_id' => $series->id, 'user_id' => $series->user_id]);
        $second->update(['position' => 7]);
        $third = Media::factory()->create(['parent_id' => $series->id, 'user_id' => $series->user_id]);
        $moved = Media::factory()->create();
        $moved->update(['parent_id' => $other->id]);

        $this->assertSame(1, $first->fresh()->position);
        $this->assertSame(8, $third->fresh()->position);
        $this->assertSame(1, $moved->fresh()->position);
    }

    public function test_a_fileless_single_created_without_an_explicit_active_is_refused(): void
    {
        $raw = Media::factory()->raw(['file_path' => null]);
        unset($raw['active']);

        $this->assertValidationFails('data.active', fn () => (new Media)->forceFill($raw)->save());

        $raw['active'] = false;
        (new Media)->forceFill($raw)->save();

        $this->assertDatabaseHas('media', ['title' => $raw['title'], 'active' => false]);
    }

    public function test_activating_a_fileless_single_is_blocked_only_at_the_moment_of_activation(): void
    {
        $draft = Media::factory()->create(['kind' => Media::KIND_AUDIO, 'file_path' => null, 'active' => false]);

        $this->assertValidationFails('data.active', fn () => $draft->update(['active' => true]));

        $draft->refresh()->update(['title' => '0-Draft Renamed']);

        $stale = Media::factory()->create(['kind' => Media::KIND_AUDIO, 'file_path' => null, 'active' => false]);
        $stale->forceFill(['active' => true])->saveQuietly();
        $stale->update(['title' => '0-Stale Renamed']);
        $stale->update(['active' => false]);

        $series = Media::factory()->series()->create(['active' => false]);
        $series->update(['active' => true]);

        $this->assertSame('0-Draft Renamed', $draft->fresh()->title);
        $this->assertSame('0-Stale Renamed', $stale->fresh()->title);
        $this->assertFalse($stale->fresh()->active);
        $this->assertTrue($series->fresh()->active);
    }

    public function test_series_saves_delete_the_stray_files_from_disk_but_keep_the_cover(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/files/stray.mp3', 'audio');
        Storage::disk('public')->put('media/tracks/stray.vtt', 'vtt');
        Storage::disk('public')->put('media/covers/cover.jpg', 'img');

        $series = Media::factory()->series()->create([
            'file_path' => 'media/files/stray.mp3',
            'transcript_path' => 'media/tracks/stray.vtt',
            'cover_image' => 'media/covers/cover.jpg',
        ]);

        $this->assertNull($series->fresh()->file_path);
        Storage::disk('public')->assertMissing('media/files/stray.mp3');
        Storage::disk('public')->assertMissing('media/tracks/stray.vtt');
        Storage::disk('public')->assertExists('media/covers/cover.jpg');

        Storage::disk('public')->put('media/files/legacy.mp3', 'audio');
        $legacy = Media::factory()->create(['kind' => Media::KIND_AUDIO, 'file_path' => 'media/files/legacy.mp3', 'active' => false]);
        $legacy->forceFill(['kind' => Media::KIND_SERIES])->saveQuietly();

        $legacy->update(['title' => '0-Legacy Series']);

        $this->assertNull($legacy->fresh()->file_path);
        Storage::disk('public')->assertMissing('media/files/legacy.mp3');
    }

    public function test_an_expiry_before_the_release_date_is_refused(): void
    {
        $item = Media::factory()->create(['kind' => Media::KIND_AUDIO, 'active' => false, 'report_date' => '2026-12-01', 'expires_at' => null]);

        $this->assertValidationFails('data.expires_at', fn () => $item->update(['expires_at' => '2026-11-01']));

        $item->refresh()->update(['expires_at' => '2026-12-20']);

        $this->assertSame('2026-12-20', $item->fresh()->expires_at->toDateString());
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
