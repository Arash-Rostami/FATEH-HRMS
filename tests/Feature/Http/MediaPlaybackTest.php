<?php

namespace Tests\Feature\Http;

use App\Http\Controllers\MediaNoteController;
use App\Http\Controllers\MediaProgressController;
use App\Livewire\Dashboard\Media\Actions\MarkMediaSeenAction;
use App\Livewire\Dashboard\Media\Actions\SaveNoteAction;
use App\Livewire\Dashboard\Media\Actions\SaveProgressAction;
use App\Models\Department;
use App\Models\KnowledgeProgress;
use App\Models\Media;
use App\Models\MediaNote;
use App\Models\Profile;
use App\Models\Report;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class MediaPlaybackTest extends TestCase
{
    private User $viewer;

    private Media $episode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();

        Department::firstOrCreate(['code' => 'KMDA'], ['name' => 'KMDA', 'description' => '']);
        Department::firstOrCreate(['code' => 'KMDB'], ['name' => 'KMDB', 'description' => '']);
        $this->viewer = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $this->viewer->id, 'department_id' => 'KMDA']);

        $series = Media::factory()->series()->create(['departments' => null, 'active' => true]);
        $this->episode = Media::factory()->create([
            'parent_id' => $series->id,
            'user_id' => $series->user_id,
            'duration_seconds' => 100,
            'active' => true,
        ]);
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

    public function test_progress_keeps_the_furthest_point_and_skips_an_unchanged_row(): void
    {
        $item = $this->item(40, 30);

        $this->assertSame(1, SaveProgressAction::execute($this->viewer, [$item]));
        $this->assertSame(0, SaveProgressAction::execute($this->viewer, [$this->item(20, 30)]));

        $row = $this->progress();
        $this->assertSame(40, $row->position);
        $this->assertSame(30, $row->last_position);

        $this->assertSame(1, SaveProgressAction::execute($this->viewer, [$this->item(10, 15)]));
        $row->refresh();

        $this->assertSame(40, $row->position);
        $this->assertSame(15, $row->last_position);
    }

    public function test_progress_clamps_past_the_duration_and_ignores_what_the_viewer_cannot_play(): void
    {
        $this->assertSame(1, SaveProgressAction::execute($this->viewer, [$this->item(500, 500)]));
        $this->assertSame(105, $this->progress()->position);

        $hidden = Media::factory()->series()->create(['departments' => ['KMDB'], 'active' => true]);
        $secret = Media::factory()->create(['parent_id' => $hidden->id, 'user_id' => $hidden->user_id, 'duration_seconds' => 100]);
        $locked = Media::factory()->locked()->create(['parent_id' => $this->episode->parent_id, 'user_id' => $this->episode->user_id, 'duration_seconds' => 100]);

        $this->assertSame(0, SaveProgressAction::execute($this->viewer, [
            ['type' => 'media', 'id' => $secret->id, 'position' => 10, 'last_position' => 10, 'completed' => false, 'duration' => 100],
            ['type' => 'media', 'id' => $locked->id, 'position' => 10, 'last_position' => 10, 'completed' => false, 'duration' => 100],
            ['type' => 'nope', 'id' => $this->episode->id, 'position' => 1, 'last_position' => 1],
        ]));
        $this->assertNull(KnowledgeProgress::query()->where('media_id', $secret->id)->first());
        $this->assertNull(KnowledgeProgress::query()->where('media_id', $locked->id)->first());
    }

    public function test_completion_is_stamped_once_and_report_tracking_can_be_switched_off(): void
    {
        $this->assertSame(1, SaveProgressAction::execute($this->viewer, [$this->item(96, 96, true)]));
        $doneAt = $this->progress()->completed_at;
        $this->assertNotNull($doneAt);

        $this->assertSame(0, SaveProgressAction::execute($this->viewer, [$this->item(96, 96, true)]));
        $this->assertTrue($doneAt->eq($this->progress()->completed_at));

        config(['media.track_reports' => false]);
        $report = Report::factory()->create(['departments' => null, 'active' => true]);

        $this->assertSame(0, SaveProgressAction::execute($this->viewer, [[
            'type' => 'report',
            'id' => $report->id,
            'position' => 1,
            'last_position' => 1,
            'completed' => false,
            'duration' => 10,
        ]]));
        $this->assertNull(KnowledgeProgress::query()->where('report_id', $report->id)->first());
    }

    public function test_the_stored_duration_wins_over_the_client_and_an_unknown_duration_does_not_cap_real_progress(): void
    {
        SaveProgressAction::execute($this->viewer, [$this->item(5000, 5000, false, 172800)]);
        $this->assertSame(105, $this->progress()->position);

        $this->episode->update(['duration_seconds' => null]);
        KnowledgeProgress::query()->where('user_id', $this->viewer->id)->delete();

        SaveProgressAction::execute($this->viewer, [$this->item(900, 900, false, null)]);
        $this->assertSame(900, $this->progress()->position);
        $this->assertNull($this->progress()->completed_at);
    }

    public function test_the_completed_flag_stamps_completion_even_when_the_duration_is_unknown(): void
    {
        $this->episode->update(['duration_seconds' => null]);

        SaveProgressAction::execute($this->viewer, [$this->item(60, 60, true, null)]);

        $this->assertNotNull($this->progress()->completed_at);
    }

    public function test_a_note_on_an_unplayable_episode_or_with_a_malformed_body_is_refused(): void
    {
        $hidden = Media::factory()->series()->create(['departments' => ['KMDB'], 'active' => true]);
        $secret = Media::factory()->create(['parent_id' => $hidden->id, 'user_id' => $hidden->user_id]);

        $this->assertNull(SaveNoteAction::execute($this->viewer, 'add', ['media_id' => $secret->id, 'position' => 1, 'body' => 'x']));
        $this->assertNull(SaveNoteAction::execute($this->viewer, 'add', ['media_id' => $hidden->id, 'position' => 1, 'body' => 'x']));
        $this->assertNull(SaveNoteAction::execute($this->viewer, 'add', ['media_id' => $this->episode->id, 'position' => 1, 'body' => ['x']]));
        $this->assertNull(SaveNoteAction::execute($this->viewer, 'add', ['media_id' => $this->episode->id, 'position' => Media::MAX_SECONDS + 1, 'body' => 'x']));
        $this->assertSame(0, MediaNote::query()->whereIn('media_id', [$secret->id, $hidden->id, $this->episode->id])->count());
    }

    public function test_a_null_duration_is_backfilled_without_touching_the_row_clock(): void
    {
        $this->episode->update(['duration_seconds' => null]);
        $stamp = $this->episode->fresh()->updated_at->toDateTimeString();

        SaveProgressAction::execute($this->viewer, [$this->item(20, 20, false, 80)]);

        $row = DB::table('media')->where('id', $this->episode->id)->first();

        $this->assertSame(80, (int) $row->duration_seconds);
        $this->assertSame($stamp, $row->updated_at);
    }

    public function test_note_add_trims_the_body_and_delete_removes_only_the_owners_note(): void
    {
        $note = SaveNoteAction::execute($this->viewer, 'add', [
            'media_id' => $this->episode->id,
            'position' => 12,
            'body' => '  یادداشت  ',
        ]);

        $this->assertSame('یادداشت', $note->body);
        $this->assertNull(SaveNoteAction::execute($this->viewer, 'add', [
            'media_id' => $this->episode->id,
            'position' => 1,
            'body' => str_repeat('ا', 501),
        ]));

        $other = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->assertNull(SaveNoteAction::execute($other, 'delete', ['id' => $note->id]));
        $this->assertNotNull(MediaNote::find($note->id));

        $this->assertNotNull(SaveNoteAction::execute($this->viewer, 'delete', ['id' => $note->id]));
        $this->assertNull(MediaNote::find($note->id));
    }

    public function test_a_note_past_the_cap_is_refused_and_writes_no_row(): void
    {
        config(['media.notes_max' => 1]);

        $this->assertNotNull(SaveNoteAction::execute($this->viewer, 'add', [
            'media_id' => $this->episode->id,
            'position' => 1,
            'body' => 'اول',
        ]));
        $this->assertNull(SaveNoteAction::execute($this->viewer, 'add', [
            'media_id' => $this->episode->id,
            'position' => 2,
            'body' => 'دوم',
        ]));
        $this->assertSame(1, MediaNote::query()->where('user_id', $this->viewer->id)->where('media_id', $this->episode->id)->count());
    }

    public function test_progress_controller_answers_json_and_never_redirects(): void
    {
        $this->assertSame(401, $this->invoke(MediaProgressController::class, '/media/progress', ['items' => []], null, true)->getStatusCode());
        $this->assertSame(419, $this->invoke(MediaProgressController::class, '/media/progress', ['items' => []], $this->viewer, false)->getStatusCode());
        $this->assertSame(422, $this->invoke(MediaProgressController::class, '/media/progress', ['items' => 'bad'], $this->viewer, true)->getStatusCode());

        $ok = $this->invoke(MediaProgressController::class, '/media/progress', [
            'items' => [$this->item(8, 8)],
        ], $this->viewer, true);

        $this->assertSame(204, $ok->getStatusCode());
        $this->assertFalse($ok->isRedirection());
        $this->assertSame(8, $this->progress()->position);
    }

    public function test_notes_controller_returns_the_created_note_as_json(): void
    {
        $created = $this->invoke(MediaNoteController::class, '/media/notes', [
            'action' => 'add',
            'media_id' => $this->episode->id,
            'position' => 4,
            'body' => 'لحظه',
        ], $this->viewer, true);

        $this->assertSame(201, $created->getStatusCode());
        $this->assertFalse($created->isRedirection());
        $payload = json_decode($created->getContent(), true);
        $this->assertSame('لحظه', $payload['body']);
        $this->assertSame(4, $payload['position']);
        $this->assertArrayNotHasKey('file_path', $payload);

        $deleted = $this->invoke(MediaNoteController::class, '/media/notes', [
            'action' => 'delete',
            'id' => $payload['id'],
        ], $this->viewer, true);

        $this->assertSame(204, $deleted->getStatusCode());
    }

    public function test_a_duration_backfill_invalidates_the_catalog_but_plain_progress_never_does(): void
    {
        ModelCacheVersion::bump(Media::class);
        $this->assertSame(['v' => 1], Media::cachedCatalog('KMDA', fn () => ['v' => 1]));

        SaveProgressAction::execute($this->viewer, [$this->item(10, 10)]);

        $this->assertSame(['v' => 1], Media::cachedCatalog('KMDA', fn () => ['v' => 'stale-check']));

        DB::table('media')->where('id', $this->episode->id)->update(['duration_seconds' => null]);
        SaveProgressAction::execute($this->viewer, [$this->item(20, 20, false, 100)]);

        $this->assertSame(100, (int) $this->episode->fresh()->duration_seconds);
        $this->assertSame(['v' => 2], Media::cachedCatalog('KMDA', fn () => ['v' => 2]));
    }

    public function test_per_user_data_never_enters_the_catalog_cache_and_seen_marking_does_not_bump(): void
    {
        ModelCacheVersion::bump(Media::class);
        $this->assertSame(['v' => 1], Media::cachedCatalog('KMDA', fn () => ['v' => 1]));

        SaveProgressAction::execute($this->viewer, [$this->item(40, 30)]);
        SaveNoteAction::execute($this->viewer, 'add', ['media_id' => $this->episode->id, 'position' => 5, 'body' => 'note']);
        MarkMediaSeenAction::execute($this->viewer);

        $this->assertSame(['v' => 1], Media::cachedCatalog('KMDA', fn () => ['v' => 'stale-check']));
    }

    public function test_playable_for_and_unseen_by_always_read_live_rows(): void
    {
        $this->assertSame(100, (int) Media::playableFor($this->viewer, [$this->episode->id])->first()->duration_seconds);

        DB::table('media')->where('id', $this->episode->id)->update(['duration_seconds' => 77]);

        $this->assertSame(77, (int) Media::playableFor($this->viewer, [$this->episode->id])->first()->duration_seconds);

        $this->assertTrue(Media::unseenBy($this->viewer->id)->exists());

        MarkMediaSeenAction::execute($this->viewer);

        $this->assertFalse(Media::unseenBy($this->viewer->id)->exists());
    }

    private function item(int $position, int $last, bool $completed = false, ?int $duration = 100): array
    {
        return [
            'type' => 'media',
            'id' => $this->episode->id,
            'position' => $position,
            'last_position' => $last,
            'completed' => $completed,
            'duration' => $duration,
        ];
    }

    private function progress(): KnowledgeProgress
    {
        return KnowledgeProgress::query()->where('user_id', $this->viewer->id)->where('media_id', $this->episode->id)->firstOrFail();
    }

    private function invoke(string $controller, string $uri, array $payload, ?User $user, bool $withToken): Response
    {
        $request = Request::create($uri, 'POST', $payload);
        $request->headers->set('Accept', 'application/json');
        $session = $this->app['session.store'];
        $session->start();
        $request->setLaravelSession($session);

        if ($withToken) {
            $request->request->set('_token', $session->token());
        }

        $request->setUserResolver(fn () => $user);

        return $this->app->make($controller)($request);
    }
}
