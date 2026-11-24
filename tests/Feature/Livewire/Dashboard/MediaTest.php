<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Knowledge\Library;
use App\Livewire\Dashboard\Knowledge\Presentation\MediaPresenter;
use App\Models\Department;
use App\Models\KnowledgeProgress;
use App\Models\Media;
use App\Models\MediaNote;
use App\Models\Profile;
use App\Models\Report;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
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
        $component = Livewire::test(Library::class)->assertHasNoErrors();
        $payload = $component->instance()->library;
        $row = collect($payload['items'])->firstWhere('id', $item->id);
        $this->assertSame('single', $row['type']);
        $this->assertCount(1, $row['episodes']);
        $this->assertSame($item->id, $row['episodes'][0]['id']);
        $this->assertArrayHasKey('serverNow', $payload);
        $this->assertArrayNotHasKey('file_path', $row['episodes'][0]);
        $this->assertSame(40, ((array) $payload['progress'])[$item->id]['last_position']);
        $this->assertSame(KnowledgeProgress::where('media_id', $item->id)->first()->updated_at->timestamp, ((array) $payload['progress'])[$item->id]['updated_at']);
        $this->assertSame('یادداشت من', ((array) $payload['notes'])[$item->id][0]['body']);
        $this->assertStringNotContainsString('یادداشت دیگری', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    private function isNew(User $viewer, Media $item): bool
    {
        $this->actingAs($viewer);
        Livewire::withoutLazyLoading();

        return collect(Livewire::test(Library::class)->instance()->library['items'])->firstWhere('id', $item->id)['is_new'];
    }

    public function test_opening_the_library_never_writes_and_keeps_a_series_new_and_episodes_never(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $viewer = User::factory()->create(['status' => 'active']);
        $series = $this->media($owner, ['kind' => 'series']);
        $episode = $this->media($owner, ['parent_id' => $series->id]);
        $this->actingAs($viewer);
        Livewire::withoutLazyLoading();
        $first = Livewire::test(Library::class);
        $row = collect($first->instance()->library['items'])->firstWhere('id', $series->id);
        $this->assertTrue($row['is_new']);
        $first->assertSee('جدید');
        $this->assertArrayNotHasKey('is_new', $row['episodes'][0]);
        $this->assertArrayNotHasKey('owner_id', $row);
        $this->assertArrayNotHasKey('new_until', $row);
        $this->assertArrayNotHasKey('download_url', $row['episodes'][0]);
        $this->assertTrue($this->isNew($viewer, $series));
        $this->assertTrue($this->isNew($viewer, $series));
    }

    public function test_the_owner_never_sees_new_and_played_state_is_left_to_the_client(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $viewer = User::factory()->create(['status' => 'active']);
        $single = $this->media($owner);
        $series = $this->media($owner, ['kind' => 'series']);
        $episode = $this->media($owner, ['parent_id' => $series->id]);
        $this->assertFalse($this->isNew($owner, $single));
        $this->assertTrue($this->isNew($viewer, $single));
        KnowledgeProgress::create(['user_id' => $viewer->id, 'media_id' => $episode->id, 'position' => 5, 'last_position' => 5]);
        $this->actingAs($viewer);
        Livewire::withoutLazyLoading();
        $payload = Livewire::test(Library::class)->instance()->library;
        $this->assertTrue(collect($payload['items'])->firstWhere('id', $series->id)['is_new']);
        $this->assertArrayHasKey($episode->id, (array) $payload['progress']);
    }

    public function test_items_older_than_new_days_are_never_new(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $viewer = User::factory()->create(['status' => 'active']);
        $dated = $this->media($owner, ['report_date' => now()->subDays(31)->toDateString()]);
        $recent = $this->media($owner, ['report_date' => now()->subDays(29)->toDateString()]);
        $created = $this->media($owner);
        DB::table('media')->where('id', $created->id)->update(['created_at' => now()->subDays(40)]);
        $this->assertFalse($this->isNew($viewer, $dated));
        $this->assertTrue($this->isNew($viewer, $recent));
        $this->assertFalse($this->isNew($viewer, $created));
        config(['media.new_days' => 60]);
        ModelCacheVersion::bump(Media::class);
        $this->assertTrue($this->isNew($viewer, $dated));
    }

    public function test_is_new_is_per_user_and_never_part_of_the_cached_catalog(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $viewer = User::factory()->create(['status' => 'active']);
        $item = $this->media($owner);
        $this->actingAs($viewer);
        Livewire::withoutLazyLoading();
        Livewire::test(Library::class);
        $cached = Media::cachedCatalog($viewer->fresh()->profile?->department_id, fn () => []);
        $this->assertContains($item->id, array_column($cached, 'id'));
        $this->assertStringNotContainsString('is_new', json_encode($cached));
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
        $payload = Livewire::test(Library::class)->instance()->library;
        $row = collect($payload['items'])->firstWhere('id', $series->id)['episodes'][0];
        $this->assertSame($episode->id, $row['id']);
        foreach (['file_url', 'transcript_url', 'chapters_url'] as $key) {
            $this->assertNull($row[$key]);
        }
        $this->assertNull($row['mime']);
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
        $payload = Livewire::test(Library::class)->instance()->library;
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
        KnowledgeProgress::where('media_id', $item->id)->update(['last_seen_at' => '2026-01-01 00:00:00']);
        $this->assertSame(Carbon::parse('2026-01-01 00:00:00')->timestamp, ((array) $presenter->library($user)['progress'])[$item->id]['updated_at']);
    }

    public function test_description_html_is_sanitized_inside_the_cached_catalog_and_keeps_rich_editor_markup(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $html = '<h2>عنوان</h2><p>متن <strong>پررنگ</strong> و <em>کج</em></p><ul><li>یک</li></ul><ol><li>دو</li></ol>'
            . '<a href="https://example.com/a">پیوند</a><table><thead><tr><th>ستون</th></tr></thead><tbody><tr><td>سلول</td></tr></tbody></table>'
            . '<script>alert(1)</script><img src="x" onerror="alert(2)"><a href="javascript:alert(3)">بد</a>';
        $item = $this->media($user, ['description' => $html]);
        $presenter = app(MediaPresenter::class);
        $presenter->library($user);
        $cached = Media::cachedCatalog($user->fresh()->profile?->department_id, fn () => []);
        $clean = collect($cached)->firstWhere('id', $item->id)['description_html'];
        $this->assertSame($clean, collect($presenter->library($user)['items'])->firstWhere('id', $item->id)['description_html']);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('alert(1)', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        foreach (['<h2>', '<p>', '<strong>', '<em>', '<ul>', '<ol>', '<li>', '<table>', '<thead>', '<tbody>', '<th>', '<td>', 'href="https://example.com/a"'] as $kept) {
            $this->assertStringContainsString($kept, $clean);
        }
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

    public function test_unit_is_the_owner_name_and_falls_back_to_the_department(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $department = Department::factory()->create();
        Profile::updateOrCreate(['user_id' => $owner->id], ['department_id' => $department->code]);
        $item = $this->media($owner, ['departments' => [$department->code]]);
        $presenter = app(MediaPresenter::class);
        $row = collect($presenter->library($owner->fresh())['items'])->firstWhere('id', $item->id);
        $this->assertSame($owner->name, $row['unit']);
        $this->assertSame('audio/mpeg', $row['episodes'][0]['mime']);
        $owner->forceFill(['name' => ''])->save();
        $row = collect($presenter->library($owner->fresh())['items'])->firstWhere('id', $item->id);
        $this->assertSame($department->name, $row['unit']);
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

    public function test_root_exposes_the_authenticated_user_id_for_the_player_storage_keys(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->media($user);
        $this->actingAs($user);
        Livewire::withoutLazyLoading();
        Livewire::test(Library::class)->assertSeeHtml('data-user-id="' . $user->id . '"');
    }

    public function test_workspace_renders_the_notes_and_lesson_controls(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->media($user);
        $this->actingAs($user);
        Livewire::withoutLazyLoading();
        Livewire::test(Library::class)->assertSeeHtml('copyNotes()')->assertSeeHtml('canMarkDone(')->assertSeeHtml('togglePictureInPicture()')->assertSeeHtml('toggleHeaderDeclutter()')->assertSeeHtml('wire:key="media-recent"');
    }

    public function test_library_renders_the_shared_progress_search_and_pill_components(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->media($user);
        $this->actingAs($user);
        Livewire::withoutLazyLoading();
        Livewire::test(Library::class)
            ->assertSeeHtml('x-bind:stroke-dashoffset=')
            ->assertSeeHtml('animate-status-morph')
            ->assertSeeHtml('rowLimit += 30')
            ->assertSeeHtml('ranks[$el.dataset.id] !== undefined && ranks[$el.dataset.id] < rowLimit')
            ->assertSeeHtml('for="media-search"')
            ->assertSeeHtml('for="media-transcript-search"');
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
        Livewire::test(Library::class, ['open' => $episode->id])
            ->assertSet('open', $episode->id)->assertSeeHtml('data-rf="media-' . $series->id . '"');
        Livewire::withoutLazyLoading();
        Livewire::test(Library::class, ['open' => $hiddenEpisode->id])->assertSet('open', null);
        $locked = $this->media($user, ['parent_id' => $series->id, 'report_date' => now()->addDays(2)->toDateString()]);
        Livewire::withoutLazyLoading();
        Livewire::test(Library::class, ['open' => $locked->id])->assertSet('open', null);
    }

    public function test_module_guide_picks_a_visible_playable_video_and_the_header_button_deep_links_to_it(): void
    {
        if (tenantOff('knowledge:media')) {
            $this->markTestSkipped('knowledge:media is off for this tenant');
        }

        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);
        $tag = 'zz-' . uniqid();
        [$cal, $ths, $chan, $none, $bare, $ser] = array_map(fn (string $suffix) => $tag . '-' . $suffix, ['cal', 'ths', 'chan', 'none', 'bare', 'ser']);

        $this->media($user, ['module' => $cal]);
        $this->media($user, ['kind' => 'video', 'module' => $cal, 'expires_at' => now()->subDay()->toDateString()]);
        $this->media($user, ['kind' => 'video', 'module' => $cal, 'position' => 2]);
        $pinned = $this->media($user, ['kind' => 'video', 'module' => $cal, 'position' => 5, 'pinned' => true]);
        $other = $this->media($user, ['kind' => 'video', 'module' => $chan]);
        $restricted = $this->media($user, ['kind' => 'video', 'module' => $ths, 'pinned' => true, 'departments' => ['ZZZ-NOT-MINE']]);
        $publicTop = $this->media($user, ['kind' => 'video', 'module' => $ths, 'position' => 9]);
        $this->media($user, ['kind' => 'video', 'module' => $bare, 'file_path' => null]);
        $this->media($user, ['kind' => 'series', 'module' => $bare, 'file_path' => null]);
        $series = $this->media($user, ['kind' => 'series', 'module' => $ser, 'file_path' => null]);
        $this->media($user, ['parent_id' => $series->id]);
        ModelCacheVersion::bump(Media::class);

        $this->assertNotSame($restricted->id, Media::guideFor($ths, $user), 'a guide restricted to another department is never offered');
        $this->assertSame($publicTop->id, Media::guideFor($ths, $user), 'the winner is chosen among guides the viewer can see');
        $this->assertSame($pinned->id, Media::guideFor($cal, $user), 'audio and expired items are ignored and the pinned video wins');
        $this->assertSame($other->id, Media::guideFor($chan, $user));
        $this->assertNull(Media::guideFor($bare, $user), 'a video without a file and a series without a playable episode are never offered');
        $this->assertSame($series->id, Media::guideFor($ser, $user), 'a series with a playable episode qualifies');
        $this->assertNull(Media::guideFor($none, $user));
        $this->assertNull(Media::guideFor(null, $user));

        $html = Blade::render('<x-ui.video-guide module="' . $cal . '"/>');
        $this->assertStringContainsString('tab=media', $html);
        $this->assertStringContainsString('open=' . $pinned->id, $html);
        $this->assertStringNotContainsString('play_circle', Blade::render('<x-ui.video-guide module="' . $none . '"/>'), 'a module without a guide renders nothing');

        ModelCacheVersion::bump(Media::class);
    }
}
