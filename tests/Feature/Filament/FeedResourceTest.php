<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\FeedResource\Pages\CreateFeed;
use App\Filament\Resources\FeedResource\Pages\EditFeed;
use App\Filament\Resources\FeedResource\Pages\ListFeeds;
use App\Models\Feed;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeedResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
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

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    public function test_list_renders_and_shows_factory_feed_row()
    {
        $admin = $this->developer();

        $feed = Feed::factory()->create([
            'user_id' => $admin->id,
            'category' => 'General',
            'content' => '<p>0-list-row</p>',
        ]);

        Livewire::actingAs($admin)->test(ListFeeds::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$feed]);
    }

    public function test_create_feed_persists_content_category_and_user()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateFeed::class)
            ->fillForm([
                'content' => '<p>0-create-body</p>',
                'category' => 'General',
                'user_id' => $admin->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('feeds', [
            'user_id' => $admin->id,
            'category' => 'General',
        ]);

        $feed = Feed::where('user_id', $admin->id)->latest('id')->first();
        $this->assertNotNull($feed);
        $this->assertStringContainsString('0-create-body', (string) $feed->content);
    }

    public function test_video_upload_with_disallowed_mime_type_shows_farsi_message()
    {
        $this->app->setLocale('fa');
        $admin = $this->developer();

        $component = Livewire::actingAs($admin)->test(CreateFeed::class)
            ->fillForm([
                'content' => '<p>0-video-mime</p>',
                'category' => 'General',
                'user_id' => $admin->id,
                'media_videos' => UploadedFile::fake()->create('clip.mkv', 500, 'video/x-matroska'),
            ])
            ->call('create');

        $messages = $component->instance()->getErrorBag()->get('data.media_videos');

        $this->assertNotEmpty($messages);
        $this->assertStringStartsWith('ویدیوها باید فایلی از نوع', $messages[0]);
        foreach (['must', 'field', 'required', 'invalid'] as $englishWord) {
            $this->assertStringNotContainsStringIgnoringCase($englishWord, $messages[0]);
        }
    }

    public function test_oversized_upload_via_livewire_endpoint_shows_farsi_message()
    {
        $this->app->setLocale('fa');
        $admin = $this->developer();
        $this->withoutExceptionHandling();

        $signed = \Illuminate\Support\Facades\URL::signedRoute('livewire.upload-file');

        try {
            $this->actingAs($admin)->post($signed, [
                'files' => [UploadedFile::fake()->create('huge.mp4', 150000, 'video/mp4')],
            ]);
            $this->fail('Expected a ValidationException for the oversized upload.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $message = $exception->validator->errors()->first('files.0');

            $this->assertSame('فایل نباید بزرگتر از 102400 کیلوبایت باشد.', $message);
            foreach (['must', 'field', 'required', 'invalid', 'files'] as $englishWord) {
                $this->assertStringNotContainsStringIgnoringCase($englishWord, $message);
            }
        }
    }

    public function test_create_feed_requires_content()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateFeed::class)
            ->fillForm([
                'category' => 'General',
                'user_id' => $admin->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['content']);
    }

    public function test_edit_feed_updates_content()
    {
        $admin = $this->developer();

        $feed = Feed::factory()->create([
            'user_id' => $admin->id,
            'category' => 'General',
            'content' => '<p>original</p>',
            'media_paths' => [],
            'poll_options' => [],
        ]);

        Livewire::actingAs($admin)->test(EditFeed::class, ['record' => $feed->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['content' => '<p>0-edited-body</p>'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertStringContainsString('0-edited-body', (string) $feed->fresh()->content);
    }

    public function test_edit_heading_strips_html_tags_from_rich_content()
    {
        $this->app->setLocale('fa');
        $admin = $this->developer();
        $feed = Feed::factory()->create([
            'user_id' => $admin->id,
            'category' => 'General',
            'content' => '<p><strong>☕️ محتوای html</strong></p>',
            'media_paths' => [],
            'poll_options' => [],
        ]);

        $heading = (string) Livewire::actingAs($admin)
            ->test(EditFeed::class, ['record' => $feed->getRouteKey()])
            ->assertSuccessful()
            ->instance()->getHeading();

        $this->assertStringContainsString('☕️ محتوای html', $heading);
        $this->assertStringNotContainsString('<p>', $heading);
        $this->assertStringNotContainsString('<strong>', $heading);
    }

    public function test_view_modal_heading_strips_html_tags_from_rich_content()
    {
        $this->app->setLocale('fa');
        $admin = $this->developer();
        $feed = Feed::factory()->create([
            'user_id' => $admin->id,
            'category' => 'General',
            'content' => '<p><strong>☕️ محتوای html</strong></p>',
            'media_paths' => [],
            'poll_options' => [],
        ]);

        $component = Livewire::actingAs($admin)
            ->test(ListFeeds::class)
            ->assertSuccessful()
            ->mountTableAction('view', $feed);

        $action = $component->instance()->getMountedAction();
        $this->assertNotNull($action, 'view table action should mount');

        $heading = (string) $action->getModalHeading();

        $this->assertStringContainsString('نمایش', $heading);
        $this->assertStringContainsString('☕️ محتوای html', $heading);
        $this->assertStringNotContainsString('<p>', $heading);
        $this->assertStringNotContainsString('<strong>', $heading);
    }

    public function test_delete_action_removes_feed_record()
    {
        $admin = $this->developer();

        $feed = Feed::factory()->create([
            'user_id' => $admin->id,
            'category' => 'General',
            'content' => '<p>0-delete-me</p>',
        ]);

        Livewire::actingAs($admin)->test(ListFeeds::class)
            ->callTableAction(\Filament\Actions\DeleteAction::class, $feed);

        $this->assertDatabaseMissing('feeds', ['id' => $feed->id]);
    }

    public function test_active_toggle_and_bulk_visibility_actions_set_active_and_inactive_rows_stay_listed()
    {
        $admin = $this->developer();
        $a = Feed::factory()->create(['user_id' => $admin->id, 'content' => '<p>0-vis-a</p>', 'active' => true]);
        $b = Feed::factory()->create(['user_id' => $admin->id, 'content' => '<p>0-vis-b</p>', 'active' => true]);

        $component = Livewire::actingAs($admin)->test(ListFeeds::class)
            ->call('updateTableColumnState', 'active', (string) $a->getKey(), false);

        $this->assertFalse((bool) $a->fresh()->active);
        $component->assertCanSeeTableRecords([$a->fresh(), $b]);

        $component->callTableBulkAction('bulk_deactivate', [$a, $b]);
        $this->assertFalse((bool) $a->fresh()->active);
        $this->assertFalse((bool) $b->fresh()->active);

        Livewire::actingAs($admin)->test(ListFeeds::class)
            ->callTableBulkAction('bulk_activate', [$a, $b])
            ->filterTable('active', false)
            ->assertCanNotSeeTableRecords([$a, $b]);
        $this->assertTrue((bool) $a->fresh()->active);
        $this->assertTrue((bool) $b->fresh()->active);
    }

    public function test_audience_filter_scopes_list_to_feeds_shared_with_the_department()
    {
        $admin = $this->developer();
        $shared = Feed::factory()->create(['user_id' => $admin->id, 'content' => '<p>0-aud-shared</p>', 'departments' => ['ZZ-FA']]);
        $public = Feed::factory()->create(['user_id' => $admin->id, 'content' => '<p>0-aud-public</p>', 'departments' => null]);

        Livewire::actingAs($admin)->test(ListFeeds::class)
            ->filterTable('audience', 'ZZ-FA')
            ->assertCanSeeTableRecords([$shared])
            ->assertCanNotSeeTableRecords([$public]);
    }

    public function test_category_filter_scopes_list_to_matching_feeds()
    {
        $admin = $this->developer();

        $general = Feed::factory()->create([
            'user_id' => $admin->id,
            'category' => 'General',
            'content' => '<p>0-general</p>',
        ]);

        $event = Feed::factory()->create([
            'user_id' => $admin->id,
            'category' => 'Event',
            'content' => '<p>0-event</p>',
        ]);

        Livewire::actingAs($admin)->test(ListFeeds::class)
            ->assertSuccessful()
            ->filterTable('category', 'General')
            ->assertCanSeeTableRecords([$general])
            ->assertCanNotSeeTableRecords([$event]);
    }

    public function test_has_media_filter_scopes_list_to_feeds_with_media()
    {
        $admin = $this->developer();

        $withMedia = Feed::factory()->create([
            'user_id' => $admin->id,
            'category' => 'General',
            'content' => '<p>0-with-media</p>',
            'media_paths' => ['feed/image/0.jpg'],
        ]);

        $withoutMedia = Feed::factory()->create([
            'user_id' => $admin->id,
            'category' => 'General',
            'content' => '<p>0-no-media</p>',
            'media_paths' => [],
        ]);

        Livewire::actingAs($admin)->test(ListFeeds::class)
            ->assertSuccessful()
            ->filterTable('has_media')
            ->assertCanSeeTableRecords([$withMedia])
            ->assertCanNotSeeTableRecords([$withoutMedia]);
    }

    public function test_user_filter_scopes_list_to_matching_user()
    {
        $admin = $this->developer();
        $otherUser = User::factory()->create();

        $adminFeed = Feed::factory()->create(['user_id' => $admin->id, 'category' => 'General', 'content' => '<p>0-admin-feed</p>']);
        $otherFeed = Feed::factory()->create(['user_id' => $otherUser->id, 'category' => 'General', 'content' => '<p>0-other-feed</p>']);

        Livewire::actingAs($admin)->test(ListFeeds::class)
            ->assertSuccessful()
            ->filterTable('user_id', $admin->id)
            ->assertCanSeeTableRecords([$adminFeed])
            ->assertCanNotSeeTableRecords([$otherFeed]);
    }

    public function test_created_at_filter_scopes_to_the_given_date_range()
    {
        $admin = $this->developer();

        $inRange = Feed::factory()->create(['user_id' => $admin->id, 'category' => 'General', 'content' => '<p>0-in-range</p>']);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = Feed::factory()->create(['user_id' => $admin->id, 'category' => 'General', 'content' => '<p>0-out-of-range</p>']);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListFeeds::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_sorting_by_category_id_created_at_and_user_orders_records_correctly()
    {
        $admin = $this->developer();
        $userA = User::factory()->create(['name' => '0-AAA-sort-user']);
        $userZ = User::factory()->create(['name' => '0-ZZZ-sort-user']);

        $first = Feed::factory()->create(['user_id' => $userA->id, 'category' => 'General', 'content' => '<p>0-sort-a</p>']);
        $first->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();
        $second = Feed::factory()->create(['user_id' => $userZ->id, 'category' => 'Question', 'content' => '<p>0-sort-b</p>']);
        $second->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListFeeds::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('category')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('category', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('created_at')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_comments_reactions_and_polls_counts_orders_records_correctly()
    {
        $admin = $this->developer();
        $low = Feed::factory()->create(['user_id' => $admin->id, 'category' => 'General', 'content' => '<p>0-counts-low</p>']);
        $high = Feed::factory()->create(['user_id' => $admin->id, 'category' => 'General', 'content' => '<p>0-counts-high</p>']);
        $high->comments()->create(['user_id' => $admin->id, 'content' => 'test comment']);
        $high->reactions()->create(['user_id' => $admin->id, 'emoji' => '👍']);
        $high->polls()->create(['user_id' => $admin->id, 'option_index' => 0]);

        $component = Livewire::actingAs($admin)->test(ListFeeds::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('comments_count')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$low->id, $high->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('comments_count', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$high->id, $low->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('reactions_count')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$low->id, $high->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('reactions_count', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$high->id, $low->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('polls_count')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$low->id, $high->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('polls_count', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$high->id, $low->id], array_values(array_intersect($ids, [$low->id, $high->id])));
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Feed::factory()->create(['user_id' => $admin->id, 'category' => 'General', 'content' => '<p>0-group-row</p>']);

        Livewire::actingAs($admin)->test(ListFeeds::class)
            ->assertSuccessful()
            ->set('tableGrouping', 'category')
            ->assertSuccessful();
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\FeedResource\Pages\CreateFeed::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\FeedResource\Pages\EditFeed::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\FeedResource\Pages\ListFeeds::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\FeedResource\Pages\CreateFeed::class, \App\Filament\Resources\FeedResource\Pages\EditFeed::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }

        $managers = [\App\Filament\Resources\FeedResource\RelationManagers\CommentsRelationManager::class, \App\Filament\Resources\FeedResource\RelationManagers\PollsRelationManager::class, \App\Filament\Resources\FeedResource\RelationManagers\ReactionsRelationManager::class];

        foreach ($managers as $manager) {
            $this->assertSame(\App\Filament\RelationManagers\RelationManager::class, get_parent_class($manager));
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, class_uses_recursive($manager));
        }
    }

    public function test_edit_tracked_file_columns_delete_only_the_dropped_media(): void
    {
        $probe = new class { use \App\Filament\Traits\HandlesDroppedFiles { trackedFilePaths as public; deleteDroppedFiles as public; } };
        $tracked = (fn () => $this->trackedFileColumns())->call(new \App\Filament\Resources\FeedResource\Pages\EditFeed());
        Storage::fake('public');
        foreach (['feed/image/old.jpg', 'feed/image/kept.jpg'] as $path) {
            Storage::disk('public')->put($path, 'x');
        }
        $record = \App\Models\Feed::factory()->create(['media_paths' => ['feed/image/old.jpg', 'feed/image/kept.jpg']]);
        $before = $probe->trackedFilePaths($record, $tracked);
        $this->assertEqualsCanonicalizing(['feed/image/old.jpg', 'feed/image/kept.jpg'], $before);

        $record->update(['media_paths' => ['feed/image/kept.jpg']]);
        $probe->deleteDroppedFiles($record, $before, $tracked);

        Storage::disk('public')->assertMissing('feed/image/old.jpg');
        Storage::disk('public')->assertExists('feed/image/kept.jpg');
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/FeedResource/Schemas/FeedTablePresenter.php'),
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
