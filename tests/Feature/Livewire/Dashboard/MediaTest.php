<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Media\Main;
use App\Livewire\Dashboard\Media\Presentation\MediaPresenter;
use App\Models\Department;
use App\Models\KnowledgeProgress;
use App\Models\Media;
use App\Models\MediaNote;
use App\Models\Profile;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class MediaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        config(['app.off' => []]);
        if (! Route::has('media')) {
            Route::get('/media', Main::class)->name('media');
            Route::post('/media/progress', fn () => response()->noContent())->name('media.progress');
            Route::post('/media/notes', fn () => response()->noContent())->name('media.notes');
        }
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

    private function media(User $user, array $attributes = []): Media
    {
        return Media::create(array_merge([
            'user_id' => $user->id, 'kind' => 'audio', 'title' => 'جلسهٔ نمونه',
            'active' => true, 'file_path' => 'media/files/example.mp3', 'duration_seconds' => 300,
        ], $attributes));
    }

    public function test_library_shape_single_episode_and_viewer_owned_progress_and_notes(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $item = $this->media($user);
        KnowledgeProgress::create(['user_id' => $user->id, 'media_id' => $item->id, 'position' => 100, 'last_position' => 40]);
        MediaNote::create(['user_id' => $user->id, 'media_id' => $item->id, 'position' => 40, 'body' => 'یادداشت من']);
        MediaNote::create(['user_id' => $other->id, 'media_id' => $item->id, 'position' => 10, 'body' => 'یادداشت دیگری']);
        $this->actingAs($user);
        Livewire::withoutLazyLoading();
        $component = Livewire::test(Main::class)->assertHasNoErrors();
        $payload = $component->instance()->library;
        $row = collect($payload['items'])->firstWhere('id', $item->id);
        $this->assertSame('single', $row['type']);
        $this->assertCount(1, $row['episodes']);
        $this->assertSame($item->id, $row['episodes'][0]['id']);
        $this->assertArrayHasKey('serverNow', $payload);
        $this->assertArrayNotHasKey('file_path', $row['episodes'][0]);
        $this->assertSame(40, ((array) $payload['progress'])[$item->id]['last_position']);
        $this->assertSame('یادداشت من', ((array) $payload['notes'])[$item->id][0]['body']);
        $this->assertStringNotContainsString('یادداشت دیگری', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function test_mounting_marks_unseen_media_seen_once_for_the_viewer(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $viewer = User::factory()->create(['status' => 'active']);
        $item = $this->media($owner);
        $this->actingAs($viewer);
        Livewire::withoutLazyLoading();
        Livewire::test(Main::class);
        $this->assertSame([$viewer->id], $item->fresh()->seen_by);
        Livewire::test(Main::class);
        $this->assertSame([$viewer->id], $item->fresh()->seen_by);
    }

    public function test_locked_episodes_never_expose_file_or_track_urls(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $series = $this->media($user, ['kind' => 'series', 'file_path' => null]);
        $episode = $this->media($user, [
            'parent_id' => $series->id, 'report_date' => now()->addDays(2)->toDateString(),
            'file_path' => 'media/files/locked-secret.mp3', 'transcript_path' => 'media/locked-secret.vtt',
            'chapters_path' => 'media/locked-chapters.vtt',
        ]);
        $this->actingAs($user);
        Livewire::withoutLazyLoading();
        $payload = Livewire::test(Main::class)->instance()->library;
        $row = collect($payload['items'])->firstWhere('id', $series->id)['episodes'][0];
        $this->assertSame($episode->id, $row['id']);
        foreach (['file_url', 'download_url', 'transcript_url', 'chapters_url'] as $key) {
            $this->assertNull($row[$key]);
        }
        $this->assertGreaterThan(now()->timestamp, $row['unlock_at']);
        $this->assertNotEmpty($row['unlock_label']);
        $this->assertStringNotContainsString('locked-secret', json_encode($payload));
    }

    public function test_inactive_expired_and_unreleased_parents_and_their_children_are_hidden(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $ids = [];
        foreach ([['active' => false], ['expires_at' => now()->subDay()->toDateString()], ['report_date' => now()->addDay()->toDateString()]] as $attributes) {
            $parent = $this->media($user, array_merge(['kind' => 'series'], $attributes));
            $child = $this->media($user, ['parent_id' => $parent->id]);
            $ids[] = $parent->id;
            $ids[] = $child->id;
        }
        $this->actingAs($user);
        $library = app(MediaPresenter::class)->library($user);
        $visible = collect($library['items'])->pluck('id')->all();
        foreach ($ids as $id) {
            $this->assertNotContains($id, $visible);
        }
    }

    public function test_audience_is_inherited_from_the_parent_and_hidden_reports_are_not_materials(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        Profile::updateOrCreate(['user_id' => $user->id], ['department_id' => $department->code]);
        $hidden = $this->media($user, ['kind' => 'series', 'departments' => [$otherDepartment->code]]);
        $child = $this->media($user, ['parent_id' => $hidden->id]);
        $publicReport = Report::factory()->create(['user_id' => $user->id, 'active' => true, 'departments' => []]);
        $hiddenReport = Report::factory()->create(['user_id' => $user->id, 'active' => true, 'departments' => [$otherDepartment->code]]);
        $visible = $this->media($user, ['report_ids' => [$publicReport->id, $hiddenReport->id, 2147483647]]);
        $this->actingAs($user);
        Livewire::withoutLazyLoading();
        $payload = Livewire::test(Main::class)->instance()->library;
        $this->assertNotContains($hidden->id, array_column($payload['items'], 'id'));
        $this->assertNotContains($child->id, array_column($payload['items'], 'id'));
        $row = collect($payload['items'])->firstWhere('id', $visible->id);
        $this->assertSame([$publicReport->id], array_column($row['materials'], 'id'));
    }

    public function test_two_departments_never_share_a_cached_catalog(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $first = Department::factory()->create();
        $second = Department::factory()->create();
        $inFirst = User::factory()->create(['status' => 'active']);
        $inSecond = User::factory()->create(['status' => 'active']);
        Profile::updateOrCreate(['user_id' => $inFirst->id], ['department_id' => $first->code]);
        Profile::updateOrCreate(['user_id' => $inSecond->id], ['department_id' => $second->code]);
        $item = $this->media($owner, ['departments' => [$first->code]]);
        $presenter = app(MediaPresenter::class);
        $this->assertContains($item->id, array_column($presenter->library($inFirst->fresh())['items'], 'id'));
        $this->assertNotContains($item->id, array_column($presenter->library($inSecond->fresh())['items'], 'id'));
        $this->assertContains($item->id, array_column($presenter->library($inFirst->fresh())['items'], 'id'));
    }

    public function test_progress_and_notes_are_never_part_of_the_cached_catalog(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $item = $this->media($user);
        KnowledgeProgress::create(['user_id' => $user->id, 'media_id' => $item->id, 'position' => 100, 'last_position' => 40]);
        MediaNote::create(['user_id' => $user->id, 'media_id' => $item->id, 'position' => 40, 'body' => 'یادداشت خصوصی']);
        $this->actingAs($user);
        $presenter = app(MediaPresenter::class);
        $presenter->library($user);
        $cached = Media::cachedCatalog($user->fresh()->profile?->department_id, fn () => []);
        $this->assertContains($item->id, array_column($cached, 'id'));
        $this->assertStringNotContainsString('یادداشت خصوصی', json_encode($cached, JSON_UNESCAPED_UNICODE));
        $this->assertArrayNotHasKey('progress', $cached[0]);
        $this->assertArrayNotHasKey('notes', $cached[0]);
        KnowledgeProgress::where('media_id', $item->id)->update(['position' => 200]);
        $this->assertSame(200, ((array) $presenter->library($user)['progress'])[$item->id]['position']);
    }

    public function test_department_label_falls_back_to_the_audience_departments(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $department = Department::factory()->create();
        Profile::updateOrCreate(['user_id' => $user->id], ['department_id' => $department->code]);
        $item = $this->media($user, ['departments' => [$department->code]]);
        $row = collect(app(MediaPresenter::class)->library($user->fresh())['items'])->firstWhere('id', $item->id);
        $this->assertSame($department->name, $row['department']);
    }

    public function test_inactive_and_expired_children_are_not_listed_in_a_visible_series(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $series = $this->media($user, ['kind' => 'series']);
        $visible = $this->media($user, ['parent_id' => $series->id]);
        $this->media($user, ['parent_id' => $series->id, 'active' => false]);
        $this->media($user, ['parent_id' => $series->id, 'expires_at' => now()->subDay()->toDateString()]);
        $this->actingAs($user);
        $payload = app(MediaPresenter::class)->library($user);
        $row = collect($payload['items'])->firstWhere('id', $series->id);
        $this->assertSame([$visible->id], array_column($row['episodes'], 'id'));
    }

    public function test_deep_link_accepts_visible_episode_but_rejects_hidden_parent_chain(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $series = $this->media($user, ['kind' => 'series']);
        $episode = $this->media($user, ['parent_id' => $series->id]);
        $hidden = $this->media($user, ['kind' => 'series', 'active' => false]);
        $hiddenEpisode = $this->media($user, ['parent_id' => $hidden->id]);
        $this->actingAs($user);
        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['open' => $episode->id, 't' => 25])->test(Main::class)
            ->assertSet('open', $episode->id)->assertSeeHtml('data-rf="media-' . $series->id . '"');
        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['open' => $hiddenEpisode->id])->test(Main::class)->assertSet('open', null);
        $locked = $this->media($user, ['parent_id' => $series->id, 'report_date' => now()->addDays(2)->toDateString()]);
        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['open' => $locked->id])->test(Main::class)->assertSet('open', null);
    }
}
